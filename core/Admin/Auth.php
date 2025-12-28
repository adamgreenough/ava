<?php

declare(strict_types=1);

namespace Ava\Admin;

/**
 * Simple session-based authentication for admin.
 */
final class Auth
{
    private const SESSION_KEY = 'ava_admin_user';
    private const CSRF_KEY = 'ava_csrf_token';

    private string $usersFile;
    private ?array $users = null;

    public function __construct(string $usersFile)
    {
        $this->usersFile = $usersFile;
    }

    /**
     * Start session if not already started.
     */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,          // Session cookie
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,       // Prevent JavaScript access
                'samesite' => 'Lax',      // CSRF protection
            ]);
            session_start();
        }
    }

    /**
     * Check if a user is logged in.
     */
    public function check(): bool
    {
        $this->startSession();
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Get the current user email.
     */
    public function user(): ?string
    {
        $this->startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get the current user's data.
     */
    public function userData(): ?array
    {
        $email = $this->user();
        if ($email === null) {
            return null;
        }

        $users = $this->loadUsers();
        return $users[$email] ?? null;
    }

    /**
     * Attempt to log in with email and password.
     */
    public function attempt(string $email, string $password): bool
    {
        $users = $this->loadUsers();

        if (!isset($users[$email])) {
            // Prevent timing attacks
            password_verify($password, '$2y$10$dummyhashtopreventtimingattacks');
            return false;
        }

        $user = $users[$email];

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Regenerate session ID to prevent fixation
        $this->startSession();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $email;

        // Update last login time
        $this->updateLastLogin($email);

        return true;
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->startSession();
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    /**
     * Generate a CSRF token.
     */
    public function csrfToken(): string
    {
        $this->startSession();

        if (!isset($_SESSION[self::CSRF_KEY])) {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_KEY];
    }

    /**
     * Verify a CSRF token.
     */
    public function verifyCsrf(string $token): bool
    {
        $this->startSession();
        return isset($_SESSION[self::CSRF_KEY]) && hash_equals($_SESSION[self::CSRF_KEY], $token);
    }

    /**
     * Regenerate CSRF token (after form submission).
     */
    public function regenerateCsrf(): void
    {
        $this->startSession();
        $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
    }

    /**
     * Get all users (for admin display).
     */
    public function allUsers(): array
    {
        return $this->loadUsers();
    }

    /**
     * Update user's last login time.
     */
    private function updateLastLogin(string $email): void
    {
        $users = $this->loadUsers();
        if (!isset($users[$email])) {
            return;
        }

        $users[$email]['last_login'] = date('Y-m-d H:i:s');

        // Write back to file
        $content = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * Users Configuration\n *\n * Managed by CLI. Do not edit manually.\n */\n\nreturn " . var_export($users, true) . ";\n";
        file_put_contents($this->usersFile, $content);

        // Update cache
        $this->users = $users;
    }

    /**
     * Load users from config file.
     */
    private function loadUsers(): array
    {
        if ($this->users === null) {
            if (file_exists($this->usersFile)) {
                $this->users = require $this->usersFile;
                if (!is_array($this->users)) {
                    $this->users = [];
                }
            } else {
                $this->users = [];
            }
        }

        return $this->users;
    }

    /**
     * Check if any users exist.
     */
    public function hasUsers(): bool
    {
        return count($this->loadUsers()) > 0;
    }
}
