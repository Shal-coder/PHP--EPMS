<?php
/**
 * Announcement Model
 */

require_once __DIR__ . '/../Config/Database.php';

class Announcement {
    public $id;
    public $title;
    public $content;
    public $priority;
    public $target_audience;
    public $created_by;
    public $is_published;
    public $published_at;
    public $expires_at;
    public $created_at;
    public $updated_at;
    
    // Additional properties from joins
    public $author_name;
    
    public static function create(array $data): ?self {
        $sql = "INSERT INTO announcements (title, content, priority, target_audience, created_by, is_published, published_at, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = Database::query($sql, [
            $data['title'],
            $data['content'],
            $data['priority'] ?? 'normal',
            $data['target_audience'] ?? 'all',
            $data['created_by'],
            $data['is_published'] ?? 1,
            $data['published_at'] ?? date('Y-m-d H:i:s'),
            $data['expires_at'] ?? null
        ]);
        
        if ($result) {
            return self::find(Database::lastInsertId());
        }
        return null;
    }
    
    public static function find(int $id): ?self {
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM announcements a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?";
        $stmt = Database::query($sql, [$id]);
        $data = $stmt->fetch();
        
        if ($data) {
            $obj = new self();
            foreach ($data as $key => $value) {
                $obj->$key = $value;
            }
            return $obj;
        }
        return null;
    }
    
    public static function getAll(): array {
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM announcements a
                LEFT JOIN users u ON a.created_by = u.id
                ORDER BY a.created_at DESC";
        $stmt = Database::query($sql);
        $results = [];
        while ($row = $stmt->fetch()) {
            $obj = new self();
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }
            $results[] = $obj;
        }
        return $results;
    }
    
    public static function getPublished(string $role = 'all'): array {
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM announcements a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.is_published = 1 
                AND (a.published_at IS NULL OR a.published_at <= NOW())
                AND (a.expires_at IS NULL OR a.expires_at > NOW())
                AND (a.target_audience = 'all' OR a.target_audience = ?)
                ORDER BY a.priority DESC, a.created_at DESC";
        
        // Guest users only see 'all' audience announcements
        if ($role === 'guest') {
            $audience = 'all';
        } elseif ($role === 'super_admin') {
            $audience = 'all';
        } elseif ($role === 'manager') {
            $audience = 'managers';
        } else {
            $audience = 'employees';
        }
        $stmt = Database::query($sql, [$audience]);
        $results = [];
        while ($row = $stmt->fetch()) {
            $obj = new self();
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }
            $results[] = $obj;
        }
        return $results;
    }
    
    public function update(array $data): bool {
        $sql = "UPDATE announcements 
                SET title = ?, content = ?, priority = ?, target_audience = ?, 
                    is_published = ?, expires_at = ?
                WHERE id = ?";
        
        $result = Database::query($sql, [
            $data['title'] ?? $this->title,
            $data['content'] ?? $this->content,
            $data['priority'] ?? $this->priority,
            $data['target_audience'] ?? $this->target_audience,
            $data['is_published'] ?? $this->is_published,
            $data['expires_at'] ?? $this->expires_at,
            $this->id
        ]);
        
        return $result !== false;
    }
    
    public function delete(): bool {
        $sql = "DELETE FROM announcements WHERE id = ?";
        $result = Database::query($sql, [$this->id]);
        return $result !== false;
    }
}
