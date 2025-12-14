<?php
/**
 * Database Connection Manager
 * Singleton PDO connection with prepared statement support
 */

require_once __DIR__ . '/env.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                    DB_HOST, DB_PORT, DB_NAME
                );
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    die("Database connection failed: " . $e->getMessage());
                }
                die("Database connection failed. Please check configuration.");
            }
        }
        return self::$instance;
    }

    // Prevent cloning
    private function __clone() {}

    // Begin transaction
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }

    // Commit transaction
    public static function commit(): bool {
        return self::getInstance()->commit();
    }

    // Rollback transaction
    public static function rollback(): bool {
        return self::getInstance()->rollBack();
    }

    // Execute query with params
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Get last insert ID
    public static function lastInsertId(): string {
        return self::getInstance()->lastInsertId();
    }
}
