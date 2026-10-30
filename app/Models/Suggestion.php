<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    public const TYPES = [
        'melhoria' => 'Melhoria em algo que já existe',
        'novo' => 'Novo recurso',
        'erro' => 'Algo não funciona (erro)',
    ];

    public const AREAS = [
        'atendimentos' => 'Atendimentos',
        'peticoes' => 'Petições',
        'biblioteca' => 'Biblioteca',
        'ficha' => 'Ficha de atendimento',
        'usuarios' => 'Usuários e acessos',
        'outro' => 'Outro',
    ];

    /**
     * The question and its answers change with the type; the answer is kept as a level.
     */
    public const IMPACTS = [
        'melhoria' => [
            'question' => 'Quanto essa melhoria ajudaria?',
            'levels' => ['alta' => 'Faria muita diferença no dia a dia', 'media' => 'Ajudaria bastante', 'baixa' => 'Seria bom ter'],
        ],
        'novo' => [
            'question' => 'Quanto esse recurso ajudaria?',
            'levels' => ['alta' => 'Essencial para o meu trabalho', 'media' => 'Ajudaria bastante', 'baixa' => 'Seria bom ter'],
        ],
        'erro' => [
            'question' => 'Qual a gravidade do erro?',
            'levels' => ['alta' => 'Impede o meu trabalho', 'media' => 'Atrapalha, mas consigo contornar', 'baixa' => 'É um pequeno detalhe'],
        ],
    ];

    public function impactLabel(): string
    {
        return self::IMPACTS[$this->type]['levels'][$this->impact] ?? $this->impact;
    }

    protected $fillable = [
        'user_id',
        'user_name',
        'type',
        'area',
        'impact',
        'title',
        'description',
        'page_title',
        'page_url',
        'attachments',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->title;
    }

    /**
     * Area guessed from the screen the suggestion was opened on.
     */
    public static function areaForRoute(?string $routeName): string
    {
        return match (true) {
            $routeName === null => 'outro',
            str_starts_with($routeName, 'appointment.') => 'atendimentos',
            str_starts_with($routeName, 'petition.') => 'peticoes',
            str_starts_with($routeName, 'settings.') => 'biblioteca',
            str_starts_with($routeName, 'admin.form.') => 'ficha',
            str_starts_with($routeName, 'admin.users.'), str_starts_with($routeName, 'admin.roles.'), str_starts_with($routeName, 'profile.') => 'usuarios',
            default => 'outro',
        };
    }
}
