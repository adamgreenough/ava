<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($type) ?>s - Ava Admin</title>
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
            --accent-soft: rgba(59, 130, 246, 0.15);
            --success: #22c55e;
            --warning: #eab308;
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
                --accent-soft: rgba(59, 130, 246, 0.1);
                --success: #16a34a;
                --warning: #ca8a04;
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

        /* Layout */
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            transform: translateX(0);
            transition: transform 0.2s ease;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }
        .main { flex: 1; padding: 2rem; margin-left: 240px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-backdrop.open { display: block; }
            .main { margin-left: 0; padding: 1rem; }
            .mobile-header { display: flex !important; }
        }

        .mobile-header {
            display: none;
            position: sticky;
            top: 0;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1rem;
            align-items: center;
            gap: 0.75rem;
            z-index: 50;
            margin: -1rem -1rem 1rem;
        }
        .mobile-header h1 { font-size: 1rem; font-weight: 600; }
        .menu-btn {
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
        }

        /* Logo & Nav */
        .logo {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        .logo h1 { font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .logo span { color: var(--text-dim); font-weight: 400; font-size: 0.6875rem; }

        .nav { flex: 1; padding: 0.75rem 0; overflow-y: auto; }
        .nav-section {
            padding: 1rem 1.25rem 0.5rem;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-dim);
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1.25rem;
            color: var(--text-muted);
            transition: all 0.15s;
            font-size: 0.875rem;
        }
        .nav-item:hover { background: var(--bg-hover); color: var(--text); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); }
        .nav-item.active .material-symbols-rounded { color: var(--accent); }
        .nav-item .material-symbols-rounded { color: var(--text-dim); font-size: 20px; }
        .nav-item:hover .material-symbols-rounded { color: var(--text-muted); }
        .nav-count {
            margin-left: auto;
            background: var(--bg-hover);
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.6875rem;
            color: var(--text-dim);
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.8125rem;
        }
        .sidebar-footer a:hover { color: var(--text); }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header h2 { font-size: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .header-actions { display: flex; gap: 0.5rem; }

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            background: var(--bg-hover);
            border-bottom: 1px solid var(--border);
        }
        .table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background: var(--bg-hover); }

        .table-title { font-weight: 500; }
        .table-meta { font-size: 0.75rem; color: var(--text-dim); margin-top: 0.125rem; }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 500;
        }
        .badge-success { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .badge-warning { background: rgba(234, 179, 8, 0.15); color: var(--warning); }
        .badge-muted { background: var(--bg-hover); color: var(--text-muted); }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn .material-symbols-rounded { font-size: 18px; }
        .btn-secondary { background: var(--bg-hover); color: var(--text); border-color: var(--border); }
        .btn-secondary:hover { background: var(--border); }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-dim);
        }
        .empty-state .material-symbols-rounded { font-size: 48px; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state p { font-size: 0.9375rem; }
        .empty-state code {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.5rem 0.875rem;
            background: var(--bg-hover);
            border-radius: 0.5rem;
            font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <h1>✨ Ava <span>v1.0</span></h1>
            </div>
            <nav class="nav">
                <div class="nav-section">Overview</div>
                <a href="<?= $admin_url ?>" class="nav-item">
                    <span class="material-symbols-rounded">dashboard</span>
                    Dashboard
                </a>

                <div class="nav-section">Content</div>
                <?php foreach ($allContent as $t => $stats): ?>
                <a href="<?= $admin_url ?>/content/<?= $t ?>" class="nav-item <?= $t === $type ? 'active' : '' ?>">
                    <span class="material-symbols-rounded"><?= $t === 'page' ? 'description' : 'article' ?></span>
                    <?= ucfirst($t) ?>s
                    <span class="nav-count"><?= $stats['total'] ?></span>
                </a>
                <?php endforeach; ?>

                <div class="nav-section">Tools</div>
                <a href="<?= $admin_url ?>/lint" class="nav-item">
                    <span class="material-symbols-rounded">check_circle</span>
                    Lint Content
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= $admin_url ?>/logout">
                    <span class="material-symbols-rounded">logout</span>
                    Sign Out
                </a>
            </div>
        </aside>

        <main class="main">
            <div class="mobile-header">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <h1>✨ Ava</h1>
            </div>

            <div class="header">
                <h2>
                    <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
                    <?= ucfirst($type) ?>s
                </h2>
                <div class="header-actions">
                    <a href="<?= $admin_url ?>" class="btn btn-secondary">
                        <span class="material-symbols-rounded">arrow_back</span>
                        Dashboard
                    </a>
                </div>
            </div>

            <div class="card">
                <?php if (!empty($items)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="table-title"><?= htmlspecialchars($item->title()) ?></div>
                                <div class="table-meta"><?= htmlspecialchars($item->slug()) ?></div>
                            </td>
                            <td>
                                <?= $item->date() ? $item->date()->format('M j, Y') : '—' ?>
                            </td>
                            <td>
                                <span class="badge <?= $item->isPublished() ? 'badge-success' : 'badge-muted' ?>">
                                    <?= $item->status() ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
                    <p>No <?= $type ?>s yet.</p>
                    <code>./ava make <?= $type ?> "Your Title"</code>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.querySelector('.sidebar-backdrop').classList.toggle('open');
        }
    </script>
</body>
</html>
