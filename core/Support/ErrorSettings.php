<?php

declare(strict_types=1);

namespace Ava\Support;

/**
 * Resolve error-handling settings independently from debug output.
 */
final class ErrorSettings
{
    /**
     * @param array<string, mixed> $debug
     * @return array{
     *     debug_enabled: bool,
     *     display_errors: bool,
     *     log_errors: bool,
     *     level: string
     * }
     */
    public static function resolve(array $debug): array
    {
        $debugEnabled = ($debug['enabled'] ?? false) === true;
        $displayErrors = ($debug['display_errors'] ?? false) === true;
        $logErrors = ($debug['log_errors'] ?? true) === true;
        $level = $debug['level'] ?? 'errors';

        return [
            'debug_enabled' => $debugEnabled,
            'display_errors' => $debugEnabled && $displayErrors,
            'log_errors' => $logErrors,
            'level' => is_string($level) ? $level : 'errors',
        ];
    }
}
