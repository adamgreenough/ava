<?php

declare(strict_types=1);

/**
 * Ava Default Theme
 *
 * A minimal, clean theme for Ava CMS.
 */

// Theme can register shortcodes, hooks, etc.

use Ava\Plugins\Hooks;

// Example: Add a custom shortcode
// Ava\Application::getInstance()->shortcodes()->register('hello', fn() => 'Hello, World!');

// Example: Modify the context
// Hooks::addFilter('render.context', function (array $context) {
//     $context['theme_version'] = '1.0.0';
//     return $context;
// });
