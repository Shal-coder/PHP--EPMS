<?php
/**
 * Payroll Controller
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Payroll.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Payroll/PayrollCalculator.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

class PayrollController {
    
    public static function getAll(): array {
        RoleMiddleware::require('super_admin');
        return Payroll::getAll();
    }

    public static function get(int $id): ?Payroll {
        RoleMiddleware::require('super_admin');
        return Payroll::find($id);
    }

    public static function create(string $periodStart, string $periodEnd): array {
        RoleMiddleware::require('super_admin');
        
        try {
            $payroll = Payroll::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_by' => AuthMiddleware::userId()
            ]);
            
            return ['success' => true, 'payroll' => $payroll, 'message' => 'Payroll run created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create payroll run.'];
        }
    }

    public static function calculate(int $payrollRunId): array {
        RoleMiddleware::require('super_admin');
        
        $payroll = Payroll::find($payrollRunId);
        if (!$payroll) {
            return ['success' => false, 'message' => 'Payroll run not found.'];
        }
        
        if ($payroll->status !== 'draft') {
            return ['success' => false, 'message' => 'Can only calculate draft payrolls.'];
        }
        
        try {
            Database::beginTransaction();
            
            // Clear existing items
            Database::query("DELETE FROM payroll_items WHERE payroll_run_id = ?", [$payrollRunId]);
            
            // Get all active employees
            $employees = Employee::getAll();
            
            foreach ($employees as $employee) {
                $calculator = new PayrollCalculator($employee, $payroll->period_start, $payroll->period_end);
                $calculator->calculate()->saveToPayrollRun($payrollRunId);
            }
            
            Database::commit();
            return ['success' => true, 'message' => 'Payroll calculated for ' . count($employees) . ' employees.'];
            
        } catch (Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => 'Calculation failed: ' . $e->getMessage()];
        }
    }

    public static function approve(int $payrollRunId): array {
        RoleMiddleware::require('super_admin');
        
        $payroll = Payroll::find($payrollRunId);
        if (!$payroll) {
            return ['success' => false, 'message' => 'Payroll run not found.'];
        }
        
        $payroll->approve(AuthMiddleware::userId());
        return ['success' => true, 'message' => 'Payroll approved.'];
    }

    public static function process(int $payrollRunId): array {
        RoleMiddleware::require('super_admin');
        
        $payroll = Payroll::find($payrollRunId);
        if (!$payroll || $payroll->status !== 'approved') {
            return ['success' => false, 'message' => 'Payroll must be approved first.'];
        }
        
        $payroll->process();
        return ['success' => true, 'message' => 'Payroll processed.'];
    }

    public static function getEmployeePayslips(?int $employeeId = null): array {
        AuthMiddleware::require();
        
        if ($employeeId && !RoleMiddleware::isSuperAdmin()) {
            $employee = Employee::findByUserId(AuthMiddleware::userId());
            if (!$employee || $employee->id !== $employeeId) {
                return [];
            }
        }
        
        if (!$employeeId) {
            $employee = Employee::findByUserId(AuthMiddleware::userId());
            $employeeId = $employee ? $employee->id : 0;
        }
        
        return Payroll::getEmployeePayslips($employeeId);
    }
}
