<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Indexer;
use Ava\Testing\TestCase;

final class IndexerFingerprintTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = AVA_ROOT . '/storage/test-fingerprint-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
    }

    public function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->directory);
    }

    public function testSourceEditIsDetectedWithUnchangedSizeAndTimestamp(): void
    {
        $path = $this->directory . '/page.md';
        file_put_contents($path, 'draft');
        touch($path, 1_700_000_000);
        clearstatcache(true, $path);
        $before = $this->fingerprintDirectory();

        file_put_contents($path, 'final');
        touch($path, 1_700_000_000);
        clearstatcache(true, $path);
        $after = $this->fingerprintDirectory();

        $this->assertNotEquals($before['digest'], $after['digest']);
    }

    public function testFingerprintIsIndependentOfDirectoryIterationOrder(): void
    {
        file_put_contents($this->directory . '/b.md', 'B');
        file_put_contents($this->directory . '/a.md', 'A');

        $first = $this->fingerprintDirectory();
        $second = $this->fingerprintDirectory();

        $this->assertEquals($first, $second);
        $this->assertEquals(2, $first['count']);
    }

    public function testApplicationFingerprintTracksRenderedSourceLocations(): void
    {
        $indexer = new Indexer($this->app);
        $method = new \ReflectionMethod($indexer, 'computeFingerprint');
        $method->setAccessible(true);
        $fingerprint = $method->invoke($indexer);

        $this->assertArrayHasKey('content', $fingerprint['directories']);
        $this->assertArrayHasKey('config', $fingerprint['directories']);
        $this->assertArrayHasKey('theme', $fingerprint['directories']);
        $this->assertArrayHasKey('snippets', $fingerprint['directories']);
        $this->assertArrayHasKey('redirects', $fingerprint['files']);

        $plugins = $this->app->config('plugins', []);
        foreach (is_array($plugins) ? $plugins : [] as $plugin) {
            if (is_string($plugin) && preg_match('/^[a-z0-9_-]+$/i', $plugin)) {
                $this->assertArrayHasKey('plugin:' . $plugin, $fingerprint['directories']);
            }
        }
    }

    private function fingerprintDirectory(): array
    {
        $indexer = new Indexer($this->app);
        $method = new \ReflectionMethod($indexer, 'fingerprintDirectory');
        $method->setAccessible(true);

        return $method->invoke($indexer, $this->directory);
    }
}
