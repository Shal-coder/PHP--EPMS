<?php
/**
 * Manager Attendance Page - View team attendance
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Models/Attendance.php';

RoleMiddleware::require('manager');

$user = AuthMiddleware::user();
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$todayAttendance = Attendance::getByDate($selectedDate, $user->id);
$myEmployees = Employee::getByManager($user->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Attendance | PayrollPro Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(139,92,246,.15), transparent 35%), radial-gradient(circle at 80% 10%, rgba(167,139,250,.12), transparent 38%), radial-gradient(circle at 50% 80%, rgba(124,58,237,.08), transparent 40%), #0b1320; position: relative; overflow-x: hidden; }
        .shell::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 300px; background: linear-gradient(180deg, rgba(139,92,246,.05), transparent); pointer-events: none; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(11,19,32,.95); backdrop-filter: blur(20px); box-shadow: 0 4px 20px rgba(0,0,0,.3); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; box-shadow: 0 4px 12px rgba(139,92,246,.4); transition: all .3s; }
        .brand:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(139,92,246,.5); }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.06); border-radius: 10px; border: 1px solid rgba(255,255,255,.05); transition: all .3s; }
        .user-mini:hover { background: rgba(255,255,255,.1); transform: translateY(-1px); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #8b5cf6; animation: pulse 2s infinite; box-shadow: 0 0 10px rgba(139,92,246,.6); }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.08); padding: 20px; background: rgba(255,255,255,.02); animation: slideIn 0.5s ease-out; }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: linear-gradient(135deg, rgba(139,92,246,.12), rgba(167,139,250,.08)); border: 1px solid rgba(139,92,246,.25); border-radius: 14px; margin-bottom: 20px; transition: all .4s; position: relative; overflow: hidden; }
        .user-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.03), transparent); animation: shimmer 3s infinite; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(139,92,246,.25); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(139,92,246,.4); transition: all .3s; }
        .user-card:hover .avatar { transform: scale(1.05); }
        .user-card .label { font-size: 11px; color: #c4b5fd; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .3s; position: relative; }
        .nav-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 0; height: 60%; background: linear-gradient(90deg, #8b5cf6, transparent); border-radius: 0 4px 4px 0; transition: width .3s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; transform: translateX(4px); }
        .nav-link:hover::before { width: 4px; }
        .nav-link.active { background: rgba(139,92,246,.14); color: #c4b5fd; box-shadow: 0 4px 12px rgba(139,92,246,.15); }
        .nav-link.active::before { width: 4px; }
        
        .main { padding: 32px; overflow-y: auto; animation: fadeIn 0.6s ease-out; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #c4b5fd, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header .lede { color: #9fb4c7; font-size: 16px; }
        
        .date-picker { padding: 12px 18px; background: rgba(255,255,255,.06); border: 1px solid rgba(139,92,246,.2); border-radius: 12px; color: #e6edf5; font-size: 14px; font-weight: 600; transition: all .3s; cursor: pointer; }
        .date-picker:hover { background: rgba(255,255,255,.1); border-color: rgba(139,92,246,.4); }
        .date-picker:focus { outline: none; border-color: rgba(139,92,246,.6); box-shadow: 0 0 0 3px rgba(139,92,246,.1); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { padding: 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,.08); background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); text-align: center; transition: all .4s; position: relative; overflow: hidden; animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, currentColor, transparent); opacity: 0; transition: opacity .3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.3); border-color: rgba(255,255,255,.15); }
        .stat-card:hover::before { opacity: 0.6; }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card .value { font-size: 36px; font-weight: 800; margin-bottom: 8px; }
        .stat-card .label { font-size: 12px; color: #9fb4c7; text-transform: uppercase; font-weight: 700; letter-spacing: .05em; }
        .stat-card.present { color: #86efac; }
        .stat-card.present .value { text-shadow: 0 0 20px rgba(34,197,94,.3); }
        .stat-card.absent { color: #fca5a5; }
        .stat-card.absent .value { text-shadow: 0 0 20px rgba(239,68,68,.3); }
        .stat-card.total { color: #c4b5fd; }
        .stat-card.total .value { text-shadow: 0 0 20px rgba(139,92,246,.3); }
        .stat-card.rate { color: #fde047; }
        .stat-card.rate .value { text-shadow: 0 0 20px rgba(251,191,36,.3); }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; overflow: hidden; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.5s both; }
        .table-container:hover { border-color: rgba(139,92,246,.2); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .table-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); }
        .table-header h3 { font-size: 18px; font-weight: 700; color: #c4b5fd; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .08em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; font-weight: 500; }
        tbody tr { transition: all .3s; }
        tbody tr:hover { background: rgba(139,92,246,.05); transform: translateX(4px); }
        
        .employee-cell { display: flex; align-items: center; gap: 12px; }
        .employee-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #fff; box-shadow: 0 2px 8px rgba(139,92,246,.3); }
        
        .time-cell { font-family: 'Courier New', monospace; font-weight: 600; color: #c4b5fd; }
        .time-cell.empty { color: #64748b; }
        
        .duration-cell { font-weight: 700; color: #93c5fd; }
        
        .status-badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; border: 1px solid; display: inline-flex; align-items: center; gap: 6px; }
        .status-badge::before { content: '●'; font-size: 8px; }
        .status-badge.present { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.3); }
        .status-badge.absent { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }
        .status-badge.late { background: rgba(251,191,36,.15); color: #fde047; border-color: rgba(251,191,36,.3); }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } 
            .stats-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; }
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
                    <a class="nav-link" href="salary.php"><span>💵</span> My Salary</a>
                    <a class="nav-link active" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>📋 Team Attendance</h1>
                        <p class="lede">Monitor your team's daily attendance</p>
                    </div>
                    <form method="GET">
                        <input type="date" name="date" class="date-picker" value="<?= $selectedDate ?>" onchange="this.form.submit()">
                    </form>
                </div>
                <?php 
                $presentCount = count(array_filter($todayAttendance, fn($a) => $a['status'] === 'present'));
                $absentCount = count($myEmployees) - $presentCount;
                $attendanceRate = count($myEmployees) > 0 ? round(($presentCount / count($myEmployees)) * 100, 1) : 0;
                
                // Calculate total work hours
                $totalHours = 0;
                foreach ($todayAttendance as $a) {
                    if ($a['clock_in'] && $a['clock_out']) {
                        $start = strtotime($a['clock_in']);
                        $end = strtotime($a['clock_out']);
                        $totalHours += ($end - $start) / 3600;
                    }
                }
                ?>
                <div class="stats-grid">
                    <div class="stat-card present">
                        <div class="value"><?= $presentCount ?></div>
                        <div class="label">✓ Present</div>
                    </div>
                    <div class="stat-card absent">
                        <div class="value"><?= $absentCount ?></div>
                        <div class="label">✗ Absent</div>
                    </div>
                    <div class="stat-card total">
                        <div class="value"><?= count($myEmployees) ?></div>
                        <div class="label">👥 Total Team</div>
                    </div>
                    <div class="stat-card rate">
                        <div class="value"><?= $attendanceRate ?>%</div>
                        <div class="label">📊 Attendance Rate</div>
                    </div>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h3>📋 Attendance for <?= date('M d, Y', strtotime($selectedDate)) ?></h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Work Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayAttendance as $a): 
                                // Calculate work hours
                                $workHours = '-';
                                if ($a['clock_in'] && $a['clock_out']) {
                                    $start = strtotime($a['clock_in']);
                                    $end = strtotime($a['clock_out']);
                                    $hours = ($end - $start) / 3600;
                                    $workHours = number_format($hours, 1) . 'h';
                                }
                                
                                // Get initials for avatar
                                $nameParts = explode(' ', $a['employee_name']);
                                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="employee-avatar"><?= $initials ?></div>
                                        <span><?= htmlspecialchars($a['employee_name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="time-cell <?= $a['clock_in'] ? '' : 'empty' ?>"><?= $a['clock_in'] ?? '-' ?></span></td>
                                <td><span class="time-cell <?= $a['clock_out'] ? '' : 'empty' ?>"><?= $a['clock_out'] ?? '-' ?></span></td>
                                <td><span class="duration-cell"><?= $workHours ?></span></td>
                                <td><span class="status-badge <?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($todayAttendance)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="empty-state-icon">📋</div>
                                    <p>No attendance records for this date.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>