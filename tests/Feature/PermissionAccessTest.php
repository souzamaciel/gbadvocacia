<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Group;
use App\Models\Petition;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_shows_only_the_allowed_areas(): void
    {
        $user = User::factory()->withPermissions([Permission::PetitionsEdit])->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Petições')
            ->assertDontSee('Biblioteca de tópicos')
            ->assertDontSee('Iniciar atendimento')
            ->assertDontSee(route('admin.users.index'));
    }

    public function test_user_without_any_permission_only_reaches_the_profile(): void
    {
        $user = User::factory()->withPermissions([])->create();

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('Seu perfil ainda não tem acesso');
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $this->actingAs($user)->get(route('petition.index'))->assertForbidden();
        $this->actingAs($user)->get(route('appointment.create'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.topics.index'))->assertForbidden();
    }

    public function test_read_only_library_hides_editing_and_blocks_the_actions(): void
    {
        $user = User::factory()->withPermissions([Permission::LibraryView])->create();
        $topic = Topic::factory()->create();

        $this->actingAs($user)->get(route('settings.topics.index'))
            ->assertOk()
            ->assertSee($topic->title)
            ->assertDontSee('class="btn btn-primary js-new-topic"', false)
            ->assertDontSee('class="gb-icon-action js-edit-topic"', false)
            ->assertDontSee('gb-icon-action-danger js-delete-topic"', false)
            ->assertDontSee('class="gb-icon-action js-new-group"', false)
            ->assertDontSee('id="preview-modal-edit"', false);

        $this->actingAs($user)->delete(route('settings.topics.destroy', $topic))->assertForbidden();
        $this->actingAs($user)->postJson(route('settings.groups.store'), ['name' => 'NOVO'])->assertForbidden();
        $this->actingAs($user)->postJson(route('settings.topics.suggest-variables'), ['content' => 'Texto'])->assertForbidden();
    }

    public function test_ai_button_is_hidden_without_the_ai_permission(): void
    {
        $user = User::factory()->withPermissions([Permission::TopicsEdit, Permission::LibraryView])->create();
        Group::factory()->create();

        $this->actingAs($user)->get(route('settings.topics.index'))
            ->assertOk()
            ->assertSee('class="btn btn-primary js-new-topic"', false)
            ->assertSee('id="ai-identify"  hidden', false);
    }

    public function test_petition_delete_and_generate_follow_the_profile(): void
    {
        $user = User::factory()->withPermissions([Permission::PetitionsEdit])->create();
        $petition = Petition::factory()->create();

        $this->actingAs($user)->get(route('petition.index'))->assertOk()->assertDontSee('class="js-delete-petition"', false);
        $this->actingAs($user)->delete(route('petition.destroy', $petition))->assertForbidden();
        $this->actingAs($user)->get(route('petition.generate.show', $petition))->assertForbidden();
        $this->assertModelExists($petition);
    }

    public function test_appointments_require_their_permission(): void
    {
        $user = User::factory()->withPermissions([Permission::LibraryView])->create();

        $this->actingAs($user)->get(route('appointment.create'))->assertForbidden();
        $this->actingAs($user)->post(route('appointment.store'), [])->assertForbidden();
    }
}
