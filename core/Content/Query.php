<?php

declare(strict_types=1);

namespace Ava\Content;

use Ava\Application;
use Ava\Support\Arr;

/**
 * Content Query
 *
 * Fluent query builder for content, operating on cached indexes.
 * Supports WP-style parameters.
 */
final class Query
{
    private Application $app;
    private Repository $repository;

    // Query parameters
    private ?string $type = null;
    private ?string $status = null;
    private array $taxonomyFilters = [];
    private array $fieldFilters = [];
    private string $orderBy = 'date';
    private string $order = 'desc';
    private int $perPage = 10;
    private int $page = 1;
    private ?string $search = null;

    // Results cache
    private ?array $results = null;
    private ?int $totalCount = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->repository = $app->repository();
    }

    // -------------------------------------------------------------------------
    // Query building (fluent)
    // -------------------------------------------------------------------------

    /**
     * Filter by content type.
     */
    public function type(string $type): self
    {
        $clone = clone $this;
        $clone->type = $type;
        $clone->results = null;
        return $clone;
    }

    /**
     * Filter by status.
     */
    public function status(string $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        $clone->results = null;
        return $clone;
    }

    /**
     * Filter to published only.
     */
    public function published(): self
    {
        return $this->status('published');
    }

    /**
     * Filter by taxonomy term.
     */
    public function whereTax(string $taxonomy, string $term): self
    {
        $clone = clone $this;
        $clone->taxonomyFilters[$taxonomy] = $term;
        $clone->results = null;
        return $clone;
    }

    /**
     * Filter by a field value.
     */
    public function where(string $field, mixed $value, string $operator = '='): self
    {
        $clone = clone $this;
        $clone->fieldFilters[] = ['field' => $field, 'value' => $value, 'operator' => $operator];
        $clone->results = null;
        return $clone;
    }

    /**
     * Set ordering.
     */
    public function orderBy(string $field, string $direction = 'desc'): self
    {
        $clone = clone $this;
        $clone->orderBy = $field;
        $clone->order = strtolower($direction);
        $clone->results = null;
        return $clone;
    }

    /**
     * Set items per page.
     */
    public function perPage(int $count): self
    {
        $clone = clone $this;
        $clone->perPage = max(1, min(100, $count)); // Cap at 100
        $clone->results = null;
        return $clone;
    }

    /**
     * Set current page.
     */
    public function page(int $page): self
    {
        $clone = clone $this;
        $clone->page = max(1, $page);
        $clone->results = null;
        return $clone;
    }

    /**
     * Set search query.
     */
    public function search(string $query): self
    {
        $clone = clone $this;
        $clone->search = trim($query);
        $clone->results = null;
        return $clone;
    }

    /**
     * Apply WP-style query parameters.
     */
    public function fromParams(array $params): self
    {
        $clone = clone $this;

        if (isset($params['type'])) {
            $clone->type = $params['type'];
        }
        if (isset($params['status'])) {
            $clone->status = $params['status'];
        }
        if (isset($params['orderby'])) {
            $clone->orderBy = $params['orderby'];
        }
        if (isset($params['order'])) {
            $clone->order = strtolower($params['order']);
        }
        if (isset($params['per_page'])) {
            $clone->perPage = max(1, min(100, (int) $params['per_page']));
        }
        if (isset($params['paged'])) {
            $clone->page = max(1, (int) $params['paged']);
        }
        if (isset($params['q']) || isset($params['search'])) {
            $clone->search = trim($params['q'] ?? $params['search'] ?? '');
        }

        // Taxonomy filters (tax_<taxonomy>=term)
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'tax_')) {
                $taxonomy = substr($key, 4);
                $clone->taxonomyFilters[$taxonomy] = $value;
            }
        }

        $clone->results = null;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Execution
    // -------------------------------------------------------------------------

    /**
     * Get all matching items (with pagination).
     *
     * @return array<Item>
     */
    public function get(): array
    {
        $this->execute();
        return $this->results;
    }

    /**
     * Get first matching item.
     */
    public function first(): ?Item
    {
        $results = $this->perPage(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Get total count (before pagination).
     */
    public function count(): int
    {
        $this->execute();
        return $this->totalCount;
    }

    /**
     * Get total number of pages.
     */
    public function totalPages(): int
    {
        return (int) ceil($this->count() / $this->perPage);
    }

    /**
     * Get current page number.
     */
    public function currentPage(): int
    {
        return $this->page;
    }

    /**
     * Check if there are more pages.
     */
    public function hasMore(): bool
    {
        return $this->page < $this->totalPages();
    }

    /**
     * Check if there are previous pages.
     */
    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    /**
     * Get pagination info.
     */
    public function pagination(): array
    {
        return [
            'current_page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->count(),
            'total_pages' => $this->totalPages(),
            'has_more' => $this->hasMore(),
            'has_previous' => $this->hasPrevious(),
        ];
    }

    /**
     * Check if query has results.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Execute the query.
     */
    private function execute(): void
    {
        if ($this->results !== null) {
            return;
        }

        // Get all items of the type
        $items = [];
        if ($this->type !== null) {
            $items = $this->repository->all($this->type);
        } else {
            // Query across all types
            foreach ($this->repository->types() as $type) {
                $items = array_merge($items, $this->repository->all($type));
            }
        }

        // Apply filters
        $items = $this->applyFilters($items);

        // Apply search if present
        if ($this->search !== null && $this->search !== '') {
            $items = $this->applySearch($items);
        }

        // Store total count before pagination
        $this->totalCount = count($items);

        // Sort
        $items = $this->applySort($items);

        // Paginate
        $offset = ($this->page - 1) * $this->perPage;
        $this->results = array_slice($items, $offset, $this->perPage);
    }

    /**
     * Apply filters to items.
     */
    private function applyFilters(array $items): array
    {
        return array_filter($items, function (Item $item) {
            // Status filter
            if ($this->status !== null && $item->status() !== $this->status) {
                return false;
            }

            // Taxonomy filters
            foreach ($this->taxonomyFilters as $taxonomy => $term) {
                $terms = $item->terms($taxonomy);
                if (!in_array($term, $terms, true)) {
                    return false;
                }
            }

            // Field filters
            foreach ($this->fieldFilters as $filter) {
                if (!$this->matchesFieldFilter($item, $filter)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Check if item matches a field filter.
     */
    private function matchesFieldFilter(Item $item, array $filter): bool
    {
        $value = $item->get($filter['field']);
        $expected = $filter['value'];
        $operator = $filter['operator'];

        return match ($operator) {
            '=' => $value === $expected,
            '!=' => $value !== $expected,
            '>' => $value > $expected,
            '>=' => $value >= $expected,
            '<' => $value < $expected,
            '<=' => $value <= $expected,
            'in' => is_array($expected) && in_array($value, $expected, true),
            'not_in' => is_array($expected) && !in_array($value, $expected, true),
            'like' => is_string($value) && str_contains(strtolower($value), strtolower($expected)),
            default => false,
        };
    }

    /**
     * Apply search to items.
     */
    private function applySearch(array $items): array
    {
        $query = strtolower($this->search);
        $tokens = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        // Score each item
        $scored = [];
        foreach ($items as $item) {
            $score = $this->scoreItem($item, $query, $tokens);
            if ($score > 0) {
                $scored[] = ['item' => $item, 'score' => $score];
            }
        }

        // Sort by score descending if search is active
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn($s) => $s['item'], $scored);
    }

    /**
     * Score an item for search relevance.
     */
    private function scoreItem(Item $item, string $phrase, array $tokens): int
    {
        $score = 0;
        $title = strtolower($item->title());
        $excerpt = strtolower($item->excerpt() ?? '');

        // Title phrase match: +80
        if (str_contains($title, $phrase)) {
            $score += 80;
        }

        // Title contains all tokens: +40
        $allInTitle = true;
        $tokenHits = 0;
        foreach ($tokens as $token) {
            if (str_contains($title, $token)) {
                $tokenHits++;
            } else {
                $allInTitle = false;
            }
        }
        if ($allInTitle && count($tokens) > 1) {
            $score += 40;
        }

        // Title token hits: +10 each (cap +30)
        $score += min(30, $tokenHits * 10);

        // Excerpt phrase match: +30
        if (str_contains($excerpt, $phrase)) {
            $score += 30;
        }

        // Excerpt token hits: +3 each (cap +15)
        $excerptHits = 0;
        foreach ($tokens as $token) {
            if (str_contains($excerpt, $token)) {
                $excerptHits++;
            }
        }
        $score += min(15, $excerptHits * 3);

        // Featured boost: +15
        if ($item->get('featured')) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Apply sorting to items.
     */
    private function applySort(array $items): array
    {
        usort($items, function (Item $a, Item $b) {
            $aVal = $this->getSortValue($a);
            $bVal = $this->getSortValue($b);

            $result = $aVal <=> $bVal;

            // Descending order reverses the comparison
            if ($this->order === 'desc') {
                $result = -$result;
            }

            // Tie-breaker: title ascending
            if ($result === 0) {
                $result = $a->title() <=> $b->title();
            }

            return $result;
        });

        return $items;
    }

    /**
     * Get the value to sort by.
     */
    private function getSortValue(Item $item): mixed
    {
        return match ($this->orderBy) {
            'date' => $item->date()?->getTimestamp() ?? 0,
            'updated' => $item->updated()?->getTimestamp() ?? 0,
            'title' => strtolower($item->title()),
            'order', 'menu_order' => $item->order(),
            default => $item->get($this->orderBy) ?? '',
        };
    }
}
