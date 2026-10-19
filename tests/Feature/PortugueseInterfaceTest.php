<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PortugueseInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_speaks_brazilian_portuguese(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());

        $errors = Validator::make(['email' => 'x', 'name' => ''], ['email' => 'email', 'name' => 'required'])->errors();

        $this->assertSame('O campo e-mail deve ser um e-mail válido.', $errors->first('email'));
        $this->assertSame('O campo nome é obrigatório.', $errors->first('name'));
    }

    public function test_error_pages_are_in_portuguese(): void
    {
        $this->get('/pagina-que-nao-existe')->assertNotFound()->assertSee('Página não encontrada');
    }

    public function test_messages_for_screens_that_call_the_server_are_in_portuguese(): void
    {
        $this->postJson(route('appointment.draft.store'))->assertUnauthorized()
            ->assertJsonPath('message', 'Sua sessão expirou. Entre novamente no sistema.');

        $lawyer = User::factory()->withPermissions([])->create();
        $this->actingAs($lawyer)->postJson(route('appointment.draft.store'))->assertForbidden()
            ->assertJsonPath('message', 'Seu perfil não tem permissão para esta ação.');
    }

    public function test_attendance_dates_use_the_portuguese_calendar(): void
    {
        $this->actingAs(User::factory()->create())->get(route('appointment.create'))
            ->assertOk()
            ->assertSee('flatpickr@4.6.13/dist/l10n/pt.js', false)
            ->assertSee('placeholder="dd/mm/aaaa"', false)
            ->assertDontSee('type="date"', false);
    }
}
