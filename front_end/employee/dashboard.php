<?php
/**
 * Employee Dashboard - With Salary Breakdown
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Attendance.php';
require_once __DIR__ . '/../../app/Models/Leave.php';
require_once __DIR__ . '/../../app/Models/Payroll.php';

RoleMiddleware::require('employee');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);

if (!$employee) {
    die('Employee profile not found.');
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

// Get attendance for this month
$attendance = Attendance::getByEmployee($employee->id, $monthStart, $monthEnd);
$presentDays = count(array_filter($attendance, fn($a) => $a->status === 'present'));
$absentDays = count(array_filter($attendance, fn($a) => $a->status === 'absent'));

// Get leave balance
$leaveBalance = [
    'annual' => ['total' => 20, 'used' => Leave::getUsedDays($employee->id, 'annual', (int)date('Y'))],
    'sick' => ['total' => 10, 'used' => Leave::getUsedDays($employee->id, 'sick', (int)date('Y'))]
];

// Get recent payslips
$payslips = Payroll::getEmployeePayslips($employee->id);
$latestPayslip = !empty($payslips) ? $payslips[0] : null;

// Get current allowances, deductions, and bonuses
$currentDate = date('Y-m-d');

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

// Calculate estimated net salary (simplified - doesn't include tax)
$grossSalary = $employee->base_salary + $totalAllowances + $totalBonuses;
$estimatedNet = $grossSalary - $totalDeductions;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 20px rgba(6,182,212,.3); } 50% { box-shadow: 0 0 40px rgba(6,182,212,.6); } }
        
        .shell { min-height: 100vh; display: flex; flex-direction: column; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.15), transparent 35%), radial-gradient(circle at 80% 10%, rgba(59,130,246,.12), transparent 38%), radial-gradient(circle at 50% 80%, rgba(139,92,246,.08), transparent 40%), #0b1320; position: relative; overflow-x: hidden; }
        .shell::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 300px; background: linear-gradient(180deg, rgba(6,182,212,.05), transparent); pointer-events: none; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(11,19,32,.95); backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(0,0,0,.3); }
        .brand { font-weight: 800; letter-spacing: .08em; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.4); transition: all .3s; }
        .brand:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(6,182,212,.5); }
        .links { display: flex; gap: 8px; }
        .links a { color: #c9d7e6; text-decoration: none; padding: 8px 14px; font-weight: 600; font-size: 14px; border-radius: 8px; transition: all .3s; position: relative; }
        .links a::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 0; height: 2px; background: linear-gradient(90deg, #06b6d4, #0891b2); transition: width .3s; }
        .links a:hover { background: rgba(255,255,255,.05); color: #fff; }
        .links a:hover::after { width: 80%; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.06); border-radius: 10px; transition: all .3s; border: 1px solid rgba(255,255,255,.05); }
        .user-mini:hover { background: rgba(255,255,255,.1); transform: translateY(-1px); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; animation: pulse 2s infinite; box-shadow: 0 0 10px rgba(6,182,212,.6); }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.08); padding: 20px; background: rgba(255,255,255,.02); animation: slideIn 0.5s ease-out; }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: linear-gradient(135deg, rgba(6,182,212,.12), rgba(59,130,246,.08)); border: 1px solid rgba(6,182,212,.25); border-radius: 14px; margin-bottom: 20px; transition: all .4s; position: relative; overflow: hidden; }
        .user-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.03), transparent); animation: shimmer 3s infinite; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(6,182,212,.25); border-color: rgba(6,182,212,.4); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.4); transition: all .3s; }
        .user-card:hover .avatar { transform: scale(1.05); box-shadow: 0 6px 16px rgba(6,182,212,.5); }
        .user-card .label { margin: 0; font-size: 11px; color: #67e8f9; letter-spacing: .1em; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin: 4px 0 0; font-weight: 700; font-size: 15px; }
        .user-card .code { color: #9fb4c7; font-size: 12px; font-weight: 600; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; border: 1px solid transparent; transition: all .3s; display: flex; align-items: center; gap: 10px; position: relative; }
        .nav-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 0; height: 60%; background: linear-gradient(90deg, #06b6d4, transparent); border-radius: 0 4px 4px 0; transition: width .3s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; transform: translateX(4px); }
        .nav-link:hover::before { width: 4px; }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; border-color: rgba(6,182,212,.28); box-shadow: 0 4px 12px rgba(6,182,212,.15); }
        .nav-link.active::before { width: 4px; }
        .nav-icon { width: 20px; text-align: center; font-size: 18px; }
        
        .main { padding: 32px; overflow-y: auto; animation: fadeIn 0.6s ease-out; position: relative; }
        
        .page-header { margin-bottom: 32px; animation: fadeInUp 0.6s ease-out; }
        .page-header h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px; background: linear-gradient(135deg, #67e8f9, #06b6d4, #0891b2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header .lede { color: #9fb4c7; font-size: 16px; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .metric-card { padding: 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,.08); background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); backdrop-filter: blur(10px); transition: all .4s; position: relative; overflow: hidden; animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, currentColor, transparent); opacity: 0; transition: opacity .3s; }
        .metric-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.3); border-color: rgba(255,255,255,.15); }
        .metric-card:hover::before { opacity: 0.6; }
        .metric-card:nth-child(1) { animation-delay: 0.1s; }
        .metric-card:nth-child(2) { animation-delay: 0.2s; }
        .metric-card:nth-child(3) { animation-delay: 0.3s; }
        .metric-card:nth-child(4) { animation-delay: 0.4s; }
        .metric-card .label { color: #9fb4c7; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .metric-card .value { font-size: 36px; font-weight: 800; margin: 12px 0 6px; line-height: 1; }
        .metric-card .hint { color: #64748b; font-size: 13px; font-weight: 500; }
        .metric-card.cyan { color: #67e8f9; }
        .metric-card.cyan .value { color: #67e8f9; text-shadow: 0 0 20px rgba(6,182,212,.3); }
        .metric-card.green { color: #86efac; }
        .metric-card.green .value { color: #86efac; text-shadow: 0 0 20px rgba(34,197,94,.3); }
        .metric-card.yellow { color: #fde047; }
        .metric-card.yellow .value { color: #fde047; text-shadow: 0 0 20px rgba(251,191,36,.3); }
        .metric-card.blue { color: #93c5fd; }
        .metric-card.blue .value { color: #93c5fd; text-shadow: 0 0 20px rgba(59,130,246,.3); }
        
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #67e8f9; display: flex; align-items: center; gap: 10px; animation: fadeInUp 0.6s ease-out 0.5s both; }
        .section-title::before { content: ''; width: 4px; height: 24px; background: linear-gradient(135deg, #06b6d4, #0891b2); border-radius: 2px; }
        
        .salary-breakdown { background: linear-gradient(135deg, rgba(6,182,212,.08), rgba(59,130,246,.05)); border: 1px solid rgba(6,182,212,.2); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.6s both; }
        .salary-breakdown::before { content: '💰'; position: absolute; right: 20px; top: 20px; font-size: 64px; opacity: 0.05; }
        .salary-breakdown:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(6,182,212,.2); border-color: rgba(6,182,212,.3); }
        .salary-breakdown h3 { font-size: 20px; font-weight: 800; margin-bottom: 24px; color: #67e8f9; }
        .salary-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(255,255,255,.02); border-radius: 8px; margin-bottom: 8px; transition: all .3s; }
        .salary-row:hover { background: rgba(255,255,255,.05); transform: translateX(4px); }
        .salary-row:last-child { border-bottom: none; }
        .salary-row.total { border-top: 2px solid rgba(6,182,212,.3); padding: 20px 16px; margin-top: 16px; font-weight: 700; font-size: 18px; background: rgba(6,182,212,.08); border-radius: 12px; }
        .salary-row.total:hover { transform: none; }
        .salary-label { color: #c9d7e6; font-size: 15px; display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .salary-value { font-weight: 700; font-size: 16px; }
        .salary-value.positive { color: #86efac; }
        .salary-value.negative { color: #fca5a5; }
        .salary-value.neutral { color: #93c5fd; }
        .salary-section { margin-bottom: 16px; }
        .salary-section-title { font-size: 13px; color: #67e8f9; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; margin-bottom: 8px; }
        .item-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: rgba(6,182,212,.2); color: #67e8f9; margin-left: 8px; border: 1px solid rgba(6,182,212,.3); }
        .empty-state { color: #64748b; font-size: 13px; font-style: italic; padding: 8px 0; }
        
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out 0.8s both; }
        .tile { display: block; padding: 28px; border-radius: 16px; border: 1px solid rgba(255,255,255,.08); background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); color: #e6edf5; text-decoration: none; transition: all .4s; position: relative; overflow: hidden; }
        .tile::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #06b6d4, #0891b2); transform: scaleX(0); transition: transform .3s; }
        .tile:hover { border-color: rgba(6,182,212,.4); transform: translateY(-4px); box-shadow: 0 12px 32px rgba(6,182,212,.2); }
        .tile:hover::before { transform: scaleX(1); }
        .tile-icon { font-size: 36px; margin-bottom: 16px; display: inline-block; transition: all .3s; }
        .tile:hover .tile-icon { transform: scale(1.1) rotate(5deg); }
        .tile-title { font-weight: 700; font-size: 17px; margin-bottom: 10px; color: #67e8f9; }
        .tile p { color: #9fb4c7; font-size: 14px; line-height: 1.6; }
        
        .leave-balance { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; animation: fadeInUp 0.6s ease-out 0.7s both; }
        .leave-card { padding: 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,.08); background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); transition: all .4s; position: relative; overflow: hidden; }
        .leave-card::before { content: ''; position: absolute; top: 0; right: 0; width: 60px; height: 60px; background: radial-gradient(circle, rgba(255,255,255,.05), transparent); }
        .leave-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); border-color: rgba(255,255,255,.15); }
        .leave-card h4 { font-size: 13px; color: #9fb4c7; margin-bottom: 16px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .leave-bar { height: 10px; background: rgba(255,255,255,.08); border-radius: 6px; overflow: hidden; margin-bottom: 12px; box-shadow: inset 0 2px 4px rgba(0,0,0,.2); }
        .leave-bar-fill { height: 100%; border-radius: 6px; transition: width .6s ease-out; position: relative; }
        .leave-bar-fill::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent); animation: shimmer 2s infinite; }
        .leave-bar-fill.annual { background: linear-gradient(90deg, #06b6d4, #0891b2); box-shadow: 0 0 10px rgba(6,182,212,.4); }
        .leave-bar-fill.sick { background: linear-gradient(90deg, #f59e0b, #d97706); box-shadow: 0 0 10px rgba(251,191,36,.4); }
        .leave-stats { display: flex; justify-content: space-between; font-size: 13px; color: #9fb4c7; font-weight: 600; }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } 
            .links { display: none; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="links">
                <a href="../index.html">Home</a>
                <a href="../support.php">Support</a>
                <a href="../announcement.php">News</a>
            </div>
            <div class="user-mini"><span class="dot"></span><span><?= htmlspecialchars($user->getFullName()) ?></span></div>
        </header>

        <div class="layout">
            <aside class="sidenav">
                <div class="user-card">
                    <div class="avatar"><?= strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) ?></div>
                    <div>
                        <p class="label">Employee</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                        <p class="code"><?= htmlspecialchars($employee->employee_code) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link active" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                    <a class="nav-link" href="profile.php"><span class="nav-icon">👤</span> My Profile</a>
                    <a class="nav-link" href="payroll.php"><span class="nav-icon">💰</span> Payslips</a>
                    <a class="nav-link" href="leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                    <a class="nav-link" href="attendance.php"><span class="nav-icon">📋</span> Attendance</a>
                    <a class="nav-link" href="../announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                    <a class="nav-link" href="../../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <h1>👋 Hello, <?= htmlspecialchars($user->first_name) ?>!</h1>
                    <p class="lede">Welcome to your employee portal. Here's your overview for <?= date('F Y') ?>.</p>
                </div>

                <section class="metrics-grid">
                    <article class="metric-card cyan">
                        <p class="label">Days Present</p>
                        <p class="value"><?= $presentDays ?></p>
                        <p class="hint">This month</p>
                    </article>
                    <article class="metric-card yellow">
                        <p class="label">Days Absent</p>
                        <p class="value"><?= $absentDays ?></p>
                        <p class="hint">This month</p>
                    </article>
                    <article class="metric-card green">
                        <p class="label">Base Salary</p>
                        <p class="value">$<?= number_format($employee->base_salary, 0) ?></p>
                        <p class="hint">Monthly</p>
                    </article>
                    <article class="metric-card blue">
                        <p class="label">Estimated Net</p>
                        <p class="value">$<?= number_format($estimatedNet, 0) ?></p>
                        <p class="hint">After allowances & deductions</p>
                    </article>
                </section>

                <div class="salary-breakdown">
                    <h3>💰 Salary Summary</h3>
                    
                    <div class="salary-row">
                        <span class="salary-label">💵 Base Salary</span>
                        <span class="salary-value neutral">$<?= number_format($employee->base_salary, 2) ?></span>
                    </div>
                    
                    <div class="salary-row">
                        <span class="salary-label">➕ Total Allowances <span class="item-badge"><?= count($allowances) ?> items</span></span>
                        <span class="salary-value positive">+$<?= number_format($totalAllowances, 2) ?></span>
                    </div>
                    
                    <div class="salary-row">
                        <span class="salary-label">🎁 Total Bonuses (This Month) <span class="item-badge"><?= count($bonuses) ?> items</span></span>
                        <span class="salary-value positive">+$<?= number_format($totalBonuses, 2) ?></span>
                    </div>
                    
                    <div class="salary-row">
                        <span class="salary-label">➖ Total Deductions <span class="item-badge"><?= count($deductions) ?> items</span></span>
                        <span class="salary-value negative">-$<?= number_format($totalDeductions, 2) ?></span>
                    </div>

                    <div class="salary-row total">
                        <span class="salary-label" style="color: #67e8f9; font-size: 16px;">💎 Estimated Net Salary</span>
                        <span class="salary-value" style="color: #67e8f9; font-size: 20px;">$<?= number_format($estimatedNet, 2) ?></span>
                    </div>
                    
                    <p style="color: #64748b; font-size: 12px; margin-top: 12px; text-align: center;">
                        <a href="payroll.php" style="color: #67e8f9; text-decoration: none;">View detailed breakdown →</a>
                    </p>
                </div>

                <h3 class="section-title">Leave Balance</h3>
                <section class="leave-balance">
                    <div class="leave-card">
                        <h4>Annual Leave</h4>
                        <?php $annualPercent = ($leaveBalance['annual']['used'] / $leaveBalance['annual']['total']) * 100; ?>
                        <div class="leave-bar">
                            <div class="leave-bar-fill annual" style="width: <?= $annualPercent ?>%"></div>
                        </div>
                        <div class="leave-stats">
                            <span>Used: <?= $leaveBalance['annual']['used'] ?> days</span>
                            <span>Remaining: <?= $leaveBalance['annual']['total'] - $leaveBalance['annual']['used'] ?> days</span>
                        </div>
                    </div>
                    <div class="leave-card">
                        <h4>Sick Leave</h4>
                        <?php $sickPercent = ($leaveBalance['sick']['used'] / $leaveBalance['sick']['total']) * 100; ?>
                        <div class="leave-bar">
                            <div class="leave-bar-fill sick" style="width: <?= $sickPercent ?>%"></div>
                        </div>
                        <div class="leave-stats">
                            <span>Used: <?= $leaveBalance['sick']['used'] ?> days</span>
                            <span>Remaining: <?= $leaveBalance['sick']['total'] - $leaveBalance['sick']['used'] ?> days</span>
                        </div>
                    </div>
                </section>

                <h3 class="section-title" style="margin-top: 28px;">Quick Actions</h3>
                <section class="quick-links">
                    <a class="tile" href="profile.php">
                        <div class="tile-icon">👤</div>
                        <div class="tile-title">My Profile</div>
                        <p>View and update your personal information.</p>
                    </a>
                    <a class="tile" href="payroll.php">
                        <div class="tile-icon">💰</div>
                        <div class="tile-title">Payslips</div>
                        <p>View and download your salary statements.</p>
                    </a>
                    <a class="tile" href="leaves.php">
                        <div class="tile-icon">📅</div>
                        <div class="tile-title">Request Leave</div>
                        <p>Submit a new leave request to your manager.</p>
                    </a>
                    <a class="tile" href="attendance.php">
                        <div class="tile-icon">📋</div>
                        <div class="tile-title">Attendance</div>
                        <p>View your attendance history and check-in.</p>
                    </a>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
