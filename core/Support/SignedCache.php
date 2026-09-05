<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * One format and validation boundary for binary array caches.
 * Readers never create or rotate signing keys.
 */
final class SignedCache
{
    public static function read(string $path): array
    {
        $content = @file_get_contents($path);
        $key = @file_get_contents(dirname($path) . '/.cache_key');
        if ($content === false || strlen($content) < 36 || $key === false || strlen($key) !== 32) {
            return [];
        }

        $payload = substr($content, 32);
        if (!hash_equals(substr($content, 0, 32), hash_hmac('sha256', $payload, $key, true))) {
            return [];
        }

        try {
            $data = match (substr($payload, 0, 3)) {
                'SZ:' => @unserialize(substr($payload, 3), ['allowed_classes' => false]),
                'IG:' => function_exists('igbinary_unserialize') ? @igbinary_unserialize(substr($payload, 3)) : null,
                default => null,
            };
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    public static function write(string $path, array $data, bool $useIgbinary = true): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create binary cache directory.');
        }

        $payload = $useIgbinary && function_exists('igbinary_serialize')
            ? 'IG:' . igbinary_serialize($data)
            : 'SZ:' . serialize($data);
        $key = self::signingKey($directory);
        if (!AtomicFile::write($path, hash_hmac('sha256', $payload, $key, true) . $payload)) {
            throw new \RuntimeException('Unable to publish binary cache: ' . basename($path));
        }
    }

    private static function signingKey(string $directory): string
    {
        $path = $directory . '/.cache_key';
        $file = @fopen($path, 'c+b');
        if ($file === false) {
            throw new \RuntimeException('Unable to open cache signing key.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock cache signing key.');
            }
            $key = stream_get_contents($file);
            if (is_string($key) && strlen($key) === 32) {
                return $key;
            }
            if ($key !== '') {
                throw new \RuntimeException('Invalid cache signing key; remove the cache and rebuild.');
            }

            $key = random_bytes(32);
            if (!chmod($path, 0600) || fwrite($file, $key) !== 32 || !fflush($file)) {
                throw new \RuntimeException('Unable to create cache signing key.');
            }
            return $key;
        } finally {
            fclose($file);
        }
    }
}
