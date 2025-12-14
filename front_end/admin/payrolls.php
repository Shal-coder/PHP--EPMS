<?php
/**
 * Admin Payrolls Page - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Payroll.php';
require_once __DIR__ . '/../../app/Controllers/PayrollController.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $result = PayrollController::create($_POST['period_start'], $_POST['period_end']);
        } elseif ($action === 'calculate') {
            $result = PayrollController::calculate((int)$_POST['payroll_id']);
        } elseif ($action === 'approve') {
            $result = PayrollController::approve((int)$_POST['payroll_id']);
        } elseif ($action === 'process') {
            $result = PayrollController::process((int)$_POST['payroll_id']);
        }
        $message = $result['success'] ?? false ? $result['message'] : '';
        $error = !($result['success'] ?? false) ? ($result['message'] ?? 'Error') : '';
    }
}

$payrolls = Payroll::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payrolls | PayrollPro Admin</title>
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
        .btn-create { padding: 12px 20px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.draft { background: rgba(251,191,36,.15); color: #fde047; }
        .status-badge.approved { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.processed { background: rgba(59,130,246,.15); color: #93c5fd; }
        .action-btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; margin-right: 6px; }
        .action-btn.calc { background: rgba(251,191,36,.15); color: #fde047; }
        .action-btn.approve { background: rgba(34,197,94,.15); color: #86efac; }
        .action-btn.process { background: rgba(59,130,246,.15); color: #93c5fd; }
        .action-btn.view { background: rgba(139,92,246,.15); color: #c4b5fd; text-decoration: none; display: inline-block; }
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a2332; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 30px; width: 100%; max-width: 450px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h2 { font-size: 20px; }
        .modal-close { background: none; border: none; color: #9fb4c7; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #9fb4c7; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #e6edf5; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: rgba(34,197,94,.4); }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; }
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
                        <h1>💰 Payrolls</h1>
                        <p class="lede">Create and manage payroll runs</p>
                    </div>
                    <button class="btn-create" onclick="openModal()">+ New Payroll Run</button>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <div class="table-container">
                    <div class="table-header"><h3>Payroll Runs</h3></div>
                    <table>
                        <thead><tr><th>Period</th><th>Created By</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($payrolls as $pr): ?>
                            <tr>
                                <td><?= date('M d', strtotime($pr['period_start'])) ?> - <?= date('M d, Y', strtotime($pr['period_end'])) ?></td>
                                <td><?= htmlspecialchars($pr['created_by_name'] ?? 'System') ?></td>
                                <td><span class="status-badge <?= $pr['status'] ?>"><?= ucfirst($pr['status']) ?></span></td>
                                <td>
                                    <?php if ($pr['status'] === 'draft'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>"><input type="hidden" name="action" value="calculate"><input type="hidden" name="payroll_id" value="<?= $pr['id'] ?>"><button type="submit" class="action-btn calc">Calculate</button></form>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="payroll_id" value="<?= $pr['id'] ?>"><button type="submit" class="action-btn approve">Approve</button></form>
                                    <?php elseif ($pr['status'] === 'approved'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>"><input type="hidden" name="action" value="process"><input type="hidden" name="payroll_id" value="<?= $pr['id'] ?>"><button type="submit" class="action-btn process">Process</button></form>
                                    <?php endif; ?>
                                    <a href="viewPayroll.php?id=<?= $pr['id'] ?>" class="action-btn view">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($payrolls)): ?><div class="empty-state"><p>No payroll runs yet. Click "New Payroll Run" to create one.</p></div><?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <div class="modal" id="payrollModal">
        <div class="modal-content">
            <div class="modal-header"><h2>New Payroll Run</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group"><label>Period Start</label><input type="date" name="period_start" required value="<?= date('Y-m-01') ?>"></div>
                <div class="form-group"><label>Period End</label><input type="date" name="period_end" required value="<?= date('Y-m-t') ?>"></div>
                <button type="submit" class="btn-submit">Create Payroll Run</button>
            </form>
        </div>
    </div>
    <script>
        function openModal() { document.getElementById('payrollModal').classList.add('active'); }
        function closeModal() { document.getElementById('payrollModal').classList.remove('active'); }
        document.getElementById('payrollModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>