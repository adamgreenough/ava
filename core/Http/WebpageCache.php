<?php

declare(strict_types=1);

namespace Ava\Http;

use Ava\Application;
use Ava\Support\AtomicFile;

/**
 * Shared cache for anonymous HTML responses.
 * Each JSON entry contains its request identity, response headers, and body.
 */
final class WebpageCache
{
    private string $cachePath;

    public function __construct(private Application $app)
    {
        $this->cachePath = $app->configPath('storage') . '/cache/pages';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->app->config('webpage_cache.enabled', false);
    }

    public function isCacheable(Request $request): bool
    {
        if (!$this->isEnabled() || $request->method() !== 'GET') {
            return false;
        }

        // Never share session-dependent or authenticated output. Also respect
        // client cache directives; this cache does not implement revalidation.
        foreach (['Cookie', 'Authorization', 'Cache-Control', 'Pragma'] as $header) {
            if ($request->header($header) !== null) {
                return false;
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return false;
        }

        $query = $request->query();
        unset($query['utm_source'], $query['utm_medium'], $query['utm_campaign'], $query['utm_term'], $query['utm_content']);
        if ($query !== []) {
            return false;
        }

        foreach ($this->app->config('webpage_cache.exclude', []) as $pattern) {
            if ($this->matchesPattern($request->path(), $pattern)) {
                return false;
            }
        }

        return true;
    }

    public function get(Request $request): ?Response
    {
        if (!$this->isCacheable($request)) {
            return null;
        }

        $file = $this->getCacheFilePath($request);
        $mtime = @filemtime($file);
        if ($mtime === false) {
            return null;
        }

        $age = max(0, time() - $mtime);
        $ttl = $this->app->config('webpage_cache.ttl');
        if ($ttl !== null && $age >= $ttl) {
            @unlink($file);
            return null;
        }

        $entry = $this->readEntry($file);
        if ($entry === null || $entry['identity'] !== $this->identity($request)) {
            return null;
        }

        try {
            $response = new Response($entry['body'], 200, $entry['headers']);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (!$this->isPublicResponse($response)) {
            return null;
        }

        return $response
            ->withHeader('X-Page-Cache', 'HIT')
            ->withHeader('X-Cache-Age', (string) $age);
    }

    public function put(Request $request, Response $response, ?bool $contentCacheOverride = null): bool
    {
        if ($contentCacheOverride === false || !$this->isCacheable($request) || !$this->isPublicResponse($response)) {
            return false;
        }

        // PHP themes may use header() or setcookie() instead of Response.
        // Inspect these too, so native session/cookie headers cannot bypass policy.
        $nativeHeaders = [];
        foreach (headers_list() as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $nativeHeaders[trim($parts[0])] = trim($parts[1]);
            }
        }
        $nativeResponse = new Response($response->content(), 200, $nativeHeaders);
        if (!$this->isPublicResponse($nativeResponse)) {
            return false;
        }
        $response = $nativeResponse->withHeaders($response->headers());

        if (!is_dir($this->cachePath) && !@mkdir($this->cachePath, 0755, true) && !is_dir($this->cachePath)) {
            return false;
        }

        $content = $response->content();
        if ($this->app->config('generator_comment', true)) {
            $content = $this->addCacheComment($content, date('Y-m-d H:i:s'));
        }

        try {
            $entry = json_encode([
                'identity' => $this->identity($request),
                'path' => $request->path(),
                'headers' => $response->headers(),
                'body' => $content,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            return false;
        }

        return AtomicFile::write($this->getCacheFilePath($request), $entry);
    }

    private function isPublicResponse(Response $response): bool
    {
        if ($response->status() !== 200) {
            return false;
        }

        // Explicit HTTP cache policy is left to the browser/proxy, rather than
        // silently replacing its freshness/revalidation rules with our own TTL.
        foreach (['Set-Cookie', 'Vary', 'Cache-Control', 'Pragma', 'Expires', 'Content-Length', 'Content-Encoding', 'Content-Range', 'Transfer-Encoding'] as $header) {
            if ($response->header($header) !== null) {
                return false;
            }
        }

        $contentType = $response->header('Content-Type');
        return $contentType === null
            || strtolower(trim(explode(';', $contentType, 2)[0])) === 'text/html';
    }

    public function clear(): int
    {
        $count = 0;
        foreach (glob($this->cachePath . '/*.json') ?: [] as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    public function clearPattern(string $pattern): int
    {
        $count = 0;
        foreach (glob($this->cachePath . '/*.json') ?: [] as $file) {
            $entry = $this->readEntry($file);
            if ($entry !== null && $this->matchesPattern($entry['path'], $pattern) && unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    public function stats(): array
    {
        $files = glob($this->cachePath . '/*.json') ?: [];
        $size = 0;
        $oldest = null;
        $newest = null;
        foreach ($files as $file) {
            $size += filesize($file);
            $mtime = filemtime($file);
            $oldest = $oldest === null ? $mtime : min($oldest, $mtime);
            $newest = $newest === null ? $mtime : max($newest, $mtime);
        }

        return [
            'enabled' => $this->isEnabled(),
            'ttl' => $this->app->config('webpage_cache.ttl'),
            'count' => count($files),
            'size' => $size,
            'oldest' => $oldest === null ? null : date('Y-m-d H:i:s', $oldest),
            'newest' => $newest === null ? null : date('Y-m-d H:i:s', $newest),
        ];
    }

    private function readEntry(string $file): ?array
    {
        $contents = @file_get_contents($file);
        $entry = $contents === false ? null : json_decode($contents, true);
        if (!is_array($entry)
            || !is_string($entry['identity'] ?? null)
            || !is_string($entry['path'] ?? null)
            || !is_array($entry['headers'] ?? null)
            || !is_string($entry['body'] ?? null)
        ) {
            return null;
        }
        return $entry;
    }

    private function identity(Request $request): string
    {
        // Length-delimited serialization prevents ambiguous host/path boundaries.
        return hash('sha256', serialize([$request->isSecure(), strtolower($request->host()), $request->path()]));
    }

    private function getCacheFilePath(Request $request): string
    {
        return $this->cachePath . '/' . $this->identity($request) . '.json';
    }

    private function matchesPattern(string $path, string $pattern): bool
    {
        $regex = str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote($pattern, '/'));
        return preg_match('/^' . $regex . '$/D', $path) === 1;
    }

    private function addCacheComment(string $content, string $timestamp): string
    {
        $comment = "<!-- Page cached: {$timestamp} -->\n";
        if (preg_match('/^<!DOCTYPE[^>]*>/i', $content, $matches)) {
            return $matches[0] . "\n" . $comment . substr($content, strlen($matches[0]));
        }
        return $comment . $content;
    }
}
