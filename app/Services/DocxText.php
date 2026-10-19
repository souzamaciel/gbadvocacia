<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Reads the text of a Word (.docx) file in reading order: paragraphs line by line,
 * table cells separated by " | " and checkboxes as [X] / [ ].
 */
class DocxText
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const W14 = 'http://schemas.microsoft.com/office/word/2010/wordml';

    private DOMXPath $xpath;

    public function extract(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo. Envie um documento do Word (.docx).');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('O arquivo não parece ser um documento do Word (.docx).');
        }

        $document = new DOMDocument;
        $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        $this->xpath = new DOMXPath($document);
        $this->xpath->registerNamespace('w', self::W);
        $this->xpath->registerNamespace('w14', self::W14);

        $body = $this->xpath->query('//w:body')->item(0);
        $lines = $body ? $this->blockLines($body) : [];

        $text = implode("\n", $lines);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Removes passwords before the text leaves the system: everything after "senha…:" on that line.
     */
    public static function redactPrivate(string $text): string
    {
        return (string) preg_replace('/(senha[^:\n]*:)[^\n]*/iu', '$1 [removido por segurança]', $text);
    }

    /**
     * @return list<string>
     */
    private function blockLines(DOMNode $container): array
    {
        $lines = [];

        foreach ($container->childNodes as $node) {
            if (! $node instanceof DOMElement || $node->namespaceURI !== self::W) {
                continue;
            }

            if ($node->localName === 'p') {
                $lines[] = $this->paragraphText($node);
            } elseif ($node->localName === 'tbl') {
                foreach ($this->xpath->query('./w:tr', $node) as $row) {
                    $cells = [];

                    foreach ($this->xpath->query('./w:tc', $row) as $cell) {
                        $cells[] = trim(implode(' ', array_filter($this->blockLines($cell), fn (string $line): bool => trim($line) !== '')));
                    }

                    $lines[] = implode(' | ', $cells);
                }
            } elseif ($node->localName === 'sdt') {
                $content = $this->xpath->query('./w:sdtContent', $node)->item(0);

                if ($content) {
                    array_push($lines, ...$this->blockLines($content));
                }
            }
        }

        return $lines;
    }

    private function paragraphText(DOMElement $paragraph): string
    {
        $text = '';

        foreach ($this->xpath->query('.//w:t | .//w:tab | .//w:br | .//w:sym | .//w14:checkbox', $paragraph) as $node) {
            /** @var DOMElement $node */
            $text .= match ($node->localName) {
                't' => $node->textContent,
                'tab' => ' ',
                'br' => "\n",
                'sym' => $this->symbol($node->getAttributeNS(self::W, 'char')),
                'checkbox' => '',
                default => '',
            };
        }

        $text = strtr($text, ['☒' => '[X]', '☑' => '[X]', '✔' => '[X]', '✓' => '[X]', '☐' => '[ ]']);

        return rtrim((string) preg_replace('/[ \t]{2,}/', ' ', $text));
    }

    private function symbol(string $code): string
    {
        $code = strtoupper($code);
        $code = strlen($code) === 4 && str_starts_with($code, 'F0') ? substr($code, 2) : $code;

        return match ($code) {
            'FE', 'FD', '78', 'FB' => '[X]',
            'A8', '6F', '71' => '[ ]',
            default => '',
        };
    }
}
