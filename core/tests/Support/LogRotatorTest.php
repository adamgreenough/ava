<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\LogRotator;
use Ava\Testing\TestCase;

final class LogRotatorTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = AVA_ROOT . '/storage/test-log-rotator-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
    }

    public function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($this->directory . '/.*') ?: [] as $file) {
            if (!in_array(basename($file), ['.', '..'], true)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testLogBelowLimitIsNotRotated(): void
    {
        $log = $this->directory . '/error.log';
        file_put_contents($log, 'short');

        $this->assertFalse(LogRotator::rotateIfNeeded($log, 10, 3));
        $this->assertEquals('short', file_get_contents($log));
    }

    public function testLogAtLimitIsRotated(): void
    {
        $log = $this->directory . '/error.log';
        file_put_contents($log, '1234567890');

        $this->assertTrue(LogRotator::rotateIfNeeded($log, 10, 3));
        $this->assertFalse(file_exists($log));
        $this->assertEquals('1234567890', file_get_contents($log . '.1'));
    }

    public function testRotationKeepsConfiguredNumberOfCopies(): void
    {
        $log = $this->directory . '/error.log';
        file_put_contents($log, 'current');
        file_put_contents($log . '.1', 'previous');
        file_put_contents($log . '.2', 'oldest');

        $this->assertTrue(LogRotator::rotateIfNeeded($log, 1, 2));
        $this->assertEquals('current', file_get_contents($log . '.1'));
        $this->assertEquals('previous', file_get_contents($log . '.2'));
        $this->assertFalse(file_exists($log . '.3'));
    }
}
