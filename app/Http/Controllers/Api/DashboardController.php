<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics based on user role.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Ticket::query();

        // Scope query based on role
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('user_id', $user->id); // In case they also created tickets
            });
        }
        // Admin and Supervisor see all

        // Calculate stats
        $total = $query->count();
        $new = (clone $query)->where('status', Ticket::STATUS_NEW)->count();
        $inProgress = (clone $query)->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $resolved = (clone $query)->where('status', Ticket::STATUS_RESOLVED)->count();
        $closed = (clone $query)->where('status', Ticket::STATUS_CLOSED)->count();

        // SLA Stats (Only relevant for open tickets)
        $now = now();
        $openTicketsQuery = (clone $query)->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        
        $breachedSla = (clone $openTicketsQuery)->where('sla_due_at', '<', $now)->count();
        $warningSla = (clone $openTicketsQuery)->where('sla_due_at', '>=', $now)
            ->where('sla_due_at', '<=', $now->copy()->addHours(2))->count();

        // Tickets by priority (for charts)
        $byPriority = (clone $query)
            ->select('priority_id', DB::raw('count(*) as total'))
            ->groupBy('priority_id')
            ->with('priority:id,name,color')
            ->get();

        return response()->json([
            'data' => [
                'overview' => [
                    'total' => $total,
                    'new' => $new,
                    'in_progress' => $inProgress,
                    'resolved' => $resolved,
                    'closed' => $closed,
                ],
                'sla' => [
                    'breached' => $breachedSla,
                    'warning' => $warningSla,
                ],
                'by_priority' => $byPriority,
            ]
        ]);
    }
}
