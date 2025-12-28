<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lint - Ava Admin</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✨</text></svg>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <style>
        :root {
            --bg: #09090b;
            --bg-card: #18181b;
            --bg-hover: #27272a;
            --border: #27272a;
            --text: #fafafa;
            --text-muted: #a1a1aa;
            --text-dim: #52525b;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #22c55e;
            --danger: #ef4444;
        }
        @media (prefers-color-scheme: light) {
            :root {
                --bg: #f8fafc;
                --bg-card: #ffffff;
                --bg-hover: #f1f5f9;
                --border: #e2e8f0;
                --text: #0f172a;
                --text-muted: #64748b;
                --text-dim: #94a3b8;
                --accent: #3b82f6;
                --accent-hover: #2563eb;
                --success: #16a34a;
                --danger: #dc2626;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            font-size: 14px;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { color: var(--accent-hover); }
        .material-symbols-rounded {
            font-size: 20px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
        }

        .admin-header {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header h1 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-header a {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .admin-header a:hover { color: var(--text); }

        .admin-main {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .success {
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
        }

        .error-count {
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .error-list {
            list-style: none;
        }
        .error-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
            font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
            font-size: 0.8125rem;
            color: var(--danger);
        }
        .error-list li:last-child { border-bottom: none; }
        .error-list .material-symbols-rounded { font-size: 18px; flex-shrink: 0; margin-top: 0.125rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            background: var(--bg-hover);
            color: var(--text);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn:hover { background: var(--border); }
        .btn .material-symbols-rounded { font-size: 18px; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>✨ Ava <span style="color: var(--text-dim); font-weight: 400;">/ Lint</span></h1>
        <a href="<?= $admin_url ?>">
            <span class="material-symbols-rounded">arrow_back</span>
            Dashboard
        </a>
    </header>

    <main class="admin-main">
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-rounded">check_circle</span>
                Content Validation
            </div>

            <?php if ($valid): ?>
                <p class="success">
                    <span class="material-symbols-rounded">verified</span>
                    All content files are valid.
                </p>
            <?php else: ?>
                <p class="error-count">Found <?= count($errors) ?> error<?= count($errors) !== 1 ? 's' : '' ?>:</p>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <span class="material-symbols-rounded">error</span>
                            <?= htmlspecialchars($error) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div style="margin-top: 1.5rem;">
                <a href="<?= $admin_url ?>" class="btn">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </main>
</body>
</html>
