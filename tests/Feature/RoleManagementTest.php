<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_initial_profiles_are_created_by_the_migration(): void
    {
        $this->assertEqualsCanonicalizing(['Administrador', 'Advogado', 'Assistente'], Role::query()->pluck('name')->all());
        $this->assertCount(count(Permission::cases()), Role::administrator()->grantedPermissions());

        $lawyer = Role::query()->where('name', 'Advogado')->first();
        $this->assertTrue($lawyer->allows(Permission::TopicsAi));
        $this->assertFalse($lawyer->allows(Permission::UsersManage));

        $assistant = Role::query()->where('name', 'Assistente')->first();
        $this->assertTrue($assistant->allows(Permission::LibraryView));
        $this->assertFalse($assistant->allows(Permission::TopicsEdit));
    }

    public function test_admin_creates_a_profile_and_dependencies_are_added(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Estagiário',
            'description' => 'Apoio na Biblioteca',
            'permissions' => [Permission::TopicsAi->value, Permission::PetitionsGenerate->value],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::query()->where('name', 'Estagiário')->firstOrFail();
        $this->assertSame([
            Permission::PetitionsEdit->value,
            Permission::PetitionsGenerate->value,
            Permission::LibraryView->value,
            Permission::TopicsEdit->value,
            Permission::TopicsAi->value,
        ], $role->permissions);
    }

    public function test_profile_names_are_unique_and_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Advogado',
            'permissions' => ['tudo.liberado'],
        ])->assertSessionHasErrors(['name' => 'Já existe um perfil com esse nome.', 'permissions.0']);
    }

    public function test_admin_updates_a_profile(): void
    {
        $role = Role::query()->where('name', 'Assistente')->first();

        $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => 'Assistente',
            'permissions' => [Permission::AppointmentsCreate->value],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame([Permission::AppointmentsCreate->value], $role->fresh()->permissions);
    }

    public function test_administrator_profile_cannot_be_changed_or_deleted(): void
    {
        $administrator = Role::administrator();

        $this->actingAs($this->admin)->put(route('admin.roles.update', $administrator), ['name' => 'Chefe', 'permissions' => []])->assertForbidden();
        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $administrator))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.roles.edit', $administrator))->assertOk()->assertDontSee('Salvar perfil');
    }

    public function test_profile_in_use_cannot_be_deleted(): void
    {
        $role = Role::factory()->create();
        User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($this->admin)->from(route('admin.roles.index'))->delete(route('admin.roles.destroy', $role))
            ->assertSessionHas('error');
        $this->assertModelExists($role);
    }

    public function test_unused_profile_is_deleted(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));
        $this->assertModelMissing($role);
    }

    public function test_duplicating_prefills_the_permissions(): void
    {
        $source = Role::query()->where('name', 'Assistente')->first();

        $this->actingAs($this->admin)->get(route('admin.roles.create', ['from' => $source->id]))
            ->assertOk()
            ->assertSee('Assistente (cópia)');
    }

    public function test_users_without_permission_cannot_manage_profiles(): void
    {
        $manager = User::factory()->withPermissions([Permission::UsersManage])->create();

        $this->actingAs($manager)->get(route('admin.roles.index'))->assertForbidden();
    }
}
