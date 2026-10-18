<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\PetitionItem;
use App\Models\Topic;
use App\Models\User;
use App\Services\PetitionDocxGenerator;
use App\Services\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class RichTextTopicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_plain_text_becomes_paragraphs(): void
    {
        $this->assertSame(
            '<p>Primeiro parágrafo<br>continua aqui.</p><p>Segundo &amp; último.</p>',
            RichText::toHtml("Primeiro parágrafo\ncontinua aqui.\n\nSegundo & último."),
        );
    }

    public function test_sanitize_keeps_only_formatting_that_reaches_word(): void
    {
        $dirty = '<p class="x" onclick="alert(1)"><b>Negrito</b> <i>itálico</i> <u>sub</u> <span style="color:red">cor</span></p>'
            .'<script>alert(1)</script><ol><li data-list="ordered">um</li></ol><img src="x" onerror="alert(1)">';

        $this->assertSame(
            '<p><strong>Negrito</strong> <em>itálico</em> <u>sub</u> cor</p><ol><li>um</li></ol>',
            RichText::sanitize($dirty),
        );
    }

    public function test_saving_a_topic_stores_clean_html(): void
    {
        $group = Group::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('settings.topics.store'), [
            'group_id' => $group->id,
            'title' => 'TÓPICO FORMATADO',
            'content' => '<p><strong>Atenção</strong> ao <a href="https://exemplo.com">prazo</a>.</p><script>alert(1)</script>',
        ])->assertRedirect(route('settings.topics.index'));

        $this->assertSame('<p><strong>Atenção</strong> ao prazo.</p>', Topic::query()->where('title', 'TÓPICO FORMATADO')->value('content'));
    }

    public function test_empty_editor_is_rejected(): void
    {
        $group = Group::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('settings.topics.index'))
            ->post(route('settings.topics.store'), ['group_id' => $group->id, 'title' => 'VAZIO', 'content' => '<p><br></p>'])
            ->assertSessionHasErrors(['content' => 'O conteúdo do tópico é obrigatório.']);
    }

    public function test_placeholders_work_inside_formatted_text(): void
    {
        $item = PetitionItem::factory()->make([
            'content' => '<p><strong>Admitido</strong> em [DATA DE ADMISSÃO] na empresa [EMPRESA &amp; CIA].</p>',
            'field_values' => ['DATA DE ADMISSÃO' => '01/02/2020', 'EMPRESA & CIA' => 'Silva & Filhos'],
        ]);

        $this->assertSame(['DATA DE ADMISSÃO', 'EMPRESA & CIA'], $item->placeholders());
        $this->assertSame([], $item->pendingPlaceholders());
        $this->assertSame(
            '<p><strong>Admitido</strong> em 01/02/2020 na empresa Silva &amp; Filhos.</p>',
            $item->renderedContent(),
        );
    }

    public function test_word_document_keeps_bold_underline_and_lists(): void
    {
        $path = (new PetitionDocxGenerator)->generateMerit([
            'preliminares_selecionadas_ids' => [],
            'contrato_trabalho_html' => '<p><strong>Negrito</strong> e <u>sublinhado</u>.</p>'
                .'<ul><li>Primeiro marcador</li></ul><ol><li>Item numerado</li><li>Outro item</li></ol>',
        ]);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertMatchesRegularExpression('/<w:b w:val="1"\/>(?:(?!<\/w:r>).)*>Negrito</s', $xml);
        $this->assertMatchesRegularExpression('/<w:u w:val="single"\/>(?:(?!<\/w:r>).)*>sublinhado</s', $xml);
        $text = html_entity_decode(strip_tags($xml));
        $this->assertStringContainsString('• Primeiro marcador', $text);
        $this->assertStringContainsString('1. Item numerado', $text);
        $this->assertStringContainsString('2. Outro item', $text);
    }
}
