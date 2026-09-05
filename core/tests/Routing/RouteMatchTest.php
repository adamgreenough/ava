<?php

declare(strict_types=1);

namespace Ava\Tests\Routing;

use Ava\Content\Item;
use Ava\Http\Response;
use Ava\Routing\RouteMatch;
use Ava\Testing\TestCase;

final class RouteMatchTest extends TestCase
{
    public function testDefaults(): void
    {
        $match = new RouteMatch('single');
        $this->assertSame('single', $match->getType());
        $this->assertSame('index.php', $match->getTemplate());
        $this->assertNull($match->getContentItem());
        $this->assertNull($match->getQuery());
        $this->assertNull($match->getTaxonomy());
        $this->assertFalse($match->isRedirect());
        $this->assertNull($match->getRedirectUrl());
        $this->assertSame(302, $match->getRedirectCode());
        $this->assertSame([], $match->getParams());
        $this->assertNull($match->getParam('missing'));
        $this->assertSame('default', $match->getParam('missing', 'default'));
        $this->assertFalse($match->hasResponse());
        $this->assertNull($match->getResponse());
    }

    public function testContentContextAndParams(): void
    {
        $item = new Item(['title' => 'Test'], 'content', '/test.md', 'post');
        $params = ['slug' => 'hello-world', 'page' => 1];
        $match = new RouteMatch('single', contentItem: $item, template: 'post.php', params: $params);
        $this->assertSame($item, $match->getContentItem());
        $this->assertSame('post.php', $match->getTemplate());
        $this->assertSame($params, $match->getParams());
        $this->assertSame('hello-world', $match->getParam('slug'));
    }

    public function testTaxonomyContext(): void
    {
        $taxonomy = ['name' => 'categories', 'term' => 'tutorials', 'label' => 'Tutorials'];
        $query = $this->app->query();
        $match = new RouteMatch('taxonomy', query: $query, taxonomy: $taxonomy);
        $this->assertSame($taxonomy, $match->getTaxonomy());
        $this->assertSame($query, $match->getQuery());
    }

    public function testRedirectContext(): void
    {
        $match = new RouteMatch('redirect', redirectUrl: '/new-url', redirectCode: 301);
        $this->assertTrue($match->isRedirect());
        $this->assertSame('/new-url', $match->getRedirectUrl());
        $this->assertSame(301, $match->getRedirectCode());
    }

    public function testPluginResponseContext(): void
    {
        $response = new Response('plugin content');
        $match = new RouteMatch('plugin', response: $response);
        $this->assertTrue($match->hasResponse());
        $this->assertSame($response, $match->getResponse());
    }
}
