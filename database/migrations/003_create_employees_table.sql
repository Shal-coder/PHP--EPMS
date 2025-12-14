-- Migration: Create employees table
-- Purpose: Employee-specific profile data linked to users

CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `employee_code` VARCHAR(20) NOT NULL UNIQUE,
    `manager_user_id` INT UNSIGNED NULL,
    `department_id` INT UNSIGNED NULL,
    `hire_date` DATE NOT NULL,
    `base_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_class` VARCHAR(10) DEFAULT 'A',
    `bank_account` VARCHAR(50) NULL,
    `bank_name` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `emergency_contact` VARCHAR(100) NULL,
    `emergency_phone` VARCHAR(20) NULL,
    `status` ENUM('active', 'on_leave', 'terminated') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emp_manager` FOREIGN KEY (`manager_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_emp_code` (`employee_code`),
    INDEX `idx_emp_manager` (`manager_user_id`),
    INDEX `idx_emp_dept` (`department_id`),
    INDEX `idx_emp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
