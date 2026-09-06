<?php

declare(strict_types=1);

namespace Ava\Content\Backends;

use Ava\Content\QueryProcessor;
use Ava\Support\SignedCache;

/**
 * Array Backend
 *
 * The original binary-serialized array backend for content indexes.
 * Uses .bin files with igbinary or PHP serialize.
 *
 * Implements a tiered caching strategy:
 * - recent_cache.bin: Fast path for archive pages 1-20
 * - slug_lookup.bin: Fast single-item lookups
 * - content_index.bin: Full index for complex queries
 *
 * Best for: Small to medium sites (<10,000 posts)
 * Memory: Loads entire index into memory
 */
final class ArrayBackend implements BackendInterface
{
    // In-memory cache
    private ?array $contentIndex = null;
    private ?array $taxIndex = null;
    private ?array $routes = null;
    private ?array $recentCache = null;
    private ?array $slugLookup = null;

    public function __construct(
        private string $storagePath,
        private string $contentPath
    ) {}

    public function name(): string
    {
        return 'array';
    }

    public function isAvailable(): bool
    {
        // Array backend is always available if cache files exist
        $indexPath = $this->getCachePath('content_index.bin');
        return file_exists($indexPath);
    }

    // -------------------------------------------------------------------------
    // Single Item Retrieval
    // -------------------------------------------------------------------------

    public function getBySlug(string $type, string $slug): ?array
    {
        // Try fast path using slug lookup first
        $lookup = $this->loadSlugLookup();
        $entry = $lookup[$type][$slug] ?? null;

        if ($entry === null) {
            return null;
        }

        // Return the lookup entry (minimal data)
        // The caller can parse the file if needed
        return [
            'type' => $type,
            'slug' => $slug,
            'file_path' => $this->contentPath . '/' . $entry['file'],
            'relative_path' => $entry['file'],
            'id' => $entry['id'] ?? null,
            'status' => $entry['status'] ?? 'published',
        ];
    }

    public function getById(string $id): ?array
    {
        $index = $this->loadContentIndex();
        return $index['by_id'][$id] ?? null;
    }

    public function getByPath(string $relativePath): ?array
    {
        $index = $this->loadContentIndex();
        return $index['by_path'][$relativePath] ?? null;
    }

    // -------------------------------------------------------------------------
    // Bulk Retrieval
    // -------------------------------------------------------------------------

    public function allRaw(string $type): array
    {
        $index = $this->loadContentIndex();
        return $index['by_type'][$type] ?? [];
    }

    public function types(): array
    {
        $index = $this->loadContentIndex();
        return array_keys($index['by_type'] ?? []);
    }

    public function count(string $type, ?string $status = null): int
    {
        $index = $this->loadContentIndex();
        $items = $index['by_type'][$type] ?? [];

        if ($status === null) {
            return count($items);
        }

        return count(array_filter(
            $items,
            fn(array $data) => ($data['status'] ?? 'published') === $status
        ));
    }

    public function exists(string $type, string $slug): bool
    {
        $index = $this->loadContentIndex();
        return isset($index['by_type'][$type][$slug]);
    }

    // -------------------------------------------------------------------------
    // Query Operations
    // -------------------------------------------------------------------------

    public function query(array $params): array
    {
        return QueryProcessor::query($this, $params);
    }

    // -------------------------------------------------------------------------
    // Recent Cache Operations
    // -------------------------------------------------------------------------

    public function canUseFastCache(string $type, int $page, int $perPage): bool
    {
        $cache = $this->loadRecentCache();
        $typeCache = $cache[$type] ?? null;

        if ($typeCache === null) {
            return false;
        }

        $offset = ($page - 1) * $perPage;
        $maxOffset = count($typeCache['items']);

        return $offset + $perPage <= $maxOffset;
    }

    public function getRecentItems(string $type, int $page, int $perPage): array
    {
        $cache = $this->loadRecentCache();
        $typeCache = $cache[$type] ?? null;

        if ($typeCache === null) {
            return ['items' => [], 'total' => 0];
        }

        $offset = ($page - 1) * $perPage;
        $items = array_slice($typeCache['items'], $offset, $perPage);

        return [
            'items' => $items,
            'total' => $typeCache['total'],
        ];
    }

    // -------------------------------------------------------------------------
    // Taxonomy Operations
    // -------------------------------------------------------------------------

    public function terms(string $taxonomy): array
    {
        $index = $this->loadTaxIndex();
        return $index[$taxonomy]['terms'] ?? [];
    }

    public function term(string $taxonomy, string $slug): ?array
    {
        $terms = $this->terms($taxonomy);
        return $terms[$slug] ?? null;
    }

    public function taxonomies(): array
    {
        $index = $this->loadTaxIndex();
        return array_keys($index);
    }

    // -------------------------------------------------------------------------
    // Route Operations
    // -------------------------------------------------------------------------

    public function routes(): array
    {
        return $this->loadRoutes();
    }

    // -------------------------------------------------------------------------
    // Cache Management
    // -------------------------------------------------------------------------

    public function clearMemoryCache(): void
    {
        $this->contentIndex = null;
        $this->taxIndex = null;
        $this->routes = null;
        $this->recentCache = null;
        $this->slugLookup = null;
    }

    // -------------------------------------------------------------------------
    // Cache Loading (Private)
    // -------------------------------------------------------------------------

    private function loadContentIndex(): array
    {
        if ($this->contentIndex === null) {
            $this->contentIndex = $this->loadCacheFile('content_index');
        }
        return $this->contentIndex;
    }

    private function loadTaxIndex(): array
    {
        if ($this->taxIndex === null) {
            $this->taxIndex = $this->loadCacheFile('tax_index');
        }
        return $this->taxIndex;
    }

    private function loadRoutes(): array
    {
        if ($this->routes === null) {
            $this->routes = $this->loadCacheFile('routes');
        }
        return $this->routes;
    }

    private function loadRecentCache(): array
    {
        if ($this->recentCache === null) {
            $this->recentCache = $this->loadCacheFile('recent_cache');
        }
        return $this->recentCache;
    }

    private function loadSlugLookup(): array
    {
        if ($this->slugLookup === null) {
            $this->slugLookup = $this->loadCacheFile('slug_lookup');
        }
        return $this->slugLookup;
    }

    /**
     * Load a binary cache file.
     */
    private function loadCacheFile(string $name): array
    {
        return SignedCache::read($this->getCachePath($name . '.bin'));
    }

    private function getCachePath(string $filename): string
    {
        return $this->storagePath . '/cache/' . $filename;
    }
}
