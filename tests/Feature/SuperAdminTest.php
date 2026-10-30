<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_hidden_from_the_office_administrators(): void
    {
        $admin = User::factory()->create(['name' => 'Administrador do Escritório']);
        $super = User::factory()->superAdmin()->create(['name' => 'Dono do Sistema']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Administrador do Escritório')
            ->assertDontSee('Dono do Sistema');
        $this->actingAs($admin)->get(route('admin.users.edit', $super))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.users.status', $super))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.users.password', $super), ['password' => 'NovaSenha123', 'password_confirmation' => 'NovaSenha123'])->assertNotFound();
        $this->assertTrue($super->fresh()->is_active);
        $this->assertSame(1, Role::administrator()->users()->count());
    }

    public function test_only_the_super_admin_sees_the_logs(): void
    {
        $admin = User::factory()->create();
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('home'))->assertDontSee('Logs de atividade');

        $this->actingAs($super)->get(route('admin.logs.index'))->assertOk();
        $this->actingAs($super)->get(route('home'))->assertSee('Logs de atividade');
    }

    public function test_logs_tutorial_is_only_in_the_super_admin_knowledge_base(): void
    {
        $this->actingAs(User::factory()->create())->get(route('help.index'))
            ->assertOk()
            ->assertSee('Sugerir melhorias')
            ->assertDontSee('Quem fez o quê, quando e de onde');

        $this->actingAs(User::factory()->superAdmin()->create())->get(route('help.index'))
            ->assertSee('Quem fez o quê, quando e de onde');
    }

    public function test_super_admin_can_do_everything_even_without_a_profile(): void
    {
        $super = User::factory()->superAdmin()->create(['role_id' => null]);

        $this->actingAs($super)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($super)->get(route('admin.form.index'))->assertOk();
        $this->actingAs($super)->get(route('petition.index'))->assertOk();
    }

    public function test_office_administrator_still_must_keep_another_visible_administrator(): void
    {
        $admin = User::factory()->create();
        User::factory()->superAdmin()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->put(route('admin.users.status', $admin))->assertRedirect();
        $this->assertFalse($admin->fresh()->is_active);
        $this->actingAs($other)->put(route('admin.users.status', $other));
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_command_creates_the_super_admin_with_a_typed_password(): void
    {
        $this->artisan('app:criar-superadmin')
            ->expectsQuestion('E-mail do superadmin', 'dono@escritorio.test')
            ->expectsQuestion('Nome', 'Dono do Sistema')
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'SenhaForte123')
            ->expectsQuestion('Repita a senha', 'SenhaForte123')
            ->assertSuccessful();

        $user = User::query()->where('email', 'dono@escritorio.test')->sole();
        $this->assertTrue($user->is_super_admin);
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(password_verify('SenhaForte123', $user->password));
    }

    public function test_command_stops_when_the_passwords_differ(): void
    {
        $this->artisan('app:criar-superadmin')
            ->expectsQuestion('E-mail do superadmin', 'dono@escritorio.test')
            ->expectsQuestion('Nome', 'Dono do Sistema')
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'SenhaForte123')
            ->expectsQuestion('Repita a senha', 'OutraSenha123')
            ->assertFailed();

        $this->assertFalse(User::query()->where('email', 'dono@escritorio.test')->exists());
    }
}
