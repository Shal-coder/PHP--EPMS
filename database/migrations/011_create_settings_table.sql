-- Migration: Create settings table
-- Purpose: System configuration (tax rates, payroll params)

CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('tax_rate_bracket_1', '0.10', 'Tax rate for income 0-10000'),
('tax_rate_bracket_2', '0.15', 'Tax rate for income 10001-30000'),
('tax_rate_bracket_3', '0.20', 'Tax rate for income 30001-60000'),
('tax_rate_bracket_4', '0.25', 'Tax rate for income above 60000'),
('tax_bracket_1_max', '10000', 'Max income for bracket 1'),
('tax_bracket_2_max', '30000', 'Max income for bracket 2'),
('tax_bracket_3_max', '60000', 'Max income for bracket 3'),
('working_days_per_month', '22', 'Standard working days per month'),
('overtime_rate', '1.5', 'Overtime pay multiplier'),
('company_name', 'PayrollPro Inc.', 'Company name for payslips'),
('company_address', '123 Business Street', 'Company address'),
('currency_symbol', '$', 'Currency symbol');
