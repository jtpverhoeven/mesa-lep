<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;

class ReportHtml
{
    public static function lines(?string $text): HtmlString
    {
        return new HtmlString(nl2br(e($text ?? '')));
    }

    public static function richText(string $html): HtmlString
    {
        $document = new DOMDocument;
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        $root = $document->getElementsByTagName('div')->item(0);

        if (! $root instanceof DOMElement) {
            throw new \UnexpectedValueException('Report HTML could not be parsed.');
        }

        self::clean($root);
        $result = '';

        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return new HtmlString($result);
    }

    private static function clean(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMComment) {
                $parent->removeChild($node);

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
                $parent->removeChild($node);

                continue;
            }

            self::clean($node);

            if (! in_array($tag, ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'span', 'div', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'ul', 'ol', 'li', 'hr'], true)) {
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }

                $parent->removeChild($node);

                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $allowed = in_array($name, ['class', 'style', 'colspan', 'rowspan', 'align', 'width', 'height', 'cellpadding', 'cellspacing'], true);

                if (! $allowed || ($name === 'style' && preg_match('/url\s*\(|expression\s*\(|@import|[\\\\<>]/i', $attribute->value))) {
                    $node->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'sup') {
                $node->setAttribute('class', 'sup');
            }
        }
    }
}
