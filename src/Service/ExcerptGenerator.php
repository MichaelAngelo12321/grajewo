<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Builds a short, plain-text excerpt from HTML content.
 *
 * Tags are stripped and HTML entities (e.g. "&oacute;" produced by TinyMCE)
 * are decoded, so the stored excerpt contains real UTF-8 characters and can be
 * safely printed with Twig auto-escaping.
 */
final class ExcerptGenerator
{
    public const MAX_LENGTH = 300;
    private const MAX_SENTENCES = 3;

    public function fromHtml(string $html): string
    {
        // Tags become spaces so that "</p><p>" does not glue two sentences together
        $text = strip_tags(preg_replace('/<[^>]+>/', ' ', $html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse whitespace (including non-breaking spaces from &nbsp;) into single spaces
        $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text));

        $excerpt = implode('. ', array_slice(explode('. ', $text), 0, self::MAX_SENTENCES));

        if (mb_strlen($excerpt) > self::MAX_LENGTH) {
            $excerpt = mb_substr($excerpt, 0, self::MAX_LENGTH - 3) . '...';
        }

        return $excerpt;
    }
}
