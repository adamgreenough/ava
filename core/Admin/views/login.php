<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ava Admin</title>
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-text: #1e293b;
            --color-muted: #64748b;
            --color-primary: #2563eb;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--color-card);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .login-header p {
            margin: 0.5rem 0 0;
            color: var(--color-muted);
            font-size: 0.875rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s;
        }
        input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }
        button:hover {
            background: #1d4ed8;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--color-danger);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .no-users {
            text-align: center;
            color: var(--color-muted);
            font-size: 0.875rem;
        }
        .no-users code {
            display: block;
            margin-top: 0.5rem;
            background: var(--color-bg);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>Ava Admin</h1>
            <p>Sign in to continue</p>
        </div>

        <?php if (!$hasUsers): ?>
            <div class="no-users">
                <p>No users configured. Create one with:</p>
                <code>php ava user:add email@example.com password</code>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($loginUrl) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit">Sign In</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
