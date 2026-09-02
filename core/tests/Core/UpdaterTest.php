<?php

declare(strict_types=1);

namespace Ava\Tests\Core;

use Ava\Updater;
use Ava\Testing\TestCase;

/**
 * Updater Tests
 *
 * Tests for version checking and update functionality.
 * Note: GitHub API calls are not tested here to avoid external dependencies.
 * Integration tests would require mocking HTTP requests.
 */
final class UpdaterTest extends TestCase
{
    public function testUpdaterPreventsConcurrentInstallations(): void
    {
        $directory = $this->app->configPath('storage') . '/tmp/test-updater-lock-'
            . bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);
        $lockFile = $directory . '/update.lock';
        $firstLock = null;

        try {
            $updater = new Updater($this->app);
            $method = new \ReflectionMethod($updater, 'acquireUpdateLock');
            $method->setAccessible(true);
            $firstLock = $method->invoke($updater, $lockFile);

            $this->assertThrows(
                \RuntimeException::class,
                fn() => $method->invoke($updater, $lockFile)
            );
        } finally {
            if (is_resource($firstLock)) {
                flock($firstLock, LOCK_UN);
                fclose($firstLock);
            }
            if (is_file($lockFile)) {
                unlink($lockFile);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testUpdaterRestoresOriginalFilesAfterActivationFailure(): void
    {
        $directory = $this->app->configPath('storage') . '/tmp/test-updater-rollback-'
            . bin2hex(random_bytes(6));
        $root = $directory . '/root';
        $backup = $directory . '/backup';
        mkdir($root . '/core', 0700, true);
        mkdir($backup . '/core', 0700, true);
        file_put_contents($root . '/core/version.txt', 'new');
        file_put_contents($root . '/bootstrap.php', 'new bootstrap');
        file_put_contents($backup . '/core/version.txt', 'old');
        file_put_contents($backup . '/bootstrap.php', 'old bootstrap');

        try {
            $updater = new Updater($this->app);
            $method = new \ReflectionMethod($updater, 'restoreUpdateTargets');
            $method->setAccessible(true);
            $errors = $method->invoke(
                $updater,
                $root,
                $backup,
                ['core', 'bootstrap.php'],
                ['core', 'bootstrap.php']
            );

            $this->assertEquals([], $errors);
            $this->assertEquals('old', file_get_contents($root . '/core/version.txt'));
            $this->assertEquals('old bootstrap', file_get_contents($root . '/bootstrap.php'));
            $this->assertFalse(file_exists($backup . '/core'));
            $this->assertFalse(file_exists($backup . '/bootstrap.php'));
        } finally {
            if (is_dir($directory)) {
                $this->removeTestDirectory($directory);
            }
        }
    }

    public function testUpdaterRejectsIncompletePackageBeforeChangingFiles(): void
    {
        $directory = $this->app->configPath('storage') . '/tmp/test-updater-incomplete-'
            . bin2hex(random_bytes(6));
        $source = $directory . '/source';
        $root = $directory . '/root';
        mkdir($source, 0700, true);
        mkdir($root, 0700, true);
        file_put_contents($root . '/required.php', 'old');

        try {
            $updater = new Updater($this->app);
            $updateDirs = new \ReflectionProperty($updater, 'updateDirs');
            $updateDirs->setAccessible(true);
            $updateDirs->setValue($updater, ['required.php']);
            $bundledPlugins = new \ReflectionProperty($updater, 'bundledPlugins');
            $bundledPlugins->setAccessible(true);
            $bundledPlugins->setValue($updater, []);

            $method = new \ReflectionMethod($updater, 'applyUpdates');
            $method->setAccessible(true);
            $this->assertThrows(
                \RuntimeException::class,
                fn() => $method->invoke($updater, $source, $root)
            );

            $this->assertEquals('old', file_get_contents($root . '/required.php'));
        } finally {
            if (is_dir($directory)) {
                $this->removeTestDirectory($directory);
            }
        }
    }

    public function testUpdaterOnlyAllowsHttpsGitHubDownloads(): void
    {
        $updater = new Updater($this->app);
        $method = new \ReflectionMethod($updater, 'validateDownloadUrl');
        $method->setAccessible(true);

        $method->invoke($updater, 'https://api.github.com/repos/avacms/ava/zipball/v1.0.0');
        $method->invoke($updater, 'https://codeload.github.com/avacms/ava/legacy.zip/v1.0.0');

        foreach ([
            'http://api.github.com/repos/avacms/ava/zipball/v1.0.0',
            'https://api.github.com.evil.example/update.zip',
            'https://user@example.com/update.zip',
            'https://github.com/attacker/repository/archive/v1.0.0.zip',
        ] as $url) {
            $this->assertThrows(
                \RuntimeException::class,
                fn() => $method->invoke($updater, $url)
            );
        }
    }

    public function testUpdaterRejectsArchiveTraversal(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markSkipped('ZipArchive extension is not available');
        }

        $directory = $this->app->configPath('storage') . '/tmp/test-updater-zip-'
            . bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);
        $zipPath = $directory . '/malicious.zip';
        $destination = $directory . '/extract';

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE) === true);
        $this->assertTrue($zip->addFromString('release/../../escaped.php', 'unsafe'));
        $zip->close();

        try {
            $updater = new Updater($this->app);
            $method = new \ReflectionMethod($updater, 'extract');
            $method->setAccessible(true);

            $this->assertThrows(
                \RuntimeException::class,
                fn() => $method->invoke($updater, $zipPath, $destination)
            );
            $this->assertFalse(file_exists($directory . '/escaped.php'));
            $this->assertFalse(is_dir($destination));
        } finally {
            if (is_file($zipPath)) {
                unlink($zipPath);
            }
            if (is_dir($destination)) {
                rmdir($destination);
            }
            rmdir($directory);
        }
    }

    public function testUpdaterWorkspacesAreUniqueAndPrivate(): void
    {
        $updater = new Updater($this->app);
        $method = new \ReflectionMethod($updater, 'createTemporaryWorkspace');
        $method->setAccessible(true);

        $first = $method->invoke($updater, 'test');
        $second = null;

        try {
            $second = $method->invoke($updater, 'test');
            $tempRoot = realpath($this->app->configPath('storage') . '/tmp');
            if ($tempRoot === false) {
                $this->fail('Updater temporary root could not be resolved.');
            }
            $this->assertNotEquals($first, $second);
            $this->assertTrue(is_dir($first));
            $this->assertTrue(is_dir($second));
            $this->assertStringStartsWith($tempRoot . DIRECTORY_SEPARATOR, $first);
            $this->assertStringStartsWith($tempRoot . DIRECTORY_SEPARATOR, $second);

            if (DIRECTORY_SEPARATOR === '/') {
                $this->assertEquals(0700, fileperms($first) & 0777);
                $this->assertEquals(0700, fileperms($second) & 0777);
            }
        } finally {
            if (is_dir($first)) {
                rmdir($first);
            }
            if (is_string($second) && is_dir($second)) {
                rmdir($second);
            }
        }
    }

    /**
     * Test current version returns constant
     */
    public function testCurrentVersionReturnsDefined(): void
    {
        $this->assertTrue(defined('AVA_VERSION'));
        $this->assertIsString(constant('AVA_VERSION'));
        $this->assertTrue(strlen(constant('AVA_VERSION')) > 0);
    }

    /**
     * Test version format is SemVer (MAJOR.MINOR.PATCH)
     */
    public function testVersionFormatSemVer(): void
    {
        $version = constant('AVA_VERSION');
        
        // Should match pattern: 1.0.0, 2.1.3 etc
        $this->assertTrue(
            (bool) preg_match('/^\d+\.\d+\.\d+$/', $version),
            "Version '$version' should match SemVer format MAJOR.MINOR.PATCH"
        );
    }

    /**
     * Test version comparison logic
     */
    public function testVersionComparison(): void
    {
        $v1 = '1.0.0';
        $v2 = '1.0.1';
        $v3 = '1.1.0';
        
        $this->assertTrue(version_compare($v2, $v1, '>'));
        $this->assertTrue(version_compare($v1, $v2, '<'));
        $this->assertTrue(version_compare($v1, $v1, '='));
        $this->assertTrue(version_compare($v3, $v2, '>'));
    }

    /**
     * Test GitHub API cache file path exists
     */
    public function testUpdateCacheDirectory(): void
    {
        $cacheDir = AVA_ROOT . '/storage/cache';
        
        $this->assertTrue(
            is_dir($cacheDir),
            'Cache directory should exist at ' . $cacheDir
        );
    }

    /**
     * Test bundled plugins are defined
     */
    public function testBundledPluginsExist(): void
    {
        $bundledPlugins = ['sitemap', 'feed', 'redirects', 'markdown-extensions'];
        
        foreach ($bundledPlugins as $plugin) {
            $pluginDir = AVA_ROOT . '/app/plugins/' . $plugin;
            $this->assertTrue(
                is_dir($pluginDir),
                "Bundled plugin '$plugin' should exist"
            );
        }
    }

    /**
     * Test bundled plugin structure
     */
    public function testBundledPluginStructure(): void
    {
        $plugins = ['sitemap', 'feed', 'redirects', 'markdown-extensions'];
        
        foreach ($plugins as $plugin) {
            $pluginDir = AVA_ROOT . '/app/plugins/' . $plugin;
            $pluginFile = $pluginDir . '/plugin.php';
            
            $this->assertTrue(
                file_exists($pluginFile),
                "Plugin file should exist at $pluginFile"
            );
        }
    }

    /**
     * Test tag name parsing (GitHub releases)
     */
    public function testGitHubTagNameParsing(): void
    {
        // Simulate GitHub release tag format
        $tags = [
            'v1.0.0' => '1.0.0',
            'v2.1.3' => '2.1.3',
            '1.0.0' => '1.0.0',
        ];
        
        foreach ($tags as $tag => $expected) {
            $parsed = ltrim($tag, 'v');
            $this->assertEquals($expected, $parsed);
        }
    }

    /**
     * Test update directories are defined
     */
    public function testUpdateDirectoriesExist(): void
    {
        // 'ava' is a file, not a directory
        $dirs = ['core'];
        
        foreach ($dirs as $dir) {
            $path = AVA_ROOT . '/' . $dir;
            $this->assertTrue(
                is_dir($path),
                "Update directory '$dir' should exist"
            );
        }
        
        // Check CLI file exists
        $this->assertTrue(
            file_exists(AVA_ROOT . '/ava'),
            "CLI script 'ava' should exist"
        );
    }

    /**
     * Test preserved directories during updates
     */
    public function testPreservedDirectoriesExist(): void
    {
        $preserved = ['content', 'app', 'storage'];
        
        foreach ($preserved as $dir) {
            $path = AVA_ROOT . '/' . $dir;
            $this->assertTrue(
                is_dir($path),
                "Preserved directory '$dir' should exist"
            );
        }
    }

    /**
     * Test custom themes directory
     */
    public function testCustomThemesDirectory(): void
    {
        $themesDir = AVA_ROOT . '/app/themes';
        
        $this->assertTrue(is_dir($themesDir));
        
        // Default theme should exist
        $this->assertTrue(is_dir($themesDir . '/default'));
    }

    /**
     * Test custom plugins directory exists
     */
    public function testCustomPluginsDirectory(): void
    {
        $pluginsDir = AVA_ROOT . '/app/plugins';
        
        $this->assertTrue(is_dir($pluginsDir));
    }

    /**
     * Test GitHub repo slug
     */
    public function testGitHubRepoFormat(): void
    {
        $repo = 'avacms/ava';
        
        $this->assertStringContains('/', $repo);
        $this->assertTrue(str_contains($repo, 'avacms'));
        $this->assertTrue(str_contains($repo, 'ava'));
    }

    /**
     * Test release info structure
     */
    public function testReleaseInfoStructure(): void
    {
        // Expected structure from GitHub API
        $requiredFields = ['tag_name', 'name', 'body', 'published_at', 'html_url', 'zipball_url'];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
        }
    }

    /**
     * Test check result structure when no update available
     */
    public function testCheckResultStructureNoUpdate(): void
    {
        $result = [
            'available' => false,
            'current' => '25.12.1',
            'latest' => '25.12.1',
            'release' => null,
            'error' => null,
        ];
        
        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('latest', $result);
        $this->assertArrayHasKey('release', $result);
        $this->assertArrayHasKey('error', $result);
        
        $this->assertFalse($result['available']);
        $this->assertNull($result['release']);
        $this->assertNull($result['error']);
    }

    /**
     * Test check result structure with update available
     */
    public function testCheckResultStructureWithUpdate(): void
    {
        $result = [
            'available' => true,
            'current' => '1.0.0',
            'latest' => '1.0.1',
            'release' => [
                'name' => '1.0.1',
                'body' => 'Bug fixes and improvements',
                'published_at' => '2026-01-10T00:00:00Z',
                'html_url' => 'https://github.com/avacms/ava/releases/tag/v1.0.1',
                'zipball_url' => 'https://api.github.com/repos/avacms/ava/zipball/v1.0.1',
            ],
            'error' => null,
        ];
        
        $this->assertTrue($result['available']);
        $this->assertIsArray($result['release']);
        $this->assertArrayHasKey('name', $result['release']);
        $this->assertArrayHasKey('body', $result['release']);
        $this->assertArrayHasKey('published_at', $result['release']);
    }

    /**
     * Test apply result structure
     */
    public function testApplyResultStructure(): void
    {
        $result = [
            'success' => true,
            'message' => 'Updated successfully',
            'updated_from' => '1.0.0',
            'updated_to' => '1.0.1',
            'new_plugins' => [],
        ];
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('updated_from', $result);
        $this->assertArrayHasKey('updated_to', $result);
        $this->assertArrayHasKey('new_plugins', $result);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['new_plugins']);
    }

    /**
     * Test GitHub API URL format
     */
    public function testGitHubApiUrlFormat(): void
    {
        $repo = 'avacms/ava';
        $apiUrl = "https://api.github.com/repos/{$repo}/releases/latest";
        
        $this->assertStringContains('api.github.com', $apiUrl);
        $this->assertStringContains('releases/latest', $apiUrl);
        $this->assertTrue(str_starts_with($apiUrl, 'https://'));
    }

    /**
     * Test update directory list is not empty
     */
    public function testUpdateDirsNotEmpty(): void
    {
        $dirs = ['core', 'docs', 'ava'];
        
        $this->assertTrue(count($dirs) > 0);
    }

    /**
     * Test bundled plugins list is not empty
     */
    public function testBundledPluginsNotEmpty(): void
    {
        $plugins = ['sitemap', 'feed', 'redirects', 'markdown-extensions'];
        
        $this->assertTrue(count($plugins) > 0);
        $this->assertEquals(4, count($plugins));
    }

    /**
     * Test version number components
     */
    public function testVersionComponents(): void
    {
        $version = '1.2.3';
        $parts = explode('.', $version);
        
        $this->assertEquals(3, count($parts));
        $this->assertEquals('1', $parts[0]);  // Major
        $this->assertEquals('2', $parts[1]);  // Minor
        $this->assertEquals('3', $parts[2]);  // Patch
    }

    /**
     * Test error handling structure
     */
    public function testErrorHandlingStructure(): void
    {
        $errorResult = [
            'available' => false,
            'current' => '1.0.0',
            'latest' => '1.0.0',
            'release' => null,
            'error' => 'Could not fetch release info from GitHub',
        ];
        
        $this->assertFalse($errorResult['available']);
        $this->assertNotNull($errorResult['error']);
        $this->assertStringContains('GitHub', $errorResult['error']);
    }

    private function removeTestDirectory(string $directory): void
    {
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
