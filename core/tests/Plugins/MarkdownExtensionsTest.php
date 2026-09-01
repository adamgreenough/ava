<?php

declare(strict_types=1);

namespace Ava\Tests\Plugins;

use Ava\Application;
use Ava\Plugins\Hooks;
use Ava\Testing\TestCase;

final class MarkdownExtensionsTest extends TestCase
{
    public function setUp(): void
    {
        Hooks::reset();
    }

    public function tearDown(): void
    {
        Hooks::reset();
    }

    public function testFeaturesAreDisabledByDefault(): void
    {
        $app = $this->createApp();
        $html = $app->markdown()->convert("Text.[^note]\n\n[^note]: Note.")->getContent();

        $this->assertStringNotContains('class="footnotes"', $html);
    }

    public function testFootnotesCanBeEnabled(): void
    {
        $app = $this->createApp(['footnotes' => true]);
        $html = $app->markdown()->convert("Text.[^note]\n\n[^note]: Note.")->getContent();

        $this->assertStringContains('class="footnote-ref"', $html);
        $this->assertStringContains('class="footnotes"', $html);
    }

    public function testAdditiveExtensionsCanBeEnabledTogether(): void
    {
        $app = $this->createApp([
            'description_lists' => true,
            'highlight' => true,
            'smart_punctuation' => true,
        ]);
        $html = $app->markdown()->convert("Term\n: Definition\n\n==Marked== \"quoted\"")->getContent();

        $this->assertStringContains('<dl>', $html);
        $this->assertStringContains('<mark>Marked</mark>', $html);
        $this->assertStringNotContains('"quoted"', $html);
    }

    public function testDocumentExtensionsCanBeEnabledTogether(): void
    {
        $app = $this->createApp([
            'external_links' => true,
            'attributes' => true,
            'table_of_contents' => true,
        ]);
        $html = $app->markdown()->convert("# Heading\n\nParagraph\n{.featured}\n\n[External](https://example.com)")->getContent();

        $this->assertStringContains('class="table-of-contents"', $html);
        $this->assertStringContains('class="featured"', $html);
        $this->assertStringContains('rel="noopener noreferrer"', $html);
    }

    private function createApp(array $config = []): Application
    {
        $app = new Application([
            'paths' => [
                'plugins' => 'app/plugins',
            ],
            'plugins' => ['markdown-extensions'],
            'markdown_extensions' => $config,
        ]);
        $app->loadPlugins();

        return $app;
    }
}