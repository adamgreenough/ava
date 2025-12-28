<?php

declare(strict_types=1);

/**
 * Site-level shortcodes
 *
 * Register custom shortcodes here.
 */

use Ava\Application;

$app = Application::getInstance();
$shortcodes = $app->shortcodes();

// Example: [greeting name="World"]
// $shortcodes->register('greeting', function (array $attrs) {
//     $name = $attrs['name'] ?? 'World';
//     return "Hello, " . htmlspecialchars($name) . "!";
// });

// Example: [recent_posts count="5"]
// $shortcodes->register('recent_posts', function (array $attrs) use ($app) {
//     $count = (int) ($attrs['count'] ?? 5);
//     $posts = $app->repository()->published('posts');
//     // ... render posts
// });
