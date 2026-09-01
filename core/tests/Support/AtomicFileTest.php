<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\AtomicFile;
use Ava\Testing\TestCase;

final class AtomicFileTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = AVA_ROOT . '/storage/test-atomic-file-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
    }

    public function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($this->directory . '/.*') ?: [] as $file) {
            if (basename($file) !== '.' && basename($file) !== '..') {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testWriteCreatesCompleteFile(): void
    {
        $path = $this->directory . '/cache.html';

        $this->assertTrue(AtomicFile::write($path, 'complete response'));
        $this->assertEquals('complete response', file_get_contents($path));
    }

    public function testWriteAtomicallyReplacesExistingFile(): void
    {
        $path = $this->directory . '/cache.html';
        file_put_contents($path, 'old response');

        $this->assertTrue(AtomicFile::write($path, 'new response'));
        $this->assertEquals('new response', file_get_contents($path));
        $this->assertCount(0, glob($this->directory . '/.*.tmp') ?: []);
    }

    public function testWriteFailsWhenDirectoryDoesNotExist(): void
    {
        $path = $this->directory . '/missing/cache.html';

        $this->assertFalse(AtomicFile::write($path, 'response'));
    }
}
