<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Draft of an attendance form ("rascunho de atendimento").
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'client_name',
        'answers',
        'preliminares',
        'pedidos',
        'import_file_name',
        'import_notes',
        'generated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'preliminares' => 'array',
            'pedidos' => 'array',
            'import_notes' => 'array',
            'generated_at' => 'datetime',
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
     * The petition reviewed and generated from this appointment.
     *
     * @return HasOne<Petition, $this>
     */
    public function petition(): HasOne
    {
        return $this->hasOne(Petition::class);
    }

    public function displayName(): string
    {
        return $this->client_name ?: 'Atendimento sem nome';
    }
}
