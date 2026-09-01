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
            'footnotes' => 'never',
            'description_lists' => 'never',
            'highlight' => 'never',
            'smart_punctuation' => 'never',
            'external_links' => 'never',
            'attributes' => 'never',
            'table_of_contents' => 'never',
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

        $isEnabled = static function (string $name, array $options) use ($config): bool {
            $mode = $config[$name];
            $mode = $mode === true ? 'always' : ($mode === false ? 'never' : $mode);
            $overrides = $options['markdown_extensions'] ?? [];
            $override = is_array($overrides) ? ($overrides[$name] ?? null) : null;

            return match ($mode) {
                'always' => true,
                'opt_in' => $override === true,
                'opt_out' => $override !== false,
                default => false,
            };
        };

        Hooks::addFilter('markdown.profile', function (array $options) use ($extensions, $isEnabled): array {
            $profile = [];
            foreach ($extensions as $name => $_extension) {
                $profile[$name] = $isEnabled($name, $options);
            }

            return $profile;
        });

        Hooks::addFilter('markdown.config', function (array $markdownConfig, array $options = []) use ($isEnabled): array {
            if ($isEnabled('table_of_contents', $options)) {
                $markdownConfig['heading_permalink']['insert'] = 'after';
                $markdownConfig['heading_permalink']['symbol'] = '#';
            }

            return $markdownConfig;
        });

        Hooks::addAction('markdown.configure', function (EnvironmentBuilderInterface $environment, array $options = []) use ($extensions, $isEnabled): void {
            foreach ($extensions as $name => $extension) {
                if ($isEnabled($name, $options)) {
                    $environment->addExtension(new $extension());
                }
            }
        });
    },
];