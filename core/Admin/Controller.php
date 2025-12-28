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
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_free' => $this->formatBytes(disk_free_space($this->app->path())),
            'extensions' => [
                'yaml' => extension_loaded('yaml'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
            ],
        ];
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
