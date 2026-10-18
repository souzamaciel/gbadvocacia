<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicLibraryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('settings.topics.index'))->assertRedirect(route('login'));
    }

    public function test_topics_follow_the_group_order_with_preliminares_first(): void
    {
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 5]);
        $second = Group::factory()->create(['name' => 'GRUPO SEGUNDO', 'sort_order' => 3]);
        $first = Group::factory()->create(['name' => 'GRUPO PRIMEIRO', 'sort_order' => 2]);
        Topic::factory()->create(['group_id' => $second->id, 'title' => 'TÓPICO B1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => $first->id, 'title' => 'TÓPICO A2', 'sort_order' => 2]);
        Topic::factory()->create(['group_id' => $first->id, 'title' => 'TÓPICO A1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'sort_order' => 1]);

        $response = $this->actingAs(User::factory()->create())->get(route('settings.topics.index'));

        $response->assertOk();
        $this->assertSame(
            ['DA GRATUIDADE DE JUSTIÇA', 'TÓPICO A1', 'TÓPICO A2', 'TÓPICO B1'],
            $response->viewData('topics')->pluck('title')->all(),
        );
        $this->assertSame(
            ['PRELIMINARES', 'GRUPO PRIMEIRO', 'GRUPO SEGUNDO'],
            $response->viewData('groups')->pluck('name')->all(),
        );
        $this->assertSame([1, 2, 1], $response->viewData('groups')->pluck('topics_count')->all());
    }

    public function test_create_and_edit_pages_open_the_modal_on_the_library(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.topics.create'))
            ->assertRedirect(route('settings.topics.index', ['novo' => 1]));
        $this->actingAs($user)->get(route('settings.topics.edit', $topic))
            ->assertRedirect(route('settings.topics.index', ['editar' => $topic->id]));
    }

    public function test_groups_page_redirects_to_the_library(): void
    {
        $this->actingAs(User::factory()->create())->get(route('settings.groups.index'))
            ->assertRedirect(route('settings.topics.index'));
    }

    public function test_invalid_topic_returns_to_the_library_with_errors(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('settings.topics.index'))
            ->post(route('settings.topics.store'), ['title' => '', 'content' => '', 'group_id' => '']);

        $response->assertRedirect(route('settings.topics.index'));
        $response->assertSessionHasErrors(['title', 'content', 'group_id']);
    }

    public function test_placeholder_count_ignores_repeated_placeholders(): void
    {
        $topic = Topic::factory()->make(['content' => 'Entre [DATA] e [DATA], a [EMPRESA] pagou [VALOR].']);

        $this->assertSame(3, $topic->placeholderCount());
    }
}
