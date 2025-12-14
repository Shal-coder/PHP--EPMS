<?php
/**
 * View Payslip Details - Manager
 * Managers can view their individual payslip
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Config/Database.php';

RoleMiddleware::require('manager');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);

if (!$employee) {
    die('Employee profile not found.');
}

$payslipId = (int)($_GET['id'] ?? 0);

if (!$payslipId) {
    header('Location: salary.php');
    exit;
}

// Get payslip item - ensure it belongs to this manager
$stmt = Database::query(
    "SELECT pi.*, pr.period_start, pr.period_end, pr.run_date, pr.status as run_status,
     CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code
     FROM payroll_items pi
     JOIN payroll_runs pr ON pi.payroll_run_id = pr.id
     JOIN employees e ON pi.employee_id = e.id
     JOIN users u ON e.user_id = u.id
     WHERE pi.id = ? AND pi.employee_id = ?",
    [$payslipId, $employee->id]
);

$payslip = $stmt->fetch();

if (!$payslip) {
    header('Location: salary.php');
    exit;
}

// Parse breakdown JSON if available
$breakdown = null;
if (!empty($payslip['breakdown_json'])) {
    $breakdown = json_decode($payslip['breakdown_json'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip Details | PayrollPro Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(139,92,246,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #8b5cf6; }
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(139,92,246,.08); border: 1px solid rgba(139,92,246,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { font-size: 11px; color: #c4b5fd; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .2s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(139,92,246,.14); color: #c4b5fd; }
        .main { padding: 28px; overflow-y: auto; max-width: 1200px; margin: 0 auto; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        .btn-back { padding: 10px 20px; background: rgba(255,255,255,.1); color: #e6edf5; text-decoration: none; border-radius: 8px; font-weight: 500; display: inline-block; }
        .btn-back:hover { background: rgba(255,255,255,.15); }
        .btn-print { padding: 10px 20px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block; margin-left: 10px; border: none; cursor: pointer; }
        .btn-print:hover { opacity: 0.9; }
        
        .payslip-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 40px; margin-bottom: 24px; }
        .payslip-header { text-align: center; padding-bottom: 30px; border-bottom: 2px solid rgba(255,255,255,.1); margin-bottom: 30px; }
        .payslip-header h2 { font-size: 32px; font-weight: 800; color: #c4b5fd; margin-bottom: 8px; }
        .payslip-header .period { font-size: 18px; color: #9fb4c7; }
        
        .employee-info { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .info-group h4 { font-size: 12px; color: #9fb4c7; text-transform: uppercase; font-weight: 700; margin-bottom: 12px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; }
        .info-item .label { color: #9fb4c7; font-size: 14px; }
        .info-item .value { font-weight: 600; font-size: 14px; }
        
        .breakdown-section { margin-bottom: 30px; }
        .breakdown-section h3 { font-size: 16px; font-weight: 700; color: #c4b5fd; margin-bottom: 16px; text-transform: uppercase; }
        .breakdown-item { display: flex; justify-content: space-between; padding: 12px 16px; background: rgba(255,255,255,.02); border-radius: 8px; margin-bottom: 8px; }
        .breakdown-item .label { color: #c9d7e6; }
        .breakdown-item .amount { font-weight: 600; }
        .breakdown-item.positive .amount { color: #86efac; }
        .breakdown-item.negative .amount { color: #fca5a5; }
        
        .summary-section { background: rgba(139,92,246,.08); border: 1px solid rgba(139,92,246,.2); border-radius: 14px; padding: 24px; }
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; font-size: 16px; }
        .summary-row.total { border-top: 2px solid rgba(139,92,246,.3); margin-top: 12px; padding-top: 16px; }
        .summary-row.total .label { font-size: 20px; font-weight: 800; color: #c4b5fd; }
        .summary-row.total .value { font-size: 24px; font-weight: 800; color: #c4b5fd; }
        
        .status-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-badge.approved { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.processed { background: rgba(59,130,246,.15); color: #93c5fd; }
        .status-badge.completed { background: rgba(59,130,246,.15); color: #93c5fd; }
        
        @media print {
            .sidenav, .topbar, .btn-back, .btn-print { display: none; }
            .layout { grid-template-columns: 1fr; }
            .shell { background: white; }
            body { background: white; color: black; }
            .payslip-container { border: 1px solid #ccc; }
        }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
            .employee-info { grid-template-columns: 1fr; gap: 20px; }
        }
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
                        <p class="label">Manager</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="employees.php"><span>👥</span> My Team</a>
                    <a class="nav-link" href="payroll.php"><span>💰</span> Team Payroll</a>
                    <a class="nav-link active" href="salary.php"><span>💵</span> My Salary</a>
                    <a class="nav-link" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>📄 Payslip Details</h1>
                        <p class="lede">Official salary statement</p>
                    </div>
                    <div>
                        <a href="salary.php" class="btn-back">← Back</a>
                        <button onclick="window.print()" class="btn-print">🖨️ Print</button>
                    </div>
                </div>
                
                <div class="payslip-container">
                    <div class="payslip-header">
                        <h2>PAYSLIP</h2>
                        <p class="period"><?= date('F Y', strtotime($payslip['period_start'])) ?></p>
                        <p style="margin-top:8px;"><span class="status-badge <?= $payslip['run_status'] ?>"><?= ucfirst($payslip['run_status']) ?></span></p>
                    </div>
                    
                    <div class="employee-info">
                        <div class="info-group">
                            <h4>Manager Information</h4>
                            <div class="info-item">
                                <span class="label">Name:</span>
                                <span class="value"><?= htmlspecialchars($payslip['employee_name']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Employee Code:</span>
                                <span class="value"><?= htmlspecialchars($payslip['employee_code']) ?></span>
                            </div>
                        </div>
                        <div class="info-group">
                            <h4>Payroll Information</h4>
                            <div class="info-item">
                                <span class="label">Period:</span>
                                <span class="value"><?= date('M d', strtotime($payslip['period_start'])) ?> - <?= date('M d, Y', strtotime($payslip['period_end'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Payment Date:</span>
                                <span class="value"><?= $payslip['payment_date'] ? date('M d, Y', strtotime($payslip['payment_date'])) : 'Pending' ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="breakdown-section">
                        <h3>💰 Earnings</h3>
                        <div class="breakdown-item">
                            <span class="label">Base Salary</span>
                            <span class="amount">$<?= number_format($payslip['base_salary'], 2) ?></span>
                        </div>
                        <?php if ($payslip['total_allowances'] > 0): ?>
                        <div class="breakdown-item positive">
                            <span class="label">Allowances</span>
                            <span class="amount">+$<?= number_format($payslip['total_allowances'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payslip['total_bonuses'] > 0): ?>
                        <div class="breakdown-item positive">
                            <span class="label">Bonuses</span>
                            <span class="amount">+$<?= number_format($payslip['total_bonuses'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($payslip['gross_salary']) && $payslip['gross_salary'] > 0): ?>
                        <div class="breakdown-item" style="background:rgba(139,92,246,.08);margin-top:8px;">
                            <span class="label"><strong>Gross Salary</strong></span>
                            <span class="amount"><strong>$<?= number_format($payslip['gross_salary'], 2) ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="breakdown-section">
                        <h3>➖ Deductions</h3>
                        <?php if ($payslip['total_deductions'] > 0): ?>
                        <div class="breakdown-item negative">
                            <span class="label">Deductions</span>
                            <span class="amount">-$<?= number_format($payslip['total_deductions'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payslip['tax_amount'] > 0): ?>
                        <div class="breakdown-item negative">
                            <span class="label">Tax</span>
                            <span class="amount">-$<?= number_format($payslip['tax_amount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payslip['total_deductions'] == 0 && $payslip['tax_amount'] == 0): ?>
                        <div class="breakdown-item">
                            <span class="label">No deductions</span>
                            <span class="amount">$0.00</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="label">Gross Salary:</span>
                            <span class="value">$<?= number_format($payslip['base_salary'] + $payslip['total_allowances'] + $payslip['total_bonuses'], 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Total Deductions:</span>
                            <span class="value">-$<?= number_format($payslip['total_deductions'] + $payslip['tax_amount'], 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span class="label">NET SALARY</span>
                            <span class="value">$<?= number_format($payslip['net_salary'], 2) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($breakdown): ?>
                    <div style="margin-top:30px;padding-top:30px;border-top:1px solid rgba(255,255,255,.06);">
                        <p style="font-size:12px;color:#9fb4c7;text-align:center;">
                            This is an official payslip generated by PayrollPro on <?= date('M d, Y', strtotime($payslip['run_date'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
