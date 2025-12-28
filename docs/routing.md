# Routing

Ava uses a custom router with no external dependencies.

## Route Matching Order

1. **Trailing slash redirect** — Normalize URLs per config
2. **Redirects** — From `redirect_from` in frontmatter
3. **System routes** — Registered at runtime (plugins, admin)
4. **Exact routes** — Compiled from content (single pages, archives)
5. **Taxonomy routes** — `/category/{term}`, `/tag/{term}`, etc.
6. **404** — No match found

## URL Configuration

### Hierarchical URLs (Pages)

Files map directly to URLs:

```yaml
# content_types.php
'pages' => [
    'url' => [
        'type' => 'hierarchical',
        'base' => '/',
    ],
]
```

| File | URL |
|------|-----|
| `content/pages/index.md` | `/` |
| `content/pages/about.md` | `/about` |
| `content/pages/services/web.md` | `/services/web` |

### Pattern URLs (Posts)

URLs built from a pattern:

```yaml
'posts' => [
    'url' => [
        'type' => 'pattern',
        'pattern' => '/blog/{slug}',
        'archive' => '/blog',
    ],
]
```

Pattern tokens:
- `{slug}` — Item slug
- `{yyyy}` — Year (4 digits)
- `{mm}` — Month (2 digits)
- `{dd}` — Day (2 digits)
- `{id}` — Item ID

### Taxonomy URLs

Configured per taxonomy:

```yaml
# taxonomies.php
'categories' => [
    'rewrite' => [
        'base' => '/category',
    ],
]
```

Results in:
- `/category/tutorials`
- `/category/news`

For hierarchical taxonomies, terms can have paths:
- `/topic/guides/basics`

## Redirects

Add `redirect_from` to frontmatter:

```yaml
---
title: New Page
slug: new-page
redirect_from:
  - /old-page
  - /legacy/page
---
```

Requests to `/old-page` redirect 301 to the new URL.

## Trailing Slash

Configure in `ava.php`:

```php
'routing' => [
    'trailing_slash' => false,  // /about (not /about/)
]
```

Non-canonical URLs redirect to canonical form.

## Route Caching

Routes are compiled to `storage/cache/routes.php`:

```php
return [
    'redirects' => [
        '/old-url' => ['to' => '/new-url', 'code' => 301],
    ],
    'exact' => [
        '/' => ['type' => 'single', 'content_type' => 'pages', 'slug' => 'index', ...],
        '/about' => ['type' => 'single', 'content_type' => 'pages', 'slug' => 'about', ...],
        '/blog' => ['type' => 'archive', 'content_type' => 'posts', ...],
        '/blog/hello-world' => ['type' => 'single', 'content_type' => 'posts', ...],
    ],
    'taxonomy' => [
        'categories' => ['base' => '/category', 'hierarchical' => true],
        'tags' => ['base' => '/tag', 'hierarchical' => false],
    ],
];
```

## Preview Mode

Drafts and private content accessible with token:

```
/blog/draft-post?preview=1&token=YOUR_TOKEN
```

Configure token in `ava.php`:

```php
'security' => [
    'preview_token' => 'your-secret-token',
]
```

## Adding Custom Routes

In plugins or `app/hooks.php`:

```php
use Ava\Application;

$router = Application::getInstance()->router();

// Exact route
$router->addRoute('/api/search', function ($request) {
    // Return RouteMatch or handle directly
});

// Prefix route
$router->addPrefixRoute('/api/', function ($request) {
    // Handles all /api/* requests
});
```
