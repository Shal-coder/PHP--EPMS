<?php
/**
 * Employee Payslips Page - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Payroll.php';

RoleMiddleware::require('employee');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);

if (!$employee) {
    die('Employee profile not found.');
}

$payslips = Payroll::getEmployeePayslips($employee->id);

// Get current allowances, deductions, and bonuses
$currentDate = date('Y-m-d');
$monthStart = date('Y-m-01');

// Get active allowances
$allowances = Database::query(
    "SELECT * FROM allowances 
     WHERE employee_id = ? 
     AND effective_from <= ? 
     AND (effective_to IS NULL OR effective_to >= ?)
     AND is_recurring = 1
     ORDER BY type",
    [$employee->id, $currentDate, $currentDate]
)->fetchAll();

// Get active deductions
$deductions = Database::query(
    "SELECT * FROM deductions 
     WHERE employee_id = ? 
     AND effective_from <= ? 
     AND (effective_to IS NULL OR effective_to >= ?)
     AND is_recurring = 1
     ORDER BY type",
    [$employee->id, $currentDate, $currentDate]
)->fetchAll();

// Get bonuses from this month
$bonuses = Database::query(
    "SELECT * FROM bonuses 
     WHERE employee_id = ? 
     AND date_awarded >= ?
     ORDER BY date_awarded DESC",
    [$employee->id, $monthStart]
)->fetchAll();

// Calculate totals
$totalAllowances = array_sum(array_column($allowances, 'amount'));
$totalDeductions = array_sum(array_column($deductions, 'amount'));
$totalBonuses = array_sum(array_column($bonuses, 'amount'));
$estimatedNet = $employee->base_salary + $totalAllowances + $totalBonuses - $totalDeductions;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        .shell { min-height: 100vh; display: flex; flex-direction: column; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.08), transparent 30%), radial-gradient(circle at 80% 10%, rgba(59,130,246,.09), transparent 32%), #0b1320; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .brand { font-weight: 800; letter-spacing: .08em; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { margin: 0; font-size: 11px; color: #67e8f9; letter-spacing: .1em; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin: 4px 0 0; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; border: 1px solid transparent; transition: all .2s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; border-color: rgba(6,182,212,.28); }
        .nav-icon { width: 20px; text-align: center; }
        
        .main { padding: 28px; overflow-y: auto; }
        
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(6,182,212,.15); color: #67e8f9; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .05em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        tr:last-child td { border-bottom: none; }
        
        .amount { font-weight: 700; }
        .amount.positive { color: #86efac; }
        .amount.negative { color: #fca5a5; }
        .amount.net { color: #67e8f9; font-size: 16px; }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.approved { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.processed { background: rgba(59,130,246,.15); color: #93c5fd; }
        
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; background: rgba(6,182,212,.15); color: #67e8f9; transition: all .2s; }
        .action-btn:hover { background: rgba(6,182,212,.25); }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        
        .breakdown-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .breakdown-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 20px; }
        .breakdown-card h4 { font-size: 14px; color: #67e8f9; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; margin-bottom: 16px; }
        .breakdown-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
        .breakdown-item:last-child { border-bottom: none; }
        .breakdown-label { color: #9fb4c7; font-size: 13px; }
        .breakdown-value { font-weight: 600; font-size: 14px; }
        .breakdown-value.positive { color: #86efac; }
        .breakdown-value.negative { color: #fca5a5; }
        .breakdown-total { background: rgba(6,182,212,.05); margin: 12px -12px -12px; padding: 12px; border-radius: 0 0 14px 14px; display: flex; justify-content: space-between; font-weight: 700; }
        .item-badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: rgba(6,182,212,.15); color: #67e8f9; margin-left: 6px; }
        .section-title { font-size: 18px; font-weight: 700; margin: 28px 0 16px; }
        
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } table { display: block; overflow-x: auto; } }
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
                        <p class="label">Employee</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                    <a class="nav-link" href="profile.php"><span class="nav-icon">👤</span> My Profile</a>
                    <a class="nav-link active" href="payroll.php"><span class="nav-icon">💰</span> Payslips</a>
                    <a class="nav-link" href="leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                    <a class="nav-link" href="attendance.php"><span class="nav-icon">📋</span> Attendance</a>
                    <a class="nav-link" href="../../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <h1>💰 My Payroll & Salary</h1>
                    <p class="lede">Detailed breakdown of your salary components and payslip history</p>
                </div>

                <h3 class="section-title">Current Salary Components</h3>
                <div class="breakdown-grid">
                    <div class="breakdown-card">
                        <h4>➕ Allowances</h4>
                        <?php if (!empty($allowances)): ?>
                            <?php foreach ($allowances as $allow): ?>
                            <div class="breakdown-item">
                                <span class="breakdown-label">
                                    <?= ucfirst($allow['type']) ?>
                                    <?php if ($allow['is_recurring']): ?><span class="item-badge">Monthly</span><?php endif; ?>
                                </span>
                                <span class="breakdown-value positive">+$<?= number_format($allow['amount'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="breakdown-total">
                                <span>Total Allowances</span>
                                <span style="color: #86efac;">+$<?= number_format($totalAllowances, 2) ?></span>
                            </div>
                        <?php else: ?>
                            <p style="color: #64748b; font-size: 13px; font-style: italic;">No active allowances</p>
                        <?php endif; ?>
                    </div>

                    <div class="breakdown-card">
                        <h4>🎁 Bonuses (This Month)</h4>
                        <?php if (!empty($bonuses)): ?>
                            <?php foreach ($bonuses as $bonus): ?>
                            <div class="breakdown-item">
                                <span class="breakdown-label">
                                    <?= htmlspecialchars($bonus['reason']) ?>
                                    <span class="item-badge"><?= date('M d', strtotime($bonus['date_awarded'])) ?></span>
                                </span>
                                <span class="breakdown-value positive">+$<?= number_format($bonus['amount'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="breakdown-total">
                                <span>Total Bonuses</span>
                                <span style="color: #fde047;">+$<?= number_format($totalBonuses, 2) ?></span>
                            </div>
                        <?php else: ?>
                            <p style="color: #64748b; font-size: 13px; font-style: italic;">No bonuses this month</p>
                        <?php endif; ?>
                    </div>

                    <div class="breakdown-card">
                        <h4>➖ Deductions</h4>
                        <?php if (!empty($deductions)): ?>
                            <?php foreach ($deductions as $deduct): ?>
                            <div class="breakdown-item">
                                <span class="breakdown-label">
                                    <?= ucfirst(str_replace('_', ' ', $deduct['type'])) ?>
                                    <?php if ($deduct['is_recurring']): ?><span class="item-badge">Monthly</span><?php endif; ?>
                                </span>
                                <span class="breakdown-value negative">-$<?= number_format($deduct['amount'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="breakdown-total">
                                <span>Total Deductions</span>
                                <span style="color: #fca5a5;">-$<?= number_format($totalDeductions, 2) ?></span>
                            </div>
                        <?php else: ?>
                            <p style="color: #64748b; font-size: 13px; font-style: italic;">No active deductions</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; padding: 20px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="color: #9fb4c7; font-size: 13px; margin-bottom: 4px;">Estimated Net Salary (Before Tax)</p>
                        <p style="color: #67e8f9; font-size: 24px; font-weight: 800;">$<?= number_format($estimatedNet, 2) ?></p>
                    </div>
                    <div style="text-align: right;">
                        <p style="color: #9fb4c7; font-size: 13px;">Base: $<?= number_format($employee->base_salary, 2) ?></p>
                        <p style="color: #86efac; font-size: 13px;">+$<?= number_format($totalAllowances + $totalBonuses, 2) ?></p>
                        <p style="color: #fca5a5; font-size: 13px;">-$<?= number_format($totalDeductions, 2) ?></p>
                    </div>
                </div>

                <h3 class="section-title">Payslip History</h3>
                <p style="color: #9fb4c7; font-size: 14px; margin-bottom: 16px;">
                    Official salary statements generated after payroll processing. These include tax calculations and final net amounts.
                </p>
                <div class="table-container">
                    <div class="table-header">
                        <h3>Payslip History</h3>
                        <span class="badge"><?= count($payslips) ?> payslips</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Base Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Tax</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payslips as $slip): ?>
                            <tr>
                                <td><?= date('M Y', strtotime($slip['period_start'])) ?></td>
                                <td class="amount">$<?= number_format($slip['base_salary'], 2) ?></td>
                                <td class="amount positive">+$<?= number_format($slip['total_allowances'], 2) ?></td>
                                <td class="amount negative">-$<?= number_format($slip['total_deductions'], 2) ?></td>
                                <td class="amount negative">-$<?= number_format($slip['tax_amount'], 2) ?></td>
                                <td class="amount net">$<?= number_format($slip['net_salary'], 2) ?></td>
                                <td><span class="status-badge <?= $slip['run_status'] ?>"><?= ucfirst($slip['run_status']) ?></span></td>
                                <td><a href="viewPayslip.php?id=<?= $slip['id'] ?>" class="action-btn">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($payslips)): ?>
                    <div class="empty-state">
                        <p>No payslips available yet. Your payslips will appear here after payroll is processed.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
