<?php

declare(strict_types=1);

/**
 * Ava Redirects Plugin
 *
 * Manage custom URL redirects and status responses.
 * 
 * Features:
 * - Add/remove custom redirects via admin UI
 * - Supports 301/302 redirects and status-only responses (410, 418, 451, etc.)
 * - Redirects stored in storage/redirects.json
 * - High priority routing (checked before content)
 *
 * @package Ava\Plugins\Redirects
 */

use Ava\Application;
use Ava\Http\Request;
use Ava\Http\Response;
use Ava\Plugins\Hooks;

/**
 * Status codes supported by this plugin.
 * Codes with 'redirect' => true require a destination URL.
 */
const REDIRECT_STATUS_CODES = [
    301 => ['label' => 'Moved Permanently', 'redirect' => true, 'description' => 'SEO-friendly permanent redirect. Browsers cache this.'],
    302 => ['label' => 'Found (Temporary)', 'redirect' => true, 'description' => 'Temporary redirect. Not cached by browsers.'],
    307 => ['label' => 'Temporary Redirect', 'redirect' => true, 'description' => 'Like 302, but preserves request method (POST stays POST).'],
    308 => ['label' => 'Permanent Redirect', 'redirect' => true, 'description' => 'Like 301, but preserves request method.'],
    410 => ['label' => 'Gone', 'redirect' => false, 'description' => 'Resource permanently deleted. Search engines will de-index.'],
    418 => ['label' => "I'm a Teapot", 'redirect' => false, 'description' => 'The server refuses to brew coffee because it is a teapot. ☕'],
    451 => ['label' => 'Unavailable For Legal Reasons', 'redirect' => false, 'description' => 'Blocked due to legal demands (DMCA, court order, etc.).'],
    503 => ['label' => 'Service Unavailable', 'redirect' => false, 'description' => 'Temporarily down for maintenance.'],
];

return [
    'name' => 'Redirects',
    'version' => '1.0.0',
    'description' => 'Manage custom URL redirects',
    'author' => 'Ava CMS',

    'boot' => function (Application $app) {
        $router = $app->router();
        $storagePath = $app->configPath('storage');
        $redirectsFile = $storagePath . '/redirects.json';

        // Load redirects with error handling
        $loadRedirects = function () use ($redirectsFile): array|string {
            if (!file_exists($redirectsFile)) {
                return [];
            }
            $contents = file_get_contents($redirectsFile);
            $data = json_decode($contents, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return 'JSON Error: ' . json_last_error_msg() . '. Check storage/redirects.json for syntax errors.';
            }
            
            return is_array($data) ? $data : [];
        };

        // Save redirects
        $saveRedirects = function (array $redirects) use ($redirectsFile): void {
            file_put_contents($redirectsFile, json_encode($redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        };

        // Register redirects with router via hook (runs early in routing)
        Hooks::addFilter('router.before_match', function ($match, Request $request) use ($loadRedirects) {
            if ($match !== null) {
                return $match; // Already matched
            }

            $redirects = $loadRedirects();
            
            // Skip if there was a JSON error
            if (is_string($redirects)) {
                return null;
            }

            $path = '/' . trim($request->path(), '/');

            foreach ($redirects as $redirect) {
                $from = $redirect['from'] ?? '';
                if ($from === $path) {
                    $code = (int) ($redirect['code'] ?? 301);
                    $codeInfo = REDIRECT_STATUS_CODES[$code] ?? ['redirect' => true];
                    
                    // Check if this is a true redirect or a status-only response
                    if ($codeInfo['redirect']) {
                        return new \Ava\Routing\RouteMatch(
                            type: 'redirect',
                            redirectUrl: $redirect['to'] ?? '/',
                            redirectCode: $code
                        );
                    } else {
                        // Return a status-only response
                        $label = $codeInfo['label'] ?? 'Error';
                        $body = $redirect['body'] ?? "<h1>{$code} {$label}</h1>";
                        return Response::html($body, $code);
                    }
                }
            }

            return null;
        }, 5); // Priority 5 = run early

        // Register admin page
        Hooks::addFilter('admin.register_pages', function (array $pages) use ($app, $loadRedirects, $saveRedirects, $redirectsFile) {
            $pages['redirects'] = [
                'label' => 'Redirects',
                'icon' => 'swap_horiz',
                'section' => 'Plugins',
                'handler' => function (Request $request, Application $app, $controller) use ($loadRedirects, $saveRedirects, $redirectsFile) {
                    $redirectsData = $loadRedirects();
                    $message = null;
                    $error = null;
                    $jsonError = null;
                    $statusCodes = REDIRECT_STATUS_CODES;
                    $storagePath = $redirectsFile;
                    
                    // Check for JSON parsing errors
                    if (is_string($redirectsData)) {
                        $jsonError = $redirectsData;
                        $redirects = [];
                    } else {
                        $redirects = $redirectsData;
                    }

                    // Handle form submissions
                    if ($request->isMethod('POST')) {
                        $csrf = $request->post('_csrf', '');
                        $auth = $controller->auth();

                        if (!$auth->verifyCsrf($csrf)) {
                            $error = 'Invalid request. Please try again.';
                        } else {
                            $action = $request->post('action', '');

                            if ($action === 'add') {
                                $from = '/' . trim($request->post('from', ''), '/');
                                $to = trim($request->post('to', ''));
                                $code = (int) $request->post('code', 301);
                                $codeInfo = REDIRECT_STATUS_CODES[$code] ?? ['redirect' => true];
                                $isRedirect = $codeInfo['redirect'];

                                if (empty($from) || $from === '/') {
                                    $error = 'Source URL is required and cannot be root.';
                                } elseif ($isRedirect && empty($to)) {
                                    $error = 'Destination URL is required for redirect codes.';
                                } else {
                                    // Check for duplicates
                                    $exists = false;
                                    foreach ($redirects as $r) {
                                        if ($r['from'] === $from) {
                                            $exists = true;
                                            break;
                                        }
                                    }

                                    if ($exists) {
                                        $error = 'A redirect for this URL already exists.';
                                    } else {
                                        $newRedirect = [
                                            'from' => $from,
                                            'code' => $code,
                                            'created' => date('Y-m-d H:i:s'),
                                        ];
                                        
                                        // Only include 'to' for actual redirects
                                        if ($isRedirect) {
                                            $newRedirect['to'] = $to;
                                        }
                                        
                                        $redirects[] = $newRedirect;
                                        $saveRedirects($redirects);
                                        
                                        $codeLabel = $codeInfo['label'] ?? $code;
                                        $message = $isRedirect 
                                            ? "Redirect added: {$from} → {$to}" 
                                            : "Status response added: {$from} → {$code} {$codeLabel}";
                                    }
                                }
                            } elseif ($action === 'delete') {
                                $from = $request->post('from', '');
                                $redirects = array_filter($redirects, fn($r) => $r['from'] !== $from);
                                $redirects = array_values($redirects);
                                $saveRedirects($redirects);
                                $message = 'Redirect deleted.';
                            }

                            $auth->regenerateCsrf();
                        }
                    }

                    $csrf = $controller->auth()->csrfToken();

                    ob_start();
                    include __DIR__ . '/views/admin.php';
                    $html = ob_get_clean();

                    return Response::html($html);
                },
            ];
            return $pages;
        });
    },
];
