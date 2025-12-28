<?php

declare(strict_types=1);

/**
 * Site-level hooks and filters
 *
 * Register custom hooks here.
 */

use Ava\Plugins\Hooks;

// Example: Add custom data to template context
// Hooks::addFilter('render.context', function (array $context) {
//     $context['custom_data'] = 'Hello from hooks!';
//     return $context;
// });

// Example: Modify markdown output
// Hooks::addFilter('markdown.output', function (string $html) {
//     // Add target="_blank" to external links
//     return $html;
// });
