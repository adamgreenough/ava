<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * Array helper utilities.
 */
final class Arr
{
    /**
     * Get a value from an array using dot notation.
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a value in an array using dot notation.
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[array_shift($keys)] = $value;
    }

    /**
     * Check if a key exists using dot notation.
     */
    public static function has(array $array, string $key): bool
    {
        if (empty($array) || $key === '') {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Flatten a multi-dimensional array with dot notation keys.
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get only the specified keys from an array.
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get all keys except the specified ones.
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Pluck values from a nested array.
     */
    public static function pluck(array $array, string $key, ?string $keyBy = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : null;

            if ($keyBy !== null) {
                $keyValue = is_array($item) ? ($item[$keyBy] ?? null) : null;
                $results[$keyValue] = $value;
            } else {
                $results[] = $value;
            }
        }

        return $results;
    }

    /**
     * Group array items by a key.
     */
    public static function groupBy(array $array, string $key): array
    {
        $results = [];

        foreach ($array as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? '') : '';
            $results[$groupKey][] = $item;
        }

        return $results;
    }

    /**
     * Sort an array by a key.
     */
    public static function sortBy(array $array, string $key, string $direction = 'asc'): array
    {
        usort($array, function ($a, $b) use ($key, $direction) {
            $aVal = is_array($a) ? ($a[$key] ?? null) : null;
            $bVal = is_array($b) ? ($b[$key] ?? null) : null;

            $result = $aVal <=> $bVal;
            return $direction === 'desc' ? -$result : $result;
        });

        return $array;
    }
}
