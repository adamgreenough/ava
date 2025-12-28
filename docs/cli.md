# CLI Reference

The Ava CLI provides commands for managing your site.

## Usage

```bash
php bin/ava <command> [arguments]
```

## Commands

### `status`

Show site status, cache info, and content counts.

```bash
php bin/ava status
```

Output:
```
=== Ava CMS Status ===

Site: My Site
URL:  https://example.com

Cache:
  Status: ✓ Fresh
  Mode:   auto
  Built:  2024-12-28 10:30:15

Content:
  pages: 3 total (3 published, 0 drafts)
  posts: 5 total (4 published, 1 drafts)

Taxonomies:
  categories: 4 terms
  tags: 8 terms
```

### `rebuild`

Rebuild all cache files.

```bash
php bin/ava rebuild
```

This regenerates:
- `storage/cache/content_index.php`
- `storage/cache/tax_index.php`
- `storage/cache/routes.php`
- `storage/cache/fingerprint.json`

### `lint`

Validate all content files.

```bash
php bin/ava lint
```

Checks for:
- Valid YAML frontmatter
- Required fields (title, slug, status)
- Valid status values
- Slug format (lowercase, alphanumeric, hyphens)
- Duplicate slugs within content types
- Duplicate IDs

### `make:page`

Create a new page.

```bash
php bin/ava make:page "About Us"
```

Creates `content/pages/about-us.md` with:
```yaml
---
id: 01JGMK...
title: About Us
slug: about-us
status: draft
---

# About Us

Your content here.
```

### `make:post`

Create a new blog post.

```bash
php bin/ava make:post "Hello World"
```

Creates `content/posts/hello-world.md` with date set to today.

### `make:type`

Create content of a custom type.

```bash
php bin/ava make:type resources "PHP Tutorial"
```

Creates content in the specified type's directory.

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Error (invalid args, validation failures, etc.) |

## Common Workflows

### Development

```bash
# Start dev server
php -S localhost:8000 -t public

# Watch for changes (cache.mode should be 'auto')
# Cache rebuilds automatically when files change
```

### Production Deploy

```bash
# Set cache.mode to 'never' in config
# Then rebuild after deploy:
php bin/ava rebuild
```

### Content Validation

```bash
# Before committing content changes:
php bin/ava lint

# If errors found, fix and re-run
```
