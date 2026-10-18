<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Topic texts may be legacy plain text (paragraphs separated by blank lines) or
 * HTML written in the rich-text editor. Only formatting the Word generator
 * understands is kept.
 */
class RichText
{
    public const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'sub', 'sup', 'h1', 'h2', 'blockquote', 'pre', 'ol', 'ul', 'li', 'a', 'span'];

    public const PLACEHOLDER_PATTERN = '/\[([^\[\]<>]+)\]/u';

    private const ALIASES = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's'];

    private const DROPPED_TAGS = ['script', 'style', 'iframe', 'img', 'video', 'audio', 'object', 'embed', 'svg', 'math', 'head', 'title', 'meta', 'link', 'noscript', 'template', 'textarea', 'select', 'button', 'input'];

    private const CLASS_PATTERN = '/^ql-(align-(center|right|justify)|indent-[1-8]|size-(small|large|huge))$/';

    private const COLOR_PATTERN = '/^(#[0-9a-f]{3}|#[0-9a-f]{6}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/i';

    public static function isHtml(string $content): bool
    {
        return (bool) preg_match('/<\s*\/?\s*('.implode('|', [...self::ALLOWED_TAGS, 'b', 'i', 'div']).')\b[^>]*>/i', $content);
    }

    /**
     * Keep only the allowed tags and safe attributes (editor classes, text colors and http/mailto links).
     */
    public static function sanitize(string $html): string
    {
        $root = self::parse($html);
        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= self::cleanNode($child);
        }

        $clean = preg_replace('#<p>(\s|<br>)*</p>#i', '', $clean);

        return trim($clean);
    }

    /**
     * HTML ready to display or edit, whatever the stored format.
     */
    public static function toHtml(string $content): string
    {
        if (self::isHtml($content)) {
            return self::sanitize($content);
        }

        $paragraphs = preg_split('/\R{2,}/u', trim($content)) ?: [];

        return implode('', array_map(
            fn (string $paragraph): string => '<p>'.preg_replace('/\R/u', '<br>', e(trim($paragraph))).'</p>',
            array_filter($paragraphs, fn (string $paragraph): bool => trim($paragraph) !== ''),
        ));
    }

    public static function toPlainText(string $content): string
    {
        if (! self::isHtml($content)) {
            return trim($content);
        }

        $text = preg_replace(['#<br\s*/?>#i', '#</(p|li|h1|h2|blockquote|pre)>#i'], ["\n", "\n"], $content);

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Parse an HTML fragment and return the element that wraps it.
     */
    public static function parse(string $html): DOMElement
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->childNodes as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return $document->createElement('div');
    }

    /**
     * Editor classes kept on an element (alignment, indentation and text size).
     *
     * @return array<int, string>
     */
    public static function allowedClasses(DOMElement $element): array
    {
        return array_values(array_filter(
            preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [],
            fn (string $class): bool => (bool) preg_match(self::CLASS_PATTERN, $class),
        ));
    }

    /**
     * Safe text colors of an element.
     *
     * @return array<string, string>
     */
    public static function allowedColors(DOMElement $element): array
    {
        $colors = [];

        foreach (explode(';', $element->getAttribute('style')) as $declaration) {
            [$property, $value] = array_map('trim', explode(':', $declaration, 2) + [1 => '']);
            $property = strtolower($property);

            if (in_array($property, ['color', 'background-color'], true) && preg_match(self::COLOR_PATTERN, $value)) {
                $colors[$property] = strtolower(preg_replace('/\s+/', ' ', $value));
            }
        }

        return $colors;
    }

    private static function cleanNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_NOQUOTES, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        $tag = self::ALIASES[$tag] ?? $tag;

        if (in_array($tag, self::DROPPED_TAGS, true)) {
            return '';
        }

        $children = '';
        foreach ($node->childNodes as $child) {
            $children .= self::cleanNode($child);
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            return $tag === 'div' ? "<p>{$children}</p>" : $children;
        }

        if ($tag === 'br') {
            return '<br>';
        }

        $attributes = '';
        $classes = self::allowedClasses($node);
        if ($classes !== []) {
            $attributes .= ' class="'.implode(' ', $classes).'"';
        }

        $colors = self::allowedColors($node);
        if ($colors !== []) {
            $attributes .= ' style="'.implode(' ', array_map(fn (string $property, string $value): string => "{$property}: {$value};", array_keys($colors), $colors)).'"';
        }

        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));

            if (! preg_match('#^(https?://|mailto:)#i', $href)) {
                return $children;
            }

            $attributes .= ' href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'"';
        }

        if ($tag === 'span' && $attributes === '') {
            return $children;
        }

        return "<{$tag}{$attributes}>{$children}</{$tag}>";
    }
}
