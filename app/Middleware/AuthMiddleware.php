<?php
/**
 * Authentication Middleware
 * Handles session management and authentication checks
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/User.php';

class AuthMiddleware {
    
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::init();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }

    public static function user(): ?User {
        if (!self::check()) return null;
        return User::find($_SESSION['user_id']);
    }

    public static function userId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public static function login(User $user): void {
        self::init();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_name'] = $user->getFullName();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_time'] = time();
    }

    public static function logout(): void {
        self::init();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function require(): void {
        if (!self::check()) {
            header('Location: /payroll-management-system/login.php');
            exit;
        }
    }

    public static function csrfToken(): string {
        self::init();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }
}