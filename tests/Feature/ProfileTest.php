<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_the_role_and_its_permissions(): void
    {
        $user = User::factory()->create(['name' => 'Ana Souza']);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Ana Souza')
            ->assertSee('Administrador')
            ->assertSee('Gerenciar usuários');
    }

    public function test_user_updates_name_and_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), ['name' => ' Ana Souza ', 'email' => 'ANA@Exemplo.com.br'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $this->assertSame('Ana Souza', $user->fresh()->name);
        $this->assertSame('ana@exemplo.com.br', $user->fresh()->email);
    }

    public function test_email_already_used_by_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'ocupado@exemplo.com.br']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Ana', 'email' => 'ocupado@exemplo.com.br'])
            ->assertSessionHasErrors(['email' => 'Este e-mail já é usado por outro usuário.']);
    }

    public function test_changing_the_password_requires_the_current_one_and_a_different_new_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-atual')]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'senha-atual',
            'password' => 'senha-atual',
            'password_confirmation' => 'senha-atual',
        ])->assertSessionHasErrors(['password' => 'A nova senha precisa ser diferente da atual.']);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'senha-atual',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
    }

    public function test_old_password_address_opens_the_profile(): void
    {
        $this->actingAs(User::factory()->create())->get(route('password.edit'))->assertRedirect(route('profile.edit'));
    }

    public function test_user_with_a_temporary_password_must_change_it_before_using_the_system(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => Hash::make('provisoria1')]);

        $this->actingAs($user)->get(route('petition.index'))->assertRedirect(route('profile.edit'));
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Defina uma nova senha para continuar.');

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'provisoria1',
            'password' => 'minha-senha-1',
            'password_confirmation' => 'minha-senha-1',
        ])->assertRedirect(route('profile.edit'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('petition.index'))->assertOk();
    }

    public function test_login_records_the_last_access_and_blocks_deactivated_users(): void
    {
        $active = User::factory()->create(['email' => 'ativo@exemplo.com.br']);
        User::factory()->inactive()->create(['email' => 'inativo@exemplo.com.br']);

        $this->post(route('login'), ['email' => 'inativo@exemplo.com.br', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Seu acesso está desativado. Fale com o administrador do sistema.']);
        $this->assertGuest();

        $this->post(route('login'), ['email' => 'ativo@exemplo.com.br', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertNotNull($active->fresh()->last_login_at);
    }

    public function test_user_deactivated_during_the_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'))->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
