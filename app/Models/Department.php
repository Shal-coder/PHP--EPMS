<?php
/**
 * Department Model
 */

require_once __DIR__ . '/../Config/Database.php';

class Department {
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public ?int $manager_user_id = null;
    public string $status = 'active';
    public ?string $manager_name = null;

    public static function find(int $id): ?self {
        $stmt = Database::query("SELECT * FROM departments WHERE id = ?", [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    public static function getAll(): array {
        $stmt = Database::query("SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as manager_name 
                FROM departments d 
                LEFT JOIN users u ON d.manager_user_id = u.id 
                WHERE d.status = 'active' ORDER BY d.name");
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function create(array $data): ?self {
        $sql = "INSERT INTO departments (name, description, manager_user_id, status) VALUES (?, ?, ?, 'active')";
        Database::query($sql, [$data['name'], $data['description'] ?? null, $data['manager_user_id'] ?? null]);
        return self::find((int)Database::lastInsertId());
    }

    public function update(array $data): bool {
        $fields = [];
        $params = [];
        foreach (['name', 'description', 'manager_user_id', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $params[] = $this->id;
        Database::query("UPDATE departments SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        return true;
    }

    public function delete(): bool {
        Database::query("UPDATE departments SET status = 'inactive' WHERE id = ?", [$this->id]);
        return true;
    }

    private static function hydrate(array $data): self {
        $dept = new self();
        $dept->id = (int)$data['id'];
        $dept->name = $data['name'];
        $dept->description = $data['description'] ?? null;
        $dept->manager_user_id = $data['manager_user_id'] ? (int)$data['manager_user_id'] : null;
        $dept->status = $data['status'] ?? 'active';
        $dept->manager_name = $data['manager_name'] ?? null;
        return $dept;
    }
}
