<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    /** Months a record is kept (Marco Civil reference for access logs). */
    public const RETENTION_MONTHS = 6;

    protected $fillable = [
        'user_id',
        'user_name',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'properties',
        'ip',
        'location',
        'browser',
        'platform',
        'device',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }
}
