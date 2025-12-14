-- Migration: Create attendance table
-- Purpose: Daily attendance records

CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `duration_minutes` INT UNSIGNED DEFAULT 0,
    `status` ENUM('present', 'absent', 'late', 'half_day', 'holiday', 'weekend') NOT NULL DEFAULT 'present',
    `source` ENUM('manual', 'biometric', 'system') DEFAULT 'manual',
    `note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    
    UNIQUE KEY `uk_emp_date` (`employee_id`, `date`),
    INDEX `idx_att_date` (`date`),
    INDEX `idx_att_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
