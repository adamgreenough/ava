# Ava CMS Storage

This directory contains generated runtime files:

- `cache/` — Compiled index files (content_index.php, routes.php, etc.)
- `logs/` — Error and debug logs
- `tmp/` — Temporary files

## Important

- This entire directory is safe to delete (will be regenerated)
- Add to `.gitignore` in production
- Cache files are auto-rebuilt based on `cache.mode` setting

## Cache Files

| File | Purpose |
|------|---------|
| `content_index.php` | All content indexed by type, slug, ID |
| `tax_index.php` | Taxonomy terms with counts |
| `routes.php` | Compiled route map |
| `fingerprint.json` | Change detection data |
