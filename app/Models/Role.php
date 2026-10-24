<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_locked' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class)->where('is_super_admin', false);
    }

    public static function administrator(): ?self
    {
        return self::query()->where('is_locked', true)->first();
    }

    /**
     * The Administrador profile always has every permission, including future ones.
     */
    public function isAdministrator(): bool
    {
        return (bool) $this->is_locked;
    }

    public function allows(Permission $permission): bool
    {
        return $this->isAdministrator() || in_array($permission->value, $this->permissions ?? [], true);
    }

    /**
     * @return list<Permission>
     */
    public function grantedPermissions(): array
    {
        return array_values(array_filter(Permission::cases(), fn (Permission $permission): bool => $this->allows($permission)));
    }
}
