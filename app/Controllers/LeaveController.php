<?php
/**
 * Leave Controller
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Leave.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

class LeaveController {
    
    public static function getMyLeaves(): array {
        AuthMiddleware::require();
        $employee = Employee::findByUserId(AuthMiddleware::userId());
        return $employee ? Leave::getByEmployee($employee->id) : [];
    }

    public static function getPending(): array {
        RoleMiddleware::requireMinimum('manager');
        
        if (RoleMiddleware::isSuperAdmin()) {
            return Leave::getPending();
        }
        
        return Leave::getPending(AuthMiddleware::userId());
    }

    public static function request(array $data): array {
        AuthMiddleware::require();
        
        $employee = Employee::findByUserId(AuthMiddleware::userId());
        if (!$employee) {
            return ['success' => false, 'message' => 'Employee profile not found.'];
        }
        
        // Validate dates
        if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
            return ['success' => false, 'message' => 'End date must be after start date.'];
        }
        
        if (strtotime($data['start_date']) < strtotime('today')) {
            return ['success' => false, 'message' => 'Cannot request leave for past dates.'];
        }
        
        try {
            $leave = Leave::create([
                'employee_id' => $employee->id,
                'type' => $data['type'] ?? 'annual',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? ''
            ]);
            
            return ['success' => true, 'leave' => $leave, 'message' => 'Leave request submitted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to submit leave request.'];
        }
    }

    public static function approve(int $leaveId): array {
        RoleMiddleware::requireMinimum('manager');
        
        $leave = Leave::find($leaveId);
        if (!$leave) {
            return ['success' => false, 'message' => 'Leave request not found.'];
        }
        
        // Check if manager can approve this leave
        if (RoleMiddleware::isManager()) {
            $employee = Employee::find($leave->employee_id);
            if (!$employee || $employee->manager_user_id !== AuthMiddleware::userId()) {
                return ['success' => false, 'message' => 'Not authorized to approve this leave.'];
            }
        }
        
        $leave->approve(AuthMiddleware::userId());
        return ['success' => true, 'message' => 'Leave approved.'];
    }

    public static function reject(int $leaveId): array {
        RoleMiddleware::requireMinimum('manager');
        
        $leave = Leave::find($leaveId);
        if (!$leave) {
            return ['success' => false, 'message' => 'Leave request not found.'];
        }
        
        // Check if manager can reject this leave
        if (RoleMiddleware::isManager()) {
            $employee = Employee::find($leave->employee_id);
            if (!$employee || $employee->manager_user_id !== AuthMiddleware::userId()) {
                return ['success' => false, 'message' => 'Not authorized to reject this leave.'];
            }
        }
        
        $leave->reject(AuthMiddleware::userId());
        return ['success' => true, 'message' => 'Leave rejected.'];
    }

    public static function getBalance(int $employeeId, int $year = null): array {
        $year = $year ?? (int)date('Y');
        
        // Default leave entitlements (could be from settings table)
        $entitlements = [
            'annual' => 20,
            'sick' => 10,
            'personal' => 5
        ];
        
        $balance = [];
        foreach ($entitlements as $type => $total) {
            $used = Leave::getUsedDays($employeeId, $type, $year);
            $balance[$type] = [
                'total' => $total,
                'used' => $used,
                'remaining' => $total - $used
            ];
        }
        
        return $balance;
    }
}
