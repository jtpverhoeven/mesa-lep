<?php

namespace App\Support;

class NoteHtml
{
    public static function fromPlainText(string $text): string
    {
        $lines = preg_split('/\R/', trim($text)) ?: [];

        return implode('', array_map(
            fn (string $line): string => trim($line) === '' ? '<p><br></p>' : '<p>'.e($line).'</p>',
            $lines,
        ));
    }

    public static function clean(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        // Quill's semantic HTML turns every space into &nbsp;, which would stop report text from wrapping.
        return trim(str_replace(['&nbsp;', "\u{a0}"], ' ', ReportHtml::richText($html)->toHtml()));
    }

    public static function toPlainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(preg_replace('#</(?:p|li)>|<br\s*/?>#i', "\n", $html))));
    }

    public static function isBlank(string $html): bool
    {
        return trim(str_replace("\u{a0}", ' ', html_entity_decode(strip_tags($html)))) === '';
    }
}
