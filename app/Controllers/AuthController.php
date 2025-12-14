<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class AuthController {
    
    public static function login(string $email, string $password): array {
        // Check if account is locked
        if (User::isLocked($email)) {
            return ['success' => false, 'message' => 'Account is locked. Try again later.'];
        }
        
        // Verify credentials
        $user = User::verifyPassword($email, $password);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        if ($user->status !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive.'];
        }
        
        // Login successful
        AuthMiddleware::login($user);
        
        // Determine redirect based on role (include project folder)
        $base = '/payroll-management-system';
        $redirect = match($user->role) {
            'super_admin' => $base . '/front_end/admin/dashboard.php',
            'manager' => $base . '/manager/dashboard.php',
            'employee' => $base . '/front_end/employee/dashboard.php',
            default => $base . '/login.php'
        };
        
        return ['success' => true, 'redirect' => $redirect, 'user' => $user];
    }

    public static function logout(): void {
        AuthMiddleware::logout();
        header('Location: /payroll-management-system/login.php');
        exit;
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Verify current password
        $verified = User::verifyPassword($user->email, $currentPassword);
        if (!$verified) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }
        
        // Update password
        $user->update(['password' => $newPassword]);
        
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    public static function resetPassword(string $email): array {
        $user = User::findByEmail($email);
        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
        }
        
        // Generate reset token (in production, send via email)
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600);
        
        Database::query(
            "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?",
            [$token, $expiry, $user->id]
        );
        
        // In production: send email with reset link
        return ['success' => true, 'message' => 'If the email exists, a reset link will be sent.'];
    }
}
