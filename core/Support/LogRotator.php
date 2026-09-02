<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * Size-based log rotation shared by runtime and indexer logs.
 */
final class LogRotator
{
    public static function rotateIfNeeded(string $logFile, int $maxSize, int $maxFiles): bool
    {
        if ($maxSize < 1 || !is_file($logFile)) {
            return false;
        }

        $lockPath = dirname($logFile) . '/.' . basename($logFile) . '.rotation-lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            return false;
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                return false;
            }

            clearstatcache(true, $logFile);
            $size = @filesize($logFile);
            if ($size === false || $size < $maxSize) {
                return false;
            }

            $maxFiles = max(0, $maxFiles);
            if ($maxFiles === 0) {
                return @unlink($logFile);
            }

            @unlink($logFile . '.' . $maxFiles);

            for ($i = $maxFiles - 1; $i >= 1; $i--) {
                $old = $logFile . '.' . $i;
                if (is_file($old)) {
                    @rename($old, $logFile . '.' . ($i + 1));
                }
            }

            return @rename($logFile, $logFile . '.1');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
