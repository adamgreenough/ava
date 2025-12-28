<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * ULID Generator
 *
 * Universally Unique Lexicographically Sortable Identifier.
 * 26 characters, Crockford Base32 encoded.
 */
final class Ulid
{
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generate a new ULID.
     */
    public static function generate(): string
    {
        $time = (int) (microtime(true) * 1000);

        // Encode timestamp (first 10 chars)
        $timestamp = '';
        for ($i = 0; $i < 10; $i++) {
            $mod = $time % 32;
            $timestamp = self::ENCODING[$mod] . $timestamp;
            $time = (int) ($time / 32);
        }

        // Generate random part (last 16 chars)
        $random = '';
        for ($i = 0; $i < 16; $i++) {
            $random .= self::ENCODING[random_int(0, 31)];
        }

        return $timestamp . $random;
    }

    /**
     * Validate a ULID string.
     */
    public static function isValid(string $ulid): bool
    {
        if (strlen($ulid) !== 26) {
            return false;
        }

        // Check all characters are valid Crockford Base32
        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $ulid) === 1;
    }

    /**
     * Extract timestamp from ULID (milliseconds since Unix epoch).
     */
    public static function timestamp(string $ulid): int
    {
        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID');
        }

        $ulid = strtoupper($ulid);
        $timestamp = 0;

        for ($i = 0; $i < 10; $i++) {
            $char = $ulid[$i];
            $value = strpos(self::ENCODING, $char);
            $timestamp = $timestamp * 32 + $value;
        }

        return $timestamp;
    }

    /**
     * Get the timestamp as a DateTime.
     */
    public static function toDateTime(string $ulid): \DateTimeImmutable
    {
        $ms = self::timestamp($ulid);
        $seconds = (int) ($ms / 1000);
        $microseconds = ($ms % 1000) * 1000;

        return \DateTimeImmutable::createFromFormat(
            'U u',
            sprintf('%d %06d', $seconds, $microseconds)
        );
    }
}
