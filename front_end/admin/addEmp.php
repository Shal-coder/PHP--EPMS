<?php
/**
 * Add Employee Page
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Department.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Employee.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$departments = Department::getAll();
$managers = User::getManagers();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        // Check if email exists
        if (User::findByEmail($_POST['email'])) {
            $error = 'Email already exists.';
        } else {
            try {
                $employee = Employee::create([
                    'email' => $_POST['email'],
                    'password' => $_POST['password'] ?: 'password123',
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'phone' => $_POST['phone'],
                    'department_id' => $_POST['department_id'] ?: null,
                    'manager_user_id' => $_POST['manager_user_id'] ?: null,
                    'hire_date' => $_POST['hire_date'],
                    'base_salary' => $_POST['base_salary'],
                    'tax_class' => $_POST['tax_class'],
                    'bank_account' => $_POST['bank_account']
                ]);
                $message = 'Employee created successfully!';
            } catch (Exception $e) {
                $error = 'Failed to create employee: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; }
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { font-size: 11px; color: #86efac; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .2s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(34,197,94,.14); color: #a6f3bf; }
        .main { padding: 28px; overflow-y: auto; }
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 28px; font-weight: 800; }
        .btn-back { padding: 10px 20px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #c9d7e6; text-decoration: none; border-radius: 10px; font-weight: 600; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        .form-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { margin-bottom: 0; }
        .form-group.full { grid-column: span 2; }
        .form-group label { display: block; font-size: 14px; color: #9fb4c7; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #e6edf5; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: rgba(34,197,94,.4); }
        .form-group input::placeholder { color: #6b7c93; }
        .form-actions { margin-top: 30px; display: flex; gap: 12px; }
        .btn-submit { padding: 14px 30px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; }
        .btn-reset { padding: 14px 30px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #c9d7e6; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } .form-grid { grid-template-columns: 1fr; } .form-group.full { grid-column: span 1; } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="user-mini"><span class="dot"></span><span><?= htmlspecialchars($user->getFullName()) ?></span></div>
        </header>
        <div class="layout">
            <aside class="sidenav">
                <div class="user-card">
                    <div class="avatar"><?= strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) ?></div>
                    <div>
                        <p class="label">Super Admin</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link active" href="employees.php"><span>👥</span> Employees</a>
                    <a class="nav-link" href="departments.php"><span>🏢</span> Departments</a>
                    <a class="nav-link" href="payrolls.php"><span>💰</span> Payrolls</a>
                    <a class="nav-link" href="allowances.php"><span>💵</span> Allowances</a>
                    <a class="nav-link" href="deductions.php"><span>➖</span> Deductions</a>
                    <a class="nav-link" href="bonuses.php"><span>🎁</span> Bonuses</a>
                    <a class="nav-link" href="users.php"><span>🔐</span> Users</a>
                    <a class="nav-link" href="announcement.php"><span>📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <h1>➕ Add Employee</h1>
                    <a href="employees.php" class="btn-back">← Back to Employees</a>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <div class="form-card">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                        <div class="form-grid">
                            <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required placeholder="John"></div>
                            <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" required placeholder="Doe"></div>
                            <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="john@example.com"></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="+1234567890"></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Leave blank for default"></div>
                            <div class="form-group"><label>Hire Date *</label><input type="date" name="hire_date" required value="<?= date('Y-m-d') ?>"></div>
                            <div class="form-group"><label>Department</label><select name="department_id"><option value="">Select Department</option><?php foreach ($departments as $d): ?><option value="<?= $d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Manager</label><select name="manager_user_id"><option value="">Select Manager</option><?php foreach ($managers as $m): ?><option value="<?= $m->id ?>"><?= htmlspecialchars($m->getFullName()) ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Base Salary *</label><input type="number" name="base_salary" required step="0.01" placeholder="5000.00"></div>
                            <div class="form-group"><label>Tax Class</label><select name="tax_class"><option value="A">Class A (Standard)</option><option value="B">Class B (Reduced)</option></select></div>
                            <div class="form-group full"><label>Bank Account</label><input type="text" name="bank_account" placeholder="Bank account number"></div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Create Employee</button>
                            <button type="reset" class="btn-reset">Reset Form</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>