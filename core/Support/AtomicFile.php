<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * Write complete files without exposing partially written contents to readers.
 */
final class AtomicFile
{
    public static function write(string $path, string $contents, int $permissions = 0644): bool
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            return false;
        }

        $temporary = $directory . '/.' . basename($path) . '.'
            . bin2hex(random_bytes(8)) . '.tmp';

        try {
            $written = @file_put_contents($temporary, $contents, LOCK_EX);
            if ($written === false || $written !== strlen($contents)) {
                return false;
            }

            @chmod($temporary, $permissions);

            return @rename($temporary, $path);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
