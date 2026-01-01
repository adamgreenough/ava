# Performance

Ava is designed to be fast by default. To achieve this, it uses a two-layer performance strategy:

1. **Content Indexing:** A pre-built index of your content metadata to avoid parsing Markdown files on every request.
2. **Webpage Caching:** A static HTML cache that serves fully rendered webpages instantly.

This page explains both systems and how to configure them for your site.

---

# Content Indexing

The Content Index is the foundation of Ava's performance. Instead of reading thousands of Markdown files every time a user visits your site, Ava builds a binary index of all your content's metadata (titles, dates, slugs, custom fields).

## How It Works

When you run [`./ava rebuild`](cli.md?id=rebuild) (or when Ava auto-detects changes), it:

1. **Scans** all your Markdown files
2. **Parses** frontmatter and extracts metadata
3. **Builds** an optimised index for fast lookups
4. **Stores** the index in your chosen backend format

### Index Files

Ava generates several files in `storage/cache/` to optimise different types of queries:

| File | Contents | Purpose |
|------|----------|---------|
| `recent_cache.bin` | Top 200 items per type | **Instant Archives:** Used for homepage, RSS, and first few pages. Extremely small (~50KB). |
| `slug_lookup.bin` | Slug → File Path map | **Fast Single Posts:** Used to find a specific post without loading the full index. |
| `content_index.bin` | Full content metadata | **Deep Queries:** Used for search, filtering, and deep pagination. |
| `tax_index.bin` | Taxonomy terms & counts | **Taxonomies:** Used for category/tag lists. |
| `routes.bin` | URL → Content map | **Routing:** Maps incoming URLs to content. |

### Tiered Caching Strategy

Ava uses a "tiered" approach to ensure common requests are fast, even on huge sites.

1. **Recent Cache (Tier 1):**
   - **Used for:** Homepage, RSS feeds, Archive pages 1-20.
   - **Performance:** Loads ~50KB. Sub-millisecond response.
   - **Why:** 90% of traffic hits these pages.

2. **Slug Lookup (Tier 2):**
   - **Used for:** Viewing a single post or page.
   - **Performance:** Loads a lightweight map (~80kB for 1k posts). Very fast (~1ms).
   - **Why:** You don't need the full index to find one file.

3. **Full Index (Tier 3):**
   - **Used for:** Search, complex filtering, deep pagination (page 50+).
   - **Performance:** Loads the full dataset. Speed depends on backend (Array vs SQLite).
   - **Why:** Necessary for complex queries that need to see "everything".

## Backend Options

Ava supports three index configurations. The best choice depends on your content size, how you use Ava, and your server setup.

### 1. Array + igbinary (Default & Recommended)

