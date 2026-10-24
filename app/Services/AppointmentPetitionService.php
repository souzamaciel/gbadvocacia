<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Services\OpenAI\TopicVariableFillService;
use Throwable;

/**
 * Turns a finished appointment into a petition draft that goes through the review
 * screen: the opening comes filled with the form's qualification and the AI suggests
 * the topics' [variables] from the form. The lawyer checks everything before generating.
 */
class AppointmentPetitionService
{
    /**
     * Qualification placeholders and the form keys that fill them. The number also
     * carries the address complement ("3.732, Andar 22 e 23").
     */
    private const QUALIFICATION_FIELDS = [
        'NOME DO RECLAMANTE' => ['cliente_nome'],
        'nacionalidade' => ['cliente_nacionalidade'],
        'estado civil' => ['cliente_estado_civil'],
        'profissão' => ['cliente_profissao'],
        'RG' => ['cliente_rg'],
        'órgão expedidor do RG' => ['cliente_orgao_expedidor'],
        'CPF' => ['cliente_cpf'],
        'endereço do reclamante' => ['cliente_endereco'],
        'número do reclamante' => ['cliente_numero', 'cliente_complemento'],
        'bairro do reclamante' => ['cliente_bairro'],
        'CEP do reclamante' => ['cliente_cep'],
        'cidade do reclamante' => ['cliente_cidade'],
        'UF do reclamante' => ['cliente_uf'],
        'CTPS' => ['cliente_ctps'],
        'série da CTPS' => ['cliente_ctps_serie'],
        'PIS/PASEP' => ['cliente_pis'],
        'NOME DA RECLAMADA' => ['empregador_nome'],
        'CNPJ' => ['empregador_cnpj'],
        'endereço da reclamada' => ['empregador_endereco'],
        'número da reclamada' => ['empregador_numero', 'empregador_complemento'],
        'bairro da reclamada' => ['empregador_bairro'],
        'cidade da reclamada' => ['empregador_cidade'],
        'UF da reclamada' => ['empregador_uf'],
        'CEP da reclamada' => ['empregador_cep'],
    ];

    public function __construct(
        protected PetitionDraftService $drafts,
        protected TopicVariableFillService $variables,
    ) {}

    /**
     * Creates the appointment's petition (or updates it when the appointment is concluded
     * again, keeping the review of topics that stay) and returns it ready for review.
     */
    public function prepare(Appointment $appointment): Petition
    {
        $petition = $appointment->petition ?? Petition::create([
            'user_id' => $appointment->user_id,
            'appointment_id' => $appointment->id,
            'name' => 'Petição — '.$appointment->displayName(),
            'status' => Petition::STATUS_DRAFT,
        ]);

        $petition->update(['status' => Petition::STATUS_DRAFT]);
        $this->drafts->syncItems($petition, $appointment->preliminares ?? [], $appointment->pedidos ?? []);
        $this->fillQualification($petition, $appointment);
        $this->suggestVariables($petition, $appointment);

        return $petition->fresh();
    }

    private function fillQualification(Petition $petition, Appointment $appointment): void
    {
        $qualification = app(AppointmentForm::class)->qualification($appointment->answers ?? []);
        $item = $petition->items()->where('kind', PetitionItem::KIND_QUALIFICACAO)->sole();
        $values = $item->field_values ?? [];

        foreach (self::QUALIFICATION_FIELDS as $placeholder => $keys) {
            $value = implode(', ', array_filter(array_map(fn (string $key): string => trim((string) ($qualification[$key] ?? '')), $keys)));

            if ($value !== '') {
                $values[$placeholder] = $value;
            }
        }

        $estadoCivil = trim((string) ($qualification['cliente_estado_civil'] ?? ''));
        $feminine = $estadoCivil !== '' && mb_substr($estadoCivil, -1) === 'a';
        $content = str_replace(
            ['residente e domiciliado ', 'residente e domiciliada '],
            $feminine ? 'residente e domiciliada ' : 'residente e domiciliado ',
            $item->content,
        );

        $item->update(['content' => $content, 'field_values' => $values, 'reviewed_at' => null]);
    }

    /**
     * Blank variables get the AI's suggestion from the form; when the AI is unavailable
     * they simply stay blank for the lawyer to fill in.
     */
    private function suggestVariables(Petition $petition, Appointment $appointment): void
    {
        $items = $petition->items()->get()->filter(fn (PetitionItem $item): bool => $item->pendingPlaceholders() !== []);

        if ($items->isEmpty()) {
            return;
        }

        try {
            $suggestions = $this->variables->fill(PetitionFormSerializer::normalizeFormData($appointment->answers ?? []), $items);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        foreach ($items as $item) {
            if (! empty($suggestions[$item->id])) {
                $item->update([
                    'field_values' => [...$suggestions[$item->id], ...array_filter($item->field_values ?? [], fn ($value): bool => trim((string) $value) !== '')],
                    'reviewed_at' => null,
                ]);
            }
        }
    }
}
