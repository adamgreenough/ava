<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Backends\SqliteBackend;
use Ava\Content\Indexer;
use Ava\Testing\TestCase;

final class SqliteBackendTest extends TestCase
{
    public function testPublishingDatabaseReplacesTheWholeSqliteGeneration(): void
    {
        $directory = $this->app->configPath('storage') . '/tmp/test-sqlite-swap-'
            . bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);
        $live = $directory . '/content_index.sqlite';
        $temporary = $directory . '/new.sqlite';
        file_put_contents($live, 'old database');
        file_put_contents($live . '-wal', 'old wal');
        file_put_contents($live . '-shm', 'old shm');
        file_put_contents($temporary, 'new database');

        try {
            $indexer = new Indexer($this->app);
            $method = new \ReflectionMethod($indexer, 'publishSqliteDatabase');
            $method->setAccessible(true);
            $method->invoke($indexer, $temporary, $live);

            $this->assertEquals('new database', file_get_contents($live));
            $this->assertFalse(is_file($live . '-wal'));
            $this->assertFalse(is_file($live . '-shm'));
            $this->assertEquals([], glob($live . '.*.backup*') ?: []);
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testHierarchicalItemsWithTheSameSlugRemainDistinct(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markSkipped('pdo_sqlite extension is not available');
        }

        $directory = $this->app->configPath('storage') . '/tmp/test-sqlite-backend-'
            . bin2hex(random_bytes(6));
        $storage = $directory . '/storage';
        $content = $directory . '/content';
        mkdir($storage . '/cache', 0700, true);
        mkdir($content, 0700, true);
        $backend = new SqliteBackend($storage, $content);

        try {
            $backend->createDatabase();
            $backend->beginTransaction();
            $backend->insertContent($this->item('company/team', 'Company team'));
            $backend->insertContent($this->item('support/team', 'Support team'));
            $backend->commit();

            $this->assertEquals(2, $backend->count('page'));
            $this->assertEquals('Company team', $backend->getBySlug('page', 'company/team')['title'] ?? null);
            $this->assertEquals('Support team', $backend->getBySlug('page', 'support/team')['title'] ?? null);
            $this->assertTrue($backend->exists('page', 'company/team'));
            $this->assertTrue($backend->exists('page', 'support/team'));
        } finally {
            $backend->clearMemoryCache();
            $this->removeDirectory($directory);
        }
    }

    public function testRoutesIncludeReverseUrlLookups(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markSkipped('pdo_sqlite extension is not available');
        }

        $directory = $this->app->configPath('storage') . '/tmp/test-sqlite-routes-'
            . bin2hex(random_bytes(6));
        $storage = $directory . '/storage';
        $content = $directory . '/content';
        mkdir($storage . '/cache', 0700, true);
        mkdir($content, 0700, true);
        $backend = new SqliteBackend($storage, $content);

        try {
            $backend->createDatabase();
            $backend->insertRoute(
                'post:release-notes',
                'reverse',
                ['url' => '/2026/09/release-notes']
            );

            $routes = $backend->routes();
            $this->assertEquals(
                '/2026/09/release-notes',
                $routes['reverse']['post:release-notes'] ?? null
            );
        } finally {
            $backend->clearMemoryCache();
            $this->removeDirectory($directory);
        }
    }

    public function testCompletedDatabaseCanBePublishedWithoutWalFiles(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markSkipped('pdo_sqlite extension is not available');
        }

        $directory = $this->app->configPath('storage') . '/tmp/test-sqlite-publish-'
            . bin2hex(random_bytes(6));
        $storage = $directory . '/storage';
        $content = $directory . '/content';
        $database = $directory . '/new-index.sqlite';
        mkdir($storage . '/cache', 0700, true);
        mkdir($content, 0700, true);
        $backend = new SqliteBackend($storage, $content, $database);

        try {
            $backend->createDatabase();
            $backend->beginTransaction();
            $backend->insertContent($this->item('company/team', 'Company team'));
            $backend->commit();
            $backend->prepareForPublication();

            $this->assertTrue(is_file($database));
            $this->assertFalse(is_file($database . '-wal'));
            $this->assertFalse(is_file($database . '-shm'));
        } finally {
            $backend->clearMemoryCache();
            $this->removeDirectory($directory);
        }
    }

    private function item(string $contentKey, string $title): array
    {
        return [
            'type' => 'page',
            'content_key' => $contentKey,
            'slug' => 'team',
            'title' => $title,
            'status' => 'published',
            'file_path' => 'pages/' . $contentKey . '.md',
        ];
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
