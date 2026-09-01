<?php

declare(strict_types=1);

namespace Ava\Tests\Http;

use Ava\Application;
use Ava\Http\Request;
use Ava\Http\Response;
use Ava\Http\WebpageCache;
use Ava\Testing\TestCase;

/**
 * Regression tests for public webpage-cache eligibility.
 */
final class WebpageCacheTest extends TestCase
{
    private string $storageRelative;
    private string $storageAbsolute;

    public function setUp(): void
    {
        $this->storageRelative = 'storage/test-webpage-cache-' . bin2hex(random_bytes(6));
        $this->storageAbsolute = AVA_ROOT . '/' . $this->storageRelative;
        mkdir($this->storageAbsolute . '/cache/pages', 0755, true);
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->storageAbsolute);
    }

    public function testPreviewRequestCannotWriteWhenContentEnablesCaching(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/draft', [
            'preview' => '1',
            'token' => 'secret',
        ]);

        $cache->put($request, Response::html('draft content'), true);

        $this->assertEquals(0, $cache->stats()['count']);
        $this->assertNull($cache->get(new Request('GET', '/draft')));
    }

    public function testPostRequestCannotWriteWhenContentEnablesCaching(): void
    {
        $cache = $this->createCache();

        $cache->put(
            new Request('POST', '/page'),
            Response::html('posted content'),
            true
        );

        $this->assertEquals(0, $cache->stats()['count']);
    }

    public function testExcludedPathCannotWriteWhenContentEnablesCaching(): void
    {
        $cache = $this->createCache();

        $cache->put(
            new Request('GET', '/private/page'),
            Response::html('private content'),
            true
        );

        $this->assertEquals(0, $cache->stats()['count']);
    }

    public function testWildcardExclusionOnlyMatchesIntendedPathPrefix(): void
    {
        $cache = $this->createCache();

        $this->assertFalse($cache->isCacheableForWrite(new Request('GET', '/private/page')));
        $this->assertTrue($cache->isCacheableForWrite(new Request('GET', '/private-page')));
    }

    public function testCleanGetCanStillBeCachedWhenContentEnablesCaching(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/public-page');

        $cache->put($request, Response::html('public content'), true);

        $cached = $cache->get($request);
        $this->assertNotNull($cached);
        $this->assertEquals('public content', $cached->content());
    }

    public function testPreviewResponseGetsPrivateNoStoreHeaders(): void
    {
        $app = $this->createApplication();
        $method = new \ReflectionMethod($app, 'applyPublicSecurityHeaders');
        $response = $method->invoke(
            $app,
            Response::html('preview'),
            new Request('GET', '/draft', ['preview' => '1', 'token' => 'secret'])
        );

        $this->assertEquals('private, no-store, max-age=0', $response->header('Cache-Control'));
        $this->assertEquals('no-cache', $response->header('Pragma'));
        $this->assertEquals('no-referrer', $response->header('Referrer-Policy'));
    }

    private function createCache(): WebpageCache
    {
        return new WebpageCache($this->createApplication());
    }

    private function createApplication(): Application
    {
        return new Application([
            'paths' => [
                'storage' => $this->storageRelative,
            ],
            'webpage_cache' => [
                'enabled' => true,
                'ttl' => null,
                'exclude' => ['/private/*'],
            ],
            'generator_comment' => false,
        ]);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
