<?php
/**
 * Manager Payroll Page - Manage team bonuses, deductions, allowances
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Employee.php';

RoleMiddleware::require('manager');

$user = AuthMiddleware::user();
$message = '';
$error = '';

// Get employees under this manager
$myEmployees = Employee::getByManager($user->id);
$employeeIds = array_map(fn($e) => $e->id, $myEmployees);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        $empId = (int)$_POST['employee_id'];
        
        // Verify employee belongs to this manager
        if (!in_array($empId, $employeeIds)) {
            $error = 'You can only manage your own team members.';
        } else {
            if ($action === 'add_bonus') {
                $sql = "INSERT INTO bonuses (employee_id, amount, reason, date_awarded, approved_by) VALUES (?, ?, ?, ?, ?)";
                Database::query($sql, [$empId, $_POST['amount'], $_POST['reason'], $_POST['date_awarded'], $user->id]);
                $message = 'Bonus awarded successfully.';
            } elseif ($action === 'add_deduction') {
                $sql = "INSERT INTO deductions (employee_id, type, amount, is_recurring, description, effective_from, effective_to) VALUES (?, ?, ?, ?, ?, ?, ?)";
                Database::query($sql, [$empId, $_POST['type'], $_POST['amount'], isset($_POST['is_recurring']) ? 1 : 0, $_POST['description'], $_POST['effective_from'], $_POST['effective_to'] ?: null]);
                $message = 'Deduction added successfully.';
            } elseif ($action === 'add_allowance') {
                $sql = "INSERT INTO allowances (employee_id, type, amount, is_recurring, description, effective_from, effective_to) VALUES (?, ?, ?, ?, ?, ?, ?)";
                Database::query($sql, [$empId, $_POST['type'], $_POST['amount'], isset($_POST['is_recurring']) ? 1 : 0, $_POST['description'], $_POST['effective_from'], $_POST['effective_to'] ?: null]);
                $message = 'Allowance added successfully.';
            } elseif ($action === 'delete_bonus') {
                Database::query("DELETE FROM bonuses WHERE id = ? AND employee_id IN (" . implode(',', $employeeIds) . ")", [$_POST['item_id']]);
                $message = 'Bonus deleted.';
            } elseif ($action === 'delete_deduction') {
                Database::query("DELETE FROM deductions WHERE id = ? AND employee_id IN (" . implode(',', $employeeIds) . ")", [$_POST['item_id']]);
                $message = 'Deduction deleted.';
            } elseif ($action === 'delete_allowance') {
                Database::query("DELETE FROM allowances WHERE id = ? AND employee_id IN (" . implode(',', $employeeIds) . ")", [$_POST['item_id']]);
                $message = 'Allowance deleted.';
            }
        }
    }
}

// Get payroll data for team
$placeholders = implode(',', array_fill(0, count($employeeIds) ?: 1, '?'));
$ids = $employeeIds ?: [0];

$bonuses = Database::query("SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code 
    FROM bonuses b JOIN employees e ON b.employee_id = e.id JOIN users u ON e.user_id = u.id 
    WHERE b.employee_id IN ($placeholders) ORDER BY b.date_awarded DESC", $ids)->fetchAll();

$deductions = Database::query("SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code 
    FROM deductions d JOIN employees e ON d.employee_id = e.id JOIN users u ON e.user_id = u.id 
    WHERE d.employee_id IN ($placeholders) ORDER BY d.created_at DESC", $ids)->fetchAll();

$allowances = Database::query("SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name, e.employee_code 
    FROM allowances a JOIN employees e ON a.employee_id = e.id JOIN users u ON e.user_id = u.id 
    WHERE a.employee_id IN ($placeholders) ORDER BY a.created_at DESC", $ids)->fetchAll();

$allowanceTypes = ['housing', 'transport', 'meal', 'communication', 'medical', 'education', 'other'];
$deductionTypes = ['pension', 'insurance', 'loan', 'tax_adjustment', 'union_dues', 'garnishment', 'other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Payroll | PayrollPro Manager</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-add { padding: 10px 16px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-add.green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .btn-add.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-add.yellow { background: linear-gradient(135deg, #eab308, #ca8a04); }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,.1); padding-bottom: 12px; }
        .tab { padding: 10px 20px; background: rgba(255,255,255,.05); border: none; border-radius: 8px; color: #9fb4c7; font-weight: 600; cursor: pointer; }
        .tab.active { background: rgba(139,92,246,.2); color: #c4b5fd; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge.yellow { background: rgba(251,191,36,.15); color: #fde047; }
        .badge.red { background: rgba(239,68,68,.15); color: #fca5a5; }
        .badge.green { background: rgba(34,197,94,.15); color: #86efac; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        .amount-green { font-weight: 700; color: #86efac; }
        .amount-red { font-weight: 700; color: #fca5a5; }
        .amount-yellow { font-weight: 700; color: #fde047; }
        .type-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59,130,246,.15); color: #93c5fd; }
        .btn-delete { padding: 6px 12px; background: rgba(239,68,68,.15); color: #fca5a5; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .empty-state { padding: 40px 20px; text-align: center; color: #9fb4c7; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a2332; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 30px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h2 { font-size: 20px; }
        .modal-close { background: none; border: none; color: #9fb4c7; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #9fb4c7; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #e6edf5; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: rgba(139,92,246,.4); }
        .form-group.checkbox { display: flex; align-items: center; gap: 10px; }
        .form-group.checkbox input { width: auto; }
        .form-group.checkbox label { margin-bottom: 0; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; }
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
                        <p class="label">Manager</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="employees.php"><span>👥</span> My Team</a>
                    <a class="nav-link active" href="payroll.php"><span>💰</span> Team Payroll</a>
                    <a class="nav-link" href="salary.php"><span>💵</span> My Salary</a>
                    <a class="nav-link" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>💰 Team Payroll</h1>
                        <p class="lede">Manage bonuses, deductions, and allowances for your team</p>
                    </div>
                    <div class="btn-group">
                        <button class="btn-add yellow" onclick="openModal('bonus')">🎁 Award Bonus</button>
                        <button class="btn-add green" onclick="openModal('allowance')">💵 Add Allowance</button>
                        <button class="btn-add red" onclick="openModal('deduction')">➖ Add Deduction</button>
                    </div>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <div class="tabs">
                    <button class="tab active" onclick="showTab('bonuses')">🎁 Bonuses (<?= count($bonuses) ?>)</button>
                    <button class="tab" onclick="showTab('allowances')">💵 Allowances (<?= count($allowances) ?>)</button>
                    <button class="tab" onclick="showTab('deductions')">➖ Deductions (<?= count($deductions) ?>)</button>
                </div>

                <!-- Bonuses Tab -->
                <div id="bonuses" class="tab-content active">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Team Bonuses</h3>
                            <span class="badge yellow"><?= count($bonuses) ?> records</span>
                        </div>
                        <table>
                            <thead><tr><th>Employee</th><th>Amount</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($bonuses as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['employee_name']) ?> <small style="color:#9fb4c7;">(<?= $b['employee_code'] ?>)</small></td>
                                    <td class="amount-yellow">+$<?= number_format($b['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($b['reason']) ?></td>
                                    <td><?= date('M d, Y', strtotime($b['date_awarded'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this bonus?')">
                                            <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_bonus">
                                            <input type="hidden" name="item_id" value="<?= $b['id'] ?>">
                                            <input type="hidden" name="employee_id" value="<?= $b['employee_id'] ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($bonuses)): ?><div class="empty-state"><p>No bonuses for your team yet.</p></div><?php endif; ?>
                    </div>
                </div>

                <!-- Allowances Tab -->
                <div id="allowances" class="tab-content">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Team Allowances</h3>
                            <span class="badge green"><?= count($allowances) ?> records</span>
                        </div>
                        <table>
                            <thead><tr><th>Employee</th><th>Type</th><th>Amount</th><th>Recurring</th><th>Effective</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($allowances as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['employee_name']) ?> <small style="color:#9fb4c7;">(<?= $a['employee_code'] ?>)</small></td>
                                    <td><span class="type-badge"><?= ucfirst($a['type']) ?></span></td>
                                    <td class="amount-green">+$<?= number_format($a['amount'], 2) ?></td>
                                    <td><?= $a['is_recurring'] ? '✓ Yes' : 'No' ?></td>
                                    <td><?= date('M d, Y', strtotime($a['effective_from'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this allowance?')">
                                            <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_allowance">
                                            <input type="hidden" name="item_id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="employee_id" value="<?= $a['employee_id'] ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($allowances)): ?><div class="empty-state"><p>No allowances for your team yet.</p></div><?php endif; ?>
                    </div>
                </div>

                <!-- Deductions Tab -->
                <div id="deductions" class="tab-content">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Team Deductions</h3>
                            <span class="badge red"><?= count($deductions) ?> records</span>
                        </div>
                        <table>
                            <thead><tr><th>Employee</th><th>Type</th><th>Amount</th><th>Recurring</th><th>Effective</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($deductions as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['employee_name']) ?> <small style="color:#9fb4c7;">(<?= $d['employee_code'] ?>)</small></td>
                                    <td><span class="type-badge"><?= ucfirst(str_replace('_', ' ', $d['type'])) ?></span></td>
                                    <td class="amount-red">-$<?= number_format($d['amount'], 2) ?></td>
                                    <td><?= $d['is_recurring'] ? '✓ Yes' : 'No' ?></td>
                                    <td><?= date('M d, Y', strtotime($d['effective_from'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this deduction?')">
                                            <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_deduction">
                                            <input type="hidden" name="item_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="employee_id" value="<?= $d['employee_id'] ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($deductions)): ?><div class="empty-state"><p>No deductions for your team yet.</p></div><?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bonus Modal -->
    <div class="modal" id="bonusModal">
        <div class="modal-content">
            <div class="modal-header"><h2>🎁 Award Bonus</h2><button class="modal-close" onclick="closeModal('bonus')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_bonus">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select Team Member</option>
                        <?php foreach ($myEmployees as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->employee_code) ?> - <?= htmlspecialchars($emp->department_name ?? 'No Dept') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required placeholder="500.00">
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <input type="text" name="reason" required placeholder="e.g., Performance bonus">
                </div>
                <div class="form-group">
                    <label>Date Awarded *</label>
                    <input type="date" name="date_awarded" required value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn-submit">Award Bonus</button>
            </form>
        </div>
    </div>

    <!-- Allowance Modal -->
    <div class="modal" id="allowanceModal">
        <div class="modal-content">
            <div class="modal-header"><h2>💵 Add Allowance</h2><button class="modal-close" onclick="closeModal('allowance')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_allowance">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select Team Member</option>
                        <?php foreach ($myEmployees as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->employee_code) ?> - <?= htmlspecialchars($emp->department_name ?? 'No Dept') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Allowance Type *</label>
                    <select name="type" required>
                        <?php foreach ($allowanceTypes as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required placeholder="200.00">
                </div>
                <div class="form-group checkbox">
                    <input type="checkbox" name="is_recurring" id="allow_recurring" checked>
                    <label for="allow_recurring">Recurring (monthly)</label>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="e.g., Transport allowance">
                </div>
                <div class="form-group">
                    <label>Effective From *</label>
                    <input type="date" name="effective_from" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Effective To (leave blank for ongoing)</label>
                    <input type="date" name="effective_to">
                </div>
                <button type="submit" class="btn-submit">Add Allowance</button>
            </form>
        </div>
    </div>

    <!-- Deduction Modal -->
    <div class="modal" id="deductionModal">
        <div class="modal-content">
            <div class="modal-header"><h2>➖ Add Deduction</h2><button class="modal-close" onclick="closeModal('deduction')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_deduction">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select Team Member</option>
                        <?php foreach ($myEmployees as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->employee_code) ?> - <?= htmlspecialchars($emp->department_name ?? 'No Dept') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Deduction Type *</label>
                    <select name="type" required>
                        <?php foreach ($deductionTypes as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst(str_replace('_', ' ', $t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required placeholder="100.00">
                </div>
                <div class="form-group checkbox">
                    <input type="checkbox" name="is_recurring" id="ded_recurring" checked>
                    <label for="ded_recurring">Recurring (monthly)</label>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="e.g., Loan repayment">
                </div>
                <div class="form-group">
                    <label>Effective From *</label>
                    <input type="date" name="effective_from" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Effective To (leave blank for ongoing)</label>
                    <input type="date" name="effective_to">
                </div>
                <button type="submit" class="btn-submit">Add Deduction</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }
        function openModal(type) { document.getElementById(type + 'Modal').classList.add('active'); }
        function closeModal(type) { document.getElementById(type + 'Modal').classList.remove('active'); }
        document.querySelectorAll('.modal').forEach(m => {
            m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
        });
    </script>
</body>
</html>
