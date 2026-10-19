<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_sees_every_tutorial(): void
    {
        $this->actingAs(User::factory()->create())->get(route('help.index'))
            ->assertOk()
            ->assertSee('Base de conhecimento')
            ->assertSee('Fazer um atendimento')
            ->assertSee('Novo tópico com IA')
            ->assertSee('Configurar a ficha de atendimento')
            ->assertSee('Perguntas frequentes');
    }

    public function test_tutorials_of_areas_outside_the_profile_are_hidden(): void
    {
        $lawyer = User::factory()->create(['role_id' => Role::query()->where('name', 'Advogado')->value('id')]);

        $this->actingAs($lawyer)->get(route('help.index'))
            ->assertOk()
            ->assertSee('Rascunhos de atendimento')
            ->assertSee('Consultar a Biblioteca')
            ->assertDontSee('id="novo-topico"', false)
            ->assertDontSee('id="usuarios"', false)
            ->assertDontSee('id="configurar-ficha"', false);
    }

    public function test_user_without_permissions_still_reads_the_basics(): void
    {
        $this->actingAs(User::factory()->withPermissions([Permission::LibraryView])->create())->get(route('help.index'))
            ->assertOk()
            ->assertSee('Entrar no sistema')
            ->assertDontSee('id="atendimento"', false);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('help.index'))->assertRedirect(route('login'));
    }
}
