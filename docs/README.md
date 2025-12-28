# Ava CMS

> Addy's Very Adaptable CMS — A developer-first, flat-file PHP CMS for long-lived personal sites.

## Features

- **Flat-file** — No database, git is your source of truth
- **Markdown + YAML** — Simple content authoring
- **Cache-first** — Fast, compiled PHP indexes
- **Developer-friendly** — Minimal magic, readable code
- **Flexible** — Custom post types, taxonomies, shortcodes

## Quick Start

```bash
# Clone the repo
git clone https://github.com/adamgreenough/ava.git mysite
cd mysite

# Install dependencies
composer install

# Check status
php bin/ava status

# Build the cache
php bin/ava rebuild

# Start development server
php -S localhost:8000 -t public
```

Visit [http://localhost:8000](http://localhost:8000) to see your site.

## Project Structure

```
mysite/
├── app/
│   ├── config/          # Configuration files
│   ├── hooks.php        # Custom hooks
│   └── shortcodes.php   # Custom shortcodes
├── content/
│   ├── pages/           # Page content
│   ├── posts/           # Blog posts
│   └── _taxonomies/     # Term registries
├── themes/
│   └── default/         # Theme templates
├── public/              # Web root
├── storage/cache/       # Generated cache
└── bin/ava              # CLI tool
```

## Next Steps

- [Configuration](configuration.md) — Site settings and content types
- [Content](content.md) — Writing pages and posts
- [Themes](themes.md) — Creating templates
- [CLI](cli.md) — Command-line tools
