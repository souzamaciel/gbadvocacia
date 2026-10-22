<?php

namespace App\Models;

use App\Services\PetitionQualification;
use App\Services\RichText;
use Database\Factories\PetitionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionItem extends Model
{
    /** @use HasFactory<PetitionItemFactory> */
    use HasFactory;

    public const KIND_PRELIMINAR = 'preliminar';

    public const KIND_PEDIDO = 'pedido';

    /**
     * The petition's opening (court, client and employer). Sorts first: items are ordered by kind descending.
     */
    public const KIND_QUALIFICACAO = 'qualificacao';

    protected $fillable = [
        'petition_id',
        'topic_id',
        'kind',
        'position',
        'title',
        'group_name',
        'content',
        'field_values',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'field_values' => 'array',
            'position' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Distinct [placeholders] in the item's text, in order of appearance.
     *
     * @return array<int, string>
     */
    public function placeholders(): array
    {
        preg_match_all(RichText::PLACEHOLDER_PATTERN, RichText::toPlainText($this->content), $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Placeholders that still have no value.
     *
     * @return array<int, string>
     */
    public function pendingPlaceholders(): array
    {
        $values = $this->field_values ?? [];

        return array_values(array_filter(
            $this->placeholders(),
            fn (string $placeholder): bool => trim((string) ($values[$placeholder] ?? '')) === '',
        ));
    }

    /**
     * The item's text as safe HTML.
     */
    public function contentHtml(): string
    {
        return RichText::toHtml($this->content);
    }

    /**
     * HTML with filled placeholders replaced by their values; empty ones keep the [brackets].
     */
    public function renderedContent(): string
    {
        $values = $this->field_values ?? [];

        return preg_replace_callback(RichText::PLACEHOLDER_PATTERN, function (array $match) use ($values): string {
            $name = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            $value = trim((string) ($values[$name] ?? ''));

            if ($this->kind === self::KIND_QUALIFICACAO && in_array($name, PetitionQualification::UPPERCASE_FIELDS, true)) {
                $value = mb_strtoupper($value);
            }

            return $value === '' ? $match[0] : e($value);
        }, $this->contentHtml());
    }
}
