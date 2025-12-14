<?php
/**
 * User Model
 * Handles user authentication and management
 */

require_once __DIR__ . '/../Config/Database.php';

class User {
    public ?int $id = null;
    public string $uuid = '';
    public string $email = '';
    public string $role = 'employee';
    public string $first_name = '';
    public string $last_name = '';
    public ?string $phone = null;
    public string $status = 'active';

    // Find user by ID
    public static function find(int $id): ?self {
        $stmt = Database::query("SELECT * FROM users WHERE id = ?", [$id]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    // Find user by email
    public static function findByEmail(string $email): ?self {
        $stmt = Database::query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch();
        return $data ? self::hydrate($data) : null;
    }

    // Create new user
    public static function create(array $data): ?self {
        $uuid = self::generateUUID();
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (uuid, email, password_hash, role, first_name, last_name, phone, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        Database::query($sql, [
            $uuid,
            $data['email'],
            $passwordHash,
            $data['role'] ?? 'employee',
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        return self::find((int)Database::lastInsertId());
    }

    // Verify password
    public static function verifyPassword(string $email, string $password): ?self {
        $stmt = Database::query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch();
        
        if ($data && password_verify($password, $data['password_hash'])) {
            // Update last login
            Database::query("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?", [$data['id']]);
            return self::hydrate($data);
        }
        
        // Increment login attempts
        if ($data) {
            Database::query("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?", [$data['id']]);
        }
        
        return null;
    }

    // Check if account is locked
    public static function isLocked(string $email): bool {
        $stmt = Database::query(
            "SELECT login_attempts, locked_until FROM users WHERE email = ?", 
            [$email]
        );
        $data = $stmt->fetch();
        
        if (!$data) return false;
        
        if ($data['locked_until'] && strtotime($data['locked_until']) > time()) {
            return true;
        }
        
        if ($data['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            Database::query("UPDATE users SET locked_until = ? WHERE email = ?", [$lockUntil, $email]);
            return true;
        }
        
        return false;
    }

    // Get all users by role
    public static function getByRole(string $role): array {
        $stmt = Database::query("SELECT * FROM users WHERE role = ? ORDER BY first_name", [$role]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    // Get all managers
    public static function getManagers(): array {
        return self::getByRole('manager');
    }

    // Update user
    public function update(array $data): bool {
        $fields = [];
        $params = [];
        
        foreach (['first_name', 'last_name', 'phone', 'status', 'role'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (empty($fields)) return false;
        
        $params[] = $this->id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::query($sql, $params);
        
        return true;
    }

    // Hydrate model from array
    private static function hydrate(array $data): self {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->uuid = $data['uuid'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->phone = $data['phone'];
        $user->status = $data['status'];
        return $user;
    }

    // Generate UUID
    private static function generateUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // Get full name
    public function getFullName(): string {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Check role
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isManager(): bool { return $this->role === 'manager'; }
    public function isEmployee(): bool { return $this->role === 'employee'; }
}
