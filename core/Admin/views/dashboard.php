<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ava Admin</title>
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-text: #1e293b;
            --color-muted: #64748b;
            --color-primary: #2563eb;
            --color-success: #10b981;
            --color-warning: #f59e0b;
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
        .admin-header h1 {
            margin: 0;
            font-size: 1.25rem;
        }
        .admin-header a {
            color: var(--color-muted);
            text-decoration: none;
        }
        .admin-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: var(--color-card);
            border-radius: 0.5rem;
            padding: 1.5rem;
            border: 1px solid var(--color-border);
        }
        .card h2 {
            margin: 0 0 1rem;
            font-size: 1rem;
            color: var(--color-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat {
            font-size: 2rem;
            font-weight: 600;
        }
        .stat-label {
            color: var(--color-muted);
            font-size: 0.875rem;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--color-border);
        }
        .stat-row:last-child {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-muted { background: #f1f5f9; color: #475569; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: var(--color-primary);
            color: white;
        }
        .btn-secondary {
            background: var(--color-border);
            color: var(--color-text);
        }
        .actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>⚡ Ava Admin</h1>
        <a href="/" target="_blank">View Site →</a>
    </header>

    <main class="admin-main">
        <?php if (isset($_GET['action']) && $_GET['action'] === 'rebuild'): ?>
            <div class="alert alert-success">
                Cache rebuilt successfully in <?= htmlspecialchars($_GET['time'] ?? '?') ?>ms
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Cache Status -->
            <div class="card">
                <h2>Cache</h2>
                <div class="stat-row">
                    <span>Status</span>
                    <?php if ($cache['fresh']): ?>
                        <span class="badge badge-success">Fresh</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Stale</span>
                    <?php endif; ?>
                </div>
                <div class="stat-row">
                    <span>Mode</span>
                    <span class="badge badge-muted"><?= htmlspecialchars($cache['mode']) ?></span>
                </div>
                <div class="stat-row">
                    <span>Built</span>
                    <span><?= htmlspecialchars($cache['built_at'] ?? 'Never') ?></span>
                </div>
                <div class="actions">
                    <form method="POST" action="<?= $admin_url ?>/rebuild">
                        <button type="submit" class="btn btn-primary">Rebuild Cache</button>
                    </form>
                    <a href="<?= $admin_url ?>/lint" class="btn btn-secondary">Lint Content</a>
                </div>
            </div>

            <!-- Content Stats -->
            <div class="card">
                <h2>Content</h2>
                <?php foreach ($content as $type => $stats): ?>
                    <div class="stat-row">
                        <span><?= htmlspecialchars(ucfirst($type)) ?></span>
                        <span>
                            <strong><?= $stats['total'] ?></strong>
                            <span class="stat-label">
                                (<?= $stats['published'] ?> published, <?= $stats['draft'] ?> drafts)
                            </span>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($content)): ?>
                    <p style="color: var(--color-muted)">No content found</p>
                <?php endif; ?>
            </div>

            <!-- Taxonomies -->
            <div class="card">
                <h2>Taxonomies</h2>
                <?php foreach ($taxonomies as $name => $count): ?>
                    <div class="stat-row">
                        <span><?= htmlspecialchars(ucfirst($name)) ?></span>
                        <span><?= $count ?> terms</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($taxonomies)): ?>
                    <p style="color: var(--color-muted)">No taxonomies defined</p>
                <?php endif; ?>
            </div>

            <!-- System Info -->
            <div class="card">
                <h2>System</h2>
                <div class="stat-row">
                    <span>PHP</span>
                    <span><?= htmlspecialchars($system['php_version']) ?></span>
                </div>
                <div class="stat-row">
                    <span>Memory Limit</span>
                    <span><?= htmlspecialchars($system['memory_limit']) ?></span>
                </div>
                <div class="stat-row">
                    <span>Disk Free</span>
                    <span><?= htmlspecialchars($system['disk_free']) ?></span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
