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

        // Login (public)
        $router->addRoute($basePath . '/login', function (Request $request) {
            return $this->handle('login', $request, requireAuth: false);
        });

        // Logout (public, but needs session)
        $router->addRoute($basePath . '/logout', function (Request $request) {
            return $this->handle('logout', $request, requireAuth: false);
        });

        // Dashboard (protected)
        $router->addRoute($basePath, function (Request $request) {
            return $this->handle('dashboard', $request);
        });

        // Rebuild action (protected)
        $router->addRoute($basePath . '/rebuild', function (Request $request) {
            return $this->handle('rebuild', $request);
        });

        // Lint action (protected)
        $router->addRoute($basePath . '/lint', function (Request $request) {
            return $this->handle('lint', $request);
        });
    }

    /**
     * Handle an admin request.
     */
    private function handle(string $action, Request $request, bool $requireAuth = true): ?RouteMatch
    {
        $auth = $this->controller->auth();

        // Check authentication for protected routes
        if ($requireAuth && !$auth->check()) {
            $loginUrl = $this->app->config('admin.path', '/admin') . '/login';
            $response = Response::redirect($loginUrl);
            return new RouteMatch(
                type: 'admin',
                template: '__raw__',
                params: ['response' => $response]
            );
        }

        $response = match ($action) {
            'login' => $this->controller->login($request),
            'logout' => $this->controller->logout($request),
            'dashboard' => $this->controller->dashboard($request),
            'rebuild' => $this->controller->rebuild($request),
            'lint' => $this->controller->lint($request),
            default => null,
        };

        if ($response === null) {
            return null;
        }

        return new RouteMatch(
            type: 'admin',
            template: '__raw__',
            params: ['response' => $response]
        );
    }
}
