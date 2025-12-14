-- Migration: Create payroll_items table
-- Purpose: Individual employee payroll records per run

CREATE TABLE IF NOT EXISTS `payroll_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payroll_run_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `base_salary` DECIMAL(12,2) NOT NULL,
    `days_worked` DECIMAL(4,1) NOT NULL,
    `days_absent` DECIMAL(4,1) DEFAULT 0,
    `days_leave` DECIMAL(4,1) DEFAULT 0,
    `gross_salary` DECIMAL(12,2) NOT NULL,
    `total_allowances` DECIMAL(10,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(10,2) DEFAULT 0.00,
    `total_bonuses` DECIMAL(10,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `net_salary` DECIMAL(12,2) NOT NULL,
    `details` JSON NULL,
    `payslip_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_pi_run` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pi_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    
    UNIQUE KEY `uk_run_employee` (`payroll_run_id`, `employee_id`),
    INDEX `idx_pi_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
