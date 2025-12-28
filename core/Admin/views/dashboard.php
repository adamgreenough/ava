<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ava Admin</title>
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
                --accent-soft: rgba(59, 130, 246, 0.1);
                --success: #16a34a;
                --warning: #ca8a04;
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

        /* Layout */
        .layout { display: flex; min-height: 100vh; }
        
        /* Sidebar */
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

        /* Mobile */
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
            align-items: center;
            justify-content: center;
        }

        /* Logo */
        .logo {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        .logo h1 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .logo span { color: var(--text-dim); font-weight: 400; font-size: 0.6875rem; }

        /* Nav */
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
        .nav-item.active {
            background: var(--accent-soft);
            color: var(--accent);
        }
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
        .header h2 { font-size: 1.5rem; font-weight: 600; }
        .header-actions { display: flex; gap: 0.5rem; }

        /* Cards */
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        @media (max-width: 1100px) { .grid-3 { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.25rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .card-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-title .material-symbols-rounded { font-size: 18px; }

        /* Stats */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem; }
        @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.25rem;
        }
        .stat-label {
            font-size: 0.6875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .stat-label .material-symbols-rounded { font-size: 16px; }
        .stat-value { font-size: 1.75rem; font-weight: 600; margin-top: 0.375rem; }
        .stat-meta { font-size: 0.75rem; color: var(--text-dim); margin-top: 0.125rem; }

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
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); color: white; }
        .btn-secondary { background: var(--bg-hover); color: var(--text); border-color: var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-sm { padding: 0.375rem 0.625rem; font-size: 0.75rem; }

        /* Alert */
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .alert .material-symbols-rounded { font-size: 20px; }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: var(--success); }

        /* List */
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--border);
        }
        .list-item:last-child { border-bottom: none; }
        .list-label { color: var(--text-muted); font-size: 0.8125rem; }
        .list-value { font-weight: 500; font-size: 0.875rem; }
        .list-item-link { text-decoration: none; color: inherit; transition: background 0.15s; }
        .list-item-link:hover { background: var(--bg-hover); margin: 0 -1.25rem; padding-left: 1.25rem; padding-right: 1.25rem; }

        /* Content list */
        .content-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--border);
        }
        .content-item:last-child { border-bottom: none; }
        .content-title { font-weight: 500; font-size: 0.875rem; }
        .content-meta { font-size: 0.75rem; color: var(--text-dim); margin-top: 0.125rem; }

        /* Code */
        code {
            background: var(--bg-hover);
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .empty-state {
            color: var(--text-dim);
            font-size: 0.875rem;
            padding: 0.5rem 0;
        }

        /* System details */
        .system-details {
            border-top: 1px solid var(--border);
            margin-top: 0.5rem;
        }
        .system-details summary {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 0;
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
            list-style: none;
        }
        .system-details summary::-webkit-details-marker { display: none; }
        .system-details summary::after {
            content: '';
            margin-left: auto;
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid var(--text-dim);
            transition: transform 0.15s;
        }
        .system-details[open] summary::after {
            transform: rotate(180deg);
        }
        .system-details summary:hover {
            color: var(--text);
        }
        .system-details summary .material-symbols-rounded {
            font-size: 18px;
            color: var(--text-dim);
        }
        .system-details .details-content {
            padding-bottom: 0.5rem;
        }
        .system-details .list-item {
            padding: 0.375rem 0 0.375rem 1.75rem;
            border-bottom: none;
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
                <a href="<?= $admin_url ?>" class="nav-item active">
                    <span class="material-symbols-rounded">dashboard</span>
                    Dashboard
                </a>

                <div class="nav-section">Content</div>
                <?php foreach ($content as $type => $stats): ?>
                <a href="<?= $admin_url ?>/content/<?= $type ?>" class="nav-item">
                    <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
                    <?= ucfirst($type) ?>s
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

            <?php if (isset($_GET['action']) && $_GET['action'] === 'rebuild'): ?>
            <div class="alert alert-success">
                <span class="material-symbols-rounded">check_circle</span>
                Cache rebuilt successfully in <?= htmlspecialchars($_GET['time'] ?? '?') ?>ms
            </div>
            <?php endif; ?>

            <div class="header">
                <h2>Dashboard</h2>
                <div class="header-actions">
                    <a href="https://adamgreenough.github.io/ava/" target="_blank" class="btn btn-secondary">
                        <span class="material-symbols-rounded">menu_book</span>
                        Docs
                    </a>
                    <a href="/" target="_blank" class="btn btn-secondary">
                        <span class="material-symbols-rounded">open_in_new</span>
                        View Site
                    </a>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stat-grid">
                <?php
                $totalContent = array_sum(array_column($content, 'total'));
                $totalPublished = array_sum(array_column($content, 'published'));
                $totalDrafts = array_sum(array_column($content, 'draft'));
                $totalTerms = array_sum($taxonomies);
                ?>
                <div class="stat-card">
                    <div class="stat-label">
                        <span class="material-symbols-rounded">folder</span>
                        Total Content
                    </div>
                    <div class="stat-value"><?= $totalContent ?></div>
                    <div class="stat-meta"><?= count($content) ?> type<?= count($content) !== 1 ? 's' : '' ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <span class="material-symbols-rounded">public</span>
                        Published
                    </div>
                    <div class="stat-value"><?= $totalPublished ?></div>
                    <div class="stat-meta" style="color: var(--success)">Live on site</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <span class="material-symbols-rounded">edit_note</span>
                        Drafts
                    </div>
                    <div class="stat-value"><?= $totalDrafts ?></div>
                    <div class="stat-meta"><?= $totalDrafts > 0 ? 'Pending review' : 'None pending' ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <span class="material-symbols-rounded">label</span>
                        Terms
                    </div>
                    <div class="stat-value"><?= $totalTerms ?></div>
                    <div class="stat-meta"><?= count($taxonomies) ?> taxonom<?= count($taxonomies) !== 1 ? 'ies' : 'y' ?></div>
                </div>
            </div>

            <div class="grid grid-3">
                <!-- Cache -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-rounded">cached</span>
                            Cache
                        </span>
                        <?php if ($cache['fresh']): ?>
                            <span class="badge badge-success">Fresh</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Stale</span>
                        <?php endif; ?>
                    </div>
                    <div class="list-item">
                        <span class="list-label">Mode</span>
                        <span class="list-value"><?= htmlspecialchars($cache['mode']) ?></span>
                    </div>
                    <div class="list-item">
                        <span class="list-label">Last Built</span>
                        <span class="list-value"><?= htmlspecialchars($cache['built_at'] ?? 'Never') ?></span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <form method="POST" action="<?= $admin_url ?>/rebuild" style="display: inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span class="material-symbols-rounded">refresh</span>
                                Rebuild
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Content Types -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-rounded">folder_open</span>
                            Content Types
                        </span>
                        <a href="<?= $admin_url ?>/content/<?= array_key_first($content) ?? 'page' ?>" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <?php foreach ($content as $type => $stats): ?>
                    <a href="<?= $admin_url ?>/content/<?= $type ?>" class="list-item list-item-link">
                        <div>
                            <span class="list-label" style="color: var(--text); font-weight: 500;"><?= ucfirst($type) ?>s</span>
                            <div style="font-size: 0.6875rem; color: var(--text-dim); margin-top: 0.125rem;">
                                <span style="color: var(--success);"><?= $stats['published'] ?> published</span>
                                <?php if ($stats['draft'] > 0): ?>
                                · <span style="color: var(--warning);"><?= $stats['draft'] ?> drafts</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <strong style="font-size: 1.25rem;"><?= $stats['total'] ?></strong>
                            <span class="material-symbols-rounded" style="color: var(--text-dim); font-size: 18px;">chevron_right</span>
                        </span>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($content)): ?>
                    <p class="empty-state">No content types configured</p>
                    <?php endif; ?>
                </div>

                <!-- Taxonomies -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-rounded">sell</span>
                            Taxonomies
                        </span>
                    </div>
                    <?php foreach ($taxonomies as $name => $count): ?>
                    <div class="list-item">
                        <span class="list-label"><?= ucfirst($name) ?></span>
                        <span class="list-value"><?= $count ?> term<?= $count !== 1 ? 's' : '' ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($taxonomies)): ?>
                    <p class="empty-state">No taxonomies defined</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-2" style="margin-top: 1rem;">
                <!-- Recent Content -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-rounded">schedule</span>
                            Recent Content
                        </span>
                    </div>
                    <?php if (!empty($recentContent)): ?>
                        <?php foreach ($recentContent as $item): ?>
                        <div class="content-item">
                            <div>
                                <div class="content-title"><?= htmlspecialchars($item->title()) ?></div>
                                <div class="content-meta">
                                    <?= $item->type() ?> · <?= $item->date() ? $item->date()->format('M j, Y') : 'No date' ?>
                                </div>
                            </div>
                            <span class="badge <?= $item->isPublished() ? 'badge-success' : 'badge-muted' ?>">
                                <?= $item->status() ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state">No content yet</p>
                    <?php endif; ?>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <span class="material-symbols-rounded">dns</span>
                            System
                        </span>
                    </div>
                    
                    <?php 
                    $formatBytes = function($bytes) {
                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                        $i = 0;
                        while ($bytes >= 1024 && $i < count($units) - 1) {
                            $bytes /= 1024;
                            $i++;
                        }
                        return round($bytes, 2) . ' ' . $units[$i];
                    };
                    $renderTime = round((microtime(true) - $system['request_time']) * 1000, 2);
                    ?>
                    
                    <!-- Quick Stats -->
                    <div class="list-item">
                        <span class="list-label">Page Rendered</span>
                        <span class="list-value"><?= $renderTime ?>ms</span>
                    </div>
                    <div class="list-item">
                        <span class="list-label">Memory</span>
                        <span class="list-value"><?= $formatBytes($system['memory_used']) ?> / <?= $system['memory_limit'] ?></span>
                    </div>
                    <div class="list-item">
                        <span class="list-label">Theme</span>
                        <span class="list-value"><?= htmlspecialchars($theme ?? 'default') ?></span>
                    </div>
                    
                    <!-- Expandable Details -->
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">memory</span>
                            PHP &amp; Runtime
                        </summary>
                        <div class="details-content">
                            <div class="list-item">
                                <span class="list-label">PHP Version</span>
                                <span class="list-value"><?= $system['php_version'] ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">SAPI</span>
                                <span class="list-value"><?= $system['php_sapi'] ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Zend Engine</span>
                                <span class="list-value"><?= $system['zend_version'] ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Memory Peak</span>
                                <span class="list-value"><?= $formatBytes($system['memory_peak']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Max Execution</span>
                                <span class="list-value"><?= $system['max_execution_time'] ?>s</span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Upload Limit</span>
                                <span class="list-value"><?= $system['upload_max_filesize'] ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">POST Max</span>
                                <span class="list-value"><?= $system['post_max_size'] ?></span>
                            </div>
                        </div>
                    </details>
                    
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">computer</span>
                            Server
                        </summary>
                        <div class="details-content">
                            <div class="list-item">
                                <span class="list-label">OS</span>
                                <span class="list-value"><?= htmlspecialchars($system['os']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Hostname</span>
                                <span class="list-value"><?= htmlspecialchars($system['hostname']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Web Server</span>
                                <span class="list-value" style="font-size: 0.75rem;"><?= htmlspecialchars($system['server']) ?></span>
                            </div>
                            <?php if ($system['load_avg']): ?>
                            <div class="list-item">
                                <span class="list-label">Load Average</span>
                                <span class="list-value"><?= implode(', ', array_map(fn($v) => number_format($v, 2), $system['load_avg'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </details>
                    
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">storage</span>
                            Storage
                        </summary>
                        <div class="details-content">
                            <?php $diskPercent = round(100 - ($system['disk_free'] / $system['disk_total'] * 100), 1); ?>
                            <div class="list-item">
                                <span class="list-label">Disk Used</span>
                                <span class="list-value"><?= $diskPercent ?>% of <?= $formatBytes($system['disk_total']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Disk Free</span>
                                <span class="list-value"><?= $formatBytes($system['disk_free']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Content Dir</span>
                                <span class="list-value"><?= $formatBytes($system['content_size']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Cache Dir</span>
                                <span class="list-value"><?= $formatBytes($system['storage_size']) ?></span>
                            </div>
                        </div>
                    </details>
                    
                    <?php if (!empty($system['network'])): ?>
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">lan</span>
                            Network
                        </summary>
                        <div class="details-content">
                            <?php foreach ($system['network'] as $iface => $ip): ?>
                            <div class="list-item">
                                <span class="list-label"><?= htmlspecialchars($iface) ?></span>
                                <span class="list-value"><code><?= htmlspecialchars($ip) ?></code></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>
                    
                    <?php if ($system['opcache']): ?>
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">speed</span>
                            OPcache
                        </summary>
                        <div class="details-content">
                            <div class="list-item">
                                <span class="list-label">Status</span>
                                <span class="list-value">
                                    <?php if ($system['opcache']['enabled']): ?>
                                        <span style="color: var(--success);">Enabled</span>
                                    <?php else: ?>
                                        <span style="color: var(--warning);">Disabled</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Hit Rate</span>
                                <span class="list-value"><?= $system['opcache']['hit_rate'] ?>%</span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Cached Scripts</span>
                                <span class="list-value"><?= number_format($system['opcache']['cached_scripts']) ?></span>
                            </div>
                            <div class="list-item">
                                <span class="list-label">Memory Used</span>
                                <span class="list-value"><?= $formatBytes($system['opcache']['memory_used']) ?></span>
                            </div>
                        </div>
                    </details>
                    <?php endif; ?>
                    
                    <details class="system-details">
                        <summary>
                            <span class="material-symbols-rounded">extension</span>
                            Extensions (<?= count($system['extensions']) ?>)
                        </summary>
                        <div class="details-content">
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.25rem;">
                                <?php 
                                $requiredExts = ['json', 'mbstring', 'curl'];
                                $recommendedExts = ['gd', 'intl', 'opcache'];
                                foreach ($system['extensions'] as $ext): 
                                    $isRequired = in_array($ext, $requiredExts);
                                    $isRecommended = in_array($ext, $recommendedExts);
                                ?>
                                <span style="font-size: 0.6875rem; padding: 0.125rem 0.375rem; background: var(--bg-hover); border-radius: 0.25rem; <?= $isRequired ? 'border-left: 2px solid var(--success);' : ($isRecommended ? 'border-left: 2px solid var(--accent);' : '') ?>"><?= $ext ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Plugins -->
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-rounded">extension</span>
                        Plugins
                    </span>
                    <span class="badge badge-muted"><?= count($plugins ?? []) ?> active</span>
                </div>
                <?php if (!empty($plugins)): ?>
                    <?php foreach ($plugins as $plugin): ?>
                    <div class="list-item">
                        <span class="list-value"><?= htmlspecialchars($plugin) ?></span>
                        <span class="badge badge-success">Active</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No plugins active. Add to <code>plugins/</code> and enable in config.</p>
                <?php endif; ?>
            </div>

            <!-- CLI -->
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-rounded">terminal</span>
                        CLI Reference
                    </span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.5rem;">
                    <div><code>./ava status</code> <span style="color: var(--text-dim)">— Site status</span></div>
                    <div><code>./ava rebuild</code> <span style="color: var(--text-dim)">— Rebuild cache</span></div>
                    <div><code>./ava lint</code> <span style="color: var(--text-dim)">— Validate content</span></div>
                    <div><code>./ava make post "Title"</code> <span style="color: var(--text-dim)">— New post</span></div>
                    <div><code>./ava user:list</code> <span style="color: var(--text-dim)">— List users</span></div>
                    <div><code>./ava help</code> <span style="color: var(--text-dim)">— All commands</span></div>
                </div>
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
