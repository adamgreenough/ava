# Ava CMS Storage

This directory contains generated runtime files:

- `cache/` — Content index files and cached HTML pages
- `logs/` — Error and debug logs
- `tmp/` — Temporary files

## Important

- This entire directory is safe to delete (will be regenerated)
- Add to `.gitignore` in production
- Content index is auto-rebuilt based on `content_index.mode` setting

## Content Index Files

| File | Purpose |
|------|---------|
| `content_index.bin` | All content indexed by type, slug, ID (full index) |
| `slug_lookup.bin` | Fast single-item lookups (type/slug → file path) |
| `recent_cache.bin` | Top 200 items per type for fast archive queries |
| `tax_index.bin` | Taxonomy terms with counts |
| `routes.bin` | Compiled route map |
| `fingerprint.json` | Change detection data |
| `pages/*.json` | Page cache entries: request identity, path, headers and HTML body |

## Page cache behavior

Page-cache entries are shared only for eligible anonymous GET requests. Cookies,
authorization, active sessions, and client cache directives bypass reads and writes.
Responses with Set-Cookie, Vary, explicit HTTP cache policy, or encoded/length-bound
bodies are not stored. Custom templates should exclude any other personalized paths.
Entries are separated by scheme, host and path; allowed UTM parameters are ignored.
Only the host from `site.base_url` (plus any in `webpage_cache.hosts`) may create
entries, so unrecognised Host headers cannot grow the cache directory.
Pattern clearing uses stored path metadata and works with generator comments disabled.

## Development cache-format change

Run `php ava rebuild` after updating, especially in manual index mode. The SQLite
index now stores body text for the shared relevance search. Automatic mode detects
the new fingerprint version and rebuilds. Existing page-cache HTML files are ignored;
the new cache uses JSON entries without a legacy reader.

The redundant pre-autoload cache path and `Application::tryCachedResponse()` were
removed. `Application::handle()` now owns boot and cache lookup. WebpageCache has one
`isCacheable()` request policy; the separate read/write and unchecked lookup APIs
were removed. Binary array caches use `Ava\Support\SignedCache` instead of Indexer
signing helpers.
