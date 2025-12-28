# Ava CMS

**Addy's Very Adaptable CMS** — A flat-file, developer-first PHP CMS for personal sites.

## What Ava Is

- A **flat-file CMS** — Content is Markdown files with YAML frontmatter
- **Developer-first** — You write PHP, edit files, use git
- **Cache-first** — Content indexed to compiled PHP for fast reads
- **Minimal** — No database, no complex dependencies, no magic

## What Ava Is Not

- Not a static site generator (renders on request, from cache)
- Not a visual builder or WYSIWYG editor
- Not a media manager (manage files via filesystem/git)
- Not for non-developers

## Quick Start

```bash
# Install dependencies
composer install

# Start dev server
php -S localhost:8000 -t public

# CLI commands
php bin/ava status    # Show site status
php bin/ava rebuild   # Rebuild cache
php bin/ava lint      # Validate content
php bin/ava make:page "About Us"
php bin/ava make:post "Hello World"
```

## Project Structure

```
ava/
├── public/              # Web root
│   ├── index.php        # Front controller
│   └── assets/          # CSS, JS, images
├── bootstrap.php        # Loads config, autoload
├── core/                # Ava CMS core (versioned)
│   ├── Application.php
│   ├── Content/         # Parser, Indexer, Repository, Query
│   ├── Routing/         # Router, RouteMatch
│   ├── Rendering/       # Engine, TemplateHelpers
│   ├── Shortcodes/      # Shortcode engine
│   ├── Plugins/         # Hooks system
│   ├── Admin/           # Optional admin dashboard
│   ├── Cli/             # CLI application
│   └── Support/         # Arr, Str, Path, Ulid helpers
├── app/                 # Your customizations
│   ├── config/
│   │   ├── ava.php           # Main config
│   │   ├── content_types.php # CPT definitions
│   │   └── taxonomies.php    # Taxonomy definitions
│   ├── hooks.php        # Custom hooks
│   └── shortcodes.php   # Custom shortcodes
├── content/             # Your content (Markdown)
│   ├── pages/
│   ├── posts/
│   └── _taxonomies/     # Term registries (YAML)
├── themes/
│   └── default/
│       ├── theme.php
│       └── templates/
├── plugins/             # Optional plugins
├── snippets/            # PHP components for shortcodes
└── storage/             # Generated (gitignored)
    ├── cache/           # Compiled indexes
    └── logs/
```

## Documentation

- [Content Authoring](docs/content.md)
- [Configuration](docs/configuration.md)
- [Themes](docs/themes.md)
- [Routing](docs/routing.md)
- [Shortcodes](docs/shortcodes.md)
- [CLI Reference](docs/cli.md)
- [AI Reference Sheet](docs/ai-reference.md)

## License

MIT
