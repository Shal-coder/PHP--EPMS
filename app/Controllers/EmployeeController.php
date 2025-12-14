<?php
/**
 * Employee Controller
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

class EmployeeController {
    
    public static function getAll(): array {
        RoleMiddleware::requireMinimum('manager');
        
        if (RoleMiddleware::isSuperAdmin()) {
            return Employee::getAll();
        }
        
        return Employee::getByManager(AuthMiddleware::userId());
    }

    public static function get(int $id): ?Employee {
        $employee = Employee::find($id);
        
        if (!$employee) return null;
        
        // Check access
        if (RoleMiddleware::isSuperAdmin()) return $employee;
        if (RoleMiddleware::isManager() && $employee->manager_user_id === AuthMiddleware::userId()) return $employee;
        if ($employee->user_id === AuthMiddleware::userId()) return $employee;
        
        return null;
    }

    public static function create(array $data): array {
        RoleMiddleware::require('super_admin');
        
        // Validate required fields
        $required = ['email', 'first_name', 'last_name', 'hire_date', 'base_salary'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required."];
            }
        }
        
        // Check email uniqueness
        if (User::findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        try {
            $employee = Employee::create($data);
            return ['success' => true, 'employee' => $employee, 'message' => 'Employee created successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create employee: ' . $e->getMessage()];
        }
    }

    public static function update(int $id, array $data): array {
        $employee = Employee::find($id);
        
        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found.'];
        }
        
        // Only super_admin can update employees
        RoleMiddleware::require('super_admin');
        
        try {
            $employee->update($data);
            
            // Update user info if provided
            if (isset($data['first_name']) || isset($data['last_name']) || isset($data['phone'])) {
                $user = User::find($employee->user_id);
                $user->update($data);
            }
            
            return ['success' => true, 'message' => 'Employee updated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update employee.'];
        }
    }

    public static function delete(int $id): array {
        RoleMiddleware::require('super_admin');
        
        $employee = Employee::find($id);
        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found.'];
        }
        
        try {
            $employee->update(['status' => 'terminated']);
            $user = User::find($employee->user_id);
            $user->update(['status' => 'inactive']);
            
            return ['success' => true, 'message' => 'Employee deactivated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete employee.'];
        }
    }

    public static function getProfile(): ?Employee {
        AuthMiddleware::require();
        return Employee::findByUserId(AuthMiddleware::userId());
    }
}
