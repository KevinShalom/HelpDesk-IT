<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    // Statuses
    public const STATUS_NEW = 'new';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_USER = 'waiting_user';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ESCALATED = 'escalated';

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'category_id',
        'priority_id',
        'department_id',
        'asset_id',
        'user_id',
        'assigned_to',
        'status',
        'sla_due_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'diagnostic',
        'solution',
        'work_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'sla_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'work_time_minutes' => 'integer',
        ];
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('created_at', 'desc');
    }

    // SLA Visual Status calculation (Green, Yellow, Red)
    public function getSlaStatusAttribute(): string
    {
        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            if ($this->resolved_at && $this->sla_due_at) {
                return $this->resolved_at <= $this->sla_due_at ? 'fulfilled' : 'breached';
            }
            return 'fulfilled';
        }

        if (! $this->sla_due_at) {
            return 'on_track';
        }

        $now = Carbon::now();

        if ($now > $this->sla_due_at) {
            return 'breached'; // Red 🔴
        }

        // Warning if less than 25% of SLA remaining or less than 1 hour
        $hoursLeft = $now->diffInHours($this->sla_due_at, false);
        if ($hoursLeft <= 2) {
            return 'warning'; // Yellow 🟡
        }

        return 'on_track'; // Green 🟢
    }
}
