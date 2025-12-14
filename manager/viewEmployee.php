<?php
/**
 * View Employee Details - Manager
 * Managers can only view employees under their supervision
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Attendance.php';
require_once __DIR__ . '/../app/Models/Leave.php';
require_once __DIR__ . '/../app/Models/Payroll.php';

RoleMiddleware::require('manager');

$user = AuthMiddleware::user();
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$employee = Employee::find($employeeId);

if (!$employee) {
    header('Location: employees.php');
    exit;
}

// Security: Ensure this employee belongs to the manager
if (!$employee->belongsToManager($user->id)) {
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
    <title>View Employee | PayrollPro Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(139,92,246,.12), transparent 35%), radial-gradient(circle at 80% 10%, rgba(167,139,250,.09), transparent 38%), #0b1320; animation: fadeIn 0.6s ease-out; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(11,19,32,.95); backdrop-filter: blur(20px); box-shadow: 0 4px 20px rgba(0,0,0,.3); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; box-shadow: 0 4px 12px rgba(139,92,246,.4); }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.06); border-radius: 10px; border: 1px solid rgba(255,255,255,.05); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #8b5cf6; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        
        .main { padding: 32px; max-width: 1200px; margin: 0 auto; }
        
        .page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; animation: fadeInUp 0.6s ease-out; }
        .page-header h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #c4b5fd, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .btn-back { padding: 12px 24px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #c9d7e6; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all .3s; }
        .btn-back:hover { background: rgba(255,255,255,.1); transform: translateY(-2px); }
        
        .profile-header { display: flex; gap: 28px; align-items: center; background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(167,139,250,.05)); border: 1px solid rgba(139,92,246,.2); border-radius: 20px; padding: 36px; margin-bottom: 28px; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.1s both; position: relative; overflow: hidden; }
        .profile-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.02), transparent); animation: shimmer 3s infinite; }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        .profile-header:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(139,92,246,.2); }
        
        .profile-avatar { width: 110px; height: 110px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 40px; color: #fff; box-shadow: 0 8px 24px rgba(139,92,246,.4); position: relative; }
        .profile-avatar::after { content: '✓'; position: absolute; bottom: 5px; right: 5px; width: 28px; height: 28px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 3px solid #0b1320; }
        
        .profile-info h2 { font-size: 26px; margin-bottom: 6px; font-weight: 800; }
        .profile-info .code { color: #c4b5fd; font-size: 15px; margin-bottom: 12px; font-weight: 600; letter-spacing: .05em; }
        .profile-info .meta { color: #9fb4c7; font-size: 14px; display: flex; flex-wrap: wrap; gap: 20px; }
        .profile-info .meta span { display: flex; align-items: center; gap: 6px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
        
        .card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 28px; transition: all .4s; animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .card:nth-child(1) { animation-delay: 0.2s; }
        .card:nth-child(2) { animation-delay: 0.3s; }
        .card:nth-child(3) { animation-delay: 0.4s; }
        .card:nth-child(4) { animation-delay: 0.5s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); border-color: rgba(139,92,246,.2); }
        
        .card h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid rgba(139,92,246,.2); color: #c4b5fd; display: flex; align-items: center; gap: 10px; }
        .card h3::before { content: ''; width: 4px; height: 24px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 2px; }
        
        .info-row { display: flex; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.04); background: rgba(255,255,255,.02); border-radius: 8px; margin-bottom: 8px; transition: all .3s; }
        .info-row:hover { background: rgba(255,255,255,.05); transform: translateX(4px); }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #9fb4c7; font-size: 14px; font-weight: 500; }
        .info-row .value { font-weight: 600; font-size: 14px; }
        
        .salary-value { color: #c4b5fd; font-size: 32px; font-weight: 800; text-shadow: 0 0 20px rgba(139,92,246,.3); }
        
        .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; border: 1px solid; }
        .status-badge.active { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.3); }
        .status-badge.present { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.3); }
        .status-badge.absent { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }
        .status-badge.pending { background: rgba(251,191,36,.15); color: #fde047; border-color: rgba(251,191,36,.3); }
        .status-badge.approved { background: rgba(59,130,246,.15); color: #93c5fd; border-color: rgba(59,130,246,.3); }
        
        .mini-table { width: 100%; font-size: 13px; }
        .mini-table th { text-align: left; color: #9fb4c7; font-weight: 700; padding: 10px 0; text-transform: uppercase; font-size: 11px; letter-spacing: .05em; }
        .mini-table td { padding: 12px 0; border-top: 1px solid rgba(255,255,255,.04); }
        .mini-table tr:hover td { background: rgba(255,255,255,.02); }
        
        @media (max-width: 768px) { 
            .profile-header { flex-direction: column; text-align: center; }
            .profile-info .meta { justify-content: center; }
        }
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
                <a href="employees.php" class="btn-back">← Back to My Team</a>
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
                    <p style="color:#9fb4c7;font-size:13px;margin-top:6px;margin-bottom:20px;">Monthly Base Salary</p>
                    <div class="info-row">
                        <span class="label">Tax Class</span>
                        <span class="value">Class <?= $employee->tax_class ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Bank Account</span>
                        <span class="value"><?= $employee->bank_account ? '****' . substr($employee->bank_account, -4) : 'N/A' ?></span>
                    </div>
                </div>
                
                <div class="card">
                    <h3>📋 Employment Details</h3>
                    <div class="info-row">
                        <span class="label">Hire Date</span>
                        <span class="value"><?= date('M d, Y', strtotime($employee->hire_date)) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Manager</span>
                        <span class="value"><?= htmlspecialchars($employee->manager_name ?? 'None') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status</span>
                        <span class="value"><span class="status-badge <?= $employee->status ?>"><?= ucfirst($employee->status) ?></span></span>
                    </div>
                </div>
                
                <div class="card">
                    <h3>📅 Recent Attendance</h3>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($attendance, 0, 5) as $a): ?>
                            <tr>
                                <td><?= date('M d', strtotime($a->date)) ?></td>
                                <td><?= $a->check_in ?? '-' ?></td>
                                <td><?= $a->check_out ?? '-' ?></td>
                                <td><span class="status-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($attendance)): ?>
                            <tr><td colspan="4" style="color:#9fb4c7;text-align:center;padding:20px;">No attendance records this month</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h3>📝 Recent Leave Requests</h3>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($leaves, 0, 5) as $l): ?>
                            <tr>
                                <td><?= ucfirst($l->type) ?></td>
                                <td><?= date('M d', strtotime($l->start_date)) ?> - <?= date('M d', strtotime($l->end_date)) ?></td>
                                <td><span class="status-badge <?= $l->status ?>"><?= ucfirst($l->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($leaves)): ?>
                            <tr><td colspan="3" style="color:#9fb4c7;text-align:center;padding:20px;">No leave requests</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
