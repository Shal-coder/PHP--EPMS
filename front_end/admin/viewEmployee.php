<?php
/**
 * View Employee Details - Admin
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Attendance.php';
require_once __DIR__ . '/../../app/Models/Leave.php';
require_once __DIR__ . '/../../app/Models/Payroll.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId) {
    $employee = Employee::findByUserId($userId);
} else {
    $employee = Employee::find($employeeId);
}

if (!$employee) {
    header('Location: employees.php');
    exit;
}

$empUser = User::find($employee->user_id);
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$attendance = Attendance::getByEmployee($employee->id, $monthStart, $monthEnd);
$leaves = Leave::getByEmployee($employee->id);
$payslips = Payroll::getEmployeePayslips($employee->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; }
        .main { padding: 28px; max-width: 1200px; margin: 0 auto; }
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 28px; font-weight: 800; }
        .btn-back { padding: 10px 20px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #c9d7e6; text-decoration: none; border-radius: 10px; font-weight: 600; }
        .profile-header { display: flex; gap: 24px; align-items: center; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 30px; margin-bottom: 24px; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 36px; color: #fff; }
        .profile-info h2 { font-size: 24px; margin-bottom: 4px; }
        .profile-info .code { color: #86efac; font-size: 14px; margin-bottom: 8px; }
        .profile-info .meta { color: #9fb4c7; font-size: 14px; }
        .profile-info .meta span { margin-right: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 24px; }
        .card h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #9fb4c7; font-size: 14px; }
        .info-row .value { font-weight: 600; font-size: 14px; }
        .salary-value { color: #86efac; font-size: 24px; font-weight: 800; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.active { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.present { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.absent { background: rgba(239,68,68,.15); color: #fca5a5; }
        .status-badge.pending { background: rgba(251,191,36,.15); color: #fde047; }
        .status-badge.approved { background: rgba(59,130,246,.15); color: #93c5fd; }
        .mini-table { width: 100%; font-size: 13px; }
        .mini-table th { text-align: left; color: #9fb4c7; font-weight: 600; padding: 8px 0; }
        .mini-table td { padding: 8px 0; border-top: 1px solid rgba(255,255,255,.04); }
        @media (max-width: 768px) { .profile-header { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="user-mini"><span class="dot"></span><span><?= htmlspecialchars($user->getFullName()) ?></span></div>
        </header>
        <main class="main">
            <div class="page-header">
                <h1>👤 Employee Details</h1>
                <a href="employees.php" class="btn-back">← Back to Employees</a>
            </div>
            <div class="profile-header">
                <div class="profile-avatar"><?= strtoupper(substr($empUser->first_name, 0, 1) . substr($empUser->last_name, 0, 1)) ?></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($empUser->getFullName()) ?></h2>
                    <div class="code"><?= htmlspecialchars($employee->employee_code) ?></div>
                    <div class="meta">
                        <span>📧 <?= htmlspecialchars($empUser->email) ?></span>
                        <span>📱 <?= htmlspecialchars($empUser->phone ?? 'N/A') ?></span>
                        <span>🏢 <?= htmlspecialchars($employee->department_name ?? 'Unassigned') ?></span>
                    </div>
                </div>
            </div>
            <div class="grid">
                <div class="card">
                    <h3>💰 Salary Information</h3>
                    <div class="salary-value">$<?= number_format($employee->base_salary, 2) ?></div>
                    <p style="color:#9fb4c7;font-size:13px;margin-top:4px;">Monthly Base Salary</p>
                    <div class="info-row" style="margin-top:16px;"><span class="label">Tax Class</span><span class="value">Class <?= $employee->tax_class ?></span></div>
                    <div class="info-row"><span class="label">Bank Account</span><span class="value"><?= $employee->bank_account ? '****' . substr($employee->bank_account, -4) : 'N/A' ?></span></div>
                </div>
                <div class="card">
                    <h3>📋 Employment Details</h3>
                    <div class="info-row"><span class="label">Hire Date</span><span class="value"><?= date('M d, Y', strtotime($employee->hire_date)) ?></span></div>
                    <div class="info-row"><span class="label">Manager</span><span class="value"><?= htmlspecialchars($employee->manager_name ?? 'None') ?></span></div>
                    <div class="info-row"><span class="label">Status</span><span class="value"><span class="status-badge <?= $employee->status ?>"><?= ucfirst($employee->status) ?></span></span></div>
                </div>
                <div class="card">
                    <h3>📅 Recent Attendance</h3>
                    <table class="mini-table">
                        <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($attendance, 0, 5) as $a): ?>
                            <tr>
                                <td><?= date('M d', strtotime($a->date)) ?></td>
                                <td><?= $a->check_in ?? '-' ?></td>
                                <td><?= $a->check_out ?? '-' ?></td>
                                <td><span class="status-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($attendance)): ?><tr><td colspan="4" style="color:#9fb4c7;">No records</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h3>📝 Recent Leave Requests</h3>
                    <table class="mini-table">
                        <thead><tr><th>Type</th><th>Dates</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($leaves, 0, 5) as $l): ?>
                            <tr>
                                <td><?= ucfirst($l->type) ?></td>
                                <td><?= date('M d', strtotime($l->start_date)) ?> - <?= date('M d', strtotime($l->end_date)) ?></td>
                                <td><span class="status-badge <?= $l->status ?>"><?= ucfirst($l->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($leaves)): ?><tr><td colspan="3" style="color:#9fb4c7;">No requests</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>