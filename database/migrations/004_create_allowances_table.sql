-- Migration: Create allowances table
-- Purpose: Employee allowances (housing, transport, meal, etc.)

CREATE TABLE IF NOT EXISTS `allowances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `is_recurring` BOOLEAN DEFAULT TRUE,
    `description` VARCHAR(255) NULL,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_alw_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_alw_employee` (`employee_id`),
    INDEX `idx_alw_type` (`type`),
    INDEX `idx_alw_effective` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
