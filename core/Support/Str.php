<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * String helper utilities.
 */
final class Str
{
    /**
     * Convert a string to a URL-safe slug.
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9\s-]/u', '', $value);
        $value = preg_replace('/[\s-]+/', $separator, $value);
        return trim($value, $separator);
    }

    /**
     * Get the portion of a string before a given value.
     */
    public static function before(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = strpos($subject, $search);
        return $pos === false ? $subject : substr($subject, 0, $pos);
    }

    /**
     * Get the portion of a string after a given value.
     */
    public static function after(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = strpos($subject, $search);
        return $pos === false ? $subject : substr($subject, $pos + strlen($search));
    }

    /**
     * Limit a string to a given number of characters.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit, 'UTF-8') . $end;
    }

    /**
     * Limit a string to a given number of words.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (!isset($matches[0]) || mb_strlen($value, 'UTF-8') === mb_strlen($matches[0], 'UTF-8')) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }
}
