<?php

namespace App\Support;

class ProductHtml
{
    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '' || $html === '<br>' || $html === '<div><br></div>') {
            return null;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return null;
        }

        self::sanitize($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        $result = trim(html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $result === '' ? null : $result;
    }

    private static function sanitize(\DOMNode $node): void
    {
        $allowed = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'span', 'img', 'h2', 'h3', 'ul', 'ol', 'li'];

        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);

            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta'], true)) {
                $node->removeChild($child);
                continue;
            }

            self::sanitize($child);

            if (! in_array($tag, $allowed, true)) {
                self::unwrap($child);
                continue;
            }

            $style = self::style($child->getAttribute('style'));
            $src = $child->getAttribute('src');
            if (preg_match('#(/uploads/products/[A-Za-z0-9\-]+\.(?:jpg|jpeg|png|webp|gif))$#i', $src, $matches)) {
                $src = $matches[1];
            }
            while ($child->attributes->length > 0) {
                $child->removeAttribute($child->attributes->item(0)->name);
            }

            if ($tag === 'img') {
                if (! preg_match('#^/uploads/products/[A-Za-z0-9\-]+\.(jpg|jpeg|png|webp|gif)$#', $src)) {
                    $child->parentNode?->removeChild($child);
                    continue;
                }
                $child->setAttribute('src', $src);
                $child->setAttribute('alt', '');
                continue;
            }

            if ($style !== '') {
                $child->setAttribute('style', $style);
            }
        }
    }

    private static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function style(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode(':', $rule, 2));
            $name = strtolower($name);

            if ($name === 'color' && ($hex = self::hexColor($value))) {
                $kept[] = 'color: '.$hex;
            }

            if ($name === 'font-size' && preg_match('/^(?:[8-9]|[1-8][0-9]|9[0-6])px$/', $value)) {
                $kept[] = 'font-size: '.$value;
            }

            if ($name === 'line-height' && preg_match('/^(?:1(?:\.\d{1,2})?|2(?:\.0{1,2})?)$/', $value)) {
                $kept[] = 'line-height: '.$value;
            }

            if ($name === 'text-align' && in_array($value, ['left', 'center', 'right'], true)) {
                $kept[] = 'text-align: '.$value;
            }

            if ($name === 'font-weight' && in_array($value, ['bold', '700'], true)) {
                $kept[] = 'font-weight: 700';
            }

            if ($name === 'font-style' && $value === 'italic') {
                $kept[] = 'font-style: italic';
            }

            if ($name === 'text-decoration') {
                $tokens = array_values(array_intersect(
                    preg_split('/\s+/', strtolower($value)) ?: [],
                    ['underline', 'line-through'],
                ));
                if ($tokens !== []) {
                    $kept[] = 'text-decoration: '.implode(' ', $tokens);
                }
            }
        }

        return implode('; ', $kept);
    }

    private static function hexColor(string $value): ?string
    {
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $matches)) {
            $channels = array_map(fn ($channel) => min(255, (int) $channel), array_slice($matches, 1));

            return sprintf('#%02x%02x%02x', $channels[0], $channels[1], $channels[2]);
        }

        return null;
    }
}
