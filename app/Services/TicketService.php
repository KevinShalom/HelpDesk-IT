<?php

namespace App\Services;

use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Create a new ticket and calculate its SLA due date.
     */
    public function createTicket(array $data, User $creator): Ticket
    {
        return DB::transaction(function () use ($data, $creator) {
            $priority = Priority::findOrFail($data['priority_id']);

            // Calculate SLA deadline based on priority sla_hours
            $slaDueAt = Carbon::now()->addHours($priority->sla_hours);

            // Generate unique ticket number (e.g. HD-000001)
            $lastTicket = Ticket::latest('id')->first();
            $nextNumber = $lastTicket ? ($lastTicket->id + 1) : 1;
            $ticketNumber = sprintf('HD-%06d', $nextNumber);

            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'priority_id' => $priority->id,
                'department_id' => $data['department_id'] ?? $creator->department_id,
                'asset_id' => $data['asset_id'] ?? null,
                'user_id' => $creator->id,
                'assigned_to' => null,
                'status' => Ticket::STATUS_NEW,
                'sla_due_at' => $slaDueAt,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $creator->id,
                'from_status' => null,
                'to_status' => Ticket::STATUS_NEW,
                'action_type' => 'created',
                'comment' => 'Ticket creado por el usuario solicitante.',
            ]);

            return $ticket;
        });
    }

    /**
     * Assign ticket to a technician.
     */
    public function assignTicket(Ticket $ticket, User $technician, User $assignedBy): Ticket
    {
        if (! $technician->hasRole([Role::TECHNICIAN, Role::SUPERVISOR, Role::ADMIN])) {
            throw ValidationException::withMessages([
                'technician_id' => ['El usuario seleccionado no tiene el rol de técnico o supervisor.'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $technician, $assignedBy) {
            $previousStatus = $ticket->status;
            $newStatus = Ticket::STATUS_ASSIGNED;

            $ticket->update([
                'assigned_to' => $technician->id,
                'status' => $newStatus,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $assignedBy->id,
                'from_status' => $previousStatus,
                'to_status' => $newStatus,
                'action_type' => 'assignment',
                'comment' => "Ticket asignado al técnico {$technician->name}.",
            ]);

            return $ticket->fresh()->load(['technician', 'statusHistories']);
        });
    }

    /**
     * Change ticket status following valid business transitions.
     */
    public function updateStatus(Ticket $ticket, string $newStatus, User $user, ?string $comment = null): Ticket
    {
        $validTransitions = [
            Ticket::STATUS_NEW => [Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS],
            Ticket::STATUS_ASSIGNED => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_ESCALATED],
            Ticket::STATUS_IN_PROGRESS => [Ticket::STATUS_WAITING_USER, Ticket::STATUS_RESOLVED, Ticket::STATUS_ESCALATED],
            Ticket::STATUS_WAITING_USER => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_ESCALATED => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_RESOLVED => [Ticket::STATUS_CLOSED, Ticket::STATUS_IN_PROGRESS], // in_progress if reopened
            Ticket::STATUS_CLOSED => [Ticket::STATUS_IN_PROGRESS], // Reopen
        ];

        $currentStatus = $ticket->status;

        if (! isset($validTransitions[$currentStatus]) || ! in_array($newStatus, $validTransitions[$currentStatus], true)) {
            throw ValidationException::withMessages([
                'status' => ["No es posible realizar la transición de estado '{$currentStatus}' a '{$newStatus}'."],
            ]);
        }

        // Rule: A ticket can only be resolved if there is a recorded solution
        if ($newStatus === Ticket::STATUS_RESOLVED && empty($ticket->solution) && empty($comment)) {
            throw ValidationException::withMessages([
                'solution' => ['Para marcar el ticket como resuelto se debe registrar una solución o diagnóstico resolutivo.'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $currentStatus, $newStatus, $user, $comment) {
            $updates = ['status' => $newStatus];

            if ($newStatus === Ticket::STATUS_IN_PROGRESS && ! $ticket->first_response_at) {
                $updates['first_response_at'] = Carbon::now();
            }

            if ($newStatus === Ticket::STATUS_RESOLVED) {
                $updates['resolved_at'] = Carbon::now();
                if ($comment && empty($ticket->solution)) {
                    $updates['solution'] = $comment;
                }
            }

            if ($newStatus === Ticket::STATUS_CLOSED) {
                $updates['closed_at'] = Carbon::now();
            }

            $ticket->update($updates);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_status' => $currentStatus,
                'to_status' => $newStatus,
                'action_type' => $newStatus === Ticket::STATUS_CLOSED ? 'closed' : ($newStatus === Ticket::STATUS_IN_PROGRESS && $currentStatus === Ticket::STATUS_CLOSED ? 'reopened' : 'status_change'),
                'comment' => $comment,
            ]);

            return $ticket->fresh();
        });
    }

    /**
     * Register solution and diagnostic details.
     */
    public function recordSolution(Ticket $ticket, string $solution, ?string $diagnostic, int $workTimeMinutes, User $technician): Ticket
    {
        return DB::transaction(function () use ($ticket, $solution, $diagnostic, $workTimeMinutes, $technician) {
            $ticket->update([
                'solution' => $solution,
                'diagnostic' => $diagnostic,
                'work_time_minutes' => $ticket->work_time_minutes + $workTimeMinutes,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'from_status' => $ticket->status,
                'to_status' => $ticket->status,
                'action_type' => 'solution_added',
                'comment' => 'Diagnóstico y solución técnica registrados.',
            ]);

            return $ticket;
        });
    }
}
