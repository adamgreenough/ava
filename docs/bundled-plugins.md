# Bundled Plugins

Ava ships with three plugins that cover common needs. They're included in the `plugins/` directory and can be enabled in your configuration.

## Sitemap

Generates XML sitemaps for search engines.

### Features

- **Sitemap index** at `/sitemap.xml` linking to per-type sitemaps
- **Per-type sitemaps** at `/sitemap-{type}.xml` (e.g., `/sitemap-post.xml`)
- **Respects noindex** — Content with `noindex: true` is excluded
- **Automatic lastmod** — Uses content `updated` or `date` fields
- **Configurable** — Set changefreq and priority per content type
- **Admin page** — View sitemap status under Plugins → Sitemap

### Enabling

```php
// app/config/ava.php
'plugins' => [
    'sitemap',
],
```

### Configuration

Optional configuration in `app/config/ava.php`:

```php
'sitemap' => [
    'enabled' => true,
    'changefreq' => [
        'page' => 'monthly',
        'post' => 'weekly',
    ],
    'priority' => [
        'page' => '0.8',
        'post' => '0.6',
    ],
],
```

### Excluding Content

Add `noindex: true` to any content's frontmatter:

```yaml
---
title: Private Page
status: published
noindex: true
---
```

### Output Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>https://example.com/sitemap-page.xml</loc>
    <lastmod>2025-01-15</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemap-post.xml</loc>
    <lastmod>2025-01-20</lastmod>
  </sitemap>
</sitemapindex>
```

---

## RSS Feed

Generates RSS 2.0 feeds for content syndication.

### Features

- **Main feed** at `/feed.xml` with all content types
- **Per-type feeds** at `/feed/{type}.xml` (e.g., `/feed/post.xml`)
- **Respects noindex** — Content with `noindex: true` is excluded
- **Configurable item count** — Default 20 items per feed
- **Full content or excerpt** — Choose what's included
- **Admin page** — View feed URLs under Plugins → RSS Feeds

### Enabling

```php
// app/config/ava.php
'plugins' => [
    'feed',
],
```

### Configuration

Optional configuration in `app/config/ava.php`:

```php
'feed' => [
    'enabled' => true,
    'items_per_feed' => 20,
    'full_content' => false,  // true = full HTML, false = excerpt only
    'types' => null,          // null = all types, or ['post'] for specific types
],
```

### Adding to Your Theme

Add the feed link to your theme's `<head>`:

```html
<link rel="alternate" type="application/rss+xml" 
      title="My Site" 
      href="/feed.xml">
```

### Output Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>My Ava Site</title>
  <link>https://example.com</link>
  <description>Latest content from My Ava Site</description>
  <atom:link href="https://example.com/feed.xml" rel="self" type="application/rss+xml"/>
  <item>
    <title>My Latest Post</title>
    <link>https://example.com/blog/my-latest-post</link>
    <guid isPermaLink="true">https://example.com/blog/my-latest-post</guid>
    <pubDate>Mon, 20 Jan 2025 12:00:00 +0000</pubDate>
    <description>Post excerpt or full content...</description>
  </item>
</channel>
</rss>
```

---

## Redirects

Manage custom URL redirects through the admin interface.

### Features

- **Admin UI** — Add and remove redirects without editing files
- **301 and 302** — Support for permanent and temporary redirects
- **High priority** — Processed before content routing
- **Persistent storage** — Saved to `storage/redirects.json`
- **Admin page** — Manage redirects under Plugins → Redirects

### Enabling

```php
// app/config/ava.php
'plugins' => [
    'redirects',
],
```

### Usage

1. Navigate to Plugins → Redirects in the admin
2. Enter the source URL (e.g., `/old-page`)
3. Enter the destination URL (e.g., `/new-page` or `https://example.com`)
4. Select redirect type (301 permanent or 302 temporary)
5. Click "Add Redirect"

### When to Use

| Redirect Type | Use Case |
|---------------|----------|
| **301 Permanent** | Content moved permanently, SEO-friendly |
| **302 Temporary** | Temporary redirect, not cached |

### Comparison with Content Redirects

Ava supports two ways to redirect:

| Method | Best For |
|--------|----------|
| **Redirects Plugin** | External URLs, legacy paths, quick fixes |
| **`redirect_from` frontmatter** | Content that's been moved/renamed |

Using `redirect_from` in content:

```yaml
---
title: New Page Location
redirect_from:
  - /old-url
  - /another-old-url
---
```

### Storage Format

Redirects are stored in `storage/redirects.json`:

```json
[
  {
    "from": "/old-page",
    "to": "/new-page",
    "code": 301,
    "created": "2025-01-20 14:30:00"
  }
]
```

---

## Enabling All Bundled Plugins

```php
// app/config/ava.php
return [
    // ...
    
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
    ],
];
```

After enabling, rebuild the cache:

```bash
./ava rebuild
```

Then access the plugin admin pages at:
- `/admin/sitemap`
- `/admin/feeds`
- `/admin/redirects`
