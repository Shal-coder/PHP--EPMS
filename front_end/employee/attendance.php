<?php
/**
 * Employee Attendance Page
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Attendance.php';

RoleMiddleware::require('employee');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);
$message = '';

// Handle check-in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
    if ($_POST['action'] === 'checkin') {
        $result = Attendance::checkIn($employee->id);
        $message = $result ? 'Checked in successfully!' : 'Already checked in today.';
    } elseif ($_POST['action'] === 'checkout') {
        Attendance::checkOut($employee->id);
        $message = 'Checked out successfully!';
    }
}

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$attendance = Attendance::getByEmployee($employee->id, $monthStart, $monthEnd);

// Check today's status
$today = date('Y-m-d');
$todayRecord = null;
foreach ($attendance as $a) {
    if ($a->date === $today) {
        $todayRecord = $a;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; }
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { font-size: 11px; color: #67e8f9; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .2s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; }
        .main { padding: 28px; overflow-y: auto; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .checkin-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; padding: 30px; text-align: center; margin-bottom: 28px; }
        .checkin-card h2 { font-size: 20px; margin-bottom: 8px; }
        .checkin-card p { color: #9fb4c7; margin-bottom: 20px; }
        .checkin-card .time { font-size: 48px; font-weight: 800; color: #67e8f9; margin-bottom: 20px; }
        .btn-checkin { padding: 14px 40px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer; margin: 0 8px; }
        .btn-checkout { padding: 14px 40px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer; margin: 0 8px; }
        .btn-checkin:disabled, .btn-checkout:disabled { opacity: 0.5; cursor: not-allowed; }
        .status-info { display: flex; justify-content: center; gap: 40px; margin-top: 20px; }
        .status-info div { text-align: center; }
        .status-info .label { font-size: 12px; color: #9fb4c7; text-transform: uppercase; }
        .status-info .value { font-size: 18px; font-weight: 700; color: #67e8f9; margin-top: 4px; }
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.present { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.absent { background: rgba(239,68,68,.15); color: #fca5a5; }
        .status-badge.late { background: rgba(251,191,36,.15); color: #fde047; }
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
                        <p class="label">Employee</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="profile.php"><span>👤</span> My Profile</a>
                    <a class="nav-link" href="payroll.php"><span>💰</span> Payslips</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link active" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <h1>📋 Attendance</h1>
                    <p class="lede">Track your daily attendance</p>
                </div>
                <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <div class="checkin-card">
                    <h2>Today's Attendance</h2>
                    <p><?= date('l, F d, Y') ?></p>
                    <div class="time" id="currentTime"><?= date('H:i:s') ?></div>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                        <input type="hidden" name="action" value="checkin">
                        <button type="submit" class="btn-checkin" <?= $todayRecord ? 'disabled' : '' ?>>Check In</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-checkout" <?= !$todayRecord || $todayRecord->check_out ? 'disabled' : '' ?>>Check Out</button>
                    </form>
                    <?php if ($todayRecord): ?>
                    <div class="status-info">
                        <div><div class="label">Check In</div><div class="value"><?= $todayRecord->clock_in ?? '-' ?></div></div>
                        <div><div class="label">Check Out</div><div class="value"><?= $todayRecord->clock_out ?? '-' ?></div></div>
                        <div><div class="label">Status</div><div class="value"><?= ucfirst($todayRecord->status) ?></div></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <div class="table-header"><h3>Attendance History - <?= date('F Y') ?></h3></div>
                    <table>
                        <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($attendance as $a): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($a->date)) ?></td>
                                <td><?= $a->clock_in ?? '-' ?></td>
                                <td><?= $a->clock_out ?? '-' ?></td>
                                <td><span class="status-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <script>
        setInterval(function() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toTimeString().split(' ')[0];
        }, 1000);
    </script>
</body>
</html>