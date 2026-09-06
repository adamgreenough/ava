<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Application;
use Ava\Content\Query;
use Ava\Testing\TestCase;

final class QueryTest extends TestCase
{
    private function createQuery(): Query
    {
        return $this->app->query();
    }

    public function testApplicationCreatesIndependentQueries(): void
    {
        $query = $this->createQuery();
        $this->assertInstanceOf(Query::class, $query);
        $this->assertNotSame($query, $this->createQuery());
    }

    public function testFluentModifiersLeaveOriginalUnchanged(): void
    {
        $query = $this->createQuery();
        $before = clone $query;
        $cases = [
            'type' => ['post'],
            'status' => ['draft'],
            'published' => [],
            'anyStatus' => [],
            'where' => ['featured', true],
            'whereTax' => ['category', 'tutorials'],
            'orderBy' => ['title', 'asc'],
            'perPage' => [5],
            'page' => [2],
            'search' => ['test'],
            'searchWeights' => [['title_token' => 20]],
            'fromParams' => [['paged' => 2]],
        ];
        foreach ($cases as $method => $arguments) {
            $modified = $query->$method(...$arguments);
            $this->assertNotSame($query, $modified, $method);
            $this->assertEquals($before, $query, "$method mutated the original");
        }
        $this->assertSame('published', $query->published()->getStatus());
    }

    public function testPaginationBoundsAndPreviousPage(): void
    {
        foreach ([0 => 1, 5 => 5, 999 => 100] as $input => $expected) {
            $this->assertSame($expected, $this->createQuery()->perPage($input)->pagination()['per_page'], "perPage($input)");
        }
        foreach ([0 => 1, 1 => 1, 2 => 2] as $input => $expected) {
            $query = $this->createQuery()->page($input);
            $this->assertSame($expected, $query->currentPage(), "page($input)");
            $this->assertSame($expected > 1, $query->hasPrevious(), "hasPrevious($input)");
        }
    }

    public function testEmptyQueryResultsAndPagination(): void
    {
        $query = $this->createQuery()->type('nonexistent');
        $this->assertSame([], $query->get());
        $this->assertNull($query->first());
        $this->assertSame(0, $query->count());
        $this->assertTrue($query->isEmpty());
        $this->assertSame([
            'current_page' => 1,
            'per_page' => 10,
            'total' => 0,
            'total_pages' => 0,
            'has_more' => false,
            'has_previous' => false,
        ], $query->pagination());
    }

    public function testTaxonomyFilterOnlyQueriesDeclaringContentTypes(): void
    {
        $query = $this->createQuery()->whereTax('category', 'tutorials');
        $method = new \ReflectionMethod($query, 'queryTypes');
        $method->setAccessible(true);

        $this->assertEquals(['post'], $method->invoke($query));
    }

    public function testTaxonomyFilterRejectsExplicitUndeclaredContentType(): void
    {
        $query = $this->createQuery()
            ->whereTax('category', 'tutorials')
            ->fromParams(['type' => 'page']);
        $method = new \ReflectionMethod($query, 'queryTypes');
        $method->setAccessible(true);

        $this->assertEquals([], $method->invoke($query));
    }

    public function testTaxonomyEligibilityDoesNotRequireAnIndex(): void
    {
        $config = $this->app->allConfig();
        $config['paths']['storage'] = 'storage/tmp/missing-query-index-' . bin2hex(random_bytes(6));
        $config['content_index']['backend'] = 'array';
        $app = new Application($config);
        $this->assertEquals([], $app->repository()->types());

        $query = $app->query()->whereTax('category', 'tutorials');
        $method = new \ReflectionMethod($query, 'queryTypes');
        $this->assertEquals(['post'], $method->invoke($query));
        $this->assertEquals([], $query->get());
    }

    public function testQueriesAreLimitedToPublishedContentUntilAskedOtherwise(): void
    {
        // SECURITY: forgetting published() must not be how drafts get out.
        // A theme writing $ava->query()->type('post') for a sidebar has no
        // idea it is opting into unpublished content, so it cannot be opt-out.
        $this->assertSame('published', $this->createQuery()->getStatus());
        $this->assertSame('published', $this->createQuery()->type('post')->getStatus());
        $this->assertNull($this->createQuery()->anyStatus()->getStatus());
        $this->assertSame('published', $this->createQuery()->anyStatus()->published()->getStatus());
    }

    public function testFromParamsIgnoresStatusToPreventDraftDisclosure(): void
    {
        // SECURITY: status is a security boundary and must never be settable by
        // a visitor via query params, otherwise `?status=draft` would expose
        // unpublished content. fromParams() must leave the status untouched.
        $base = $this->createQuery()->published();
        $tampered = $base->fromParams(['status' => 'draft']);

        $this->assertEquals('published', $tampered->getStatus());
    }

    public function testFromParamsRejectsNestedAndMalformedValues(): void
    {
        $query = $this->createQuery()
            ->type('post')
            ->fromParams([
                'type' => ['page'],
                'orderby' => ['title'],
                'order' => ['asc'],
                'per_page' => ['100'],
                'paged' => ['2'],
                'q' => ['hello'],
                'tax_category' => ['tutorials'],
                0 => 'numeric key',
            ]);

        $pagination = $query->pagination();
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $query->currentPage());
        $this->assertEquals('date', $query->getOrderBy());
        $this->assertEquals('desc', $query->getOrder());
    }

    public function testFromParamsRejectsUnknownTypeAndSortField(): void
    {
        $query = $this->createQuery()
            ->type('post')
            ->fromParams([
                'type' => 'unknown',
                'orderby' => 'private_field',
                'order' => 'sideways',
            ]);
        $method = new \ReflectionMethod($query, 'queryTypes');
        $method->setAccessible(true);

        $this->assertEquals(['post'], $method->invoke($query));
        $this->assertEquals('date', $query->getOrderBy());
        $this->assertEquals('desc', $query->getOrder());
    }

    public function testPageIsBounded(): void
    {
        $query = $this->createQuery()->fromParams(['paged' => '999999999']);

        $this->assertEquals(1_000_000, $query->currentPage());
    }

    public function testSearchLengthIsBounded(): void
    {
        $query = $this->createQuery()->search(str_repeat('x', 1_000));
        $property = new \ReflectionProperty($query, 'search');
        $property->setAccessible(true);

        $this->assertEquals(200, strlen($property->getValue($query)));
    }
}
