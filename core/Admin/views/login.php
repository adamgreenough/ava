<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ava Admin</title>
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
            --accent: #3b82f6;
            --accent-hover: #2563eb;
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
                --accent: #3b82f6;
                --accent-hover: #2563eb;
                --danger: #dc2626;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .material-symbols-rounded {
            font-size: 20px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            width: 100%;
            max-width: 380px;
            margin: 1rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-weight: 500;
            font-size: 0.8125rem;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }
        label .material-symbols-rounded { font-size: 18px; }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            color: var(--text);
            transition: border-color 0.15s;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
        }
        input::placeholder {
            color: var(--text-muted);
        }
        button {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        button:hover {
            background: var(--accent-hover);
        }
        button .material-symbols-rounded { font-size: 20px; }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .no-users {
            text-align: center;
            padding: 1rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .no-users code {
            display: block;
            margin-top: 0.75rem;
            padding: 0.625rem 1rem;
            background: var(--bg);
            border-radius: 0.5rem;
            font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>✨ Ava</h1>
            <p>Sign in to admin</p>
        </div>

        <?php if (!$hasUsers): ?>
        <div class="no-users">
            <p>No admin users configured.</p>
            <code>./ava user:add email@example.com password</code>
        </div>
        <?php else: ?>

        <?php if ($error): ?>
        <div class="error">
            <span class="material-symbols-rounded">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($loginUrl) ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            
            <div class="form-group">
                <label for="email">
                    <span class="material-symbols-rounded">mail</span>
                    Email
                </label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">
                    <span class="material-symbols-rounded">lock</span>
                    Password
                </label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit">
                <span class="material-symbols-rounded">login</span>
                Sign In
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
