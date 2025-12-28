# Content Authoring

Content in Ava CMS is Markdown files with YAML frontmatter.

## File Location

Content lives under `content/` organized by type:

```
content/
├── pages/           # Pages (hierarchical URLs)
│   ├── index.md     # Home page (/)
│   ├── about.md     # /about
│   └── services/
│       └── web.md   # /services/web
├── posts/           # Posts (/blog/{slug})
│   └── hello.md
└── _taxonomies/     # Term registries (optional)
    ├── category.yml
    └── tag.yml
```

## Frontmatter

Every content file starts with YAML frontmatter:

```yaml
---
id: 01JGMK0000POST0000000000001
title: My Post Title
slug: my-post-title
status: published
date: 2024-12-28
excerpt: A brief summary
category:
  - tutorials
tag:
  - php
  - cms
---

# Content starts here

Your Markdown content...
```

### Required Fields

| Field | Description |
|-------|-------------|
| `title` | Display title |
| `slug` | URL-safe identifier (auto-generated from filename if missing) |
| `status` | `draft`, `published`, or `private` |

### Recommended Fields

| Field | Description |
|-------|-------------|
| `id` | ULID for stable references (auto-generated via CLI) |
| `date` | Publication date (for dated types) |
| `updated` | Last modified date |
| `excerpt` | Summary for listings |

### SEO Fields

| Field | Description |
|-------|-------------|
| `meta_title` | Custom title for `<title>` tag |
| `meta_description` | Meta description |
| `noindex` | Set to `true` to add noindex |
| `canonical` | Canonical URL |
| `og_image` | Open Graph image path |

### Redirects

```yaml
redirect_from:
  - /old-url
  - /another-old-url
```

### Per-Item Assets

```yaml
assets:
  css:
    - "@uploads:2024/custom-post.css"
  js:
    - "@assets:post.js"
```

## Taxonomies

Assign terms in frontmatter:

```yaml
# Simple format
category:
  - tutorials
  - php
tag:
  - getting-started

# Hierarchical paths
topic:
  - guides/basics
```

## Path Aliases

Use magic tokens instead of hard-coded URLs:

| Alias | Expands To |
|-------|------------|
| `@media:` | `/media/` |
| `@uploads:` | `/media/uploads/` |
| `@assets:` | `/assets/` |

Example:
```markdown
![Hero image](@uploads:2024/hero.jpg)
```

## Shortcodes

Shortcodes are processed after Markdown:

```markdown
Current year: [year]

Site name: [site_name]

[button url="/contact"]Contact Us[/button]

[snippet name="cta" heading="Join Us"]
```

## Status

- `draft` — Not visible unless preview mode
- `published` — Visible to everyone
- `private` — Only visible with preview token
