<?php

declare(strict_types=1);

namespace Ava\Admin;

use Ava\Application;
use Ava\Http\Request;
use Ava\Http\Response;
use Ava\Routing\RouteMatch;

/**
 * Admin Router
 *
 * Registers admin routes when admin is enabled.
 */
final class AdminRouter
{
    private Application $app;
    private Controller $controller;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->controller = new Controller($app);
    }

    /**
     * Register admin routes with the main router.
     */
    public function register(): void
    {
        if (!$this->app->config('admin.enabled', false)) {
            return;
        }

        $basePath = $this->app->config('admin.path', '/admin');
        $router = $this->app->router();

        // Dashboard
        $router->addRoute($basePath, function (Request $request) {
            return $this->handle('dashboard', $request);
        });

        // Rebuild action
        $router->addRoute($basePath . '/rebuild', function (Request $request) {
            return $this->handle('rebuild', $request);
        });

        // Lint action
        $router->addRoute($basePath . '/lint', function (Request $request) {
            return $this->handle('lint', $request);
        });
    }

    /**
     * Handle an admin request.
     */
    private function handle(string $action, Request $request): ?RouteMatch
    {
        // Authentication check would go here
        // For now, we trust the config check

        $response = match ($action) {
            'dashboard' => $this->controller->dashboard($request),
            'rebuild' => $this->controller->rebuild($request),
            'lint' => $this->controller->lint($request),
            default => null,
        };

        if ($response === null) {
            return null;
        }

        // Return a special RouteMatch that carries the response
        return new RouteMatch(
            type: 'admin',
            template: '__raw__',
            params: ['response' => $response]
        );
    }
}
