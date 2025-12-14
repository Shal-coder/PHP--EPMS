<?php
/**
 * Payroll Model
 */

require_once __DIR__ . '/../Config/Database.php';

class Payroll {
    public ?int $id = null;
    public string $period_start;
    public string $period_end;
    public string $status = 'draft';
    public ?int $created_by = null;
    public ?int $approved_by = null;

    public static function find(int $id): ?self {
        $stmt = Database::query("SELECT * FROM payroll_runs WHERE id = ?", [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    public static function getAll(): array {
        $sql = "SELECT pr.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM payroll_runs pr
                LEFT JOIN users u ON pr.created_by = u.id
                ORDER BY pr.period_start DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }

    public static function create(array $data): ?self {
        $sql = "INSERT INTO payroll_runs (period_start, period_end, status, created_by) VALUES (?, ?, 'draft', ?)";
        Database::query($sql, [$data['period_start'], $data['period_end'], $data['created_by']]);
        return self::find((int)Database::lastInsertId());
    }

    public function approve(int $approvedBy): bool {
        Database::query("UPDATE payroll_runs SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?", 
            [$approvedBy, $this->id]);
        return true;
    }

    public function process(): bool {
        Database::query("UPDATE payroll_runs SET status = 'processed', processed_at = NOW() WHERE id = ?", [$this->id]);
        return true;
    }

    public function getItems(): array {
        $sql = "SELECT pi.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code
                FROM payroll_items pi
                JOIN employees e ON pi.employee_id = e.id
                JOIN users u ON e.user_id = u.id
                WHERE pi.payroll_run_id = ?
                ORDER BY u.first_name";
        $stmt = Database::query($sql, [$this->id]);
        return $stmt->fetchAll();
    }

    public static function getEmployeePayslips(int $employeeId): array {
        $sql = "SELECT pi.*, pr.period_start, pr.period_end, pr.status as run_status
                FROM payroll_items pi
                JOIN payroll_runs pr ON pi.payroll_run_id = pr.id
                WHERE pi.employee_id = ? AND pr.status IN ('approved', 'processed')
                ORDER BY pr.period_start DESC";
        $stmt = Database::query($sql, [$employeeId]);
        return $stmt->fetchAll();
    }

    private static function hydrate(array $data): self {
        $pr = new self();
        $pr->id = (int)$data['id'];
        $pr->period_start = $data['period_start'];
        $pr->period_end = $data['period_end'];
        $pr->status = $data['status'];
        $pr->created_by = $data['created_by'] ? (int)$data['created_by'] : null;
        $pr->approved_by = $data['approved_by'] ? (int)$data['approved_by'] : null;
        return $pr;
    }
}
