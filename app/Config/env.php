<?php
/**
 * Environment Configuration Loader
 * Reads .env file and provides access to configuration values
 */

class Env {
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $path = null): void {
        if (self::$loaded) return;
        
        $envFile = $path ?? dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            // Fall back to .env.example for development
            $envFile = dirname(__DIR__, 2) . '/.env.example';
        }
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                self::$values[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }

    public static function get(string $key, $default = null) {
        self::load();
        return self::$values[$key] ?? getenv($key) ?: $default;
    }
}

// Auto-load on include
Env::load();

// Database configuration constants
define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_PORT', Env::get('DB_PORT', '3306'));
define('DB_NAME', Env::get('DB_NAME', 'payroll_pro'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));

// Application constants
define('APP_NAME', Env::get('APP_NAME', 'PayrollPro'));
define('APP_URL', Env::get('APP_URL', 'http://localhost'));
define('APP_ENV', Env::get('APP_ENV', 'development'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'true') === 'true');

// Security constants
define('SESSION_LIFETIME', (int)Env::get('SESSION_LIFETIME', 120));
define('MAX_LOGIN_ATTEMPTS', (int)Env::get('MAX_LOGIN_ATTEMPTS', 5));
define('LOCKOUT_DURATION', (int)Env::get('LOCKOUT_DURATION', 300));

// Paths
define('PAYSLIP_PATH', Env::get('PAYSLIP_STORAGE_PATH', 'storage/payslips'));
define('LOG_PATH', Env::get('LOG_PATH', 'storage/logs'));
