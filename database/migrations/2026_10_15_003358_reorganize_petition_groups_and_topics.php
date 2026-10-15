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
     * Only topics already in production are moved; topic texts are never changed.
     */
    public function up(): void
    {
        if (! Topic::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            foreach ($this->layout() as $groupIndex => $definition) {
                $group = $this->resolveGroup($definition, $groupIndex + 1);

                foreach ($definition['topics'] as $topicIndex => $topicId) {
                    Topic::query()->whereKey($topicId)->update([
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
     * @param  array{id?: int, name: string, topics: array<int, int>}  $definition
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
     * Groups in display order, each with its production topic ids in display order.
     *
     * @return array<int, array{id?: int, name: string, topics: array<int, int>}>
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
            ['id' => 7, 'name' => 'DANOS MORAIS', 'topics' => [35, 36, 37, 55]],
            ['id' => 9, 'name' => 'BENEFÍCIOS E OUTROS', 'topics' => [47, 48, 49]],
            ['id' => 11, 'name' => 'HONORÁRIOS E OUTROS', 'topics' => [54, 23]],
        ];
    }
};
