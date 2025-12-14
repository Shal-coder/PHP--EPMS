<?php
/**
 * Admin Dashboard - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Department.php';
require_once __DIR__ . '/../../app/Models/Attendance.php';
require_once __DIR__ . '/../../app/Models/Leave.php';

// Require super_admin role
RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$today = date('Y-m-d');

// Get stats
$employees = Employee::getAll();
$totalEmp = count($employees);
$departments = Department::getAll();
$totalDepts = count($departments);

// Get today's attendance
$todayAttendance = Attendance::getByDate($today);
$countPresent = count(array_filter($todayAttendance, fn($a) => $a['status'] === 'present'));
$absent = $totalEmp - $countPresent;
$percent = $totalEmp > 0 ? round(100 * $countPresent / $totalEmp, 1) : 0;

// Get pending leaves
$pendingLeaves = Leave::getPending();
$pendingCount = count($pendingLeaves);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        .shell { min-height: 100vh; display: flex; flex-direction: column; background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.08), transparent 30%), radial-gradient(circle at 80% 10%, rgba(59,130,246,.09), transparent 32%), #0b1320; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .brand { font-weight: 800; letter-spacing: .08em; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
        .links { display: flex; gap: 8px; }
        .links a { color: #c9d7e6; text-decoration: none; padding: 8px 14px; font-weight: 600; font-size: 14px; border-radius: 8px; transition: all .2s; }
        .links a:hover { background: rgba(255,255,255,.05); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { margin: 0; font-size: 11px; color: #86efac; letter-spacing: .1em; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin: 4px 0 0; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; border: 1px solid transparent; transition: all .2s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(34,197,94,.14); color: #a6f3bf; border-color: rgba(34,197,94,.28); }
        .nav-icon { width: 20px; text-align: center; }
        
        .main { padding: 28px; overflow-y: auto; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .metric-card { padding: 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.03); backdrop-filter: blur(10px); }
        .metric-card .label { color: #9fb4c7; font-size: 12px; letter-spacing: .05em; text-transform: uppercase; font-weight: 600; }
        .metric-card .value { font-size: 32px; font-weight: 800; margin: 8px 0 4px; }
        .metric-card .hint { color: #64748b; font-size: 13px; }
        .metric-card.green .value { color: #86efac; }
        .metric-card.red .value { color: #fca5a5; }
        .metric-card.blue .value { color: #93c5fd; }
        .metric-card.yellow .value { color: #fde047; }
        
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .tile { display: block; padding: 24px; border-radius: 14px; border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.03); color: #e6edf5; text-decoration: none; transition: all .3s; }
        .tile:hover { border-color: rgba(34,197,94,.4); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,.3); }
        .tile-icon { font-size: 28px; margin-bottom: 12px; }
        .tile-title { font-weight: 700; font-size: 16px; margin-bottom: 8px; }
        .tile p { color: #9fb4c7; font-size: 14px; line-height: 1.5; }
        
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
                        <p class="label">Super Admin</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link active" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                    <a class="nav-link" href="employees.php"><span class="nav-icon">👥</span> Employees</a>
                    <a class="nav-link" href="departments.php"><span class="nav-icon">🏢</span> Departments</a>
                    <a class="nav-link" href="payrolls.php"><span class="nav-icon">💰</span> Payrolls</a>
                    <a class="nav-link" href="allowances.php"><span class="nav-icon">💵</span> Allowances</a>
                    <a class="nav-link" href="deductions.php"><span class="nav-icon">➖</span> Deductions</a>
                    <a class="nav-link" href="bonuses.php"><span class="nav-icon">🎁</span> Bonuses</a>
                    <a class="nav-link" href="users.php"><span class="nav-icon">🔐</span> Users</a>
                    <a class="nav-link" href="announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                    <a class="nav-link" href="../../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>👋 Welcome back, <?= htmlspecialchars($user->first_name) ?>!</h1>
                        <p class="lede">Here's what's happening with your team today.</p>
                    </div>
                </div>

                <section class="metrics-grid">
                    <article class="metric-card green">
                        <p class="label">Present Today</p>
                        <p class="value"><?= $countPresent ?></p>
                        <p class="hint">Checked in employees</p>
                    </article>
                    <article class="metric-card red">
                        <p class="label">Absent</p>
                        <p class="value"><?= $absent ?></p>
                        <p class="hint">Not checked in yet</p>
                    </article>
                    <article class="metric-card blue">
                        <p class="label">Total Employees</p>
                        <p class="value"><?= $totalEmp ?></p>
                        <p class="hint">Across <?= $totalDepts ?> departments</p>
                    </article>
                    <article class="metric-card yellow">
                        <p class="label">Attendance Rate</p>
                        <p class="value"><?= $percent ?>%</p>
                        <p class="hint">Today's coverage</p>
                    </article>
                </section>

                <?php if ($pendingCount > 0): ?>
                <div style="background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #fbbf24;">⚠️ <?= $pendingCount ?> Pending Leave Request<?= $pendingCount > 1 ? 's' : '' ?></strong>
                        <p style="color: #9fb4c7; font-size: 14px; margin-top: 4px;">Review and approve employee leave requests</p>
                    </div>
                    <a href="leaves.php" style="padding: 10px 20px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #000; text-decoration: none; border-radius: 8px; font-weight: 600;">Review</a>
                </div>
                <?php endif; ?>

                <section class="quick-links">
                    <a class="tile" href="employees.php">
                        <div class="tile-icon">👥</div>
                        <div class="tile-title">Employees</div>
                        <p>Manage employee profiles, salaries, and assignments.</p>
                    </a>
                    <a class="tile" href="departments.php">
                        <div class="tile-icon">🏢</div>
                        <div class="tile-title">Departments</div>
                        <p>Organize teams and assign department managers.</p>
                    </a>
                    <a class="tile" href="payrolls.php">
                        <div class="tile-icon">💰</div>
                        <div class="tile-title">Payrolls</div>
                        <p>Run payroll, manage allowances and deductions.</p>
                    </a>
                    <a class="tile" href="users.php">
                        <div class="tile-icon">🔐</div>
                        <div class="tile-title">Users</div>
                        <p>Manage system access and user accounts.</p>
                    </a>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
