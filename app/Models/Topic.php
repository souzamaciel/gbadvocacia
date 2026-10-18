<?php

namespace App\Models;

use App\Services\RichText;
use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    public const JUIZO_100_DIGITAL_ID = 1;

    public const GRATUIDADE_JUSTICA_ID = 2;

    protected $fillable = [
        'group_id',
        'title',
        'content',
        'notes',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Order topics by their group's position first, then by their own position.
     *
     * @param  Builder<Topic>  $query
     */
    public function scopeOrderedByGroup(Builder $query): void
    {
        $query
            ->orderBy(Group::query()->select('sort_order')->whereColumn('groups.id', 'topics.group_id'))
            ->orderBy('sort_order');
    }

    /**
     * Number of distinct [placeholders] the lawyer completes in the generated document.
     */
    public function placeholderCount(): int
    {
        preg_match_all(RichText::PLACEHOLDER_PATTERN, (string) $this->content, $matches);

        return count(array_unique($matches[0]));
    }

    /**
     * The text as safe HTML, whether it was stored as plain text or from the editor.
     */
    public function contentHtml(): string
    {
        return RichText::toHtml((string) $this->content);
    }
}
