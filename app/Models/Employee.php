<?php
/**
 * Employee Model
 * Handles employee profile and related operations
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/User.php';

class Employee {
    public ?int $id = null;
    public int $user_id;
    public string $employee_code = '';
    public ?int $manager_user_id = null;
    public ?int $department_id = null;
    public string $hire_date = '';
    public float $base_salary = 0.00;
    public string $tax_class = 'A';
    public ?string $bank_account = null;
    public ?string $bank_name = null;
    public string $status = 'active';
    
    // Related data
    public ?User $user = null;
    public ?string $department_name = null;
    public ?string $manager_name = null;

    // Find by ID
    public static function find(int $id): ?self {
        $sql = "SELECT e.*, d.name as department_name, 
                CONCAT(m.first_name, ' ', m.last_name) as manager_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN users m ON e.manager_user_id = m.id
                WHERE e.id = ?";
        $stmt = Database::query($sql, [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    // Find by user ID
    public static function findByUserId(int $userId): ?self {
        $sql = "SELECT e.*, d.name as department_name,
                CONCAT(m.first_name, ' ', m.last_name) as manager_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN users m ON e.manager_user_id = m.id
                WHERE e.user_id = ?";
        $stmt = Database::query($sql, [$userId]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    // Get all employees
    public static function getAll(): array {
        $sql = "SELECT e.*, d.name as department_name,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.email,
                CONCAT(m.first_name, ' ', m.last_name) as manager_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN users m ON e.manager_user_id = m.id
                WHERE e.status = 'active'
                ORDER BY u.first_name";
        $stmt = Database::query($sql);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    // Get employees by manager
    public static function getByManager(int $managerUserId): array {
        $sql = "SELECT e.*, d.name as department_name,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.email
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.manager_user_id = ? AND e.status = 'active'
                ORDER BY u.first_name";
        $stmt = Database::query($sql, [$managerUserId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    // Create employee with user account
    public static function create(array $data): ?self {
        Database::beginTransaction();
        
        try {
            // Create user first
            $user = User::create([
                'email' => $data['email'],
                'password' => $data['password'] ?? 'changeme123',
                'role' => 'employee',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null
            ]);
            
            if (!$user) {
                Database::rollback();
                return null;
            }
            
            // Generate employee code
            $code = self::generateEmployeeCode();
            
            // Create employee record
            $sql = "INSERT INTO employees (user_id, employee_code, manager_user_id, department_id, 
                    hire_date, base_salary, tax_class, bank_account, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            
            Database::query($sql, [
                $user->id,
                $code,
                $data['manager_user_id'] ?? null,
                $data['department_id'] ?? null,
                $data['hire_date'] ?? date('Y-m-d'),
                $data['base_salary'] ?? 0,
                $data['tax_class'] ?? 'A',
                $data['bank_account'] ?? null
            ]);
            
            Database::commit();
            return self::find((int)Database::lastInsertId());
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    // Update employee
    public function update(array $data): bool {
        $fields = [];
        $params = [];
        
        $allowedFields = ['manager_user_id', 'department_id', 'base_salary', 'tax_class', 'bank_account', 'bank_name', 'status'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $this->id;
        $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::query($sql, $params);
        
        return true;
    }

    // Check if employee belongs to manager
    public function belongsToManager(int $managerUserId): bool {
        return $this->manager_user_id === $managerUserId;
    }

    // Get allowances
    public function getAllowances(string $date = null): array {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT * FROM allowances 
                WHERE employee_id = ? 
                AND effective_from <= ?
                AND (effective_to IS NULL OR effective_to >= ?)
                AND is_recurring = TRUE";
        $stmt = Database::query($sql, [$this->id, $date, $date]);
        return $stmt->fetchAll();
    }

    // Get deductions
    public function getDeductions(string $date = null): array {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT * FROM deductions 
                WHERE employee_id = ? 
                AND effective_from <= ?
                AND (effective_to IS NULL OR effective_to >= ?)
                AND is_recurring = TRUE";
        $stmt = Database::query($sql, [$this->id, $date, $date]);
        return $stmt->fetchAll();
    }

    // Get bonuses for period
    public function getBonuses(string $startDate, string $endDate): array {
        $sql = "SELECT * FROM bonuses 
                WHERE employee_id = ? 
                AND date_awarded BETWEEN ? AND ?";
        $stmt = Database::query($sql, [$this->id, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

    // Generate employee code
    private static function generateEmployeeCode(): string {
        $stmt = Database::query("SELECT MAX(id) as max_id FROM employees");
        $result = $stmt->fetch();
        $nextId = ($result['max_id'] ?? 0) + 1;
        return 'EMP' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    // Hydrate model
    private static function hydrate(array $data): self {
        $emp = new self();
        $emp->id = (int)$data['id'];
        $emp->user_id = (int)$data['user_id'];
        $emp->employee_code = $data['employee_code'];
        $emp->manager_user_id = $data['manager_user_id'] ? (int)$data['manager_user_id'] : null;
        $emp->department_id = $data['department_id'] ? (int)$data['department_id'] : null;
        $emp->hire_date = $data['hire_date'];
        $emp->base_salary = (float)$data['base_salary'];
        $emp->tax_class = $data['tax_class'];
        $emp->bank_account = $data['bank_account'] ?? null;
        $emp->bank_name = $data['bank_name'] ?? null;
        $emp->status = $data['status'];
        $emp->department_name = $data['department_name'] ?? null;
        $emp->manager_name = $data['manager_name'] ?? null;
        return $emp;
    }
}
