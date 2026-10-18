<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\UserFactory;
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
        return $this->is_active && $this->role !== null && $this->role->allows($permission);
    }

    public function isAdministrator(): bool
    {
        return $this->role?->isAdministrator() ?? false;
    }

    /**
     * Whether some other active user keeps the Administrador profile.
     */
    public function hasAnotherActiveAdministrator(): bool
    {
        return self::query()
            ->whereKeyNot($this->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('is_locked', true))
            ->exists();
    }

    /**
     * Only administrators may manage other administrators.
     */
    public function canManageUser(self $user): bool
    {
        return $this->isAdministrator() || ! $user->isAdministrator();
    }
}
