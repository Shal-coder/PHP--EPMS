<?php
/**
 * Attendance Model
 */

require_once __DIR__ . '/../Config/Database.php';

class Attendance {
    public ?int $id = null;
    public int $employee_id;
    public string $date;
    public ?string $check_in = null;
    public ?string $check_out = null;
    public string $status = 'present';
    public ?string $notes = null;

    public static function find(int $id): ?self {
        $stmt = Database::query("SELECT * FROM attendance WHERE id = ?", [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    public static function getByEmployee(int $employeeId, string $startDate, string $endDate): array {
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date";
        $stmt = Database::query($sql, [$employeeId, $startDate, $endDate]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByDate(string $date, ?int $managerId = null): array {
        if ($managerId) {
            $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name
                    FROM attendance a
                    JOIN employees e ON a.employee_id = e.id
                    JOIN users u ON e.user_id = u.id
                    WHERE a.date = ? AND e.manager_user_id = ?
                    ORDER BY u.first_name";
            $stmt = Database::query($sql, [$date, $managerId]);
        } else {
            $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name
                    FROM attendance a
                    JOIN employees e ON a.employee_id = e.id
                    JOIN users u ON e.user_id = u.id
                    WHERE a.date = ? ORDER BY u.first_name";
            $stmt = Database::query($sql, [$date]);
        }
        return $stmt->fetchAll();
    }

    public static function checkIn(int $employeeId): ?self {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        
        // Check if already checked in
        $stmt = Database::query("SELECT * FROM attendance WHERE employee_id = ? AND date = ?", [$employeeId, $today]);
        if ($stmt->fetch()) return null;
        
        $sql = "INSERT INTO attendance (employee_id, date, clock_in, status) VALUES (?, ?, ?, 'present')";
        Database::query($sql, [$employeeId, $today, $now]);
        return self::find((int)Database::lastInsertId());
    }

    public static function checkOut(int $employeeId): bool {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        Database::query("UPDATE attendance SET clock_out = ? WHERE employee_id = ? AND date = ?", [$now, $employeeId, $today]);
        return true;
    }

    public static function markAbsent(int $employeeId, string $date, string $notes = ''): ?self {
        $sql = "INSERT INTO attendance (employee_id, date, status, notes) VALUES (?, ?, 'absent', ?)
                ON DUPLICATE KEY UPDATE status = 'absent', notes = ?";
        Database::query($sql, [$employeeId, $date, $notes, $notes]);
        $stmt = Database::query("SELECT * FROM attendance WHERE employee_id = ? AND date = ?", [$employeeId, $date]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    public static function countByStatus(int $employeeId, string $status, string $startDate, string $endDate): int {
        $sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = ? AND status = ? AND date BETWEEN ? AND ?";
        $stmt = Database::query($sql, [$employeeId, $status, $startDate, $endDate]);
        return (int)$stmt->fetch()['count'];
    }

    private static function hydrate(array $data): self {
        $att = new self();
        $att->id = (int)$data['id'];
        $att->employee_id = (int)$data['employee_id'];
        $att->date = $data['date'];
        $att->check_in = $data['clock_in'] ?? $data['check_in'] ?? null;
        $att->check_out = $data['clock_out'] ?? $data['check_out'] ?? null;
        $att->status = $data['status'];
        $att->notes = $data['notes'] ?? null;
        return $att;
    }
}
