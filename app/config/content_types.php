<?php

declare(strict_types=1);

/**
 * Content Type Definitions
 *
 * Each content type defines how content is organized, routed, and rendered.
 * Docs: https://ava.addy.zone/#/configuration?id=content-types-content_typesphp
 */

return [
    // Pages - hierarchical, URL reflects folder structure
    'page' => [
        'label' => 'Pages',
        'content_dir' => 'pages',
        'url' => [
            'type' => 'hierarchical',
            'base' => '/',
        ],
        'templates' => [
            'single' => 'page.php',
        ],
        'taxonomies' => [],
        'fields' => [],
        'sorting' => 'manual',
    ],

    // Posts - dated content with pattern-based URLs
    'post' => [
        'label' => 'Posts',
        'content_dir' => 'posts',
        'url' => [
            'type' => 'pattern',
            'pattern' => '/blog/{slug}',
            'archive' => '/blog',
        ],
        'templates' => [
            'single' => 'post.php',
            'archive' => 'archive.php',
        ],
        'taxonomies' => ['category', 'tag'],
        'fields' => [],
        'sorting' => 'date_desc',
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'excerpt', 'body'],
        ],
    ],
];
