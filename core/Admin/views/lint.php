<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Lint - Ava Admin</title>
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-text: #1e293b;
            --color-muted: #64748b;
            --color-success: #10b981;
            --color-danger: #ef4444;
            --color-border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.5;
        }
        .admin-header {
            background: var(--color-card);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header h1 { margin: 0; font-size: 1.25rem; }
        .admin-header a { color: var(--color-muted); text-decoration: none; }
        .admin-main {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .card {
            background: var(--color-card);
            border-radius: 0.5rem;
            padding: 1.5rem;
            border: 1px solid var(--color-border);
        }
        .success { color: var(--color-success); }
        .error-list { margin: 0; padding: 0; list-style: none; }
        .error-list li {
            padding: 0.75rem;
            border-bottom: 1px solid var(--color-border);
            font-family: monospace;
            font-size: 0.875rem;
            color: var(--color-danger);
        }
        .error-list li:last-child { border-bottom: none; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            text-decoration: none;
            background: var(--color-border);
            color: var(--color-text);
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>⚡ Ava Admin / Lint</h1>
        <a href="<?= $admin_url ?>">← Back to Dashboard</a>
    </header>

    <main class="admin-main">
        <div class="card">
            <h2>Content Validation</h2>

            <?php if ($valid): ?>
                <p class="success">✓ All content files are valid.</p>
            <?php else: ?>
                <p>Found <?= count($errors) ?> error(s):</p>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p style="margin-top: 1.5rem;">
                <a href="<?= $admin_url ?>" class="btn">Back to Dashboard</a>
            </p>
        </div>
    </main>
</body>
</html>
