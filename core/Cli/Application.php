<?php

declare(strict_types=1);

namespace Ava\Cli;

use Ava\Application as AvaApp;
use Ava\Support\Ulid;
use Ava\Support\Str;

/**
 * CLI Application
 *
 * Handles command-line interface for Ava CMS.
 */
final class Application
{
    private AvaApp $app;
    private array $commands = [];

    public function __construct()
    {
        $this->app = AvaApp::getInstance();
        $this->registerCommands();
    }

    /**
     * Run the CLI application.
     */
    public function run(array $argv): int
    {
        $script = array_shift($argv);
        $command = array_shift($argv) ?? 'help';

        // Handle help
        if ($command === 'help' || $command === '--help' || $command === '-h') {
            $this->showHelp();
            return 0;
        }

        // Handle version
        if ($command === 'version' || $command === '--version' || $command === '-v') {
            $this->writeln('Ava CMS v1.0.0');
            return 0;
        }

        // Find and run command
        if (!isset($this->commands[$command])) {
            $this->error("Unknown command: {$command}");
            $this->showHelp();
            return 1;
        }

        try {
            return $this->commands[$command]($argv);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Register available commands.
     */
    private function registerCommands(): void
    {
        $this->commands['status'] = [$this, 'cmdStatus'];
        $this->commands['rebuild'] = [$this, 'cmdRebuild'];
        $this->commands['lint'] = [$this, 'cmdLint'];
        $this->commands['make'] = [$this, 'cmdMake'];
    }

    // =========================================================================
    // Commands
    // =========================================================================

    /**
     * Show site status.
     */
    private function cmdStatus(array $args): int
    {
        $this->writeln('');
        $this->writeln('=== Ava CMS Status ===');
        $this->writeln('');

        // Site info
        $this->writeln('Site: ' . $this->app->config('site.name'));
        $this->writeln('URL:  ' . $this->app->config('site.base_url'));
        $this->writeln('');

        // Cache status
        $cachePath = $this->app->configPath('storage') . '/cache';
        $fingerprintPath = $cachePath . '/fingerprint.json';

        if (file_exists($fingerprintPath)) {
            $fingerprint = json_decode(file_get_contents($fingerprintPath), true);
            $fresh = $this->app->indexer()->isCacheFresh();

            $this->writeln('Cache:');
            $this->writeln('  Status: ' . ($fresh ? '✓ Fresh' : '✗ Stale'));
            $this->writeln('  Mode:   ' . $this->app->config('cache.mode', 'auto'));

            if (file_exists($cachePath . '/content_index.php')) {
                $mtime = filemtime($cachePath . '/content_index.php');
                $this->writeln('  Built:  ' . date('Y-m-d H:i:s', $mtime));
            }
        } else {
            $this->writeln('Cache: Not built');
        }
        $this->writeln('');

        // Content counts
        $this->writeln('Content:');
        $repository = $this->app->repository();

        foreach ($repository->types() as $type) {
            $total = $repository->count($type);
            $published = $repository->count($type, 'published');
            $drafts = $repository->count($type, 'draft');

            $this->writeln("  {$type}: {$total} total ({$published} published, {$drafts} drafts)");
        }
        $this->writeln('');

        // Taxonomies
        $this->writeln('Taxonomies:');
        foreach ($repository->taxonomies() as $taxonomy) {
            $terms = $repository->terms($taxonomy);
            $this->writeln("  {$taxonomy}: " . count($terms) . ' terms');
        }
        $this->writeln('');

        return 0;
    }

    /**
     * Rebuild cache.
     */
    private function cmdRebuild(array $args): int
    {
        $this->writeln('Rebuilding cache...');

        $start = microtime(true);
        $this->app->indexer()->rebuild();
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->success("Cache rebuilt in {$elapsed}ms");

        return 0;
    }

    /**
     * Lint content files.
     */
    private function cmdLint(array $args): int
    {
        $this->writeln('Validating content...');

        $errors = $this->app->indexer()->lint();

        if (empty($errors)) {
            $this->success('All content files are valid.');
            return 0;
        }

        $this->error('Found ' . count($errors) . ' error(s):');
        foreach ($errors as $error) {
            $this->writeln('  - ' . $error);
        }

        return 1;
    }

    /**
     * Create content of a specific type.
     */
    private function cmdMake(array $args): int
    {
        if (count($args) < 2) {
            $this->error('Usage: ava make <type> "Title"');
            $this->writeln('');
            $this->showAvailableTypes();
            return 1;
        }

        $type = array_shift($args);
        $title = implode(' ', $args);

        // Verify type exists
        $contentTypes = require $this->app->path('app/config/content_types.php');
        if (!isset($contentTypes[$type])) {
            $this->error("Unknown content type: {$type}");
            $this->showAvailableTypes();
            return 1;
        }

        $typeConfig = $contentTypes[$type];
        $extra = ['status' => 'draft'];

        // Add date for dated content types
        if (($typeConfig['sorting'] ?? 'manual') === 'date_desc') {
            $extra['date'] = date('Y-m-d');
        }

        return $this->createContent($type, $title, $extra);
    }

    /**
     * Show available content types.
     */
    private function showAvailableTypes(): void
    {
        $contentTypes = require $this->app->path('app/config/content_types.php');
        $this->writeln('Available types:');
        foreach ($contentTypes as $name => $config) {
            $label = $config['label'] ?? ucfirst($name);
            $this->writeln("  {$name} - {$label}");
        }
    }

    /**
     * Create a content file.
     */
    private function createContent(string $type, string $title, array $extra = []): int
    {
        // Load content type config
        $contentTypes = require $this->app->path('app/config/content_types.php');
        $typeConfig = $contentTypes[$type] ?? [];
        $contentDir = $typeConfig['content_dir'] ?? $type;

        // Generate slug and ID
        $slug = Str::slug($title);
        $id = Ulid::generate();

        // Build frontmatter
        $frontmatter = array_merge([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
        ], $extra);

        // Generate YAML
        $yaml = "---\n";
        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$key}:\n";
                foreach ($value as $item) {
                    $yaml .= "  - {$item}\n";
                }
            } else {
                $yaml .= "{$key}: {$value}\n";
            }
        }
        $yaml .= "---\n\n";
        $yaml .= "# {$title}\n\nYour content here.\n";

