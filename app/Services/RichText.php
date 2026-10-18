<?php

namespace App\Services;

/**
 * Topic texts may be legacy plain text (paragraphs separated by blank lines) or
 * simple HTML written in the rich-text editor. Only formatting the Word generator
 * understands is kept.
 */
class RichText
{
    public const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li'];

    public const PLACEHOLDER_PATTERN = '/\[([^\[\]<>]+)\]/u';

    public static function isHtml(string $content): bool
    {
        return (bool) preg_match('/<\s*(p|br|strong|b|em|i|u|ol|ul|li)\b[^>]*>/i', $content);
    }

    /**
     * Keep only the allowed tags, without attributes; <b>/<i> become <strong>/<em>.
     */
    public static function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace(['#<(/?)b\b[^>]*>#i', '#<(/?)i\b[^>]*>#i'], ['<$1strong>', '<$1em>'], $html);
        $html = strip_tags($html, '<'.implode('><', self::ALLOWED_TAGS).'>');
        $html = preg_replace('#<(/?)('.implode('|', self::ALLOWED_TAGS).')\b[^>]*?(/?)>#i', '<$1$2>', $html);
        $html = preg_replace('#<p>(\s|<br>)*</p>#i', '', $html);

        return trim($html);
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

        $text = preg_replace(['#<br\s*/?>#i', '#</(p|li)>#i'], ["\n", "\n"], $content);

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }
}
