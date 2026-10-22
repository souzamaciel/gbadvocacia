<?php

namespace App\Services;

use DOMElement;
use DOMNode;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font;

/**
 * Turns the editor's HTML (paragraphs, headings, quotes, code, lists, links and
 * inline formatting) into Word paragraphs, following the office template.
 */
class WordHtmlRenderer
{
    private const BLOCK_TAGS = ['p', 'h1', 'h2', 'blockquote', 'pre', 'ol', 'ul', 'li', 'div'];

    private const INDENT_STEP = 720;

    private const SIZES = ['ql-size-small' => 10, 'ql-size-large' => 16, 'ql-size-huge' => 22];

    private const ALIGNMENTS = ['ql-align-center' => Jc::CENTER, 'ql-align-right' => Jc::END, 'ql-align-justify' => Jc::BOTH];

    /**
     * @param  array<string, mixed>  $paragraphStyle  style of a regular paragraph
     */
    public function __construct(private array $paragraphStyle) {}

    public function render(Section $section, string $html): void
    {
        $root = RichText::parse($html);
        $inline = [];

        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement && in_array(strtolower($node->tagName), self::BLOCK_TAGS, true)) {
                $this->flushInline($section, $inline);
                $this->renderBlock($section, $node);

                continue;
            }

            $inline[] = $node;
        }

        $this->flushInline($section, $inline);
    }

    /**
     * Loose text or inline tags outside a paragraph become one paragraph.
     *
     * @param  array<int, DOMNode>  $nodes
     */
    private function flushInline(Section $section, array &$nodes): void
    {
        $hasText = array_filter($nodes, fn (DOMNode $node): bool => trim($node->textContent) !== '' || ($node instanceof DOMElement && $node->tagName !== 'br'));

        if ($hasText !== []) {
            $textRun = $section->addTextRun($this->paragraphStyle);
            foreach ($nodes as $node) {
                $this->renderInline($textRun, $node, []);
            }
        }

        $nodes = [];
    }

    private function renderBlock(Section $section, DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if ($tag === 'ol' || $tag === 'ul') {
            $this->renderList($section, $element);

            return;
        }

        if (trim($element->textContent) === '') {
            return;
        }

        [$paragraph, $font] = match ($tag) {
            'h1' => [['keepNext' => true], ['bold' => true, 'size' => 16]],
            'h2' => [['keepNext' => true], ['bold' => true, 'size' => 14]],
            'blockquote' => [['indentation' => ['left' => (int) round(Converter::cmToTwip(4))], 'lineHeight' => 1.0, 'spaceAfter' => Converter::pointToTwip(12)], ['size' => 10]],
            'pre' => [['alignment' => Jc::START, 'lineHeight' => 1.0, 'spaceAfter' => Converter::pointToTwip(12)], ['name' => 'Courier New', 'size' => 10]],
            default => [[], []],
        };

        $textRun = $section->addTextRun($this->paragraphFor($element, $paragraph));
        foreach ($element->childNodes as $child) {
            $this->renderInline($textRun, $child, $font);
        }
    }

    private function renderList(Section $section, DOMElement $list): void
    {
        $ordered = strtolower($list->tagName) === 'ol';
        $counters = [];

        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($item->tagName), ['ol', 'ul'], true)) {
                $this->renderList($section, $item);

                continue;
            }

            $level = $this->indentLevel($item);
            $counters = array_slice($counters, 0, $level + 1, true);
            $counters[$level] = ($counters[$level] ?? 0) + 1;

            $textRun = $section->addTextRun($this->paragraphFor($item, []));
            $textRun->addText($ordered ? $counters[$level].'. ' : ($level > 0 ? '◦ ' : '• '));

            $nested = [];
            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ol', 'ul'], true)) {
                    $nested[] = $child;

                    continue;
                }

                $this->renderInline($textRun, $child, []);
            }

            foreach ($nested as $nestedList) {
                $this->renderList($section, $nestedList);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $font
     */
    private function renderInline(TextRun $textRun, DOMNode $node, array $font): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $this->addText($textRun, $node->nodeValue ?? '', $font);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'br') {
            $textRun->addTextBreak();

            return;
        }

        if ($tag === 'a' && preg_match('#^(https?://|mailto:)#i', $node->getAttribute('href'))) {
            $textRun->addLink(
                $node->getAttribute('href'),
                trim(preg_replace('/\s+/u', ' ', $node->textContent)),
                [...$font, 'color' => '0563C1', 'underline' => Font::UNDERLINE_SINGLE],
            );

            return;
        }

        $font = match ($tag) {
            'strong', 'b' => [...$font, 'bold' => true],
            'em', 'i' => [...$font, 'italic' => true],
            'u' => [...$font, 'underline' => Font::UNDERLINE_SINGLE],
            's', 'strike', 'del' => [...$font, 'strikethrough' => true],
            'sub' => [...$font, 'subScript' => true],
            'sup' => [...$font, 'superScript' => true],
            default => $font,
        };

        foreach (RichText::allowedClasses($node) as $class) {
            if (isset(self::SIZES[$class])) {
                $font['size'] = self::SIZES[$class];
            }
        }

        $colors = RichText::allowedColors($node);
        if (isset($colors['color'])) {
            $font['color'] = $this->hexColor($colors['color']);
        }
        if (isset($colors['background-color'])) {
            $font['bgColor'] = $this->hexColor($colors['background-color']);
        }

        foreach ($node->childNodes as $child) {
            $this->renderInline($textRun, $child, $font);
        }
    }

    /**
     * @param  array<string, mixed>  $font
     */
    private function addText(TextRun $textRun, string $text, array $font): void
    {
        foreach (explode("\n", $text) as $index => $line) {
            if ($index > 0) {
                $textRun->addTextBreak();
            }

            if ($line !== '') {
                WordBlanks::add($textRun, $line, $font);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function paragraphFor(DOMElement $element, array $extra): array
    {
        $style = [...$this->paragraphStyle, ...$extra];

        foreach (RichText::allowedClasses($element) as $class) {
            if (isset(self::ALIGNMENTS[$class])) {
                $style['alignment'] = self::ALIGNMENTS[$class];
            }
        }

        $level = $this->indentLevel($element);
        if ($level > 0) {
            $style['indentation'] = ['left' => ($style['indentation']['left'] ?? 0) + $level * self::INDENT_STEP];
        }

        return $style;
    }

    private function indentLevel(DOMElement $element): int
    {
        foreach (RichText::allowedClasses($element) as $class) {
            if (preg_match('/^ql-indent-(\d)$/', $class, $match)) {
                return (int) $match[1];
            }
        }

        return 0;
    }

    private function hexColor(string $color): string
    {
        if (preg_match('/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/', $color, $match)) {
            return sprintf('%02X%02X%02X', min(255, (int) $match[1]), min(255, (int) $match[2]), min(255, (int) $match[3]));
        }

        $hex = ltrim($color, '#');

        return strtoupper(strlen($hex) === 3 ? $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2] : $hex);
    }
}
