<?php
/**
 * Admin Allowances Management
 * Manage employee allowances (housing, transport, meal, etc.)
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
            try {
                $sql = "INSERT INTO allowances (employee_id, type, amount, is_recurring, description, effective_from, effective_to) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                Database::query($sql, [
                    $_POST['employee_id'],
                    $_POST['type'],
                    $_POST['amount'],
                    isset($_POST['is_recurring']) ? 1 : 0,
                    $_POST['description'],
                    $_POST['effective_from'],
                    $_POST['effective_to'] ?: null
                ]);
                $message = 'Allowance added successfully.';
            } catch (Exception $e) {
                $error = 'Failed to add allowance: ' . $e->getMessage();
            }
        } elseif ($action === 'delete') {
            try {
                Database::query("DELETE FROM allowances WHERE id = ?", [(int)$_POST['allowance_id']]);
                $message = 'Allowance deleted.';
            } catch (Exception $e) {
                $error = 'Failed to delete allowance.';
            }
        }
    }
}

// Get all allowances with employee info and role
$sql = "SELECT a.*, e.employee_code, CONCAT(u.first_name, ' ', u.last_name) as employee_name, u.role
        FROM allowances a
        JOIN employees e ON a.employee_id = e.id
        JOIN users u ON e.user_id = u.id
        ORDER BY a.created_at DESC";
$allowances = Database::query($sql)->fetchAll();

// Calculate category statistics
$categoryStats = [
    'all' => count($allowances),
    'employee' => 0,
    'manager' => 0,
    'housing' => 0,
    'transport' => 0,
    'meal' => 0,
    'communication' => 0,
    'medical' => 0,
    'education' => 0,
    'other' => 0
];
foreach ($allowances as $a) {
    if (isset($categoryStats[$a['type']])) {
        $categoryStats[$a['type']]++;
    }
    if ($a['role'] === 'manager') {
        $categoryStats['manager']++;
    } else {
        $categoryStats['employee']++;
    }
}

// Get all employees for dropdown
$employees = Employee::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allowances | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
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
        .page-header h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #86efac, #22c55e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header .lede { color: #9fb4c7; font-size: 16px; }
        
        .btn-add { padding: 14px 24px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600; animation: fadeInUp 0.6s ease-out; }
        .alert.success { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; overflow: hidden; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .table-container:hover { border-color: rgba(34,197,94,.2); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .table-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 18px; font-weight: 700; color: #86efac; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(34,197,94,.2); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; animation: fadeInUp 0.6s ease-out 0.15s both; }
        .filter-tab { padding: 10px 18px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 10px; color: #9fb4c7; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .3s; display: flex; align-items: center; gap: 8px; }
        .filter-tab:hover { background: rgba(255,255,255,.06); color: #e6edf5; transform: translateY(-2px); }
        .filter-tab.active { background: linear-gradient(135deg, rgba(34,197,94,.2), rgba(16,185,129,.15)); border-color: rgba(34,197,94,.4); color: #86efac; box-shadow: 0 4px 12px rgba(34,197,94,.2); }
        .filter-tab .count { padding: 2px 8px; background: rgba(255,255,255,.1); border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .08em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; font-weight: 500; }
        tbody tr { transition: all .3s; }
        tbody tr:hover { background: rgba(34,197,94,.05); transform: translateX(4px); }
        
        .type-badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .recurring-badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        .onetime-badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; background: rgba(251,191,36,.15); color: #fde047; border: 1px solid rgba(251,191,36,.3); }
        .amount { font-weight: 700; color: #86efac; font-size: 15px; }
        
        .btn-delete { padding: 8px 14px; background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.3); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all .3s; }
        .btn-delete:hover { background: rgba(239,68,68,.25); transform: translateY(-1px); }
        
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
        .form-group textarea { resize: vertical; min-height: 90px; font-family: inherit; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 10px; }
        .checkbox-label { display: flex; align-items: center; color: #e6edf5; font-weight: 500; }
        
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .empty-state { padding: 80px 20px; text-align: center; color: #9fb4c7; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.2; }
        
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
                    <a class="nav-link active" href="allowances.php"><span>➕</span> Allowances</a>
                    <a class="nav-link" href="deductions.php"><span>➖</span> Deductions</a>
                    <a class="nav-link" href="bonuses.php"><span>🎁</span> Bonuses</a>
                    <a class="nav-link" href="users.php"><span>🔐</span> Users</a>
                    <a class="nav-link" href="announcement.php"><span>📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leaves</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>➕ Allowances Management</h1>
                        <p class="lede">Manage allowances for all employees and managers</p>
                    </div>
                    <button class="btn-add" onclick="openModal()">+ Add Allowance</button>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all" data-filter-type="all">
                        <span>📋 All</span>
                        <span class="count"><?= $categoryStats['all'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="employee" data-filter-type="role">
                        <span>👤 Employees</span>
                        <span class="count"><?= $categoryStats['employee'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="manager" data-filter-type="role">
                        <span>👔 Managers</span>
                        <span class="count"><?= $categoryStats['manager'] ?></span>
                    </div>
                    <div style="width: 100%; height: 1px; background: rgba(255,255,255,.05); margin: 8px 0;"></div>
                    <div class="filter-tab" data-filter="housing" data-filter-type="type">
                        <span>🏠 Housing</span>
                        <span class="count"><?= $categoryStats['housing'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="transport" data-filter-type="type">
                        <span>🚗 Transport</span>
                        <span class="count"><?= $categoryStats['transport'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="meal" data-filter-type="type">
                        <span>🍽️ Meal</span>
                        <span class="count"><?= $categoryStats['meal'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="communication" data-filter-type="type">
                        <span>📱 Communication</span>
                        <span class="count"><?= $categoryStats['communication'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="medical" data-filter-type="type">
                        <span>⚕️ Medical</span>
                        <span class="count"><?= $categoryStats['medical'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="education" data-filter-type="type">
                        <span>🎓 Education</span>
                        <span class="count"><?= $categoryStats['education'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="other" data-filter-type="type">
                        <span>📦 Other</span>
                        <span class="count"><?= $categoryStats['other'] ?></span>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Allowances</h3>
                        <span class="badge"><?= count($allowances) ?> records</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee / Manager</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Frequency</th>
                                <th>Effective From</th>
                                <th>Effective To</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="allowancesTable">
                            <?php foreach ($allowances as $alw): ?>
                            <tr data-type="<?= htmlspecialchars($alw['type']) ?>" data-role="<?= htmlspecialchars($alw['role']) ?>">
                                <td>
                                    <div>
                                        <?php if ($alw['role'] === 'manager'): ?>
                                            <span style="background:rgba(139,92,246,.15);color:#c4b5fd;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:600;margin-right:4px;">👔 MANAGER</span>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($alw['employee_name']) ?></strong>
                                    </div>
                                    <div style="font-size:12px;color:#9fb4c7;"><?= htmlspecialchars($alw['employee_code']) ?></div>
                                </td>
                                <td><span class="type-badge"><?= ucfirst($alw['type']) ?></span></td>
                                <td class="amount">$<?= number_format($alw['amount'], 2) ?></td>
                                <td>
                                    <?php if ($alw['is_recurring']): ?>
                                        <span class="recurring-badge">Recurring</span>
                                    <?php else: ?>
                                        <span class="onetime-badge">One-time</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($alw['effective_from'])) ?></td>
                                <td><?= $alw['effective_to'] ? date('M d, Y', strtotime($alw['effective_to'])) : 'Ongoing' ?></td>
                                <td><?= htmlspecialchars($alw['description'] ?: '-') ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this allowance?')">
                                        <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="allowance_id" value="<?= $alw['id'] ?>">
                                        <button type="submit" class="btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($allowances)): ?>
                    <div class="empty-state"><p>No allowances yet. Click "Add Allowance" to create one.</p></div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <div class="modal" id="allowanceModal">
        <div class="modal-content">
            <div class="modal-header"><h2>Add Allowance</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
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
                    <label>Allowance Type *</label>
                    <select name="type" required>
                        <option value="housing">Housing</option>
                        <option value="transport">Transport</option>
                        <option value="meal">Meal</option>
                        <option value="communication">Communication</option>
                        <option value="medical">Medical</option>
                        <option value="education">Education</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required placeholder="500.00">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_recurring" checked>
                        Recurring (monthly)
                    </label>
                </div>
                <div class="form-group">
                    <label>Effective From *</label>
                    <input type="date" name="effective_from" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Effective To (optional)</label>
                    <input type="date" name="effective_to">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Optional notes..."></textarea>
                </div>
                <button type="submit" class="btn-submit">Add Allowance</button>
            </form>
        </div>
    </div>
    <script>
        function openModal() { document.getElementById('allowanceModal').classList.add('active'); }
        function closeModal() { document.getElementById('allowanceModal').classList.remove('active'); }
        document.getElementById('allowanceModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        
        // Category filtering
        const filterTabs = document.querySelectorAll('.filter-tab');
        const tableRows = document.querySelectorAll('#allowancesTable tr');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const filter = this.dataset.filter;
                const filterType = this.dataset.filterType;
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter rows
                tableRows.forEach(row => {
                    let show = false;
                    
                    if (filter === 'all') {
                        show = true;
                    } else if (filterType === 'role') {
                        show = row.dataset.role === filter;
                    } else if (filterType === 'type') {
                        show = row.dataset.type === filter;
                    }
                    
                    row.style.display = show ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>
