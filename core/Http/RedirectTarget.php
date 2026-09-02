<?php

declare(strict_types=1);

namespace Ava\Http;

/**
 * Validate administrator-configured redirect destinations.
 */
final class RedirectTarget
{
    public static function sanitize(string $target): ?string
    {
        $target = trim($target);

        if ($target === '') {
            return '';
        }

        // Backslashes and control characters are parsed inconsistently by URL
        // consumers and can turn an apparently local path into another host.
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $target) === 1) {
            return null;
        }

        if (str_starts_with($target, '/')) {
            return str_starts_with($target, '//') ? null : $target;
        }

        $parts = parse_url($target);
        if (
            $parts === false
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || filter_var($target, FILTER_VALIDATE_URL) === false
        ) {
            return null;
        }

        return $target;
    }
}
