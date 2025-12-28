# Shortcodes

Shortcodes let you embed dynamic content in Markdown.

## Syntax

```markdown
# Inline (self-closing)
[year]

# With attributes
[snippet name="cta" heading="Join Us"]

# Paired (with content)
[button url="/contact"]Contact Us[/button]
```

## Built-in Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[year]` | Current year |
| `[date format="Y-m-d"]` | Current date |
| `[site_name]` | Site name from config |
| `[site_url]` | Site URL from config |
| `[email]addr@example.com[/email]` | Obfuscated email link |
| `[button url="..." class="..."]Text[/button]` | Styled link |
| `[snippet name="..." ...]` | Load PHP snippet |
| `[include file="..."]` | Include a partial |

## Snippets

Snippets are PHP files in `snippets/` that render HTML:

### Using a Snippet

```markdown
[snippet name="cta" heading="Get Started" button_url="/signup"]
```

### Creating a Snippet

`snippets/cta.php`:

```php
<?php

$heading = $params['heading'] ?? 'Default Heading';
$buttonUrl = $params['button_url'] ?? '/';
$buttonText = $params['button_text'] ?? 'Learn More';

?>
<div class="cta-box">
    <h3><?= htmlspecialchars($heading) ?></h3>
    <a href="<?= htmlspecialchars($buttonUrl) ?>">
        <?= htmlspecialchars($buttonText) ?>
    </a>
</div>
```

### Snippet Variables

| Variable | Description |
|----------|-------------|
| `$params` | Array of shortcode attributes |
| `$content` | Inner content (for paired shortcodes) |
| `$app` | Ava Application instance |

## Custom Shortcodes

Register in `app/shortcodes.php`:

```php
<?php

use Ava\Application;

$shortcodes = Application::getInstance()->shortcodes();

// Simple shortcode
$shortcodes->register('greeting', function (array $attrs) {
    $name = $attrs['name'] ?? 'World';
    return "Hello, " . htmlspecialchars($name) . "!";
});

// Using the app
$shortcodes->register('recent_posts', function (array $attrs) {
    $app = \\Ava\\Application::getInstance();
    $count = (int) ($attrs['count'] ?? 5);
    
    $posts = $app->repository()->published('post');
    $posts = array_slice($posts, 0, $count);
    
    $html = '<ul class="recent-posts">';
    foreach ($posts as $post) {
        $url = $app->router()->urlFor('post', $post->slug());
        $html .= '<li><a href="' . $url . '">' . htmlspecialchars($post->title()) . '</a></li>';
    }
    $html .= '</ul>';
    
    return $html;
});
```

## Processing Order

1. Markdown is rendered to HTML
2. Shortcodes are processed in the HTML output

This means shortcodes can output raw HTML without escaping.

## Security Notes

- Snippet names are validated (no path traversal)
- Snippets can be disabled via `security.shortcodes.allow_php_snippets`
- Unknown shortcodes are left as-is (not an error)
