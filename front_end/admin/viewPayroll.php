<?php
/**
 * View Payroll Details - Admin
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Payroll.php';
require_once __DIR__ . '/../../app/Config/Database.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$payrollId = (int)($_GET['id'] ?? 0);

if (!$payrollId) {
    header('Location: payrolls.php');
    exit;
}

$payrollObj = Payroll::find($payrollId);
if (!$payrollObj) {
    header('Location: payrolls.php');
    exit;
}

// Get payroll data as array for display
$stmt = Database::query("SELECT * FROM payroll_runs WHERE id = ?", [$payrollId]);
$payroll = $stmt->fetch();

$items = $payrollObj->getItems();
$totals = [
    'base' => 0,
    'allowances' => 0,
    'bonuses' => 0,
    'deductions' => 0,
    'tax' => 0,
    'net' => 0
];

foreach ($items as $item) {
    $totals['base'] += $item['base_salary'];
    $totals['allowances'] += $item['total_allowances'];
    $totals['bonuses'] += $item['total_bonuses'];
    $totals['deductions'] += $item['total_deductions'];
    $totals['tax'] += $item['tax_amount'];
    $totals['net'] += $item['net_salary'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Details | PayrollPro Admin</title>
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
        
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .summary-card { padding: 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.03); }
        .summary-card .label { color: #9fb4c7; font-size: 12px; text-transform: uppercase; font-weight: 600; }
        .summary-card .value { font-size: 24px; font-weight: 800; margin-top: 8px; }
        .summary-card.green .value { color: #86efac; }
        .summary-card.blue .value { color: #93c5fd; }
        .summary-card.yellow .value { color: #fde047; }
        .summary-card.red .value { color: #fca5a5; }
        
        .info-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 20px; margin-bottom: 24px; }
        .info-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #9fb4c7; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.draft { background: rgba(251,191,36,.15); color: #fde047; }
        .status-badge.approved { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.completed { background: rgba(59,130,246,.15); color: #93c5fd; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        .text-right { text-align: right; }
        .total-row { background: rgba(34,197,94,.08); font-weight: 700; }
        .total-row td { border-bottom: none; }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } }
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
                    <a class="nav-link" href="employees.php"><span>👥</span> Employees</a>
                    <a class="nav-link" href="departments.php"><span>🏢</span> Departments</a>
                    <a class="nav-link active" href="payrolls.php"><span>💰</span> Payrolls</a>
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
                        <h1>📋 Payroll Details</h1>
                        <p class="lede"><?= date('M d', strtotime($payroll['period_start'])) ?> - <?= date('M d, Y', strtotime($payroll['period_end'])) ?></p>
                    </div>
                    <a href="payrolls.php" class="btn-back">← Back to Payrolls</a>
                </div>
                
                <div class="info-card">
                    <h3>Payroll Information</h3>
                    <div class="info-row">
                        <span class="label">Period</span>
                        <span><?= date('M d, Y', strtotime($payroll['period_start'])) ?> - <?= date('M d, Y', strtotime($payroll['period_end'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status</span>
                        <span class="status-badge <?= $payroll['status'] ?>"><?= ucfirst($payroll['status']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Run Date</span>
                        <span><?= date('M d, Y', strtotime($payroll['run_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Created</span>
                        <span><?= date('M d, Y H:i', strtotime($payroll['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="summary-grid">
                    <div class="summary-card">
                        <p class="label">Base Salary</p>
                        <p class="value">$<?= number_format($totals['base'], 2) ?></p>
                    </div>
                    <div class="summary-card green">
                        <p class="label">Allowances</p>
                        <p class="value">$<?= number_format($totals['allowances'], 2) ?></p>
                    </div>
                    <div class="summary-card yellow">
                        <p class="label">Bonuses</p>
                        <p class="value">$<?= number_format($totals['bonuses'], 2) ?></p>
                    </div>
                    <div class="summary-card red">
                        <p class="label">Deductions</p>
                        <p class="value">$<?= number_format($totals['deductions'], 2) ?></p>
                    </div>
                    <div class="summary-card red">
                        <p class="label">Tax</p>
                        <p class="value">$<?= number_format($totals['tax'], 2) ?></p>
                    </div>
                    <div class="summary-card blue">
                        <p class="label">Net Total</p>
                        <p class="value">$<?= number_format($totals['net'], 2) ?></p>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header"><h3>Employee Payroll Items</h3></div>
                    <?php if (empty($items)): ?>
                        <div class="empty-state">
                            <p>No payroll items calculated yet. Go back and click "Calculate" to generate payroll items.</p>
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th class="text-right">Base Salary</th>
                                <th class="text-right">Allowances</th>
                                <th class="text-right">Bonuses</th>
                                <th class="text-right">Deductions</th>
                                <th class="text-right">Tax</th>
                                <th class="text-right">Net Salary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['employee_name'] ?? 'Employee #' . $item['employee_id']) ?></td>
                                <td class="text-right">$<?= number_format($item['base_salary'], 2) ?></td>
                                <td class="text-right">$<?= number_format($item['total_allowances'], 2) ?></td>
                                <td class="text-right">$<?= number_format($item['total_bonuses'], 2) ?></td>
                                <td class="text-right">$<?= number_format($item['total_deductions'], 2) ?></td>
                                <td class="text-right">$<?= number_format($item['tax_amount'], 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($item['net_salary'], 2) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td><strong>TOTAL</strong></td>
                                <td class="text-right">$<?= number_format($totals['base'], 2) ?></td>
                                <td class="text-right">$<?= number_format($totals['allowances'], 2) ?></td>
                                <td class="text-right">$<?= number_format($totals['bonuses'], 2) ?></td>
                                <td class="text-right">$<?= number_format($totals['deductions'], 2) ?></td>
                                <td class="text-right">$<?= number_format($totals['tax'], 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($totals['net'], 2) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
