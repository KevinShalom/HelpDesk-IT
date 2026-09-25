<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'phone',
        'is_active',
    ];

    /**
     * Always eager-load the role so role checks work without extra queries
     * (e.g. when the user model is hydrated from a Sanctum token).
     */
    protected $with = ['role'];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Role helper methods
    public function isAdmin(): bool
    {
        return $this->role?->slug === Role::ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role?->slug === Role::SUPERVISOR;
    }

    public function isTechnician(): bool
    {
        return $this->role?->slug === Role::TECHNICIAN;
    }

    public function isEmployee(): bool
    {
        return $this->role?->slug === Role::EMPLOYEE;
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->role?->slug, $roles, true);
    }
}
