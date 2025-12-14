<?php
/**
 * PayrollPro Complete Installation Script
 * One-click setup for database, tables, and sample data
 * 
 * Usage:
 * - First time: http://localhost/payroll-management-system/install.php
 * - Reset all: http://localhost/payroll-management-system/install.php?reset=1
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Configuration
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'payroll_pro';
$DB_USER = 'root';
$DB_PASS = '';

$reset = isset($_GET['reset']) && $_GET['reset'] === '1';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayrollPro Installation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.15), transparent 35%), 
                        radial-gradient(circle at 80% 10%, rgba(16,185,129,.12), transparent 38%), 
                        #0b1320; 
            color: #e6edf5; 
            padding: 40px 20px; 
            min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { 
            font-size: 42px; 
            font-weight: 800; 
            background: linear-gradient(135deg, #86efac, #22c55e); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            margin-bottom: 10px;
        }
        .header p { color: #9fb4c7; font-size: 16px; }
        
        .card { 
            background: rgba(255,255,255,.03); 
            border: 1px solid rgba(255,255,255,.08); 
            border-radius: 16px; 
            padding: 24px; 
            margin: 20px 0; 
        }
        .card h2 { 
            color: #86efac; 
            font-size: 20px; 
            margin-bottom: 16px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
        }
        .step { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: #fff; 
            width: 32px; 
            height: 32px; 
            border-radius: 50%; 
            font-size: 14px; 
            font-weight: 700;
        }
        
        .log-item { 
            padding: 10px 16px; 
            margin: 8px 0; 
            border-radius: 8px; 
            font-size: 14px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
        }
        .success { background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        .error { background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }
        .info { background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .warn { background: rgba(251,191,36,.15); color: #fde047; border: 1px solid rgba(251,191,36,.3); }
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 16px; 
            margin: 20px 0;
        }
        .stat-card { 
            background: rgba(255,255,255,.05); 
            padding: 16px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,.06);
        }
        .stat-card .label { color: #9fb4c7; font-size: 12px; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { color: #86efac; font-size: 24px; font-weight: 800; margin-top: 8px; }
        
        .accounts-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .accounts-table th { 
            text-align: left; 
            padding: 12px; 
            background: rgba(255,255,255,.05); 
            color: #9fb4c7; 
            font-size: 12px; 
            text-transform: uppercase; 
            font-weight: 600;
        }
        .accounts-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.05); }
        .accounts-table tr:last-child td { border-bottom: none; }
        
        .btn { 
            display: inline-block; 
            padding: 14px 28px; 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: #fff; 
            text-decoration: none; 
            border-radius: 12px; 
            font-weight: 700; 
            margin: 10px 10px 10px 0; 
            transition: all .3s;
            box-shadow: 0 4px 12px rgba(34,197,94,.3);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 12px rgba(239,68,68,.3); }
        .btn-danger:hover { box-shadow: 0 6px 16px rgba(239,68,68,.4); }
        
        .icon { font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 PayrollPro Installation</h1>
            <p>Complete database setup and configuration</p>
        </div>

<?php
// STEP 1: Database Connection
echo '<div class="card">';
echo '<h2><span class="step">1</span> Database Connection</h2>';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo '<div class="log-item success"><span class="icon">✓</span> Connected to database: <strong>' . htmlspecialchars($DB_NAME) . '</strong></div>';
} catch (PDOException $e) {
    echo '<div class="log-item error"><span class="icon">✗</span> Connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="log-item info"><span class="icon">ℹ</span> Make sure XAMPP MySQL is running and database "payroll_pro" exists</div>';
    echo '</div></div></body></html>';
    exit;
}
echo '</div>';

// STEP 2: Drop Tables (if reset)
if ($reset) {
    echo '<div class="card">';
    echo '<h2><span class="step">2</span> Resetting Database</h2>';
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['payroll_items', 'payroll_runs', 'leaves', 'attendance', 'bonuses', 'deductions', 'allowances', 'employees', 'users', 'departments', 'settings', 'audit_logs', 'sessions'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo '<div class="log-item warn"><span class="icon">⚠</span> All tables dropped successfully</div>';
    echo '</div>';
}

// STEP 3: Create Tables
$stepNum = $reset ? 3 : 2;
echo '<div class="card">';
echo "<h2><span class='step'>$stepNum</span> Creating Tables</h2>";

$tables = [
    'departments' => "CREATE TABLE IF NOT EXISTS departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        manager_user_id INT UNSIGNED NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        uuid CHAR(36) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
        login_attempts TINYINT UNSIGNED DEFAULT 0,
        locked_until TIMESTAMP NULL,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'employees' => "CREATE TABLE IF NOT EXISTS employees (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL UNIQUE,
        employee_code VARCHAR(20) NOT NULL UNIQUE,
        manager_user_id INT UNSIGNED NULL,
        department_id INT UNSIGNED NULL,
        hire_date DATE NOT NULL,
        base_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        tax_class VARCHAR(10) DEFAULT 'A',
        bank_account VARCHAR(50) NULL,
        bank_name VARCHAR(100) NULL,
        address TEXT NULL,
        emergency_contact VARCHAR(100) NULL,
        emergency_phone VARCHAR(20) NULL,
        status ENUM('active', 'on_leave', 'terminated') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'allowances' => "CREATE TABLE IF NOT EXISTS allowances (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        is_recurring TINYINT(1) DEFAULT 1,
        description TEXT NULL,
        effective_from DATE NOT NULL,
        effective_to DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'deductions' => "CREATE TABLE IF NOT EXISTS deductions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        is_recurring TINYINT(1) DEFAULT 1,
        description TEXT NULL,
        effective_from DATE NOT NULL,
        effective_to DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'bonuses' => "CREATE TABLE IF NOT EXISTS bonuses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        date_awarded DATE NOT NULL,
        approved_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'attendance' => "CREATE TABLE IF NOT EXISTS attendance (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        date DATE NOT NULL,
        clock_in TIME NULL,
        clock_out TIME NULL,
        status ENUM('present', 'absent', 'late', 'half_day', 'holiday', 'weekend') DEFAULT 'present',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        UNIQUE KEY unique_attendance (employee_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'leaves' => "CREATE TABLE IF NOT EXISTS leaves (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT UNSIGNED NOT NULL,
        leave_type ENUM('annual', 'sick', 'personal', 'maternity', 'paternity', 'unpaid') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        days INT NOT NULL,
        reason TEXT NULL,
        status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
        approved_by INT UNSIGNED NULL,
        approved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'payroll_runs' => "CREATE TABLE IF NOT EXISTS payroll_runs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        run_date DATE NOT NULL,
        status ENUM('draft', 'processing', 'approved', 'completed', 'cancelled', 'processed') DEFAULT 'draft',
        created_by INT UNSIGNED NOT NULL,
        approved_by INT UNSIGNED NULL,
        approved_at TIMESTAMP NULL,
        processed_at TIMESTAMP NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'payroll_items' => "CREATE TABLE IF NOT EXISTS payroll_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payroll_run_id INT UNSIGNED NOT NULL,
        employee_id INT UNSIGNED NOT NULL,
        base_salary DECIMAL(12,2) NOT NULL,
        total_allowances DECIMAL(12,2) DEFAULT 0,
        total_bonuses DECIMAL(12,2) DEFAULT 0,
        total_deductions DECIMAL(12,2) DEFAULT 0,
        tax_amount DECIMAL(12,2) DEFAULT 0,
        gross_salary DECIMAL(12,2) DEFAULT 0,
        net_salary DECIMAL(12,2) NOT NULL,
        breakdown_json JSON NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'settings' => "CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(100) NOT NULL UNIQUE,
        value TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'audit_logs' => "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(100) NULL,
        record_id INT UNSIGNED NULL,
        old_values JSON NULL,
        new_values JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        payload TEXT NOT NULL,
        last_activity INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'announcements' => "CREATE TABLE IF NOT EXISTS announcements (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
        target_audience ENUM('all', 'employees', 'managers') NOT NULL DEFAULT 'all',
        created_by INT UNSIGNED NOT NULL,
        is_published TINYINT(1) DEFAULT 1,
        published_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_published (is_published, published_at),
        INDEX idx_target (target_audience),
        INDEX idx_priority (priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$created = 0;
$existed = 0;
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<div class='log-item success'><span class='icon'>✓</span> $name</div>";
        $created++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<div class='log-item info'><span class='icon'>○</span> $name (already exists)</div>";
            $existed++;
        } else {
            echo "<div class='log-item error'><span class='icon'>✗</span> $name: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
echo '</div>';

// STEP 4: Seed Data
$stepNum = $reset ? 4 : 3;
echo '<div class="card">';
echo "<h2><span class='step'>$stepNum</span> Seeding Sample Data</h2>";

$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

if ($userCount > 0 && !$reset) {
    echo "<div class='log-item info'><span class='icon'>ℹ</span> Data already exists ($userCount users). Use <a href='?reset=1' style='color:#fde047;'>?reset=1</a> to reset.</div>";
} else {
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    
    // Departments
    $pdo->exec("INSERT INTO departments (id, name, description, status) VALUES 
        (1, 'Engineering', 'Software development and IT', 'active'),
        (2, 'Human Resources', 'HR and recruitment', 'active'),
        (3, 'Finance', 'Accounting and finance', 'active'),
        (4, 'Marketing', 'Sales and marketing', 'active')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Departments (4)</div>";
    
    // Users
    $stmt = $pdo->prepare("INSERT INTO users (id, uuid, email, password_hash, role, first_name, last_name, phone, status) VALUES (?, UUID(), ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([1, 'admin@payrollpro.com', $hash, 'super_admin', 'Super', 'Admin', '+1234567890']);
    $stmt->execute([2, 'manager.eng@payrollpro.com', $hash, 'manager', 'John', 'Manager', '+1234567891']);
    $stmt->execute([3, 'manager.hr@payrollpro.com', $hash, 'manager', 'Jane', 'HRManager', '+1234567892']);
    $stmt->execute([4, 'emp1@payrollpro.com', $hash, 'employee', 'Alice', 'Developer', '+1234567893']);
    $stmt->execute([5, 'emp2@payrollpro.com', $hash, 'employee', 'Bob', 'Designer', '+1234567894']);
    $stmt->execute([6, 'emp3@payrollpro.com', $hash, 'employee', 'Charlie', 'Analyst', '+1234567895']);
    echo "<div class='log-item success'><span class='icon'>✓</span> Users (6)</div>";
    
    // Employees (including managers as employees)
    $pdo->exec("INSERT INTO employees (id, user_id, employee_code, manager_user_id, department_id, hire_date, base_salary, tax_class, bank_account, status) VALUES
        (1, 2, 'MGR001', 1, 1, '2022-06-01', 7000.00, 'A', '9876543210', 'active'),
        (2, 3, 'MGR002', 1, 2, '2022-08-01', 6500.00, 'A', '9876543211', 'active'),
        (3, 4, 'EMP001', 2, 1, '2023-01-15', 5000.00, 'A', '1234567890', 'active'),
        (4, 5, 'EMP002', 2, 1, '2023-03-01', 4500.00, 'A', '1234567891', 'active'),
        (5, 6, 'EMP003', 3, 2, '2023-06-01', 4000.00, 'B', '1234567892', 'active')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Employees (5 - includes 2 managers)</div>";
    
    // Allowances
    $pdo->exec("INSERT INTO allowances (employee_id, type, amount, is_recurring, description, effective_from) VALUES
        (1, 'housing', 800.00, 1, 'Manager housing allowance', '2022-06-01'),
        (1, 'transport', 300.00, 1, 'Manager transport allowance', '2022-06-01'),
        (2, 'housing', 750.00, 1, 'Manager housing allowance', '2022-08-01'),
        (2, 'transport', 300.00, 1, 'Manager transport allowance', '2022-08-01'),
        (3, 'housing', 500.00, 1, 'Housing allowance', '2023-01-15'),
        (3, 'transport', 200.00, 1, 'Transport allowance', '2023-01-15'),
        (4, 'housing', 400.00, 1, 'Housing allowance', '2023-03-01'),
        (5, 'meal', 150.00, 1, 'Meal allowance', '2023-06-01')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Allowances (8)</div>";
    
    // Deductions
    $pdo->exec("INSERT INTO deductions (employee_id, type, amount, is_recurring, description, effective_from) VALUES
        (1, 'pension', 350.00, 1, 'Manager pension contribution', '2022-06-01'),
        (1, 'insurance', 150.00, 1, 'Manager health insurance', '2022-06-01'),
        (2, 'pension', 325.00, 1, 'Manager pension contribution', '2022-08-01'),
        (2, 'insurance', 150.00, 1, 'Manager health insurance', '2022-08-01'),
        (3, 'pension', 250.00, 1, 'Pension contribution', '2023-01-15'),
        (4, 'insurance', 100.00, 1, 'Health insurance', '2023-03-01'),
        (5, 'loan', 200.00, 1, 'Personal loan', '2023-06-01')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Deductions (7)</div>";
    
    // Sample Attendance
    $today = date('Y-m-d');
    $pdo->exec("INSERT INTO attendance (employee_id, date, clock_in, clock_out, status) VALUES
        (3, '$today', '09:00:00', '17:30:00', 'present'),
        (4, '$today', '09:15:00', '17:45:00', 'present'),
        (5, '$today', '09:05:00', NULL, 'present')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Attendance (3 sample records)</div>";
    
    // Sample Announcements
    $now = date('Y-m-d H:i:s');
    $pdo->exec("INSERT INTO announcements (title, content, priority, target_audience, created_by, is_published, published_at) VALUES
        ('Welcome to PayrollPro', 'Welcome to our new payroll management system! This platform will help streamline all payroll operations.', 'normal', 'all', 1, 1, '$now'),
        ('Holiday Schedule 2025', 'Please note the upcoming holiday schedule. The office will be closed on December 25th and January 1st.', 'high', 'all', 1, 1, '$now'),
        ('System Maintenance Notice', 'The payroll system will undergo scheduled maintenance this weekend. Please complete any pending tasks before Friday 5 PM.', 'urgent', 'all', 1, 1, '$now')");
    echo "<div class='log-item success'><span class='icon'>✓</span> Announcements (3 sample records)</div>";
}
echo '</div>';

// STEP 5: Verification
$stepNum = $reset ? 5 : 4;
echo '<div class="card">';
echo "<h2><span class='step'>$stepNum</span> Database Statistics</h2>";
echo '<div class="stats">';

$checkTables = [
    'departments' => '🏢',
    'users' => '👥',
    'employees' => '👤',
    'allowances' => '➕',
    'deductions' => '➖',
    'bonuses' => '🎁',
    'attendance' => '📋',
    'leaves' => '📅',
    'payroll_runs' => '💰',
    'announcements' => '📢'
];

foreach ($checkTables as $table => $icon) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "<div class='stat-card'>";
    echo "<div class='label'>$icon " . ucfirst($table) . "</div>";
    echo "<div class='value'>$count</div>";
    echo "</div>";
}
echo '</div></div>';

// STEP 6: Demo Accounts
$stepNum = $reset ? 6 : 5;
echo '<div class="card">';
echo "<h2><span class='step'>$stepNum</span> Demo Accounts</h2>";
echo '<table class="accounts-table">';
echo '<thead><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>';
echo '<tbody>';
echo '<tr><td>🔐 Super Admin</td><td>admin@payrollpro.com</td><td>password123</td></tr>';
echo '<tr><td>👔 Manager (Engineering)</td><td>manager.eng@payrollpro.com</td><td>password123</td></tr>';
echo '<tr><td>👔 Manager (HR)</td><td>manager.hr@payrollpro.com</td><td>password123</td></tr>';
echo '<tr><td>👤 Employee</td><td>emp1@payrollpro.com</td><td>password123</td></tr>';
echo '<tr><td>👤 Employee</td><td>emp2@payrollpro.com</td><td>password123</td></tr>';
echo '<tr><td>👤 Employee</td><td>emp3@payrollpro.com</td><td>password123</td></tr>';
echo '</tbody></table>';
echo '</div>';

// Complete
echo '<div class="card" style="text-align:center; background: linear-gradient(135deg, rgba(34,197,94,.15), rgba(16,185,129,.1));">';
echo '<h2 style="color:#86efac; font-size:28px; margin-bottom:20px;">✅ Installation Complete!</h2>';
echo '<p style="color:#9fb4c7; margin-bottom:24px;">Your PayrollPro system is ready to use</p>';
echo '<a href="login.php" class="btn">Go to Login →</a>';
if (!$reset) {
    echo '<a href="?reset=1" class="btn btn-danger">Reset Database</a>';
}
echo '</div>';
?>

    </div>
</body>
</html>
