<?php

declare(strict_types=1);

namespace Ava\Content\Backends;

/**
 * Contract for content index backends.
 *
 * Query talks only to this interface, so array and SQLite storage are
 * interchangeable.
 */
interface BackendInterface
{
    public function name(): string;

    public function isAvailable(): bool;

    // -------------------------------------------------------------------------
    // Single Item Retrieval
    // -------------------------------------------------------------------------

    /**
     * @param string $slug Slug for pattern types, path for hierarchical ones
     */
    public function getBySlug(string $type, string $slug): ?array;

    public function getById(string $id): ?array;

    /** @param string $relativePath Path relative to the content directory */
    public function getByPath(string $relativePath): ?array;

    // -------------------------------------------------------------------------
    // Bulk Retrieval
    // -------------------------------------------------------------------------

    /** @return array<array> */
    public function allRaw(string $type): array;

    /** @return array<string> Types that have at least one item */
    public function types(): array;

    public function count(string $type, ?string $status = null): int;

    public function exists(string $type, string $slug): bool;

    // -------------------------------------------------------------------------
    // Query Operations
    // -------------------------------------------------------------------------

    /**
     * Filter, sort and paginate in whatever way the backend does best.
     *
     * @param array $params Query parameters:
     *   - type: string|null - Content type filter
     *   - types: array|null - Restrict eligible content types (takes precedence over type)
     *   - status: string|null - Status filter
     *   - taxonomies: array - Taxonomy filters [taxonomy => term]
     *   - fields: array - Field filters [{field, value, operator}]
     *   - search: string|null - Relevance search query (shared scorer)
     *   - stopWords: array - Search stop words
     *   - synonyms: array - Search synonym groups
     *   - searchWeights: array|null - Relevance weights and custom search fields
     *   - orderBy: string - Field to sort by
     *   - order: string - Sort direction (asc/desc)
     *   - page: int - Page number (1-based)
     *   - perPage: int - Items per page
     * @return array{items: array, total: int}
     */
    public function query(array $params): array;

    // -------------------------------------------------------------------------
    // Recent Cache Operations
    // -------------------------------------------------------------------------

    /**
     * Can this listing take the backend's optimised path?
     *
     * True only for the simple case: published, date descending, no filters.
     */
    public function canUseFastCache(string $type, int $page, int $perPage): bool;

    /** @return array{items: array, total: int} */
    public function getRecentItems(string $type, int $page, int $perPage): array;

    // -------------------------------------------------------------------------
    // Taxonomy Operations
    // -------------------------------------------------------------------------

    /** @return array<string, array> Terms indexed by slug */
    public function terms(string $taxonomy): array;

    public function term(string $taxonomy, string $slug): ?array;

    /** @return array<string> */
    public function taxonomies(): array;

    // -------------------------------------------------------------------------
    // Route Operations
    // -------------------------------------------------------------------------

    public function routes(): array;

    // -------------------------------------------------------------------------
    // Cache Management
    // -------------------------------------------------------------------------

    public function clearMemoryCache(): void;
}
