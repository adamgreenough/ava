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
}
