# Ava CMS — Full Specification (Draft)

**Name:** Ava CMS (Addy’s Very Adaptable CMS)

**Purpose:** A lightweight, flat‑file PHP CMS for brochure sites, blogs with multiple post types, and non‑geo directory-style content sites. Content lives as Markdown with frontmatter. Rendering is theme-driven. Extensions are via plugins, themes, and shortcodes.

---

## 0. Design goals

### 0.1 Non-goals (for core)
- No database requirement.
- No geo/distance location search (plugin later).
- No complex visual builder in core.

### 0.2 Goals
- **Flat file**: content is files under `content/`.
- **Modern but simple PHP**: readable, dependency-light; Composer autoload recommended.
- **Content flexibility**: unlimited content types (CPTs), taxonomies, and custom fields.
- **Predictable routing**: generated from config + indexed metadata.
- **Fast**: index/cache first; never reparse all content on each request.
- **Safe**: content files never execute PHP.
- **Extensible**: plugins/themes can add shortcodes, routes, types, filters.

---

## 1. Project layout

Recommended structure:

```
ava/
  public/
    index.php                 # front controller
    assets/
  bootstrap.php               # loads config, autoload, core

  core/                       # Ava CMS core (versioned)
    Content/
    Routing/
    Rendering/
    Shortcodes/
    Plugins/
    Support/

  app/                        # site layer (user code)
    config/
      ava.php
      content_types.php
      taxonomies.php
      fields.php              # optional reusable field schemas
      menus.php               # optional
    hooks.php                 # site hooks/filters registration
    shortcodes.php            # site shortcodes

  content/                    # flat-file content
    pages/
    posts/
    resources/
    _shared/
      media/
    _taxonomies/
      categories.yml
      topics.yml

  themes/
    default/
      theme.php
      templates/
      partials/
      assets/

  plugins/
    example-plugin/
      plugin.php
      src/

  snippets/                   # safe PHP components invoked via shortcode

  storage/                    # generated, gitignored
    cache/
    logs/
    tmp/
```

Rules:
- `core/` is treated as vendor-like.
- `app/` is the project customization layer.
- `content/` is data only.
- `storage/` is runtime output, always safe to delete.

---

## 2. Configuration

### 2.1 Main config: `app/config/ava.php`
Must return an array.

Required keys:
- `site.name`
- `site.base_url`
- `site.timezone`
- `paths.content`, `paths.themes`, `paths.plugins`, `paths.snippets`, `paths.storage`
- `cache.enabled`
- `security.shortcodes.allow_php_snippets`

Optional keys:
- `routing.trailing_slash` (true/false)
- `content.frontmatter.format` (yaml initially)
- `content.markdown.allow_html` (true/false)

---

## 3. Content model

### 3.1 Content files
Content files are Markdown (`.md`) with YAML frontmatter.

Frontmatter delimiter: `---`.

### 3.2 Required frontmatter fields (core)
- `title` (string)
- `slug` (string; URL-safe)
- `status` (`draft|published|private`)

Recommended:
- `id` (ULID/UUID stable ID)
- `date` (for dated types)
- `updated` (timestamp or date)
- `template` (template override)
- `redirect_from` (array)
- `canonical` (string)

### 3.3 Content type inference
A content item’s type is derived from folder by default (e.g. `content/posts/*.md` => type `posts`).

Optionally allow `type:` override in frontmatter to support moving items between folders, but canonical type remains whichever the index resolves after validation.

---

## 4. Content Types (CPT)

### 4.1 Definition file
`app/config/content_types.php` returns a map of content types.

Each type supports:
- `label` (string)
- `content_dir` (relative under `content/`)
- `url`:
  - `type`: `hierarchical|pattern`
  - for `pattern`: `pattern` e.g. `/blog/{slug}` or `/news/{yyyy}/{mm}/{slug}`
  - for `hierarchical`: `base` e.g. `/`
- `templates`:
  - `single` (e.g. `post.php`)
  - `archive` optional
- `taxonomies`: list of taxonomy keys
- `fields`: list or schema reference
- `search`:
  - `enabled`
  - `fields` (e.g. `title|excerpt|body|tax|custom_fields...`)
  - `boosts`
