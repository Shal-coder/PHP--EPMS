<?php
/**
 * Admin Bonuses Management Page
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/User.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $sql = "INSERT INTO bonuses (employee_id, amount, reason, date_awarded, approved_by) 
                    VALUES (?, ?, ?, ?, ?)";
            Database::query($sql, [
                $_POST['employee_id'],
                $_POST['amount'],
                $_POST['reason'],
                $_POST['date_awarded'],
                $user->id
            ]);
            $message = 'Bonus added successfully.';
        } elseif ($action === 'delete') {
            Database::query("DELETE FROM bonuses WHERE id = ?", [$_POST['bonus_id']]);
            $message = 'Bonus deleted.';
        }
    }
}

// Get all bonuses with employee info and role
$sql = "SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code, u.role,
        CONCAT(approver.first_name, ' ', approver.last_name) as approved_by_name
        FROM bonuses b
        JOIN employees e ON b.employee_id = e.id
        JOIN users u ON e.user_id = u.id
        LEFT JOIN users approver ON b.approved_by = approver.id
        ORDER BY b.date_awarded DESC";
$bonuses = Database::query($sql)->fetchAll();

$employees = Employee::getAll();

// Calculate total bonuses this month
$thisMonth = date('Y-m');
$totalThisMonth = array_reduce($bonuses, function($sum, $b) use ($thisMonth) {
    return substr($b['date_awarded'], 0, 7) === $thisMonth ? $sum + $b['amount'] : $sum;
}, 0);

// Calculate category statistics
$categoryStats = [
    'all' => count($bonuses),
    'employee' => 0,
    'manager' => 0,
    'this_month' => 0,
    'this_year' => 0
];
$thisYear = date('Y');
foreach ($bonuses as $b) {
    if ($b['role'] === 'manager') {
        $categoryStats['manager']++;
    } else {
        $categoryStats['employee']++;
    }
    if (substr($b['date_awarded'], 0, 7) === $thisMonth) {
        $categoryStats['this_month']++;
    }
    if (substr($b['date_awarded'], 0, 4) === $thisYear) {
        $categoryStats['this_year']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonuses | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 20px rgba(251,191,36,.3); } 50% { box-shadow: 0 0 30px rgba(251,191,36,.5); } }
        
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.15), transparent 35%), radial-gradient(circle at 80% 10%, rgba(16,185,129,.12), transparent 38%), radial-gradient(circle at 50% 80%, rgba(5,150,105,.08), transparent 40%), #0b1320; position: relative; overflow-x: hidden; }
        .shell::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 300px; background: linear-gradient(180deg, rgba(34,197,94,.05), transparent); pointer-events: none; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(11,19,32,.95); backdrop-filter: blur(20px); box-shadow: 0 4px 20px rgba(0,0,0,.3); position: sticky; top: 0; z-index: 100; }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 4px 12px rgba(34,197,94,.4); transition: all .3s; }
        .brand:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.5); }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.06); border-radius: 10px; border: 1px solid rgba(255,255,255,.05); transition: all .3s; }
        .user-mini:hover { background: rgba(255,255,255,.1); transform: translateY(-1px); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; box-shadow: 0 0 10px rgba(34,197,94,.6); }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.08); padding: 20px; background: rgba(255,255,255,.02); animation: slideIn 0.5s ease-out; }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: linear-gradient(135deg, rgba(34,197,94,.12), rgba(16,185,129,.08)); border: 1px solid rgba(34,197,94,.25); border-radius: 14px; margin-bottom: 20px; transition: all .4s; position: relative; overflow: hidden; }
        .user-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.03), transparent); animation: shimmer 3s infinite; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(34,197,94,.25); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(34,197,94,.4); transition: all .3s; }
        .user-card:hover .avatar { transform: scale(1.05); }
        .user-card .label { font-size: 11px; color: #86efac; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .3s; position: relative; }
        .nav-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 0; height: 60%; background: linear-gradient(90deg, #22c55e, transparent); border-radius: 0 4px 4px 0; transition: width .3s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; transform: translateX(4px); }
        .nav-link:hover::before { width: 4px; }
        .nav-link.active { background: rgba(34,197,94,.14); color: #a6f3bf; box-shadow: 0 4px 12px rgba(34,197,94,.15); }
        .nav-link.active::before { width: 4px; }
        
        .main { padding: 32px; overflow-y: auto; animation: fadeIn 0.6s ease-out; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #fde047, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header .lede { color: #9fb4c7; font-size: 16px; }
        
        .btn-add { padding: 14px 24px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600; animation: fadeInUp 0.6s ease-out; }
        .alert.success { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; animation: fadeInUp 0.6s ease-out 0.1s both; }
        .stat-card { padding: 20px; background: rgba(255,255,255,.03); border: 1px solid rgba(251,191,36,.2); border-radius: 14px; transition: all .4s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(251,191,36,.05), transparent); animation: shimmer 3s infinite; }
        .stat-card:hover { transform: translateY(-4px); animation: glow 2s infinite; }
        .stat-card .label { color: #9fb4c7; font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: .05em; }
        .stat-card .value { font-size: 28px; font-weight: 800; color: #fde047; margin-top: 8px; text-shadow: 0 0 20px rgba(251,191,36,.3); }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; overflow: hidden; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .table-container:hover { border-color: rgba(34,197,94,.2); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .table-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 18px; font-weight: 700; color: #86efac; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(251,191,36,.2); color: #fde047; border: 1px solid rgba(251,191,36,.3); }
        
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; animation: fadeInUp 0.6s ease-out 0.15s both; }
        .filter-tab { padding: 10px 18px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 10px; color: #9fb4c7; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .3s; display: flex; align-items: center; gap: 8px; }
        .filter-tab:hover { background: rgba(255,255,255,.06); color: #e6edf5; transform: translateY(-2px); }
        .filter-tab.active { background: linear-gradient(135deg, rgba(251,191,36,.2), rgba(234,179,8,.15)); border-color: rgba(251,191,36,.4); color: #fde047; box-shadow: 0 4px 12px rgba(251,191,36,.2); }
        .filter-tab .count { padding: 2px 8px; background: rgba(255,255,255,.1); border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .08em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; font-weight: 500; }
        tbody tr { transition: all .3s; }
        tbody tr:hover { background: rgba(34,197,94,.05); transform: translateX(4px); }
        
        .amount { font-weight: 700; color: #fde047; font-size: 15px; text-shadow: 0 0 10px rgba(251,191,36,.2); }
        .btn-delete { padding: 8px 14px; background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.3); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all .3s; }
        .btn-delete:hover { background: rgba(239,68,68,.25); transform: translateY(-1px); }
        
        .empty-state { padding: 80px 20px; text-align: center; color: #9fb4c7; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.2; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; animation: fadeIn 0.3s ease-out; }
        .modal-content { background: linear-gradient(135deg, #1a2332, #151e2b); border: 1px solid rgba(34,197,94,.2); border-radius: 20px; padding: 32px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.5); animation: fadeInUp 0.3s ease-out; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .modal-header h2 { font-size: 22px; font-weight: 800; color: #86efac; }
        .modal-close { background: rgba(255,255,255,.05); border: none; color: #9fb4c7; font-size: 24px; cursor: pointer; width: 36px; height: 36px; border-radius: 50%; transition: all .3s; }
        .modal-close:hover { background: rgba(239,68,68,.15); color: #fca5a5; transform: rotate(90deg); }
        
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-size: 13px; color: #9fb4c7; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 16px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; color: #e6edf5; font-size: 14px; font-weight: 500; transition: all .3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: rgba(34,197,94,.5); background: rgba(255,255,255,.08); box-shadow: 0 0 0 3px rgba(34,197,94,.1); }
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
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
                        <p class="label">Super Admin</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="employees.php"><span>👥</span> Employees</a>
                    <a class="nav-link" href="departments.php"><span>🏢</span> Departments</a>
                    <a class="nav-link" href="payrolls.php"><span>💰</span> Payrolls</a>
                    <a class="nav-link" href="allowances.php"><span>💵</span> Allowances</a>
                    <a class="nav-link" href="deductions.php"><span>➖</span> Deductions</a>
                    <a class="nav-link active" href="bonuses.php"><span>🎁</span> Bonuses</a>
                    <a class="nav-link" href="users.php"><span>🔐</span> Users</a>
                    <a class="nav-link" href="announcement.php"><span>📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>🎁 Bonuses Management</h1>
                        <p class="lede">Award bonuses to all employees and managers</p>
                    </div>
                    <button class="btn-add" onclick="openModal()">+ Award Bonus</button>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <div class="stats-row">
                    <div class="stat-card">
                        <p class="label">Total Bonuses This Month</p>
                        <p class="value">$<?= number_format($totalThisMonth, 2) ?></p>
                    </div>
                </div>
                
                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all">
                        <span>📋 All Bonuses</span>
                        <span class="count"><?= $categoryStats['all'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="employee">
                        <span>👤 Employees</span>
                        <span class="count"><?= $categoryStats['employee'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="manager">
                        <span>👔 Managers</span>
                        <span class="count"><?= $categoryStats['manager'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="this_month">
                        <span>📅 This Month</span>
                        <span class="count"><?= $categoryStats['this_month'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="this_year">
                        <span>📆 This Year</span>
                        <span class="count"><?= $categoryStats['this_year'] ?></span>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Bonuses</h3>
                        <span class="badge"><?= count($bonuses) ?> records</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee / Manager</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Date Awarded</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bonusesTable">
                            <?php foreach ($bonuses as $b): 
                                $isThisMonth = substr($b['date_awarded'], 0, 7) === date('Y-m') ? '1' : '0';
                                $isThisYear = substr($b['date_awarded'], 0, 4) === date('Y') ? '1' : '0';
                            ?>
                            <tr data-role="<?= htmlspecialchars($b['role']) ?>" data-month="<?= $isThisMonth ?>" data-year="<?= $isThisYear ?>">
                                <td>
                                    <?php if ($b['role'] === 'manager'): ?>
                                        <span style="background:rgba(139,92,246,.15);color:#c4b5fd;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:600;margin-right:4px;">👔 MANAGER</span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($b['employee_name']) ?> <small style="color:#9fb4c7;">(<?= $b['employee_code'] ?>)</small>
                                </td>
                                <td class="amount">+$<?= number_format($b['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($b['reason']) ?></td>
                                <td><?= date('M d, Y', strtotime($b['date_awarded'])) ?></td>
                                <td><?= htmlspecialchars($b['approved_by_name'] ?? 'System') ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this bonus?')">
                                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="bonus_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($bonuses)): ?><div class="empty-state"><p>No bonuses awarded yet. Click "Award Bonus" to create one.</p></div><?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <div class="modal" id="bonusModal">
        <div class="modal-content">
            <div class="modal-header"><h2>Award Bonus</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group">
                    <label>Employee / Manager *</label>
                    <select name="employee_id" required>
                        <option value="">Select Employee or Manager</option>
                        <?php foreach ($employees as $emp): 
                            $empUser = User::find($emp->user_id);
                            $roleLabel = $empUser->role === 'manager' ? '👔 Manager' : '👤 Employee';
                        ?>
                        <option value="<?= $emp->id ?>"><?= $roleLabel ?> - <?= htmlspecialchars($empUser->getFullName()) ?> (<?= $emp->employee_code ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required placeholder="500.00">
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <input type="text" name="reason" required placeholder="e.g., Performance bonus Q4">
                </div>
                <div class="form-group">
                    <label>Date Awarded *</label>
                    <input type="date" name="date_awarded" required value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn-submit">Award Bonus</button>
            </form>
        </div>
    </div>
    <script>
        function openModal() { document.getElementById('bonusModal').classList.add('active'); }
        function closeModal() { document.getElementById('bonusModal').classList.remove('active'); }
        document.getElementById('bonusModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        
        // Category filtering
        const filterTabs = document.querySelectorAll('.filter-tab');
        const tableRows = document.querySelectorAll('#bonusesTable tr');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter rows
                tableRows.forEach(row => {
                    let show = false;
                    
                    if (filter === 'all') {
                        show = true;
                    } else if (filter === 'employee' || filter === 'manager') {
                        show = row.dataset.role === filter;
                    } else if (filter === 'this_month') {
                        show = row.dataset.month === '1';
                    } else if (filter === 'this_year') {
                        show = row.dataset.year === '1';
                    }
                    
                    row.style.display = show ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>
