<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Indexer;
use Ava\Content\Item;
use Ava\Testing\TestCase;

final class IndexerTaxonomyTest extends TestCase
{
    public function testBuildTaxonomyIndexOnlyIncludesDeclaringContentTypes(): void
    {
        $contentPath = rtrim($this->app->configPath('content'), '/');
        $post = new Item(
            ['slug' => 'post', 'status' => 'published', 'category' => 'design'],
            '',
            $contentPath . '/posts/post.md',
            'post'
        );
        $page = new Item(
            ['slug' => 'page', 'status' => 'published', 'category' => 'design'],
            '',
            $contentPath . '/pages/page.md',
            'page'
        );

        $indexer = new Indexer($this->app);
        $method = new \ReflectionMethod($indexer, 'buildTaxonomyIndex');
        $method->setAccessible(true);

        $index = $method->invoke(
            $indexer,
            ['post' => [$post], 'page' => [$page]],
            ['category' => []],
            [
                'post' => ['taxonomies' => ['category'], 'url' => ['type' => 'pattern']],
                'page' => ['taxonomies' => [], 'url' => ['type' => 'hierarchical']],
            ]
        );

        $this->assertEquals(1, $index['category']['terms']['design']['count']);
        $this->assertEquals(['post:post'], $index['category']['terms']['design']['items']);
    }
}