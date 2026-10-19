<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use App\Services\DocxText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class AppointmentImportTest extends TestCase
{
    use RefreshDatabase;

    private const FICHA = [
        'FICHA DE ATENDIMENTO',
        'Nome: Joana Martins de Souza  Nacionalidade: brasileira  Estado civil: casada',
        'CPF: 087.654.321-09  Data de nascimento: 14/05/1990',
        'Empregador: Supermercado Bom Preço Ltda.  CNPJ/CPF: 11.222.333/0001-44',
        'Qual foi o período trabalhado, admissão 03/03/2021, dispensa 10/02/2025. Possuía registro em CTPS? (X) Sim ( ) Não.',
        'Quanto tempo de intervalo para refeição realizava? 20 minutos',
        '13.4 Senha GOV (Para entrar no Portal INSS): Joana.2024 secreta. Tem confirmação de duas etapas?',
        'Outras informações: descontavam do salário as diferenças do caixa.',
    ];

    private function docx(array $lines, ?array $table = null): UploadedFile
    {
        $word = new PhpWord;
        $section = $word->addSection();

        foreach ($lines as $line) {
            $section->addText(htmlspecialchars($line));
        }

        if ($table) {
            $grid = $section->addTable();

            foreach ($table as $row) {
                $grid->addRow();

                foreach ($row as $cell) {
                    $grid->addCell(3000)->addText(htmlspecialchars($cell));
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'ficha').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return new UploadedFile($path, 'Ficha Joana.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    /**
     * @param  array<string, mixed>  $output
     */
    private function fakeAi(array $output): void
    {
        Http::fake(['*/responses' => Http::response([
            'id' => 'resp_test',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($output)]]]],
        ])]);
    }

    private function answer(string $key, string $value = '', array $values = [], string $other = '', array $rows = []): array
    {
        return ['chave' => $key, 'valor' => $value, 'valores' => $values, 'outro' => $other, 'linhas' => $rows];
    }

    public function test_word_text_is_read_in_order_with_tables_and_checkboxes(): void
    {
        $file = $this->docx(['Primeira linha', 'Marcado: ☒ Sim ☐ Não'], [['Nome', 'Função'], ['Paula', 'Caixa']]);

        $text = (new DocxText)->extract($file->getRealPath());

        $this->assertStringContainsString("Primeira linha\nMarcado: [X] Sim [ ] Não", $text);
        $this->assertStringContainsString('Nome | Função', $text);
        $this->assertStringContainsString('Paula | Caixa', $text);
        $this->assertSame('Senha GOV (portal): [removido por segurança]', DocxText::redactPrivate('Senha GOV (portal): abc.123 e mais'));
    }

    public function test_imported_form_becomes_a_checked_draft_with_suggested_topics(): void
    {
        Storage::fake('local');
        $preliminar = Topic::factory()->create(['group_id' => Group::factory()->create(['id' => Group::PRELIMINARES_ID])->id]);
        $pedido = Topic::factory()->create(['title' => 'DA SUPRESSÃO DO INTERVALO INTRAJORNADA']);

        $this->fakeAi([
            'respostas' => [
                $this->answer('cliente_nome', 'Joana Martins de Souza'),
                $this->answer('cliente_cpf', '087.654.321-09'),
                $this->answer('contrato_registro_ctps', 'sim'),
                $this->answer('contrato_admissao', '03/03/2021'),
                $this->answer('contrato_modalidade', 'Temporário'),
                $this->answer('urgencia_motivos', '', ['Prescrição próxima', 'Doença grave']),
                $this->answer('funcoes', '', [], '', [['celulas' => [['coluna' => 'nome', 'valor' => 'Caixa'], ['coluna' => 'De', 'valor' => '01/01/2022']]]]),
                $this->answer('afastamento_senha_gov', 'nao-devia'),
                $this->answer('chave_inventada', 'x'),
            ],
            'preliminares' => [$preliminar->id, 9999],
            'pedidos' => [['id' => $pedido->id, 'motivo' => 'Intervalo de 20 minutos.'], ['id' => $preliminar->id, 'motivo' => 'errado']],
            'observacoes' => ['Faltam dados sobre EPIs.'],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('appointment.import.store'), ['file' => $this->docx(self::FICHA)], ['Accept' => 'application/json']);

        $appointment = Appointment::sole();
        $response->assertOk()->assertJsonPath('redirect', route('appointment.edit', $appointment));

        $this->assertSame('Joana Martins de Souza', $appointment->client_name);
        $this->assertSame('Ficha Joana.docx', $appointment->import_file_name);
        $this->assertSame('Sim', $appointment->answers['contrato_registro_ctps']);
        $this->assertSame('2021-03-03', $appointment->answers['contrato_admissao']);
        $this->assertArrayNotHasKey('contrato_modalidade', $appointment->answers);
        $this->assertSame(['Prescrição próxima', 'Outros'], $appointment->answers['urgencia_motivos']);
        $this->assertSame('Doença grave', $appointment->answers['urgencia_motivos_outro']);
        $this->assertSame([['nome' => 'Caixa', 'de' => '2022-01-01']], $appointment->answers['funcoes']);
        $this->assertArrayNotHasKey('afastamento_senha_gov', $appointment->answers);
        $this->assertArrayNotHasKey('chave_inventada', $appointment->answers);
        $this->assertSame([$preliminar->id], $appointment->preliminares);
        $this->assertSame([$pedido->id], $appointment->pedidos);
        $this->assertSame('Intervalo de 20 minutos.', $appointment->import_notes['pedidos'][0]['motivo']);
        $this->assertContains('Faltam dados sobre EPIs.', $appointment->import_notes['observacoes']);
        $this->assertTrue(collect($appointment->import_notes['observacoes'])->contains(fn (string $note): bool => str_contains($note, 'Temporário')));

        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'Joana Martins de Souza')
            && ! str_contains($request->body(), 'Joana.2024')
            && ! str_contains($request->body(), 'secreta'));
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->actingAs(User::factory()->create())->get(route('appointment.edit', $appointment))
            ->assertOk()
            ->assertSee('Ficha importada de “Ficha Joana.docx” pela IA.', false)
            ->assertSee('Intervalo de 20 minutos.');
    }

    public function test_only_word_files_with_content_are_accepted(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('appointment.import.store'), ['file' => UploadedFile::fake()->create('ficha.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors(['file' => 'Envie a ficha em Word (.docx). Se ela estiver em .doc ou PDF, abra no Word e use “Salvar como” → Documento do Word (.docx).']);

        $this->actingAs($user)->post(route('appointment.import.store'), ['file' => $this->docx(['Nome:', 'CPF:'])], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'O arquivo está vazio ou tem muito pouco texto. Confira se é a ficha de atendimento preenchida.');

        Http::assertNothingSent();
        $this->assertSame(0, Appointment::count());
    }

    public function test_ai_failure_returns_a_friendly_message(): void
    {
        Http::fake(['*/responses' => Http::response(['error' => 'quota'], 429)]);

        $this->actingAs(User::factory()->create())
            ->post(route('appointment.import.store'), ['file' => $this->docx(self::FICHA)], ['Accept' => 'application/json'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Não foi possível ler a ficha agora. Tente novamente em instantes.');

        $this->assertSame(0, Appointment::count());
    }

    public function test_import_needs_the_appointments_permission(): void
    {
        $user = User::factory()->withPermissions([Permission::PetitionsEdit])->create();

        $this->actingAs($user)->get(route('appointment.import'))->assertForbidden();
        $this->actingAs($user)->post(route('appointment.import.store'), ['file' => $this->docx(self::FICHA)], ['Accept' => 'application/json'])->assertForbidden();
    }
}
