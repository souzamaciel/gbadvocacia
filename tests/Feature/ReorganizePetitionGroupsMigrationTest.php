<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Topic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorganizePetitionGroupsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require database_path('migrations/2026_10_15_003358_reorganize_petition_groups_and_topics.php');
    }

    public function test_it_does_nothing_on_an_empty_database(): void
    {
        $this->migration->up();

        $this->assertSame(0, Group::query()->count());
        $this->assertSame(0, Topic::query()->count());
    }

    public function test_it_reorganizes_groups_following_the_document(): void
    {
        $this->createProductionSnapshot();

        $this->migration->up();

        $this->assertSame([
            'PRELIMINARES',
            'RESPONSABILIDADE E PRESCRIÇÃO',
            'HORAS EXTRAS',
            'ADICIONAIS',
            'RECONHECIMENTO DE VÍNCULO E UNICIDADE CONTRATUAL',
            'DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO',
            'ESTABILIDADES',
            'VERBAS RESCISÓRIAS',
            'COMISSÕES E REMUNERAÇÃO',
            'MULTAS E TRINTÍDIO',
            'DANOS MORAIS',
            'BENEFÍCIOS E OUTROS',
            'HONORÁRIOS E OUTROS',
        ], Group::query()->orderBy('sort_order')->pluck('name')->all());

        $this->assertSame(61, Topic::query()->count());
    }

    public function test_it_moves_existing_topics_keeping_their_content(): void
    {
        $this->createProductionSnapshot();
        $originalContent = Topic::query()->find(52)->content;

        $this->migration->up();

        $this->assertSame([1, 2], $this->topicIdsOf('PRELIMINARES'));
        $this->assertSame([3, 4, 5], $this->topicIdsOf('RESPONSABILIDADE E PRESCRIÇÃO'));
        $this->assertSame([54, 23], $this->topicIdsOf('HONORÁRIOS E OUTROS'));

        $inadimplemento = Topic::query()->find(52);
        $this->assertSame('DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO', $inadimplemento->group->name);
        $this->assertSame(4, $inadimplemento->sort_order);
        $this->assertSame($originalContent, $inadimplemento->content);
    }

    public function test_it_never_changes_titles_or_texts(): void
    {
        $this->createProductionSnapshot();
        $before = Topic::query()->orderBy('id')->get(['id', 'title', 'content', 'active'])->toArray();

        $this->migration->up();

        $this->assertSame($before, Topic::query()->whereKey(range(1, 58))->orderBy('id')->get(['id', 'title', 'content', 'active'])->toArray());
    }

    public function test_every_topic_ends_up_in_exactly_one_position(): void
    {
        $this->createProductionSnapshot();

        $this->migration->up();

        $positions = Topic::query()->get()->map(fn (Topic $topic): string => $topic->group_id.'-'.$topic->sort_order);
        $this->assertSame(61, $positions->unique()->count());
    }

    public function test_it_creates_the_multas_group_with_its_three_topics(): void
    {
        $this->createProductionSnapshot();

        $this->migration->up();

        $multas = Group::query()->where('name', 'MULTAS E TRINTÍDIO')->firstOrFail();
        $this->assertSame(10, $multas->sort_order);
        $this->assertSame(
            ['MULTA DO ART. 477, § 8º DA CLT', 'MULTA DO ARTIGO 467 DA CLT', 'TRINTÍDIO QUE ANTECEDE A DATA BASE'],
            $multas->topics->pluck('title')->all(),
        );
        $this->assertTrue($multas->topics->every(fn (Topic $topic): bool => $topic->active && $topic->content !== ''));
        $this->assertStringStartsWith('O pagamento das verbas rescisórias', $multas->topics->first()->content);
    }

    public function test_it_deletes_the_empty_x_group(): void
    {
        $this->createProductionSnapshot();

        $this->migration->up();

        $this->assertFalse(Group::query()->where('name', 'x')->exists());
    }

    public function test_it_keeps_the_x_group_when_it_has_topics(): void
    {
        $this->createProductionSnapshot();
        Topic::factory()->create(['group_id' => 14]);

        $this->migration->up();

        $this->assertTrue(Group::query()->where('name', 'x')->exists());
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->createProductionSnapshot();

        $this->migration->up();
        $this->migration->up();

        $this->assertSame(13, Group::query()->count());
        $this->assertSame(61, Topic::query()->count());
    }

    /**
     * Recreates the production ids: groups 1-11 plus the empty "x" group (14) and topics 1-58.
     */
    private function createProductionSnapshot(): void
    {
        foreach (range(1, 11) as $groupId) {
            Group::factory()->create(['id' => $groupId, 'sort_order' => $groupId]);
        }

        Group::factory()->create(['id' => 14, 'name' => 'x', 'sort_order' => 12]);

        foreach (range(1, 58) as $topicId) {
            Topic::factory()->create(['id' => $topicId, 'group_id' => 11, 'sort_order' => $topicId]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function topicIdsOf(string $groupName): array
    {
        return Group::query()->where('name', $groupName)->firstOrFail()->topics->pluck('id')->all();
    }
}