        // Determine file path
        $basePath = $this->app->configPath('content') . '/' . $contentDir;
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $filePath = $basePath . '/' . $slug . '.md';

        // Check if file exists
        if (file_exists($filePath)) {
            $this->error("File already exists: {$filePath}");
            return 1;
        }

        // Write file
        file_put_contents($filePath, $yaml);

        $this->success("Created: {$filePath}");
        $this->writeln("  ID: {$id}");
        $this->writeln("  Slug: {$slug}");

        return 0;
    }

    // =========================================================================
    // Output helpers
    // =========================================================================

    private function showHelp(): void
    {
        $this->writeln('');
        $this->writeln('Ava CMS - Command Line Interface');
        $this->writeln('');
        $this->writeln('Usage:');
        $this->writeln('  php ava <command> [options] [arguments]');
        $this->writeln('');
        $this->writeln('Commands:');
        $this->writeln('  status         Show site status and cache info');
        $this->writeln('  rebuild        Rebuild all cache files');
        $this->writeln('  lint           Validate content files');
        $this->writeln('  make <type>    Create content of a specific type');
        $this->writeln('');
        $this->writeln('Examples:');
        $this->writeln('  php ava status');
        $this->writeln('  php ava rebuild');
        $this->writeln('  php ava make pages "About Us"');
        $this->writeln('  php ava make posts "Hello World"');
        $this->writeln('');
    }

    private function writeln(string $message): void
    {
        echo $message . "\n";
    }

    private function success(string $message): void
    {
        echo "\033[32m✓ {$message}\033[0m\n";
    }

    private function error(string $message): void
    {
        echo "\033[31m✗ {$message}\033[0m\n";
    }

    private function warning(string $message): void
    {
        echo "\033[33m⚠ {$message}\033[0m\n";
    }
}
