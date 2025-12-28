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

### `make`

Create content of any type.

```bash
php bin/ava make <type> "Title"
```

Examples:

```bash
# Create a page
php bin/ava make pages "About Us"

# Create a blog post (date auto-added for dated types)
php bin/ava make posts "Hello World"

# Create a custom type item
php bin/ava make resources "PHP Tutorial"
```

Creates a file in the type's content directory with:
```yaml
---
id: 01JGMK...
title: About Us
slug: about-us
status: draft
date: 2024-12-28  # Only for dated types
---

# About Us

Your content here.
```

If you run `make` without arguments, it shows available types:
```
Available types:
  pages - Pages
  posts - Posts
```

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
