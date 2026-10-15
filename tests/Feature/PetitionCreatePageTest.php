<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetitionCreatePageTest extends TestCase
{
    use RefreshDatabase;

    private Group $secondGroup;

    private Group $firstGroup;

    protected function setUp(): void
    {
        parent::setUp();

        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'sort_order' => 1]);

        $this->secondGroup = Group::factory()->create(['name' => 'GRUPO SEGUNDO', 'sort_order' => 3]);
        $this->firstGroup = Group::factory()->create(['name' => 'GRUPO PRIMEIRO', 'sort_order' => 2]);

        Topic::factory()->create(['group_id' => $this->secondGroup->id, 'title' => 'PEDIDO B1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO A2', 'sort_order' => 2]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO A1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO INATIVO', 'sort_order' => 3, 'active' => false]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('petition.create'))->assertRedirect(route('login'));
    }

    public function test_groups_are_listed_in_their_configured_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $response->assertOk();
        $response->assertSeeInOrder(['GRUPO PRIMEIRO', 'PEDIDO A1', 'PEDIDO A2', 'GRUPO SEGUNDO', 'PEDIDO B1']);
        $this->assertSame(
            ['GRUPO PRIMEIRO', 'GRUPO SEGUNDO'],
            $response->viewData('groups')->pluck('name')->all(),
        );
    }

    public function test_pedidos_search_list_follows_group_order_then_topic_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $this->assertSame(
            ['PEDIDO A1', 'PEDIDO A2', 'PEDIDO B1'],
            $response->viewData('pedidos')->pluck('title')->all(),
        );
    }

    public function test_preliminares_are_not_mixed_with_pedidos(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $this->assertSame(['DA GRATUIDADE DE JUSTIÇA'], $response->viewData('preliminares')->pluck('title')->all());
        $this->assertNotContains('DA GRATUIDADE DE JUSTIÇA', $response->viewData('pedidos')->pluck('title')->all());
    }

    public function test_appointment_page_uses_the_same_pedidos_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('appointment.create'));

        $response->assertOk();
        $this->assertSame(
            ['PEDIDO A1', 'PEDIDO A2', 'PEDIDO B1'],
            $response->viewData('pedidos')->pluck('title')->all(),
        );
    }
}
