<?php
/**
 * Leave Model
 */

require_once __DIR__ . '/../Config/Database.php';

class Leave {
    public ?int $id = null;
    public int $employee_id;
    public string $type = 'annual';
    public string $start_date;
    public string $end_date;
    public int $days;
    public string $reason = '';
    public string $status = 'pending';
    public ?int $approved_by = null;
    public ?string $approved_at = null;

    public static function find(int $id): ?self {
        $stmt = Database::query("SELECT * FROM leaves WHERE id = ?", [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    public static function getByEmployee(int $employeeId): array {
        $sql = "SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC";
        $stmt = Database::query($sql, [$employeeId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getPending(?int $managerId = null): array {
        if ($managerId) {
            $sql = "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name
                    FROM leaves l
                    JOIN employees e ON l.employee_id = e.id
                    JOIN users u ON e.user_id = u.id
                    WHERE l.status = 'pending' AND e.manager_user_id = ?
                    ORDER BY l.created_at";
            $stmt = Database::query($sql, [$managerId]);
        } else {
            $sql = "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name
                    FROM leaves l
                    JOIN employees e ON l.employee_id = e.id
                    JOIN users u ON e.user_id = u.id
                    WHERE l.status = 'pending' ORDER BY l.created_at";
            $stmt = Database::query($sql);
        }
        return $stmt->fetchAll();
    }

    public static function create(array $data): ?self {
        $start = new DateTime($data['start_date']);
        $end = new DateTime($data['end_date']);
        $days = $start->diff($end)->days + 1;
        
        $sql = "INSERT INTO leaves (employee_id, leave_type, start_date, end_date, days, reason, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        Database::query($sql, [
            $data['employee_id'],
            $data['leave_type'] ?? $data['type'] ?? 'annual',
            $data['start_date'],
            $data['end_date'],
            $days,
            $data['reason'] ?? ''
        ]);
        return self::find((int)Database::lastInsertId());
    }

    public function approve(int $approvedBy): bool {
        $sql = "UPDATE leaves SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?";
        Database::query($sql, [$approvedBy, $this->id]);
        return true;
    }

    public function reject(int $rejectedBy): bool {
        $sql = "UPDATE leaves SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?";
        Database::query($sql, [$rejectedBy, $this->id]);
        return true;
    }

    public static function getUsedDays(int $employeeId, string $type, int $year): int {
        $sql = "SELECT COALESCE(SUM(days), 0) as total FROM leaves 
                WHERE employee_id = ? AND leave_type = ? AND status = 'approved' 
                AND YEAR(start_date) = ?";
        $stmt = Database::query($sql, [$employeeId, $type, $year]);
        return (int)$stmt->fetch()['total'];
    }

    private static function hydrate(array $data): self {
        $leave = new self();
        $leave->id = (int)$data['id'];
        $leave->employee_id = (int)$data['employee_id'];
        $leave->type = $data['leave_type'] ?? $data['type'] ?? 'annual';
        $leave->start_date = $data['start_date'];
        $leave->end_date = $data['end_date'];
        $leave->days = (int)$data['days'];
        $leave->reason = $data['reason'] ?? '';
        $leave->status = $data['status'];
        $leave->approved_by = isset($data['approved_by']) ? (int)$data['approved_by'] : null;
        $leave->approved_at = $data['approved_at'] ?? null;
        return $leave;
    }
}
