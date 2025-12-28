# Ava CMS

> ✨ **Addy's Very Adaptable CMS** — A developer-first, flat-file PHP CMS for long-lived personal sites.

## Why Ava?

- 📁 **Flat-file** — No database, content is Markdown files. Git is your source of truth
- ⚡ **Cache-first** — Blazing fast. Content indexes compiled to PHP arrays
- 🛠️ **Developer-friendly** — Minimal magic, readable code, easy to extend
- 🎨 **Flexible** — Custom content types, taxonomies, shortcodes, plugins
- 🔒 **Secure** — Content never executes PHP, optional admin dashboard

## Quick Start

```bash
# Clone the repo
git clone https://github.com/adamgreenough/ava.git mysite
cd mysite

# Install dependencies
composer install

# Check status
./ava status

# Build the cache
./ava rebuild

# Start development server
php -S localhost:8000 -t public
```

Visit [http://localhost:8000](http://localhost:8000) to see your site.

## Project Structure

```
mysite/
├── app/
│   ├── config/          # ⚙️ Configuration files
│   ├── hooks.php        # 🎣 Custom hooks
│   └── shortcodes.php   # 📝 Custom shortcodes
├── content/
│   ├── pages/           # 📄 Page content
│   ├── posts/           # ✏️ Blog posts
│   └── _taxonomies/     # 🏷️ Term registries
├── themes/
│   └── default/         # 🎨 Theme templates
├── plugins/             # 🔌 Plugins
├── public/              # 🌐 Web root
├── storage/cache/       # 📦 Generated cache
└── bin/ava              # CLI tool
```

## Next Steps

- [Configuration](configuration.md) — Site settings and content types
- [Content](content.md) — Writing pages and posts
- [Themes](themes.md) — Creating templates
- [CLI](cli.md) — Command-line tools
