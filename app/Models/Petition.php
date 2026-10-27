<?php

namespace App\Models;

use App\Services\Pedidos\PedidoValues;
use Database\Factories\PetitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petition extends Model
{
    /** @use HasFactory<PetitionFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_GENERATED = 'generated';

    protected $fillable = [
        'user_id',
        'appointment_id',
        'name',
        'status',
        'pedidos',
        'pedidos_signature',
        'pedidos_confirmed_at',
        'file_name',
        'file_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'pedidos' => 'array',
            'pedidos_confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The appointment this petition was built from, if any.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Items in document order: preliminares first ("preliminar" sorts after "pedido"), then by position.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PetitionItem::class)
            ->orderByDesc('kind')
            ->orderBy('position');
    }

    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    /**
     * Whether the lawyer confirmed the amounts of the pedidos for the current topics and ficha.
     */
    public function hasConfirmedPedidos(): bool
    {
        return $this->pedidos_confirmed_at !== null
            && $this->pedidos !== null
            && $this->pedidos_signature === app(PedidoValues::class)->signature($this);
    }

    /**
     * Whether every item was marked as reviewed (and there is at least one pedido).
     */
    public function isFullyReviewed(): bool
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items->where('kind', PetitionItem::KIND_PEDIDO)->isNotEmpty()
            && $items->every(fn (PetitionItem $item): bool => $item->reviewed_at !== null);
    }
}
