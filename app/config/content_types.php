<?php

declare(strict_types=1);

/**
 * Content Type Definitions
 *
 * Each content type defines how content is organized, routed, and rendered.
 * You can add your own types here (recipes, projects, team members, etc.).
 * Docs: https://ava.addy.zone/#/configuration?id=content-types-content_typesphp
 */

return [
    // Pages - URL structure mirrors your folder hierarchy
    // content/pages/about.md           → /about
    // content/pages/services/web.md    → /services/web
    'page' => [
        'label' => 'Pages',              // Display name in admin and CLI
        'content_dir' => 'pages',        // Folder inside content/ for this type
        'url' => [
            'type' => 'hierarchical',    // URLs mirror folder structure
            'base' => '/',               // URL prefix (/ means root)
        ],
        'templates' => [
            'single' => 'page.php',      // Template for individual pages
        ],
        'taxonomies' => [],              // Pages don't use categories/tags by default
        'fields' => [],                  // Custom fields (see docs)
        'sorting' => 'manual',           // No automatic sorting
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'body'],
        ],
    ],

    // Posts - dated content with pattern-based URLs
    // Great for blogs, news, changelogs, recipes, etc.
    'post' => [
        'label' => 'Posts',
        'content_dir' => 'posts',
        'url' => [
            'type' => 'pattern',         // URL built from a template
            'pattern' => '/blog/{slug}', // {slug} = the post's slug field
            'archive' => '/blog',        // URL for the posts listing page
        ],
        'templates' => [
            'single' => 'post.php',      // Template for individual posts
            'archive' => 'archive.php',  // Template for the posts listing
        ],
        'taxonomies' => ['category', 'tag'],  // Enable categories and tags
        'fields' => [],                  // Custom fields (see docs)
        'sorting' => 'date_desc',        // Newest first
        
        // Search configuration
        // Weights control how different matches affect result ranking.
        // Higher weights = more relevance. Set to 0 to disable that match type.
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'excerpt', 'body'],
            // Optional: Custom weights (these are the defaults)
            // 'weights' => [
            //     'title_phrase' => 80,      // Exact phrase match in title
            //     'title_all_tokens' => 40,  // All search words found in title
            //     'title_token' => 10,       // Per-word match in title (max 30)
            //     'excerpt_phrase' => 30,    // Exact phrase match in excerpt
            //     'excerpt_token' => 3,      // Per-word match in excerpt (max 15)
            //     'body_phrase' => 20,       // Exact phrase match in body
            //     'body_token' => 2,         // Per-word match in body (max 10)
            //     'featured' => 15,          // Boost for featured items
            //     'field_weight' => 5,       // Per custom field match
            // ],
        ],
        // Optional: Extra fields to include in the recent cache for archive listings.
        // By default only id, slug, title, date, status, excerpt, and taxonomies are cached.
        // Add frontmatter fields here to make them available without loading full content.
        // 'cache_fields' => ['author', 'featured_image'],
    ],
];
