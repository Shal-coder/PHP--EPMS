<?php
/**
 * Edit Employee - Admin
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Department.php';

RoleMiddleware::require('super_admin');

$currentUser = AuthMiddleware::user();
$empId = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

if (!$empId) {
    header('Location: employees.php');
    exit;
}

$employee = Employee::find($empId);
if (!$employee) {
    header('Location: employees.php');
    exit;
}

$empUser = User::find($employee->user_id);
$departments = Department::getAll();
$managers = User::getManagers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $empData = [
            'department_id' => (int)$_POST['department_id'] ?: null,
            'manager_user_id' => (int)$_POST['manager_user_id'] ?: null,
            'base_salary' => (float)$_POST['base_salary'],
            'tax_class' => $_POST['tax_class'] ?? 'A',
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'status' => $_POST['emp_status'] ?? 'active'
        ];
        
        $userData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        if ($employee->update($empData) && $empUser->update($userData)) {
            $message = 'Employee updated successfully.';
            $employee = Employee::find($empId);
            $empUser = User::find($employee->user_id);
        } else {
            $error = 'Failed to update employee.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); }
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
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        .btn-back { padding: 10px 20px; background: rgba(255,255,255,.1); color: #e6edf5; text-decoration: none; border-radius: 8px; font-weight: 500; }
        .btn-back:hover { background: rgba(255,255,255,.15); }
        
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        
        .form-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 30px; max-width: 700px; }
        .form-card h2 { font-size: 18px; font-weight: 700; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,.06); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #9fb4c7; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #e6edf5; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: rgba(34,197,94,.4); }
        .form-group input:disabled { opacity: 0.6; cursor: not-allowed; }
        .form-group select option { background: #1a2332; }
        .form-group small { display: block; margin-top: 6px; color: #64748b; font-size: 12px; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .section-title { font-size: 14px; font-weight: 700; color: #86efac; text-transform: uppercase; margin: 30px 0 16px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,.06); }
        
        .btn-submit { padding: 14px 28px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all .2s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(34,197,94,.3); }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="user-mini"><span class="dot"></span><span><?= htmlspecialchars($currentUser->getFullName()) ?></span></div>
        </header>
        <div class="layout">
            <aside class="sidenav">
                <div class="user-card">
                    <div class="avatar"><?= strtoupper(substr($currentUser->first_name, 0, 1) . substr($currentUser->last_name, 0, 1)) ?></div>
                    <div>
                        <p class="label">Super Admin</p>
                        <p class="name"><?= htmlspecialchars($currentUser->getFullName()) ?></p>
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
                    <div>
                        <h1>✏️ Edit Employee</h1>
                        <p class="lede"><?= htmlspecialchars($employee->employee_code) ?> - <?= htmlspecialchars($empUser->getFullName()) ?></p>
                    </div>
                    <a href="employees.php" class="btn-back">← Back to Employees</a>
                </div>
                
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <div class="form-card">
                    <h2>Employee Information</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Employee Code</label>
                                <input type="text" value="<?= htmlspecialchars($employee->employee_code) ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?= htmlspecialchars($empUser->email) ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($empUser->first_name) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($empUser->last_name) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($empUser->phone ?? '') ?>">
                        </div>
                        
                        <div class="section-title">Employment Details</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id">
                                    <option value="">-- No Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept->id ?>" <?= $employee->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Manager</label>
                                <select name="manager_user_id">
                                    <option value="">-- No Manager --</option>
                                    <?php foreach ($managers as $mgr): ?>
                                    <option value="<?= $mgr->id ?>" <?= $employee->manager_user_id == $mgr->id ? 'selected' : '' ?>><?= htmlspecialchars($mgr->getFullName()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Base Salary ($)</label>
                                <input type="number" name="base_salary" step="0.01" min="0" value="<?= $employee->base_salary ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tax Class</label>
                                <select name="tax_class">
                                    <option value="A" <?= $employee->tax_class === 'A' ? 'selected' : '' ?>>Class A</option>
                                    <option value="B" <?= $employee->tax_class === 'B' ? 'selected' : '' ?>>Class B</option>
                                    <option value="C" <?= $employee->tax_class === 'C' ? 'selected' : '' ?>>Class C</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="emp_status">
                                <option value="active" <?= $employee->status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="on_leave" <?= $employee->status === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                <option value="terminated" <?= $employee->status === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                            </select>
                        </div>
                        
                        <div class="section-title">Bank Details</div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name" value="<?= htmlspecialchars($employee->bank_name ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Bank Account</label>
                                <input type="text" name="bank_account" value="<?= htmlspecialchars($employee->bank_account ?? '') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">Save Changes</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
