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
        // Release temporary Application/Indexer cycles and their file locks.
        gc_collect_cycles();
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

        $this->assertFalse($cache->isCacheable(new Request('GET', '/private/page')));
        $this->assertTrue($cache->isCacheable(new Request('GET', '/private-page')));
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

    public function testNonHtmlResponseIsNotCached(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/feed.xml');

        $cache->put(
            $request,
            new Response('<rss></rss>', 200, ['Content-Type' => 'application/rss+xml; charset=utf-8'])
        );

        $this->assertEquals(0, $cache->stats()['count']);
        $this->assertNull($cache->get($request));
    }

    public function testExplicitHtmlResponseCanBeCached(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/html-page');

        $cache->put($request, Response::html('html content'));

        $this->assertNotNull($cache->get($request));
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
        $this->assertEquals('noindex, nofollow', $response->header('X-Robots-Tag'));
    }

    public function testPreBootCacheIsBypassedInAutomaticIndexMode(): void
    {
        $app = $this->createApplication('auto');
        $request = new Request('GET', '/public-page');
        $app->webpageCache()->put($request, Response::html('cached'));

        $response = $app->handle($request);
        $this->assertNotEquals('cached', $response->content());
        $this->assertNull($response->header('X-Page-Cache'));
        $this->assertEquals(0, $app->webpageCache()->stats()['count']);
    }

    public function testPreBootCacheIsAllowedInManualIndexMode(): void
    {
        $app = $this->createApplication('never');
        $request = new Request('GET', '/public-page');
        $app->webpageCache()->put($request, Response::html('cached'));

        $this->assertEquals('cached', $app->handle($request)->content());
        $this->assertFalse(is_file($this->storageAbsolute . '/cache/.rebuild.lock'));
    }

    public function testAuthenticatedRequestsCannotReadOrPopulateSharedCache(): void
    {
        $cache = $this->createCache();
        $clean = new Request('GET', '/public-page');
        foreach (['Cookie' => 'session=secret', 'Authorization' => 'Bearer secret'] as $header => $value) {
            $request = new Request('GET', '/public-page', [], [$header => $value]);
            $this->assertFalse($cache->put($request, Response::html('private'), true));
            $this->assertNull($cache->get($clean));
            $this->assertTrue($cache->put($clean, Response::html('public')));
            $this->assertNull($cache->get($request));
            $cache->clear();
        }
    }

    public function testManualModeUsesTheSameAuthenticationPolicy(): void
    {
        $app = $this->createApplication('never');
        $app->webpageCache()->put(new Request('GET', '/missing'), Response::html('cached'));
        $response = $app->handle(new Request('GET', '/missing', [], ['Cookie' => 'session=secret']));
        $this->assertEquals(404, $response->status());
        $this->assertNotEquals('cached', $response->content());
    }

    public function testResponsesWithPrivateOrVaryingPolicyAreNotStored(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/page');
        foreach ([
            ['cache-control' => 'private, no-store'],
            ['Cache-Control' => 'no-cache'],
            ['Cache-Control' => 'public, max-age=0'],
            ['Set-Cookie' => 'session=secret'],
            ['Vary' => 'Accept-Language'],
            ['Vary' => '*'],
            ['Pragma' => 'no-cache'],
            ['Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT'],
        ] as $headers) {
            $this->assertFalse($cache->put($request, Response::html('private')->withHeaders($headers)));
        }
        $this->assertNull($cache->get($request));
    }

    public function testClientCacheDirectivesBypassExistingEntries(): void
    {
        $cache = $this->createCache();
        $cache->put(new Request('GET', '/page'), Response::html('cached'));
        foreach (['Cache-Control' => 'no-cache', 'Pragma' => 'no-cache'] as $header => $value) {
            $request = new Request('GET', '/page', [], [$header => $value]);
            $this->assertNull($cache->get($request));
            $this->assertFalse($cache->put($request, Response::html('new')));
        }
    }

    public function testUtmQueriesStillShareTheAnonymousPageCache(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/page?utm_source=newsletter', ['utm_source' => 'newsletter']);
        $this->assertTrue($cache->put($request, Response::html('public')));
        $this->assertEquals('public', $cache->get(new Request('GET', '/page'))?->content());
    }

    public function testCacheHitRetainsResponseHeaders(): void
    {
        $cache = $this->createCache();
        $request = new Request('GET', '/page');
        $cache->put($request, Response::html('public')->withHeaders([
            'Content-Security-Policy' => "default-src 'none'",
            'X-Robots-Tag' => 'noindex',
            'Content-Language' => 'fr',
        ]));
        $cached = $cache->get($request);
        $this->assertNotNull($cached);
        $this->assertEquals("default-src 'none'", $cached->header('Content-Security-Policy'));
        $this->assertEquals('noindex', $cached->header('X-Robots-Tag'));
        $this->assertEquals('fr', $cached->header('Content-Language'));
    }

    public function testCacheDoesNotShareAcrossHostsOrSchemes(): void
    {
        $cache = $this->createCache();
        $originalServer = $_SERVER;
        try {
            $_SERVER['HTTPS'] = 'off';
            $_SERVER['SERVER_PORT'] = '80';
            $request = new Request('GET', '/page', [], ['Host' => 'one.example']);
            $cache->put($request, Response::html('one'));
            $this->assertNull($cache->get(new Request('GET', '/page', [], ['Host' => 'two.example'])));
            $_SERVER['HTTPS'] = 'on';
            $this->assertNull($cache->get($request));
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testArbitraryHostHeadersCannotPopulateOrReadTheCache(): void
    {
        $config = $this->createApplication()->allConfig();
        $config['site'] = ['base_url' => 'https://ava.test'];
        $cache = new WebpageCache(new Application($config));

        $canonical = new Request('GET', '/page', [], ['Host' => 'ava.test']);
        $spoofed = new Request('GET', '/page', [], ['Host' => 'attacker.example']);

        $this->assertTrue($cache->put($canonical, Response::html('public')));
        $this->assertEquals('public', $cache->get($canonical)?->content());

        $this->assertFalse($cache->isCacheable($spoofed));
        $this->assertNull($cache->get($spoofed));
        $this->assertFalse($cache->put($spoofed, Response::html('spoofed'), true));
        $this->assertEquals(1, $cache->stats()['count']);
    }

    public function testConfiguredPortAndExplicitAliasHostsMayCache(): void
    {
        $config = $this->createApplication()->allConfig();
        $config['site'] = ['base_url' => 'http://ava.test:8080'];
        $config['webpage_cache']['hosts'] = ['www.ava.test'];
        $cache = new WebpageCache(new Application($config));

        foreach (['ava.test:8080', 'WWW.AVA.TEST'] as $host) {
            $request = new Request('GET', '/page', [], ['Host' => $host]);
            $this->assertTrue($cache->put($request, Response::html('public')));
            $this->assertNotNull($cache->get($request));
        }

        $this->assertFalse($cache->isCacheable(
            new Request('GET', '/page', [], ['Host' => 'ava.test'])
        ));
    }

    public function testPatternClearingDoesNotDependOnHtmlComments(): void
    {
        foreach ([false, true] as $comments) {
            $config = $this->createApplication()->allConfig();
            $config['generator_comment'] = $comments;
            $cache = new WebpageCache(new Application($config));
            foreach (['/posts/first', '/posts/second', '/posts-other'] as $path) {
                $cache->put(new Request('GET', $path), Response::html('<!DOCTYPE html><p>Page</p>'));
            }
            $this->assertEquals(2, $cache->clearPattern('/posts/*'));
            $this->assertNull($cache->get(new Request('GET', '/posts/first')));
            $this->assertNotNull($cache->get(new Request('GET', '/posts-other')));
            $this->assertEquals(1, $cache->clear());
        }
    }

    public function testExpiredAndMalformedEntriesAreCacheMisses(): void
    {
        $config = $this->createApplication()->allConfig();
        $config['webpage_cache']['ttl'] = 1;
        $cache = new WebpageCache(new Application($config));
        $request = new Request('GET', '/page');
        $cache->put($request, Response::html('old'));
        $file = (glob($this->storageAbsolute . '/cache/pages/*.json') ?: [])[0];
        touch($file, time() - 5);
        clearstatcache(true, $file);
        $this->assertNull($cache->get($request));

        $cache->put($request, Response::html('new'));
        file_put_contents($file, '{"body": []}');
        clearstatcache(true, $file);
        $this->assertNull($cache->get($request));
    }

    public function testMissingPreviewAndOrdinary404ReceiveSecurityHeaders(): void
    {
        $app = $this->createApplication('never');
        foreach ([[], ['preview' => '1', 'token' => 'secret']] as $query) {
            $response = $app->handle(new Request('GET', '/missing', $query));
            $this->assertEquals(404, $response->status());
            $this->assertEquals("default-src 'self'", $response->header('Content-Security-Policy'));
            if ($query !== []) {
                $this->assertEquals('no-referrer', $response->header('Referrer-Policy'));
                $this->assertEquals('private, no-store, max-age=0', $response->header('Cache-Control'));
            }
        }
    }

    public function testPluginAndRedirectResponsesReceiveSecurityHeaders(): void
    {
        $app = $this->createApplication('never');
        $app->router()->addRoute('/plugin', fn() => Response::json(['ok' => true]));
        $app->router()->addRoute('/redirect', fn() => Response::redirect('/target'));
        foreach (['/plugin', '/redirect'] as $path) {
            $response = $app->handle(new Request('GET', $path));
            $this->assertEquals("default-src 'self'", $response->header('Content-Security-Policy'));
        }
    }

    private function createCache(): WebpageCache
    {
        return new WebpageCache($this->createApplication());
    }

    private function createApplication(string $indexMode = 'auto'): Application
    {
        return new Application([
            'paths' => [
                'storage' => $this->storageRelative,
                'content' => $this->storageRelative . '/content',
                'themes' => $this->storageRelative . '/themes',
                'plugins' => $this->storageRelative . '/plugins',
                'snippets' => $this->storageRelative . '/snippets',
            ],
            'webpage_cache' => [
                'enabled' => true,
                'ttl' => null,
                'exclude' => ['/private/*'],
            ],
            'content_index' => [
                'mode' => $indexMode,
            ],
            'generator_comment' => false,
            'security' => ['headers' => ['content_security_policy' => "default-src 'self'"]],
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
