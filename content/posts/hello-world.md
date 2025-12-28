---
id: 01JGMK0000POST0000000000001
title: Hello World
slug: hello-world
status: published
date: 2024-12-28
excerpt: Welcome to Ava CMS. This is your first post.
category:
  - general
tag:
  - welcome
  - getting-started
---

This is your first post on Ava CMS.

## What's Next?

Here are some things you can try:

1. **Create more content** — Add `.md` files to `content/posts/`
2. **Customize the theme** — Edit templates in `themes/default/templates/`
3. **Add shortcodes** — Register custom shortcodes in `app/shortcodes.php`

## Shortcode Examples

Current year: [year]

Site name: [site_name]

## Code Example

```php
<?php
// Ava is built with modern PHP
$app = Ava\\Application::getInstance();
$items = $app->repository()->published('post');
```

Enjoy building with Ava!