- `sorting` default: `date_desc|updated_desc|title_asc|manual`

### 4.2 Pages as default but not mandatory
Core ships with `pages` enabled by default, but the system must function with any set of types.

---

## 5. Taxonomies

### 5.1 Taxonomy definitions
`app/config/taxonomies.php` returns taxonomy definitions.

Supported keys:
- `label`
- `hierarchical` (bool)
- `public` (bool)
- `rewrite.base`:
  - non-hierarchical: `/tag/{term}`
  - hierarchical: `/topic/{term_path}`
- `rewrite.separator` default `/`
- `behaviour.allow_unknown_terms` default true
- `behaviour.hierarchy_rollup` default true (for hierarchical)
- `ui.show_counts`, `ui.sort_terms`

### 5.2 Assigning terms in frontmatter
Simple form:
```yaml
tags: [php, cms]
topics: [guides/basics]
```

Explicit form:
```yaml
tax:
  topics:
    - name: Guides
    - name: Basics
      parent: Guides
```

Canonical stored representation:
- Non-hierarchical: list of term slugs
- Hierarchical: list of term paths `parent/child`

### 5.3 Optional term registry
If present, load `content/_taxonomies/<taxonomy>.yml`.

Registry provides:
- `slug` (identity)
- `name` (display)
- `description`
- `order` (manual sorting)
- `seo.title`, `seo.description`
- `redirect_from` (list)

Derived terms still exist if referenced in content and `allow_unknown_terms` is true.

### 5.4 Taxonomy archives
Taxonomy archives are generated routes. They resolve to a Query:
- published only unless preview
- optional type-scope if the route is scoped
- filter by taxonomy term (slug/path)
- pagination + sorting

### 5.5 Facets
Ava should be able to produce counts per term:
- global counts
- counts within the current filtered result set

---

## 6. Routing

### 6.1 Route layers
Order:
1) Hard routes (system/app): `/sitemap.xml`, `/feed.xml`, `/search`, etc.
2) CPT routes: archive + single
3) Taxonomy archive routes
4) Redirect routes from `redirect_from`
5) 404

### 6.2 Canonicalization
Config: `routing.trailing_slash`.

Rules:
- redirect non-canonical to canonical
- taxonomy term URLs normalized to slug/path

---

## 7. Rendering

### 7.1 Theme
Theme lives in `themes/<name>/`.

`theme.php` can:
- register templates
- register theme shortcodes
- register hooks

### 7.2 Template resolution
Order:
1) frontmatter `template`
2) type-specific template (if convention supported)
3) type default `templates.single`
4) fallback `single.php` / `index.php`

### 7.3 Path aliases (magic tokens)

Ava supports **path aliases** to keep content human‑writable and avoid copy‑pasting URLs.

Aliases are expanded into public URLs during rendering (and optionally during indexing for frontmatter fields).

Default aliases (configurable):
- `@media:` → `/media/`
- `@uploads:` → `/media/uploads/`
- `@assets:` → `/assets/`

Examples:
```md
![Hero](@uploads:2025/12/hero.jpg)

<link rel="stylesheet" href="@assets:theme.css">
```

Rules:
- Aliases are simple string replacements, not a templating language.
- Expansion applies to:
  - markdown body output
  - HTML embedded in markdown
  - frontmatter string values
- Aliases are not expanded inside code fences (best‑effort; v1 may expand everywhere and document this caveat).

Config (`app/config/ava.php`):
- `paths.aliases` (map: token => base path)

Purpose:
- Keep content portable
- Avoid admin‑driven media workflows
- Allow filesystem‑managed assets

### 7.4 Per‑item assets (CSS/JS)

Ava supports optional per‑item assets for art‑directed pages/posts.

Frontmatter:
```yaml
assets:
  css:
    - "@uploads:2025/12/custom-post.css"
  js:
    - "@assets:post.js"
```

Rules:
- Assets must resolve to site‑local paths after alias expansion.
- JS is emitted in `<head>` with `defer` in v1.
- No bundling/minification in core.

