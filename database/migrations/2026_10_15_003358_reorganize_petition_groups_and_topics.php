<?php

use App\Models\Group;
use App\Models\Topic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reorganize the existing production groups and topics following the "Grupos de pedidos" document.
     *
     * Integer references point to topics already in production (their texts are never changed);
     * string references are new topics created from newTopics() when missing.
     */
    public function up(): void
    {
        if (! Topic::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $newTopics = $this->newTopics();

            foreach ($this->layout() as $groupIndex => $definition) {
                $group = $this->resolveGroup($definition, $groupIndex + 1);

                foreach ($definition['topics'] as $topicIndex => $reference) {
                    $topic = is_int($reference)
                        ? Topic::query()->find($reference)
                        : Topic::query()->firstOrCreate(
                            ['title' => $reference],
                            ['group_id' => $group->id, 'content' => $newTopics[$reference], 'active' => true],
                        );

                    $topic?->update([
                        'group_id' => $group->id,
                        'sort_order' => $topicIndex + 1,
                    ]);
                }
            }

            Group::query()->where('name', 'x')->doesntHave('topics')->delete();
        });
    }

    public function down(): void
    {
        //
    }

    /**
     * @param  array{id?: int, name: string, topics: array<int, int|string>}  $definition
     */
    private function resolveGroup(array $definition, int $sortOrder): Group
    {
        $group = isset($definition['id']) ? Group::query()->find($definition['id']) : null;
        $group ??= Group::query()->firstOrCreate(['name' => $definition['name']]);

        $group->update([
            'name' => $definition['name'],
            'sort_order' => $sortOrder,
            'active' => true,
        ]);

        return $group;
    }

    /**
     * Groups in display order, each with its topics in display order.
     *
     * @return array<int, array{id?: int, name: string, topics: array<int, int|string>}>
     */
    private function layout(): array
    {
        return [
            ['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'topics' => [1, 2]],
            ['name' => 'RESPONSABILIDADE E PRESCRIÇÃO', 'topics' => [3, 4, 5]],
            ['id' => 2, 'name' => 'HORAS EXTRAS', 'topics' => [12, 11, 8, 6, 7, 9, 14, 13, 15, 16, 56, 10]],
            ['id' => 3, 'name' => 'ADICIONAIS', 'topics' => [17, 18, 19]],
            ['id' => 4, 'name' => 'RECONHECIMENTO DE VÍNCULO E UNICIDADE CONTRATUAL', 'topics' => [25, 26, 24]],
            ['id' => 5, 'name' => 'DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO', 'topics' => [27, 28, 29, 52, 30, 51]],
            ['id' => 10, 'name' => 'ESTABILIDADES', 'topics' => [22, 50]],
            ['id' => 6, 'name' => 'VERBAS RESCISÓRIAS', 'topics' => [31, 53, 32, 33, 34, 58]],
            ['id' => 8, 'name' => 'COMISSÕES E REMUNERAÇÃO', 'topics' => [20, 21, 38, 39, 40, 41, 42, 43, 44, 45, 46, 57]],
            [
                'name' => 'MULTAS E TRINTÍDIO',
                'topics' => [
                    'MULTA DO ART. 477, § 8º DA CLT',
                    'MULTA DO ARTIGO 467 DA CLT',
                    'TRINTÍDIO QUE ANTECEDE A DATA BASE',
                ],
            ],
            ['id' => 7, 'name' => 'DANOS MORAIS', 'topics' => [35, 36, 37, 55]],
            ['id' => 9, 'name' => 'BENEFÍCIOS E OUTROS', 'topics' => [47, 48, 49]],
            ['id' => 11, 'name' => 'HONORÁRIOS E OUTROS', 'topics' => [54, 23]],
        ];
    }

    /**
     * Texts for topics that do not exist in production yet, provided by the office.
     *
     * @return array<string, string>
     */
    private function newTopics(): array
    {
        return [
            'MULTA DO ART. 477, § 8º DA CLT' => <<<'TEXT'
            O pagamento das verbas rescisórias e as guias rescisórias se deram em 20/05/2026, qual seja, 13 dias depois do desligamento. Registre-se que, para as rescisões ocorridas a partir de 11/11/17, passou a ser necessária a realização de dois atos no prazo de 10 dias: (I) o pagamento das verbas rescisórias; e (II) a entrega ao empregado dos documentos comprobatórios da comunicação da extinção contratual aos órgãos competentes, o que não foi observado pela Ré.

            Descumprido qualquer um desses requisitos, passou a ser aplicável a multa do § 8º do mesmo dispositivo, cuja redação permaneceu intocada.

            Sendo assim, requer seja a Ré condenada ao pagamento da multa em tela, destacando-se que, em caso de acolhimento do pedido da parte autora, que a multa prevista no art. 477, § 8º, da CLT, incida sobre a remuneração, assim considerada todas as parcelas de natureza salarial percebidas pela empregada, assim definido pelo art. 457, §1º, da CLT.
            TEXT,
            'MULTA DO ARTIGO 467 DA CLT' => <<<'TEXT'
            O art. 467 da CLT estabelece que: "em caso de rescisão de contrato de trabalho, motivada pelo empregador ou pelo empregado, havendo controvérsia sobre o montante das verbas rescisórias, o empregador é obrigado a pagar ao trabalhador, na data do comparecimento à Justiça do Trabalho, a parte incontroversa dessas verbas, sob pena de pagá-las acrescidas de 50% (cinquenta por cento)".
            TEXT,
            'TRINTÍDIO QUE ANTECEDE A DATA BASE' => <<<'TEXT'
            MMº Juiz, a data base da categoria foi em 01 de janeiro de 2025, e tendo autor sido dispensado sem justa causa em 19.12.2024, tem-se que a empresa não obedeceu a indenização adicional. Para fins de incidência do art. 9º da Lei 7.238/84, considera-se a projeção do aviso prévio, sendo devido o pagamento da indenização adicional quando a rescisão contratual ocorre nos 30 dias que antecedem a data-base da categoria, o que desde já requer.
            TEXT,
        ];
    }
};
