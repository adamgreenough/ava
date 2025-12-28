<?php

declare(strict_types=1);

namespace Ava\Admin;

use Ava\Application;
use Ava\Http\Request;
use Ava\Http\Response;

/**
 * Admin Controller
 *
 * Read-only dashboard + safe tooling.
 * NOT an editor. Effectively a web UI wrapper around CLI.
 */
final class Controller
{
    private Application $app;
    private Auth $auth;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->auth = new Auth($app->path('app/config/users.php'));
    }

    /**
     * Get the auth instance.
     */
    public function auth(): Auth
    {
        return $this->auth;
    }

    /**
     * Login page.
     */
    public function login(Request $request): Response
    {
        // Already logged in?
        if ($this->auth->check()) {
            return Response::redirect($this->adminUrl());
        }

        $error = null;

        // Handle login attempt
        if ($request->isMethod('POST')) {
            $csrf = $request->post('_csrf', '');
            if (!$this->auth->verifyCsrf($csrf)) {
                $error = 'Invalid request. Please try again.';
            } else {
                $email = $request->post('email', '');
                $password = $request->post('password', '');

                if ($this->auth->attempt($email, $password)) {
                    $this->auth->regenerateCsrf();
                    return Response::redirect($this->adminUrl());
                }

                $error = 'Invalid email or password.';
            }
        }

        return Response::html($this->render('login', [
            'error' => $error,
            'csrf' => $this->auth->csrfToken(),
            'loginUrl' => $this->adminUrl() . '/login',
            'hasUsers' => $this->auth->hasUsers(),
        ]));
    }

    /**
     * Logout action.
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect($this->adminUrl() . '/login');
    }

    /**
     * Dashboard - main admin page.
     */
    public function dashboard(Request $request): Response
    {
        $data = [
            'site' => [
                'name' => $this->app->config('site.name'),
                'url' => $this->app->config('site.base_url'),
            ],
            'cache' => $this->getCacheStatus(),
            'content' => $this->getContentStats(),
            'taxonomies' => $this->getTaxonomyStats(),
            'system' => $this->getSystemInfo(),
            'recentContent' => $this->getRecentContent(),
            'plugins' => $this->getActivePlugins(),
            'theme' => $this->app->config('theme', 'default'),
            'csrf' => $this->auth->csrfToken(),
            'user' => $this->auth->user(),
        ];

        return Response::html($this->render('dashboard', $data));
    }

    /**
     * Rebuild cache action.
     */
    public function rebuild(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return Response::redirect($this->adminUrl());
        }

        // CSRF check
        $csrf = $request->post('_csrf', '');
        if (!$this->auth->verifyCsrf($csrf)) {
            return Response::redirect($this->adminUrl() . '?error=csrf');
        }

        $start = microtime(true);
        $this->app->indexer()->rebuild();
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->auth->regenerateCsrf();
        return Response::redirect($this->adminUrl() . '?action=rebuild&time=' . $elapsed);
    }

    /**
     * Content list page.
     */
    public function contentList(Request $request, string $type): ?Response
    {
        $repository = $this->app->repository();
        $types = $repository->types();

        // Check if type exists
        if (!in_array($type, $types)) {
            return null; // 404
        }

        $items = $repository->all($type);

        // Sort by date descending
        usort($items, function($a, $b) {
            $aDate = $a->date();
            $bDate = $b->date();
            if (!$aDate && !$bDate) return 0;
            if (!$aDate) return 1;
            if (!$bDate) return -1;
            return $bDate->getTimestamp() - $aDate->getTimestamp();
        });

        $data = [
            'type' => $type,
            'items' => $items,
            'allContent' => $this->getContentStats(),
        ];

        return Response::html($this->render('content-list', $data));
    }

    /**
     * Lint content action.
     */
    public function lint(Request $request): Response
    {
        $errors = $this->app->indexer()->lint();

        $data = [
            'errors' => $errors,
            'valid' => empty($errors),
        ];

        return Response::html($this->render('lint', $data));
    }

    // -------------------------------------------------------------------------
    // Data gathering
    // -------------------------------------------------------------------------

    private function getCacheStatus(): array
    {
        $cachePath = $this->app->configPath('storage') . '/cache';
        $fingerprintPath = $cachePath . '/fingerprint.json';

        $status = [
            'mode' => $this->app->config('cache.mode', 'auto'),
            'fresh' => false,
            'built_at' => null,
        ];

        if (file_exists($fingerprintPath)) {
            $status['fresh'] = $this->app->indexer()->isCacheFresh();
        }

        if (file_exists($cachePath . '/content_index.php')) {
            $status['built_at'] = date('Y-m-d H:i:s', filemtime($cachePath . '/content_index.php'));
        }

        return $status;
    }

    private function getContentStats(): array
    {
        $repository = $this->app->repository();
        $stats = [];

        foreach ($repository->types() as $type) {
            $stats[$type] = [
                'total' => $repository->count($type),
                'published' => $repository->count($type, 'published'),
                'draft' => $repository->count($type, 'draft'),
            ];
        }

        return $stats;
    }

    private function getTaxonomyStats(): array
    {
        $repository = $this->app->repository();
        $stats = [];

        foreach ($repository->taxonomies() as $taxonomy) {
            $terms = $repository->terms($taxonomy);
            $stats[$taxonomy] = count($terms);
        }

        return $stats;
    }

    private function getSystemInfo(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
        
        // Get network interfaces if available
        $networkInfo = [];
        if (function_exists('net_get_interfaces')) {
            $interfaces = @net_get_interfaces();
            if ($interfaces) {
                foreach ($interfaces as $name => $iface) {
                    if (isset($iface['unicast']) && $name !== 'lo') {
                        foreach ($iface['unicast'] as $addr) {
                            if (isset($addr['address']) && filter_var($addr['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                $networkInfo[$name] = $addr['address'];
                            }
                        }
                    }
                }
            }
        }

        // OPcache info
        $opcacheEnabled = function_exists('opcache_get_status');
        $opcacheStats = null;
        if ($opcacheEnabled) {
            $status = @opcache_get_status(false);
            if ($status) {
                $opcacheStats = [
                    'enabled' => $status['opcache_enabled'] ?? false,
                    'memory_used' => $status['memory_usage']['used_memory'] ?? 0,
                    'memory_free' => $status['memory_usage']['free_memory'] ?? 0,
                    'hit_rate' => isset($status['opcache_statistics']['opcache_hit_rate']) 
                        ? round($status['opcache_statistics']['opcache_hit_rate'], 2) : 0,
                    'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                ];
            }
        }

        // Disk usage for content directory
        $contentPath = $this->app->configPath('content');
        $contentSize = $this->getDirectorySize($contentPath);
        $storagePath = $this->app->configPath('storage');
        $storageSize = $this->getDirectorySize($storagePath);

        return [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'memory_used' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_free' => disk_free_space($this->app->path()),
            'disk_total' => disk_total_space($this->app->path()),
            'content_size' => $contentSize,
            'storage_size' => $storageSize,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
            'hostname' => gethostname(),
            'load_avg' => $loadAvg,
            'network' => $networkInfo,
            'opcache' => $opcacheStats,
            'extensions' => get_loaded_extensions(),
            'extensions_check' => [
                'yaml' => extension_loaded('yaml'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'curl' => extension_loaded('curl'),
                'gd' => extension_loaded('gd'),
                'intl' => extension_loaded('intl'),
                'opcache' => $opcacheEnabled,
            ],
            'zend_version' => zend_version(),
            'include_path' => get_include_path(),
            'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
        ];
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        if (!is_dir($path)) return 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    private function getRecentContent(int $limit = 5): array
    {
        $repository = $this->app->repository();
        $all = [];

        foreach ($repository->types() as $type) {
            $items = $repository->all($type);
            foreach ($items as $item) {
                $all[] = $item;
            }
        }

        // Sort by date descending
        usort($all, function($a, $b) {
            $aDate = $a->date();
            $bDate = $b->date();
            if (!$aDate && !$bDate) return 0;
            if (!$aDate) return 1;
            if (!$bDate) return -1;
            return $bDate->getTimestamp() - $aDate->getTimestamp();
        });

        return array_slice($all, 0, $limit);
    }

    private function getActivePlugins(): array
    {
        $plugins = $this->app->config('plugins', []);
        return is_array($plugins) ? $plugins : [];
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    private function render(string $view, array $data): string
    {
        $data['admin_url'] = $this->adminUrl();
        $data['ava'] = $this->app;

        extract($data);

        ob_start();
        include __DIR__ . '/views/' . $view . '.php';
        return ob_get_clean();
    }

    private function adminUrl(): string
    {
        return $this->app->config('admin.path', '/admin');
    }

    private function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
