<?php
/**
 * Role-Based Access Control Middleware
 */

require_once __DIR__ . '/AuthMiddleware.php';

class RoleMiddleware {
    
    private static array $roleHierarchy = [
        'super_admin' => 3,
        'manager' => 2,
        'employee' => 1
    ];

    public static function require(string|array $roles): void {
        AuthMiddleware::require();
        
        $currentRole = AuthMiddleware::role();
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        
        if (!in_array($currentRole, $allowedRoles)) {
            self::forbidden();
        }
    }

    public static function requireMinimum(string $minimumRole): void {
        AuthMiddleware::require();
        
        $currentRole = AuthMiddleware::role();
        $currentLevel = self::$roleHierarchy[$currentRole] ?? 0;
        $requiredLevel = self::$roleHierarchy[$minimumRole] ?? 0;
        
        if ($currentLevel < $requiredLevel) {
            self::forbidden();
        }
    }

    public static function isSuperAdmin(): bool {
        return AuthMiddleware::role() === 'super_admin';
    }

    public static function isManager(): bool {
        return AuthMiddleware::role() === 'manager';
    }

    public static function isEmployee(): bool {
        return AuthMiddleware::role() === 'employee';
    }

    public static function canManageEmployee(int $employeeUserId): bool {
        $role = AuthMiddleware::role();
        
        if ($role === 'super_admin') return true;
        
        if ($role === 'manager') {
            require_once __DIR__ . '/../Models/Employee.php';
            $employee = Employee::findByUserId($employeeUserId);
            return $employee && $employee->manager_user_id === AuthMiddleware::userId();
        }
        
        return false;
    }

    public static function canViewOwnResource(int $resourceUserId): bool {
        return AuthMiddleware::userId() === $resourceUserId;
    }

    private static function forbidden(): void {
        http_response_code(403);
        include __DIR__ . '/../../public/errors/403.php';
        exit;
    }
}