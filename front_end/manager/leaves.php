<?php
/**
 * Manager Leave Requests Page - Approve/Reject team leaves
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Models/Leave.php';
require_once __DIR__ . '/../app/Controllers/LeaveController.php';

RoleMiddleware::require('manager');

$user = AuthMiddleware::user();
$message = '';
$error = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $leaveId = (int)$_POST['leave_id'];
        if ($_POST['action'] === 'approve') {
            $result = LeaveController::approve($leaveId);
        } else {
            $result = LeaveController::reject($leaveId);
        }
        $message = $result['success'] ? $result['message'] : '';
        $error = !$result['success'] ? $result['message'] : '';
    }
}

$pendingLeaves = LeaveController::getPending();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests | PayrollPro Manager</title>
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
        
        .main { padding: 28px; overflow-y: auto; }
        
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(251,191,36,.15); color: #fde047; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        
        .emp-name { font-weight: 600; }
        .type-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59,130,246,.15); color: #93c5fd; }
        
        .action-btns { display: flex; gap: 8px; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn-approve { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
        .btn-reject { background: rgba(239,68,68,.15); color: #fca5a5; }
        .btn:hover { transform: translateY(-1px); }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        
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
                        <p class="label">Manager</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="employees.php"><span>👥</span> My Team</a>
                    <a class="nav-link" href="payroll.php"><span>💰</span> Team Payroll</a>
                    <a class="nav-link" href="salary.php"><span>💵</span> My Salary</a>
                    <a class="nav-link" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link active" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <h1>📅 Leave Requests</h1>
                    <p class="lede">Review and approve your team's leave applications</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Pending Requests</h3>
                        <span class="badge"><?= count($pendingLeaves) ?> pending</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingLeaves as $leave): ?>
                            <tr>
                                <td class="emp-name"><?= htmlspecialchars($leave['employee_name']) ?></td>
                                <td><span class="type-badge"><?= ucfirst($leave['type']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($leave['start_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($leave['end_date'])) ?></td>
                                <td><?= $leave['days'] ?></td>
                                <td><?= htmlspecialchars($leave['reason'] ?: '-') ?></td>
                                <td>
                                    <div class="action-btns">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                            <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                            <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-reject">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($pendingLeaves)): ?>
                    <div class="empty-state">
                        <p>🎉 No pending leave requests. All caught up!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
