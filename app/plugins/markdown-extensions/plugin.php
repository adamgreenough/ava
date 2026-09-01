<?php

declare(strict_types=1);

use Ava\Application;
use Ava\Plugins\Hooks;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Highlight\HighlightExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

return [
    'name' => 'Markdown Extensions',
    'version' => '1.0.0',
    'description' => 'Adds optional Markdown features using bundled CommonMark extensions',
    'author' => 'Ava CMS',

    'boot' => function (Application $app): void {
        $config = array_merge([
            'footnotes' => false,
            'description_lists' => false,
            'highlight' => false,
            'smart_punctuation' => false,
            'external_links' => false,
            'attributes' => false,
            'table_of_contents' => false,
        ], $app->config('markdown_extensions', []));

        $extensions = [
            'footnotes' => FootnoteExtension::class,
            'description_lists' => DescriptionListExtension::class,
            'highlight' => HighlightExtension::class,
            'smart_punctuation' => SmartPunctExtension::class,
            'external_links' => ExternalLinkExtension::class,
            'attributes' => AttributesExtension::class,
            'table_of_contents' => TableOfContentsExtension::class,
        ];

        if ($config['table_of_contents'] === true) {
            Hooks::addFilter('markdown.config', function (array $markdownConfig): array {
                $markdownConfig['heading_permalink']['insert'] = 'before';

                return $markdownConfig;
            });
        }

        Hooks::addAction('markdown.configure', function (EnvironmentBuilderInterface $environment) use ($config, $extensions): void {
            foreach ($extensions as $name => $extension) {
                if ($config[$name] === true) {
                    $environment->addExtension(new $extension());
                }
            }
        });
    },
];