The default and best option for most sites where the indexes can sit comfortably in memory. It uses [igbinary](https://github.com/igbinary/igbinary), a popular PHP extension that serializes data ~5× faster and ~9× smaller than standard PHP serialization.

- **Pros:** Fastest for almost all operations. Compact cache files.
- **Cons:** Loads the full index into memory for deep archives or search.
- **Requirement:** `igbinary` PHP extension (standard on most quality hosts).

### 2. Array + serialize (Fallback)

If `igbinary` isn't installed, Ava falls back to standard PHP `serialize()`.

- **Pros:** Works everywhere with zero configuration.
- **Cons:** Larger cache files. Slower to load. Higher memory usage.
- **Best for:** Local development or small sites where you can't install extensions.

### 3. SQLite (Optional)

Stores the index in a local SQLite database file. Instead of loading the whole index into memory, Ava runs SQL queries.

- **Pros:** Constant memory usage (~2MB) regardless of site size. Instant counts.
- **Cons:** Slightly slower for complex pages. Requires `pdo_sqlite` extension.
- **Best for:** Large sites, very dynamic sites or memory-constrained environments.

## Benchmark Comparison

We tested all three backends with realistic content on a standard server. You can run these tests on your own machine using the [`./ava benchmark`](cli.md?id=benchmark) command.

### 1,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Build index** | 244ms | 253ms | 286ms |
| **Count all posts** | 2.1ms | 25ms | 0.6ms |
| **Get by slug** | 0.5ms | 0.8ms | 1.0ms |
| **Homepage** (Recent) | 0.2ms | 0.4ms | 1.7ms |
| **Deep Archive** (Page 50) | 8ms | 30ms | 21ms |
| **Sort by date** | 7ms | 34ms | 24ms |
| **Sort by title** | 8ms | 30ms | 23ms |
| **Search** | 8ms | 32ms | 25ms |
| **Cache Size** | 0.6 MB | 4.4 MB | 1.2 MB |

### 10,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Build index** | 2.7s | 2.7s | 3.7s |
| **Count all posts** | 49ms | 373ms | 1.3ms |
| **Get by slug** | 10ms | 16ms | 0.9ms |
| **Homepage** (Recent) | 0.2ms | 0.5ms | 7ms |
| **Deep Archive** (Page 50) | 137ms | 495ms | 314ms |
| **Sort by date** | 126ms | 494ms | 332ms |
| **Sort by title** | 156ms | 521ms | 336ms |
| **Search** | 120ms | 475ms | 306ms |
| **Cache Size** | 5.3 MB | 43 MB | 11.4 MB |

### 100,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Build index** | 29s | 31s | 34s |
| **Count all posts** | 520ms | 2575ms | 8ms |
| **Get by slug** | 111ms | 113ms | 0.8ms |
| **Homepage** (Recent) | 0.3ms | 0.4ms | 51ms |
| **Deep Archive** (Page 50) | 1695ms | 3936ms | 4572ms |
| **Sort by date** | 1701ms | 3868ms | 5109ms |
| **Sort by title** | 2171ms | 4105ms | 5129ms |
| **Search** | 1645ms | 3463ms | 4910ms |
| **Cache Size** | 54 MB | 432 MB | 116 MB |

<details>
<summary><strong>Benchmark Environment & Methodology</strong></summary>

**Environment:**
- **Ava:** v25.12.3
- **OS:** Linux x86_64 (Ubuntu)
- **PHP:** 8.3 (CLI)
- **Hardware:** Standard cloud instance

**Methodology:**
Benchmarks were run using the built-in Ava CLI tools.
1. Content generated via `./ava stress:generate post <count>`
2. Benchmarks run via `./ava benchmark --compare`
3. Each test iterated 5 times, average result shown.
4. Repository cache cleared between each iteration for accurate measurements.

**Note:** OPcache is disabled for CLI by default. This doesn't affect results since OPcache only caches compiled PHP bytecode, not data operations. The benchmarks measure I/O, unserialization, and query performance—none of which OPcache influences.
</details>

### Analysis

- **Build Index:** All backends take roughly the same time to build the index (29-34 seconds at 100k posts), since the cost is dominated by parsing Markdown files. This is a one-time cost when content changes.
- **Homepage:** Array backends are instant because they use the "Recent Cache" optimisation. SQLite is slightly slower as it must query the database.
- **Counts:** SQLite is the clear winner for counting items (e.g. `{{ count('post') }}`).
- **Single Item:** SQLite is extremely fast (~1ms) for looking up a post by slug, whereas Array backends must load the lookup table into memory.
- **Deep Archives:** Array + igbinary remains faster than SQLite even at 100k posts, *provided you have enough RAM* to load the 54MB index.
- **Memory:** SQLite wins on memory. It stays at ~2MB usage, while Array backends load the full index size into RAM.

### Why isn't SQLite faster?

You might expect a database to be faster than a file-based array, but PHP's arrays are incredibly optimised in-memory structures.

- **Array Backend:** Loads the *entire* dataset into RAM. Sorting, searching and filtering happen instantly in memory.
- **SQLite:** Must read from disk (or OS cache) and parse records.

**The Catch:** The Array backend consumes memory *per concurrent request*.
- 10k posts = ~5.3MB RAM.
- 10 concurrent visitors searching/filtering/deep archive cache misses = 53MB RAM.
- 100 concurrent visitors searching/filtering/deep archive cache misses = 530MB RAM.

**SQLite** uses constant memory (~2MB). It won't be as fast for a single user, but it won't crash your server under load.

### When is SQLite faster?

SQLite shines when you only need a single item or a simple count.

- **Get by Slug:** SQLite uses a database index to jump directly to the specific row on disk. It reads almost nothing.
- **Array Backend:** Must load and unserialize the entire `slug_lookup` map into memory just to find one key. At 100k posts, that's a lot of overhead for one item. A significant optimisation for high-traffic sites, sites that don't make extensive use of webpage caching, or memory-constrained servers.

## Choosing a Backend

> ### Where to start?
>
> The default **Array** backend is designed to be the fastest option for typical websites. Unless you are running on very limited hosting or have a large amount of content, you likely don't need to change anything.
>
> We recommend starting with the default settings and only switching if you notice high memory usage or if your benchmarks suggest SQLite would be better for your specific setup.

Ava offers different backends to balance **speed** (CPU/Time) vs **resources** (Memory/RAM).

### How to Decide

The best way to choose is to test your specific site on your specific server.

1. **Check your resources:** If your site runs fine and doesn't hit memory limits, stick with the default.
2. **Run the benchmark:** Use Ava's built-in tool to see real numbers for your content.

```bash
./ava benchmark --compare
```

If you see the Array backend using a significant portion of your available RAM, or if you expect your content to grow significantly, try switching to SQLite.

## Configuration

Configure your index and caching in `app/config/ava.php`:

### Content Index Options

```php
'content_index' => [
    // Index rebuild mode
    // 'auto'   - Rebuild automatically when content changes (default, recommended for most sites)
    // 'never'  - Only rebuild when you explicitly run ./ava rebuild (best for high-traffic production)
    // 'always' - Rebuild on every request (slow! only for debugging)
    'mode' => 'auto',

    // Index storage backend
    // 'array'  - Serialized PHP arrays (default, fastest for typical sites)
    // 'sqlite' - SQLite database (recommended for 10k+ items or memory-constrained servers)
    'backend' => 'array',

    // Compression for array backend
    // true  - Use igbinary for ~5x faster, ~9x smaller cache files (recommended)
    // false - Use standard PHP serialize() (for compatibility/testing)
    'use_igbinary' => true,
],
```

#### Mode Comparison

| Mode | Use Case | Behaviour |
|------|----------|----------|
| `auto` | Most sites | Automatically rebuilds index when files change. Slight latency on first request after changes. |
| `never` | Production / High traffic | Manual rebuild via CLI only. Fastest requests. Best for scheduled deployments. |
| `always` | Development / Debugging | Rebuilds on every request. Slowest. Use only for testing. |

#### Backend Comparison

| Backend | Best For | Speed | Memory | Cache Size |
|---------|----------|-------|--------|-----------|
| `array` (default) | Most sites, 1-5k posts | Fastest | ~50-100MB | Compact |
| `sqlite` | Large sites, 10k+ posts | Good | ~2MB constant | Moderate |

#### igbinary Option

- **Recommended:** `true` (enabled by default)
  - Requires `igbinary` PHP extension (standard on quality hosting)
  - ~5x faster serialization, ~9x smaller cache files
  
- **Fallback:** `false`
  - Uses standard `serialize()` function
  - Works everywhere but slower and larger cache files
  - Use if `igbinary` isn't available on your server

---

# Webpage Caching

For the ultimate performance, enable Ava's full webpage cache. This saves a static HTML copy of all rendered webpages—posts, archives, taxonomy pages, and any custom content types—after the first visit.

- **First visit:** ~50ms (renders template)
- **Subsequent visits:** ~1ms (serves static file)

This bypasses the content index entirely for most visitors.

### Webpage Rendering

These benchmarks measure the end-to-end time to serve a webpage, which is what your visitors actually experience. The content index benchmarks above only measure data retrieval—webpage rendering adds template processing and Markdown conversion on top.

| Operation | 1,000 Posts | 10,000 Posts | 100,000 Posts |
|-----------|-------------|--------------|---------------|
| **Render post (uncached)** | 4.9ms | 5.5ms | 5.9ms |
| **Cache write** | 0.12ms | 0.13ms | 0.30ms |
| **Cache read (HIT)** | 0.02ms | 0.02ms | 0.05ms |

**Key insights:**
- **Uncached rendering is fast** — Even with 100k posts, rendering a single post takes ~6ms. This includes loading the content item, converting Markdown to HTML, and rendering the full page template.
- **Cached pages are instant** — Reading a cached HTML file takes just 0.02-0.05ms, making the webpage cache essential for high-traffic sites.
- **Content count doesn't affect rendering** — The rendering time is nearly constant regardless of how many posts exist, since you're only rendering one item.

?> **Note:** These benchmarks render a single post with no additional queries. Real-world themes often include sidebars, related posts, category lists, or recent post widgets—each adding extra query time. A theme with a "Recent Posts" sidebar would add ~0.2ms (from the Recent cache), while a "Related Posts" section using search could add 8-30ms depending on backend and content size. Design your templates with this in mind.

## Configuration

Configure your webpage cache in `app/config/ava.php`:

```php
// app/config/ava.php
'webpage_cache' => [
    'enabled' => true,
    'ttl' => null, // Cache until next rebuild
],
```

### Per-Page Control

You can override the global setting for individual pages using the `cache` field in your frontmatter:

```yaml
---
title: Dynamic Page
cache: false
---
```

- `cache: false` — Disables caching for this page, even if globally enabled. Useful for pages with random content or dynamic logic.
- `cache: true` — Forces caching for this page (if global caching is enabled), even if it would normally be excluded (e.g. by an exclude pattern).

### What Gets Cached

- ✅ Single content pages (posts, pages, custom types)
- ✅ Archive pages (post lists, paginated archives)
- ✅ Taxonomy pages (categories, tags)
- ❌ Admin pages
- ❌ URLs with query parameters (except UTM tags)
- ❌ Logged-in admin users
- ❌ POST/PUT/DELETE requests

### Cache Invalidation

The webpage cache is automatically cleared when:
- You run `./ava rebuild`
- Content changes (if `content_index.mode` is `'auto'`)
- You click "Rebuild Now" or "Flush Webpages" in the Admin Dashboard

You can also clear it manually via CLI:

```bash
./ava cache:clear              # Clear all cached webpages
./ava cache:clear /blog/*      # Clear only matching webpages
```

### Monitoring & Security

You can check the status of your cache via CLI:

```bash
./ava status         # Shows content index and webpage cache status
./ava cache:stats    # Detailed webpage cache stats
```

**Security Note:** The webpage cache is secure by default. It respects your exclude patterns, never caches admin pages, and hashes filenames to prevent path traversal. Query strings (like `?search=foo`) bypass the cache to prevent poisoning.

---

## Tools & Troubleshooting

### Running Your Own Benchmarks

You can test performance on your own server using the built-in CLI command.

1. **Generate test content:**
   ```bash
   ./ava stress:generate post 5000
   ```

2. **Run the benchmark:**
   ```bash
   ./ava benchmark --compare
   ```

3. **Clean up:**
   ```bash
   ./ava stress:clean post
   ```

### What is `content_index.sqlite`?

This file appears in `storage/cache/` if you've set `backend: 'sqlite'` in your config. It's the SQLite index—a single file, not a database server. If you see this file but didn't enable SQLite, you can safely delete it.

### SQLite backend not available

If you set `backend: 'sqlite'` but get errors:

1. Check if `pdo_sqlite` is installed: `php -m | grep -i sqlite`
2. If not installed, ask your host or run `apt install php-sqlite3` (Linux)
3. Or set `backend: 'array'` to use the default

### Webpages not being cached

1. Check if enabled: `./ava status`
2. Log out of admin (admin users bypass cache)
3. Check exclude patterns in `config/ava.php`
4. Check for query parameters in the URL

### Content changes not appearing

1. If `mode` is `never`, run `./ava rebuild`
2. Delete `storage/cache/fingerprint.json` to force a rebuild
3. Run `./ava rebuild` to reset everything

### Cache files not being created
1. Ensure `storage/cache/` is writable by the web server
2. Check for errors in `storage/logs/ava.log`
3. Verify page caching is enabled in `app/config/ava.php`

### High memory usage with Array backend

1. Check memory usage history with host or current with `./ava status`
2. Consider switching to `backend: 'sqlite'` for large sites
