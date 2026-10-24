<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'is_super_admin',
        'must_change_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'is_super_admin' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->is_active && ($this->isSuperAdmin() || ($this->role !== null && $this->role->allows($permission)));
    }

    /**
     * Super administrators can do everything, see the activity logs and are hidden from
     * user management (the office's administrators do not see or change them).
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_active && $this->is_super_admin;
    }

    /**
     * User management never finds a super administrator unless a super administrator asks.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return self::query()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->when(! auth()->user()?->isSuperAdmin(), fn ($query) => $query->where('is_super_admin', false))
            ->first();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_super_admin', false);
    }

    public function isAdministrator(): bool
    {
        return $this->isSuperAdmin() || ($this->role?->isAdministrator() ?? false);
    }

    /**
     * Whether some other active user keeps the Administrador profile.
     */
    public function hasAnotherActiveAdministrator(): bool
    {
        return self::query()
            ->whereKeyNot($this->id)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->whereHas('role', fn ($query) => $query->where('is_locked', true))
            ->exists();
    }

    /**
     * Only administrators may manage other administrators.
     */
    public function canManageUser(self $user): bool
    {
        if ($user->is_super_admin && ! $this->isSuperAdmin()) {
            return false;
        }

        return $this->isSuperAdmin() || $this->isAdministrator() || ! $user->isAdministrator();
    }
}
