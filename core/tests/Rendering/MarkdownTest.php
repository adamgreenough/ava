<?php

declare(strict_types=1);

namespace Ava\Tests\Rendering;

use Ava\Application;
use Ava\Testing\TestCase;
use League\CommonMark\MarkdownConverter;

/**
 * Tests for Application::markdown(), the shared converter used by both the
 * rendering engine and the indexer.
 *
 * These exercise the configuration Ava applies on top of CommonMark —
 * raw HTML policy, disallowed tags, unsafe links and heading ids — rather
 * than re-testing CommonMark's own syntax support.
 */
final class MarkdownTest extends TestCase
{
    /** @var array<string, MarkdownConverter> */
    private array $converters = [];

    public function tearDown(): void
    {
        $this->converters = [];
    }

    public function testConfiguredSyntaxSupportIsWiredUp(): void
    {
        // The extension set Ava registers (CommonMark core + GFM) is a
        // configuration choice, so assert the features themes depend on.
        $cases = [
            'This is **bold** text' => '<strong>bold</strong>',
            'This is ~~struck~~ text' => '<del>struck</del>',
            'Use `code` here' => '<code>code</code>',
            "- Item 1\n- Item 2" => '<ul>',
            "1. First\n2. Second" => '<ol>',
            '[Link](https://example.com)' => '<a href="https://example.com">Link</a>',
            '![Alt](/img.png)' => '<img src="/img.png" alt="Alt"',
            "```php\n\$x = 1;\n```" => '<pre><code class="language-php">',
            '> Quoted' => '<blockquote>',
            '---' => '<hr',
            "| H1 | H2 |\n|----|----|\n| C1 | C2 |" => '<th>H1</th>',
            "- [x] Checked" => 'type="checkbox"',
            'Visit https://example.com now' => '<a href="https://example.com"',
            'Tom & Jerry' => '&amp;',
            'Less than < here' => '&lt;',
            '\\*not italic\\*' => '*not italic*',
        ];

        foreach ($cases as $markdown => $expected) {
            $this->assertStringContains($expected, $this->render($markdown), $markdown);
        }

        $this->assertStringNotContains('<em>', $this->render('\\*not italic\\*'));
        $this->assertEquals('', trim($this->render("   \n   ")));
    }

    public function testRawHtmlIsAllowedButDisallowedTagsAreNeutralised(): void
    {
        $html = $this->render(
            '<div class="custom">Content</div>' . "\n\n"
            . "Text with <span>inline</span> HTML\n\n"
            . "<script>alert('xss')</script>\n\n"
            . '<noscript>fallback</noscript>'
        );

        $this->assertStringContains('<div class="custom">Content</div>', $html);
        $this->assertStringContains('<span>inline</span>', $html);

        // config disallows script/noscript: the opening angle bracket is
        // escaped, so the tag is inert rather than executed.
        $this->assertStringNotContains('<script>', $html);
        $this->assertStringNotContains('<noscript>', $html);
        $this->assertStringContains('&lt;script', $html);
    }

    public function testAllowHtmlFalseStripsRawHtmlEntirely(): void
    {
        $html = $this->render(
            '<div class="custom">Content</div>',
            ['allow_html' => false]
        );

        $this->assertStringNotContains('<div', $html);
        $this->assertStringNotContains('&lt;div', $html);
    }

    public function testUnsafeLinksAreNeutralisedRegardlessOfHtmlPolicy(): void
    {
        foreach ([true, false] as $allowHtml) {
            $html = $this->render(
                '[Click](javascript:alert(1))',
                ['allow_html' => $allowHtml]
            );

            $this->assertStringNotContains('javascript:', $html, 'allow_html=' . var_export($allowHtml, true));
        }
    }

    public function testHeadingIdsAreAppliedOnlyWhenEnabled(): void
    {
        $enabled = $this->render('# Hello World', ['heading_ids' => true]);
        $this->assertMatchesRegex('/<h1[^>]*id="[^"]+"/', $enabled);

        $disabled = $this->render('# Hello World', ['heading_ids' => false]);
        $this->assertStringContains('<h1>Hello World</h1>', $disabled);
    }

    private function render(string $markdown, array $markdownConfig = []): string
    {
        return $this->converter($markdownConfig)->convert($markdown)->getContent();
    }

    /** Converters are cached per markdown config so each case reuses one Application. */
    private function converter(array $markdownConfig): MarkdownConverter
    {
        $key = serialize($markdownConfig);
        if (!isset($this->converters[$key])) {
            $config = $this->app->allConfig();
            $config['content']['markdown'] = array_merge(
                $config['content']['markdown'] ?? [],
                $markdownConfig
            );
            $this->converters[$key] = (new Application($config))->markdown();
        }

        return $this->converters[$key];
    }
}
