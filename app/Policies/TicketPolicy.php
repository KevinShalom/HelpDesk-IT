<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    /**
     * Super Admin bypass.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered in query by role
    }

    /**
     * Determine whether the user can view the specific ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Technician can view if assigned or if supervisor/admin
        if ($user->isSupervisor()) {
            return true;
        }

        if ($user->isTechnician()) {
            return $ticket->assigned_to === $user->id;
        }

        // Employee can only view their own tickets
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the ticket basic info.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return false;
        }

        return $user->isSupervisor() || ($user->id === $ticket->user_id && $ticket->status === Ticket::STATUS_NEW);
    }

    /**
     * Determine whether the user can assign tickets.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isSupervisor();
    }

    /**
     * Determine whether the user can change status or diagnose.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($user->isSupervisor()) {
            return true;
        }

        if ($user->isTechnician() && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Employee can close or reopen their own resolved tickets
        if ($user->id === $ticket->user_id) {
            return in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }

        return false;
    }
}
