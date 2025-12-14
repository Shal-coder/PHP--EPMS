-- Migration: Create sessions table
-- Purpose: Secure session management

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_session_user` (`user_id`),
    INDEX `idx_session_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
