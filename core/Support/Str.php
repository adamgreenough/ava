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
        // Convert to lowercase
        $value = mb_strtolower($value, 'UTF-8');

        // Replace non-alphanumeric with separator
        $value = preg_replace('/[^a-z0-9\s-]/u', '', $value);

        // Replace whitespace and multiple separators with single separator
        $value = preg_replace('/[\s-]+/', $separator, $value);

        // Trim separators from ends
        return trim($value, $separator);
    }

    /**
     * Check if a string starts with a given substring.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Check if a string ends with a given substring.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Check if a string contains a given substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
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

    /**
     * Convert to title case.
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert to camelCase.
     */
    public static function camel(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return lcfirst(str_replace(' ', '', $value));
    }

    /**
     * Convert to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value);
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert to kebab-case.
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Strip HTML tags and decode entities.
     */
    public static function plain(string $value): string
    {
        return html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate a random string.
     */
    public static function random(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }

    /**
     * Ensure a string starts with a given prefix.
     */
    public static function ensureLeft(string $value, string $prefix): string
    {
        if (!str_starts_with($value, $prefix)) {
            return $prefix . $value;
        }
        return $value;
    }

    /**
     * Ensure a string ends with a given suffix.
     */
    public static function ensureRight(string $value, string $suffix): string
    {
        if (!str_ends_with($value, $suffix)) {
            return $value . $suffix;
        }
        return $value;
    }
}
