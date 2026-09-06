<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Item;
use Ava\Testing\TestCase;

/**
 * Tests for the Content Item class.
 *
 * Format detection and HTML passthrough live in Rendering\HtmlFormatTest.
 */
final class ItemTest extends TestCase
{
    /**
     * Frontmatter-backed accessors, including their defaults when the field
     * is absent. Each case is [frontmatter, method, expected].
     */
    public function testFrontmatterAccessorsAndDefaults(): void
    {
        $cases = [
            [['id' => '01ARYZ6S41ABCDEFGHIJKLMNOP'], 'id', '01ARYZ6S41ABCDEFGHIJKLMNOP'],
            [[], 'id', null],
            [['title' => 'Hello World'], 'title', 'Hello World'],
            [[], 'title', ''],
            [['slug' => 'hello-world'], 'slug', 'hello-world'],
            [['status' => 'published'], 'status', 'published'],
            [[], 'status', 'draft'],
            [['excerpt' => 'A short summary'], 'excerpt', 'A short summary'],
            [[], 'excerpt', null],
            [['template' => 'custom'], 'template', 'custom'],
            [[], 'template', null],
            [['meta_title' => 'SEO Title'], 'metaTitle', 'SEO Title'],
            [['meta_description' => 'SEO description'], 'metaDescription', 'SEO description'],
            [['canonical' => 'https://example.com/original'], 'canonical', 'https://example.com/original'],
            [['noindex' => true], 'noindex', true],
            [[], 'noindex', false],
            // og_image falls back to featured_image, but wins when both are set.
            [['og_image' => '/og.jpg'], 'ogImage', '/og.jpg'],
            [['featured_image' => '/featured.jpg'], 'ogImage', '/featured.jpg'],
            [['og_image' => '/og.jpg', 'featured_image' => '/featured.jpg'], 'ogImage', '/og.jpg'],
        ];

        foreach ($cases as [$frontmatter, $method, $expected]) {
            $this->assertSame(
                $expected,
                $this->createItem($frontmatter)->$method(),
                $method . '(' . json_encode($frontmatter) . ')'
            );
        }
    }

    public function testStatusPredicatesAreMutuallyExclusive(): void
    {
        foreach (['published' => 'isPublished', 'draft' => 'isDraft', 'unlisted' => 'isUnlisted'] as $status => $expected) {
            $item = $this->createItem(['status' => $status]);
            foreach (['isPublished', 'isDraft', 'isUnlisted'] as $method) {
                $this->assertSame($method === $expected, $item->$method(), "$status::$method");
            }
        }
    }

    public function testDateAcceptsStringsObjectsAndTimestamps(): void
    {
        $cases = [
            ['2024-01-15', '2024-01-15'],
            [new \DateTime('2024-06-20'), '2024-06-20'],
            [strtotime('2024-03-10'), '2024-03-10'],
        ];

        foreach ($cases as [$value, $expected]) {
            $date = $this->createItem(['date' => $value])->date();
            $this->assertInstanceOf(\DateTimeImmutable::class, $date, $expected);
            $this->assertEquals($expected, $date->format('Y-m-d'), $expected);
        }

        $this->assertNull($this->createItem([])->date());
    }

    public function testUpdatedFallsBackToDate(): void
    {
        $withBoth = $this->createItem(['date' => '2024-01-15', 'updated' => '2024-06-20']);
        $this->assertEquals('2024-01-15', $withBoth->date()->format('Y-m-d'));
        $this->assertEquals('2024-06-20', $withBoth->updated()->format('Y-m-d'));

        $dateOnly = $this->createItem(['date' => '2024-01-15']);
        $this->assertEquals('2024-01-15', $dateOnly->updated()->format('Y-m-d'));
    }

    /** Single values are normalised to arrays so templates can always iterate. */
    public function testListFieldsNormaliseSingleValuesToArrays(): void
    {
        foreach ([
            [['tutorials', 'php'], ['tutorials', 'php']],
            ['tutorials', ['tutorials']],
            [null, []],
        ] as [$value, $expected]) {
            $frontmatter = $value === null ? [] : ['categories' => $value];
            $this->assertEquals($expected, $this->createItem($frontmatter)->terms('categories'));
        }

        foreach ([
            [['/old-url', '/legacy/path'], ['/old-url', '/legacy/path']],
            ['/old-url', ['/old-url']],
            [null, []],
        ] as [$value, $expected]) {
            $frontmatter = $value === null ? [] : ['redirect_from' => $value];
            $this->assertEquals($expected, $this->createItem($frontmatter)->redirectFrom());
        }
    }

    public function testMetadataComesFromConstructorNotFrontmatter(): void
    {
        $item = new Item(['title' => 'Test'], 'This is **markdown**', '/content/posts/test.md', 'page');

        $this->assertEquals('page', $item->type());
        $this->assertEquals('/content/posts/test.md', $item->filePath());
        $this->assertEquals('This is **markdown**', $item->rawContent());
    }

    public function testWithHtmlLeavesTheOriginalItemUnchanged(): void
    {
        $item = $this->createItem([]);
        $this->assertNull($item->html());

        $rendered = $item->withHtml('<p>Rendered HTML</p>');

        $this->assertNull($item->html());
        $this->assertEquals('<p>Rendered HTML</p>', $rendered->html());
    }

    public function testFormatAndContentKeySurviveTheIndexRoundTrip(): void
    {
        $data = (new Item(['title' => 'Test'], '', '/test.html', 'post', Item::FORMAT_HTML))->toArray();
        $this->assertEquals(Item::FORMAT_HTML, $data['format']);

        $restored = Item::fromArray($data);
        $this->assertEquals(Item::FORMAT_HTML, $restored->format());
        $this->assertTrue($restored->isHtml());

        $keyed = Item::fromArray([
            'frontmatter' => ['title' => 'Team', 'slug' => 'team'],
            'content_key' => 'about/team',
            'type' => 'page',
        ]);
        $this->assertEquals('about/team', $keyed->get('content_key'));
    }

    private function createItem(array $frontmatter, string $content = ''): Item
    {
        return new Item($frontmatter, $content, '/test.md', 'post');
    }
}