### 7.5 Context available to templates
Templates receive a `$context` array containing:
- `site`
- `request`
- `page` (current content item)
- `query` (for archives)
- `tax` (for taxonomy archive)
- `theme`

Templates receive a `$context` array containing:
- `site`
- `request`
- `page` (current content item)
- `query` (for archives)
- `tax` (for taxonomy archive)
- `theme`

Templates receive a `$context` array containing:
- `site`
- `request`
- `page` (current content item)
- `query` (for archives)
- `tax` (for taxonomy archive)
- `theme`

---

## 8. Markdown + HTML

### 8.1 Markdown
Markdown rendering is required.

### 8.2 HTML support
If `content.markdown.allow_html` is true, raw HTML in markdown is allowed.

Security note: output sanitization is optional but recommended for multi-user installs; for personal installs you may accept trusted content.

---

## 9. Shortcodes

### 9.1 Shortcode syntax
- Inline: `[year]`
- With attrs: `[button url="/contact"]Contact[/button]`
- Self-closing: `[snippet name="cta" heading="Join"]`

### 9.2 Shortcode engine
- Runs after markdown-to-HTML OR before (choose and document). Recommended: run **after markdown** on HTML output so shortcodes can insert HTML fragments cleanly.
- Provide escaping helpers.

### 9.3 Snippet shortcode (safe PHP components)
`[snippet name="cta" ...]` loads `snippets/cta.php`.

Security rules:
- `name` maps to a file inside snippets dir
- no `../`
- optional whitelist

Snippet receives only:
- `$params`
- `$context`
- helper functions (optional)

---

## 10. Plugins

### 10.1 Plugin format
`plugins/<name>/plugin.php` returns a manifest + registers callbacks.

Capabilities:
- register hooks/filters
- register shortcodes
- add routes
- add content types/taxonomies (optional)

### 10.2 Load order
1) Core
2) Plugins
3) Theme
4) App/site overrides

---

## 11. Indexing + caching

### 11.1 Cache artifacts
Under `storage/cache/`:
- `content_index.php`
- `tax_index.php`
- `search_index.php` (or merged)
- `routes.php`
- `fingerprint.json`

### 11.2 Cache modes
Config (`app/config/ava.php`):
- `cache.mode` = `auto` | `always` | `never`

Meaning:
- `auto`: rebuild indexes/routes when fingerprint changes
- `always`: rebuild on every request (dev)
- `never`: never rebuild automatically (prod; use CLI `ava rebuild`)

### 11.3 Rebuild triggers
Rebuild indexes when changes occur in:
- `content/`
- `content/_taxonomies/`
- `app/config/`
- `themes/<active>/` (if templates affect routing/context)
- plugin boot files if they affect routes/types

### 11.4 Fingerprinting strategy
Store:
- latest mtime per watched directory
- file count per watched directory
- optional hash of config files

If fingerprint differs, rebuild.

Store:
- latest mtime per watched directory
- file count per watched directory
- optional hash of config files

If fingerprint differs, rebuild.

---

## 12. Search

### 12.1 Search goals
- Type filters
- Taxonomy filters
- Field filters
- Relevance scoring
- Pagination
- Facets (optional)

### 12.2 Search index document shape
Per content item store:
- id, type, url, status
- normalized: title/excerpt/body
- tax terms
- selected custom fields

### 12.3 Search pipeline
1) load search index
2) filter: status + type
3) filter: taxonomy + field filters
4) score text matches
5) sort + paginate
6) compute facets (optional)

Query params (WP-style):
- `q` (search string)
- `type` (comma-separated)
- `paged` (1-based page number)
- `per_page` (capped)
- `orderby` (`relevance|date|updated|title`)
- `order` (`asc|desc`)
- taxonomy filters: `tax_<taxonomy>=term` (repeatable)
1) load search index
2) filter: status + type
3) filter: taxonomy + field filters
4) score text matches
5) sort + paginate
6) compute facets (optional)

### 12.4 Ranking model (default)
- Title phrase match: +80
- Title all tokens: +40
- Title token hits: +10 each (cap +30)
- Excerpt phrase: +30
- Excerpt token hits: +3 each (cap +15)
- Body token hits: +1 each (cap +10)
- boosts: e.g. featured +15

