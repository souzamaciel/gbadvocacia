<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\QuestionType;
use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentFormConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function step(string $title = 'Cliente'): AppointmentFormStep
    {
        return AppointmentFormStep::query()->where('title', $title)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'appointment_form_step_id' => $this->step()->id,
            'type' => 'short_text',
            'label' => 'Qual é o apelido do cliente?',
            'width' => 'half',
            'required' => false,
            'send_to_ai' => true,
            ...$overrides,
        ];
    }

    public function test_only_profiles_with_the_permission_open_the_configuration(): void
    {
        $this->actingAs($this->admin)->get(route('admin.form.index'))->assertOk()->assertSee('Ficha de atendimento');

        $lawyer = User::factory()->create(['role_id' => Role::query()->where('name', 'Advogado')->value('id')]);
        $this->actingAs($lawyer)->get(route('admin.form.index'))->assertForbidden();
        $this->actingAs($lawyer)->postJson(route('admin.form.questions.store'), $this->payload())->assertForbidden();
        $this->actingAs($lawyer)->get(route('appointment.create'))->assertOk()->assertDontSee(route('admin.form.index'));
    }

    public function test_admin_adds_a_question_with_a_readable_key_at_the_end_of_the_step(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload())->assertOk();

        $question = AppointmentFormQuestion::query()->where('label', 'Qual é o apelido do cliente?')->firstOrFail();
        $this->assertSame('qual_e_o_apelido_do_cliente', $question->key);
        $this->assertSame($this->step()->questions()->max('position'), $question->position);
        $this->assertFalse($question->is_system);
    }

    public function test_choice_questions_need_two_options_and_keep_the_other_field(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'type' => 'single_choice',
            'choices_text' => 'Só uma',
        ]))->assertJsonValidationErrors(['choices' => 'Informe pelo menos 2 opções de resposta.']);

        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'type' => 'multiple_choice',
            'label' => 'Meios de transporte',
            'choices_text' => "Ônibus\nCarro\n\nBicicleta",
            'allow_other' => true,
        ]))->assertOk();

        $question = AppointmentFormQuestion::query()->where('label', 'Meios de transporte')->firstOrFail();
        $this->assertSame(['Ônibus', 'Carro', 'Bicicleta'], $question->choices());
        $this->assertTrue($question->allowsOther());
    }

    public function test_repeatable_list_saves_its_columns(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'type' => 'repeater',
            'label' => 'Filhos',
            'columns' => [['label' => 'Nome', 'type' => 'short_text'], ['label' => 'Nascimento', 'type' => 'date'], ['label' => '', 'type' => 'short_text']],
        ]))->assertOk();

        $this->assertSame(
            [['key' => 'nome', 'label' => 'Nome', 'type' => 'short_text'], ['key' => 'nascimento', 'label' => 'Nascimento', 'type' => 'date']],
            AppointmentFormQuestion::query()->where('label', 'Filhos')->first()->columns(),
        );
    }

    public function test_condition_must_point_to_a_choice_question_and_its_answers(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'show_if_key' => 'cliente_nome',
            'show_if_values' => ['Sim'],
        ]))->assertJsonValidationErrors('show_if_key');

        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'show_if_key' => 'fora_ipatinga',
            'show_if_values' => ['Talvez'],
        ]))->assertJsonValidationErrors('show_if_values');

        $this->actingAs($this->admin)->postJson(route('admin.form.questions.store'), $this->payload([
            'show_if_key' => 'fora_ipatinga',
            'show_if_values' => ['Sim'],
        ]))->assertOk();
    }

    public function test_qualification_fields_cannot_be_deleted_or_change_type(): void
    {
        $name = AppointmentFormQuestion::query()->where('key', 'cliente_nome')->firstOrFail();

        $this->actingAs($this->admin)->deleteJson(route('admin.form.questions.destroy', $name))->assertStatus(422);
        $this->actingAs($this->admin)->putJson(route('admin.form.questions.update', $name), $this->payload(['type' => 'long_text', 'label' => 'Nome completo']))
            ->assertJsonValidationErrors('type');
        $this->actingAs($this->admin)->putJson(route('admin.form.questions.update', $name), $this->payload(['label' => 'Nome completo', 'required' => true]))
            ->assertOk();

        $this->assertSame('Nome completo', $name->fresh()->label);
        $this->assertModelExists($name);
    }

    public function test_question_controlling_others_cannot_be_deleted_until_they_are_adjusted(): void
    {
        $controller = AppointmentFormQuestion::query()->where('key', 'fora_ipatinga')->firstOrFail();

        $this->actingAs($this->admin)->deleteJson(route('admin.form.questions.destroy', $controller))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Em qual localidade?'));

        AppointmentFormQuestion::query()->where('key', 'fora_ipatinga_localidade')->delete();

        $this->actingAs($this->admin)->deleteJson(route('admin.form.questions.destroy', $controller))->assertOk();
        $this->assertModelMissing($controller);
    }

    public function test_removing_an_answer_clears_conditions_that_used_it(): void
    {
        $question = AppointmentFormQuestion::factory()->choices(QuestionType::SingleChoice, ['A', 'B'])->create(['appointment_form_step_id' => $this->step()->id]);
        $dependent = AppointmentFormQuestion::factory()->create([
            'appointment_form_step_id' => $this->step()->id,
            'show_if' => ['key' => $question->key, 'values' => ['B']],
        ]);

        $this->actingAs($this->admin)->putJson(route('admin.form.questions.update', $question), $this->payload([
            'type' => 'single_choice',
            'label' => $question->label,
            'choices_text' => "A\nC",
        ]))->assertOk();

        $this->assertNull($dependent->fresh()->show_if);
    }

    public function test_questions_and_steps_are_reordered(): void
    {
        $step = $this->step();
        [$first, $second] = $step->questions()->take(2)->get()->all();

        $this->actingAs($this->admin)->putJson(route('admin.form.questions.move', $second), ['direction' => 'up'])->assertOk();
        $this->assertSame($second->id, $step->questions()->first()->id);

        $this->actingAs($this->admin)->putJson(route('admin.form.steps.move', $step), ['direction' => 'down'])->assertOk();
        $this->assertSame('Empregador', AppointmentFormStep::query()->orderBy('position')->first()->title);
    }

    public function test_steps_are_created_renamed_and_deleted(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.form.steps.store'), ['title' => 'Dano moral', 'icon' => 'scale'])->assertOk();
        $step = AppointmentFormStep::query()->where('title', 'Dano moral')->firstOrFail();
        $this->assertSame(13, $step->position);

        $this->actingAs($this->admin)->putJson(route('admin.form.steps.update', $step), ['title' => 'Danos morais'])->assertOk();
        $this->actingAs($this->admin)->deleteJson(route('admin.form.steps.destroy', $step))->assertOk();
        $this->assertModelMissing($step);

        $this->actingAs($this->admin)->deleteJson(route('admin.form.steps.destroy', $this->step()))->assertStatus(422);
    }

    public function test_permission_is_listed_for_profiles(): void
    {
        $this->assertSame('Administração', Permission::AppointmentFormManage->area());
        $this->assertTrue($this->admin->can('appointment_form.manage'));
    }
}
