-- Seed data for PayrollPro system
-- Passwords are bcrypt hashed. Default password for all: "password123"

-- Insert departments
INSERT INTO `departments` (`id`, `name`, `description`) VALUES
(1, 'Engineering', 'Software development and IT'),
(2, 'Human Resources', 'HR and recruitment'),
(3, 'Finance', 'Accounting and finance'),
(4, 'Marketing', 'Marketing and sales');

-- Insert users (password: password123)
-- Hash generated with password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `uuid`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `status`) VALUES
(1, UUID(), 'admin@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Super', 'Admin', '+1234567890', 'active'),
(2, UUID(), 'manager.eng@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'John', 'Manager', '+1234567891', 'active'),
(3, UUID(), 'manager.hr@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Jane', 'HRManager', '+1234567892', 'active'),
(4, UUID(), 'emp1@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Alice', 'Developer', '+1234567893', 'active'),
(5, UUID(), 'emp2@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Bob', 'Designer', '+1234567894', 'active'),
(6, UUID(), 'emp3@payrollpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Charlie', 'Analyst', '+1234567895', 'active');

-- Insert employees (linked to users)
-- Note: Managers are also employees with salaries
INSERT INTO `employees` (`id`, `user_id`, `employee_code`, `manager_user_id`, `department_id`, `hire_date`, `base_salary`, `tax_class`, `bank_account`, `status`) VALUES
(1, 2, 'MGR001', 1, 1, '2022-06-01', 7000.00, 'A', '9876543210', 'active'),  -- John Manager (Engineering)
(2, 3, 'MGR002', 1, 2, '2022-08-01', 6500.00, 'A', '9876543211', 'active'),  -- Jane HRManager (HR)
(3, 4, 'EMP001', 2, 1, '2023-01-15', 5000.00, 'A', '1234567890', 'active'),  -- Alice Developer
(4, 5, 'EMP002', 2, 1, '2023-03-01', 4500.00, 'A', '1234567891', 'active'),  -- Bob Designer
(5, 6, 'EMP003', 3, 2, '2023-06-01', 4000.00, 'B', '1234567892', 'active');  -- Charlie Analyst

-- Insert sample allowances
INSERT INTO `allowances` (`employee_id`, `type`, `amount`, `is_recurring`, `description`, `effective_from`) VALUES
(1, 'housing', 800.00, TRUE, 'Manager housing allowance', '2022-06-01'),  -- John Manager
(1, 'transport', 300.00, TRUE, 'Manager transport allowance', '2022-06-01'),
(2, 'housing', 750.00, TRUE, 'Manager housing allowance', '2022-08-01'),  -- Jane HRManager
(2, 'transport', 300.00, TRUE, 'Manager transport allowance', '2022-08-01'),
(3, 'housing', 500.00, TRUE, 'Housing allowance', '2023-01-15'),  -- Alice
(3, 'transport', 200.00, TRUE, 'Transport allowance', '2023-01-15'),
(4, 'housing', 400.00, TRUE, 'Housing allowance', '2023-03-01'),  -- Bob
(5, 'meal', 150.00, TRUE, 'Meal allowance', '2023-06-01');  -- Charlie

-- Insert sample deductions
INSERT INTO `deductions` (`employee_id`, `type`, `amount`, `is_recurring`, `description`, `effective_from`) VALUES
(1, 'pension', 350.00, TRUE, 'Manager pension contribution', '2022-06-01'),  -- John Manager
(1, 'insurance', 150.00, TRUE, 'Manager health insurance', '2022-06-01'),
(2, 'pension', 325.00, TRUE, 'Manager pension contribution', '2022-08-01'),  -- Jane HRManager
(2, 'insurance', 150.00, TRUE, 'Manager health insurance', '2022-08-01'),
(3, 'pension', 250.00, TRUE, 'Pension contribution', '2023-01-15'),  -- Alice
(4, 'insurance', 100.00, TRUE, 'Health insurance', '2023-03-01'),  -- Bob
(5, 'loan', 200.00, TRUE, 'Personal loan repayment', '2023-06-01');  -- Charlie