Tie-break: updated desc, title asc.

### 12.5 Snippets
Prefer frontmatter excerpt; else generate around first match (max 200 chars).

---

## 12.6 IDs

Ava supports stable IDs for content items.

- Default ID type: **ULID**
- Optional ID type: **UUIDv7** (selectable via config)

Rules:
- `id` is stored in frontmatter.
- IDs should be generated via Admin or CLI (not manually typed).
- Indexer must detect duplicate IDs:
  - dev: report error with both file paths
  - prod: skip later file and log warning

---

## 13. SEO (core essentials)

Frontmatter fields supported:
- `meta_title`
- `meta_description`
- `noindex` (bool)
- `canonical`
- `og_image` (path)

Core rendering should expose helpers to output meta tags, but **no sitemap/feed generation is required in core**.

Recommended default plugins (ship with Ava, enabled by config):
- **Sitemap**: `/sitemap.xml` (index-based)
- **RSS/Atom**: `/feed.xml` (index-based)

Robots handling (default):
- A static `public/robots.txt` file is recommended for Ava’s target audience.
- A plugin may optionally **override** robots output (e.g. environment-aware rules). If a Robots plugin is enabled, it should take precedence over the static file.

---

## 14. Menus (theme responsibility)

Ava core does **not** define a global menus system.

Themes may implement navigation however they like, for example:
- a theme config file defining named menus
- reading a `content/_shared/navigation.yml`
- deriving menus from page hierarchy

Core should only provide primitives useful for themes:
- Query helpers (e.g. fetch pages, sorted)
- Current URL/active item helper

---

## 15. Preview / drafts

Optional preview mode:
- `?preview=1&token=...`

Rules:
- drafts visible only with token
- private visible only with token

---

## 16. Admin (optional core module)

Ava includes an optional **Admin dashboard** focused on transparency and safe tooling, not content editing.

Admin is **disabled by default** and must be explicitly enabled.

### 16.1 Goals
- Provide visibility into site state without becoming a file editor
- Expose safe, explicit actions that mirror CLI commands
- Avoid widening the security surface unnecessarily

### 16.2 What admin is (v1)

Read-only dashboards:
- Cache status (built_at, fingerprint, cache mode)
- Content stats (counts per type, published/draft)
- Taxonomy stats
- Media stats (file count, total size)
- Diagnostics (PHP version, extensions available, disk free, memory_limit)

Safe actions:
- Rebuild cache (same logic as `php ava rebuild`)
- Run lint/validation (same logic as `php ava lint`)
- Generate new content files from stubs (same logic as `php ava make:*`)

Admin is effectively a **web UI wrapper around the CLI**, sharing the same underlying services.

### 16.3 What admin is NOT (by design)
- No file browser
- No content editor
- No config editor
- No arbitrary delete operations
- No media upload UI

### 16.4 Authentication
- File-based users (`app/config/admin_users.php`)
- Session-based auth
- CSRF required for all actions

### 16.5 Routing
- Admin routes return 404 when disabled
- Admin path configurable and renameable

---

## 17. Media handling (v1)

Media handling in Ava v1 is intentionally **filesystem-driven** and minimal.

### 17.1 Principles
- Media is managed outside the CMS (git, SFTP, file manager)
- Ava does not attempt to own or abstract the filesystem
- No fragile pipelines or rebuild steps are required for media

### 17.2 Public media locations
- `public/media/` — shared/static assets
- `public/media/uploads/` — convention for uploaded or ad-hoc assets

There is no enforced structure beyond this; developers may organise files as they wish.

### 17.3 Referencing media
Media should be referenced using **path aliases** (see Rendering section):
- `@media:` → `/media/`
- `@uploads:` → `/media/uploads/`
- `@assets:` → `/assets/`

This avoids copy-pasting URLs and keeps content portable.

### 17.4 What core does NOT do (by design)
- No media upload UI
- No image resizing or format conversion
- No SVG sanitization
- No EXIF handling
- No asset bundling

### 17.5 Extensibility
Advanced media features (uploads, resizing, srcset generation, format conversion, optimisation) are expected to be implemented via plugins.

---
