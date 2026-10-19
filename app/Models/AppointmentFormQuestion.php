<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\AppointmentFormQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentFormQuestion extends Model
{
    /** @use HasFactory<AppointmentFormQuestionFactory> */
    use HasFactory;

    /**
     * Keys read by the petition (qualification of the parties). They cannot be deleted.
     *
     * @var list<string>
     */
    public const SYSTEM_KEYS = [
        'cliente_nome', 'cliente_nacionalidade', 'cliente_estado_civil', 'cliente_profissao',
        'cliente_rg', 'cliente_orgao_expedidor', 'cliente_cpf', 'cliente_ctps', 'cliente_ctps_serie', 'cliente_pis',
        'cliente_cep', 'cliente_endereco', 'cliente_numero', 'cliente_complemento', 'cliente_bairro', 'cliente_cidade', 'cliente_uf',
        'empregador_nome', 'empregador_cnpj', 'empregador_email',
        'empregador_cep', 'empregador_endereco', 'empregador_numero', 'empregador_complemento', 'empregador_bairro', 'empregador_cidade', 'empregador_uf',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'appointment_form_step_id',
        'key',
        'type',
        'label',
        'help',
        'placeholder',
        'options',
        'show_if',
        'width',
        'required',
        'send_to_ai',
        'is_system',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'show_if' => 'array',
            'required' => 'boolean',
            'send_to_ai' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AppointmentFormStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(AppointmentFormStep::class, 'appointment_form_step_id');
    }

    /**
     * @return list<string>
     */
    public function choices(): array
    {
        if ($this->type === QuestionType::YesNo) {
            return ['Sim', 'Não'];
        }

        return array_values($this->options['choices'] ?? []);
    }

    public function allowsOther(): bool
    {
        return $this->type->hasChoices() && (bool) ($this->options['allow_other'] ?? false);
    }

    /**
     * @return list<array{key: string, label: string, type: string}>
     */
    public function columns(): array
    {
        return array_values($this->options['columns'] ?? []);
    }

    /**
     * Prefix of the address fields filled by a CEP question (e.g. "cliente" for "cliente_cep").
     */
    public function addressPrefix(): ?string
    {
        return $this->type === QuestionType::Cep && str_ends_with($this->key, '_cep')
            ? substr($this->key, 0, -4)
            : null;
    }
}
