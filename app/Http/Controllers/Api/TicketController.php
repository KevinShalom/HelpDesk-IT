<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AddCommentRequest;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    /**
     * List tickets (filtered by role).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Ticket::with([
            'user:id,name,email',
            'technician:id,name,email',
            'category:id,name,slug',
            'priority:id,name,slug,color,sla_hours',
            'department:id,name',
        ]);

        // Role-based filtering
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->where('assigned_to', $user->id);
        }
        // Supervisors and Admins see all tickets

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('priority_id')) {
            $query->where('priority_id', $request->query('priority_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }
        if ($request->filled('sla_status')) {
            $now = now();
            match($request->query('sla_status')) {
                'breached' => $query->where('sla_due_at', '<', $now)->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]),
                'warning' => $query->where('sla_due_at', '>=', $now)->where('sla_due_at', '<=', $now->copy()->addHours(2)),
                default => null,
            };
        }

        $tickets = $query->latest()->paginate(15)->through(function ($ticket) {
            $ticket->sla_status_label = $ticket->sla_status;
            return $ticket;
        });

        return response()->json($tickets);
    }

    /**
     * Create a new ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = $this->ticketService->createTicket(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Ticket creado exitosamente.',
            'data' => $ticket->load(['category', 'priority', 'department', 'user']),
        ], 201);
    }

    /**
     * Display the specified ticket with full detail.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'data' => $ticket->load([
                'user:id,name,email',
                'technician:id,name,email',
                'category:id,name',
                'priority:id,name,slug,color,sla_hours',
                'department:id,name',
                'asset:id,inventory_code,name,type',
                'comments.user:id,name,email',
                'attachments:id,ticket_id,file_name,file_size,mime_type,created_at',
                'statusHistories.user:id,name',
            ]),
            'sla_status' => $ticket->sla_status,
        ]);
    }

    /**
     * Assign ticket to a technician.
     */
    public function assign(AssignTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('assign', $ticket);

        $technician = User::findOrFail($request->validated('technician_id'));

        $ticket = $this->ticketService->assignTicket($ticket, $technician, $request->user());

        return response()->json([
            'message' => "Ticket asignado a {$technician->name} exitosamente.",
            'data' => $ticket->load(['technician:id,name,email', 'statusHistories.user:id,name']),
        ]);
    }

    /**
     * Change ticket status and optionally register solution or comment.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('updateStatus', $ticket);

        $validated = $request->validated();

        // Record solution separately if provided
        if (! empty($validated['solution']) || ! empty($validated['diagnostic'])) {
            $this->ticketService->recordSolution(
                $ticket,
                $validated['solution'] ?? '',
                $validated['diagnostic'] ?? null,
                $validated['work_time_minutes'] ?? 0,
                $request->user()
            );
        }

        $ticket = $this->ticketService->updateStatus(
            $ticket,
            $validated['status'],
            $request->user(),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'data' => $ticket->load(['statusHistories.user:id,name']),
            'sla_status' => $ticket->sla_status,
        ]);
    }

    /**
     * Add a comment to a ticket.
     */
    public function comment(AddCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $user = $request->user();

        // Only technicians and supervisors can post internal notes
        $isInternal = ($request->validated('is_internal') ?? false)
            && $user->hasRole(['admin', 'supervisor', 'technician']);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment' => $request->validated('comment'),
            'is_internal' => $isInternal,
        ]);

        return response()->json([
            'message' => 'Comentario agregado.',
            'data' => $comment->load('user:id,name,email'),
        ], 201);
    }
}
