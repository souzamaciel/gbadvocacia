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
            'content' => '<p><strong>Atenção</strong> ao <a href="https://exemplo.com" target="_blank">prazo</a> e <a href="javascript:alert(1)">aqui</a>.</p><script>alert(1)</script>',
        ])->assertRedirect(route('settings.topics.index'));

        $this->assertSame(
            '<p><strong>Atenção</strong> ao <a href="https://exemplo.com">prazo</a> e aqui.</p>',
            Topic::query()->where('title', 'TÓPICO FORMATADO')->value('content'),
        );
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

    public function test_sanitize_keeps_editor_classes_and_colors_but_not_other_styles(): void
    {
        $this->assertSame(
            '<p class="ql-align-center ql-indent-2"><span class="ql-size-large" style="color: rgb(230, 0, 0); background-color: #ffff00;">Destaque</span></p>'
            .'<h1>Título</h1><blockquote>Citação</blockquote><p><s>tachado</s> H<sub>2</sub>O x<sup>2</sup></p>',
            RichText::sanitize(
                '<p class="ql-align-center ql-indent-2 evil"><span class="ql-size-large ql-font-serif" style="color: rgb(230, 0, 0); background-color: #ffff00; position: fixed">Destaque</span></p>'
                .'<h1 onclick="x()">Título</h1><blockquote>Citação</blockquote><p><strike>tachado</strike> H<sub>2</sub>O x<sup>2</sup></p>'
            ),
        );
    }

    public function test_word_document_keeps_the_complete_editor_formatting(): void
    {
        $path = (new PetitionDocxGenerator)->generateMerit([
            'preliminares_selecionadas_ids' => [],
            'contrato_trabalho_html' => '<h1>Título grande</h1>'
                .'<p class="ql-align-center">Centralizado</p>'
                .'<p class="ql-indent-1">Recuado</p>'
                .'<p><span style="color: rgb(230, 0, 0);">Vermelho</span> <span style="background-color: #ffff00;">Marcado</span> <s>Tachado</s> H<sub>2</sub>O x<sup>2</sup> <span class="ql-size-large">Grande</span></p>'
                .'<blockquote>Citação recuada</blockquote>'
                .'<p>Veja <a href="https://www.tst.jus.br">o TST</a>.</p>',
        ]);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $zip->close();
        @unlink($path);

        $run = fn (string $text): string => preg_match('/<w:r>(?:(?!<w:r>).)*?>'.preg_quote($text, '/').'</s', $xml, $match) ? $match[0] : '';
        $paragraph = fn (string $text): string => preg_match('/<w:p>(?:(?!<w:p>).)*?>'.preg_quote($text, '/').'</s', $xml, $match) ? $match[0] : '';

        $this->assertStringContainsString('<w:sz w:val="32"/>', $run('Título grande'));
        $this->assertStringContainsString('<w:jc w:val="center"/>', $paragraph('Centralizado'));
        $this->assertStringContainsString('<w:ind w:left="720"', $paragraph('Recuado'));
        $this->assertStringContainsString('<w:color w:val="E60000"/>', $run('Vermelho'));
        $this->assertStringContainsString('w:fill="FFFF00"', $run('Marcado'));
        $this->assertStringContainsString('<w:strike w:val="1"/>', $run('Tachado'));
        $this->assertMatchesRegularExpression('/<w:vertAlign w:val="subscript"\/>(?:(?!<\/w:r>).)*>2</s', $xml);
        $this->assertMatchesRegularExpression('/<w:vertAlign w:val="superscript"\/>(?:(?!<\/w:r>).)*>2</s', $xml);
        $this->assertStringContainsString('<w:sz w:val="32"/>', $run('Grande'));
        $this->assertStringContainsString('<w:ind w:left="2268"', $paragraph('Citação recuada'));
        $this->assertStringContainsString('<w:hyperlink', $paragraph('o TST'));
        $this->assertStringContainsString('https://www.tst.jus.br', $rels);
    }
}
