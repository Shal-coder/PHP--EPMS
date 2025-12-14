-- Migration: Create payroll_runs table
-- Purpose: Track payroll processing batches

CREATE TABLE IF NOT EXISTS `payroll_runs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `run_uuid` CHAR(36) NOT NULL UNIQUE,
    `initiator_id` INT UNSIGNED NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `pay_date` DATE NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `total_employees` INT UNSIGNED DEFAULT 0,
    `total_gross` DECIMAL(14,2) DEFAULT 0.00,
    `total_net` DECIMAL(14,2) DEFAULT 0.00,
    `notes` TEXT NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    
    CONSTRAINT `fk_pr_initiator` FOREIGN KEY (`initiator_id`) REFERENCES `users`(`id`),
    
    INDEX `idx_pr_status` (`status`),
    INDEX `idx_pr_period` (`period_start`, `period_end`),
    INDEX `idx_pr_initiator` (`initiator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
