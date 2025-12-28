# Configuration

All configuration lives in `app/config/`.

## Main Config: `ava.php`

```php
<?php

return [
    // Site settings
    'site' => [
        'name' => 'My Site',
        'base_url' => 'https://example.com',
        'timezone' => 'UTC',
    ],

    // Paths (relative to project root)
    'paths' => [
        'content' => 'content',
        'themes' => 'themes',
        'plugins' => 'plugins',
        'snippets' => 'snippets',
        'storage' => 'storage',

        // Path aliases
        'aliases' => [
            '@media:' => '/media/',
            '@uploads:' => '/media/uploads/',
            '@assets:' => '/assets/',
        ],
    ],

    // Active theme
    'theme' => 'default',

    // Cache mode: auto, always, never
    'cache' => [
        'mode' => 'auto',
    ],

    // Routing
    'routing' => [
        'trailing_slash' => false,
    ],

    // Content parsing
    'content' => [
        'markdown' => [
            'allow_html' => true,
        ],
        'id' => [
            'type' => 'ulid',  // or 'uuid7'
        ],
    ],

    // Security
    'security' => [
        'shortcodes' => [
            'allow_php_snippets' => true,
        ],
        'preview_token' => null,  // Set for preview mode
    ],

    // Admin (disabled by default)
    'admin' => [
        'enabled' => false,
        'path' => '/admin',
    ],

    // Active plugins
    'plugins' => [],
];
```

## Content Types: `content_types.php`

```php
<?php

return [
    'pages' => [
        'label' => 'Pages',
        'content_dir' => 'pages',
        'url' => [
            'type' => 'hierarchical',  // URL matches folder structure
            'base' => '/',
        ],
        'templates' => [
            'single' => 'page.php',
        ],
        'taxonomies' => [],
        'sorting' => 'manual',
    ],

    'posts' => [
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
        'taxonomies' => ['categories', 'tags'],
        'sorting' => 'date_desc',
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'excerpt', 'body'],
        ],
    ],
];
```

### URL Types

**Hierarchical** — URL reflects file path:
- `content/pages/about.md` → `/about`
- `content/pages/services/web.md` → `/services/web`

**Pattern** — URL from template:
- `{slug}` — Item slug
- `{yyyy}`, `{mm}`, `{dd}` — Date parts
- `{id}` — Item ID

## Taxonomies: `taxonomies.php`

```php
<?php

return [
    'categories' => [
        'label' => 'Categories',
        'hierarchical' => true,
        'public' => true,
        'rewrite' => [
            'base' => '/category',
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,
        ],
    ],

    'tags' => [
        'label' => 'Tags',
        'hierarchical' => false,
        'public' => true,
        'rewrite' => [
            'base' => '/tag',
        ],
    ],
];
```

## Cache Modes

| Mode | Behavior |
|------|----------|
| `auto` | Rebuild when files change (fingerprint check) |
| `always` | Rebuild every request (dev mode) |
| `never` | Only rebuild via CLI (production) |
