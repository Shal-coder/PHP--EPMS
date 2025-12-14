<?php
/**
 * Admin Departments Page
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Department.php';
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
            $result = Department::create([
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'manager_user_id' => $_POST['manager_user_id'] ?: null
            ]);
            $message = $result ? 'Department created successfully.' : '';
            $error = !$result ? 'Failed to create department.' : '';
        } elseif ($action === 'delete') {
            $dept = Department::find((int)$_POST['dept_id']);
            if ($dept) {
                $dept->delete();
                $message = 'Department deleted.';
            }
        }
    }
}

$departments = Department::getAll();
$managers = User::getManagers();

// Calculate statistics
$totalDepts = count($departments);
$activeDepts = count(array_filter($departments, fn($d) => $d->status === 'active'));
$withManagers = count(array_filter($departments, fn($d) => $d->manager_user_id !== null));
$withoutManagers = $totalDepts - $withManagers;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | PayrollPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        
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
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out 0.1s both; }
        .stat-card { padding: 20px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; transition: all .4s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(34,197,94,.05), transparent); animation: shimmer 3s infinite; }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(34,197,94,.3); box-shadow: 0 8px 24px rgba(34,197,94,.15); }
        .stat-card .label { color: #9fb4c7; font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: .05em; }
        .stat-card .value { font-size: 28px; font-weight: 800; color: #86efac; margin-top: 8px; }
        
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .dept-card { background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 28px; transition: all .4s; position: relative; overflow: hidden; animation: scaleIn 0.5s ease-out; }
        .dept-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #22c55e, #16a34a); transform: scaleX(0); transform-origin: left; transition: transform .4s; }
        .dept-card:hover { transform: translateY(-6px); border-color: rgba(34,197,94,.3); box-shadow: 0 12px 32px rgba(0,0,0,.3); }
        .dept-card:hover::before { transform: scaleX(1); }
        .dept-icon { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, rgba(34,197,94,.2), rgba(16,185,129,.15)); display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 16px; transition: all .3s; }
        .dept-card:hover .dept-icon { transform: scale(1.1) rotate(5deg); }
        .dept-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 10px; color: #e6edf5; }
        .dept-card p { color: #9fb4c7; font-size: 14px; line-height: 1.6; margin-bottom: 20px; min-height: 42px; }
        .dept-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid rgba(255,255,255,.08); }
        .dept-meta .manager-info { display: flex; align-items: center; gap: 8px; }
        .dept-meta .manager-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; }
        .dept-meta .manager-name { color: #86efac; font-size: 13px; font-weight: 600; }
        .dept-meta .unassigned { color: #64748b; font-size: 13px; font-style: italic; }
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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 16px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; color: #e6edf5; font-size: 14px; font-weight: 500; transition: all .3s; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: rgba(34,197,94,.5); background: rgba(255,255,255,.08); box-shadow: 0 0 0 3px rgba(34,197,94,.1); }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .empty-state { padding: 80px 20px; text-align: center; color: #9fb4c7; animation: fadeInUp 0.6s ease-out 0.3s both; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.2; }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
            .page-header { flex-direction: column; }
            .dept-grid { grid-template-columns: 1fr; }
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
                    <a class="nav-link active" href="departments.php"><span>🏢</span> Departments</a>
                    <a class="nav-link" href="payrolls.php"><span>💰</span> Payrolls</a>
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
                        <h1>🏢 Departments</h1>
                        <p class="lede">Manage company departments</p>
                    </div>
                    <button class="btn-add" onclick="openModal()">+ Add Department</button>
                </div>
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="label">🏢 Total Departments</div>
                        <div class="value"><?= $totalDepts ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">✅ Active</div>
                        <div class="value"><?= $activeDepts ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">👔 With Managers</div>
                        <div class="value"><?= $withManagers ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">⚠️ Unassigned</div>
                        <div class="value"><?= $withoutManagers ?></div>
                    </div>
                </div>
                
                <div class="dept-grid">
                    <?php 
                    $deptIcons = [
                        'Engineering' => '⚙️',
                        'Human Resources' => '👥',
                        'Finance' => '💰',
                        'Marketing' => '📢',
                        'Sales' => '💼',
                        'IT' => '💻',
                        'Operations' => '🔧',
                        'Support' => '🎧'
                    ];
                    foreach ($departments as $dept): 
                        $icon = $deptIcons[$dept->name] ?? '🏢';
                        $managerInitials = '';
                        if ($dept->manager_name) {
                            $names = explode(' ', $dept->manager_name);
                            $managerInitials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                        }
                    ?>
                    <div class="dept-card">
                        <div class="dept-icon"><?= $icon ?></div>
                        <h3><?= htmlspecialchars($dept->name) ?></h3>
                        <p><?= htmlspecialchars($dept->description ?? 'No description provided') ?></p>
                        <div class="dept-meta">
                            <?php if ($dept->manager_name): ?>
                                <div class="manager-info">
                                    <div class="manager-avatar"><?= $managerInitials ?></div>
                                    <span class="manager-name"><?= htmlspecialchars($dept->manager_name) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="unassigned">No manager assigned</span>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?= htmlspecialchars($dept->name) ?>?')">
                                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="dept_id" value="<?= $dept->id ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($departments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏢</div>
                    <p>No departments yet. Click "Add Department" to create one.</p>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <div class="modal" id="deptModal">
        <div class="modal-content">
            <div class="modal-header"><h2>Add Department</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <div class="form-group"><label>Department Name</label><input type="text" name="name" required placeholder="e.g. Engineering"></div>
                <div class="form-group"><label>Description</label><textarea name="description" placeholder="Brief description..."></textarea></div>
                <div class="form-group"><label>Department Manager</label><select name="manager_user_id"><option value="">Select Manager</option><?php foreach ($managers as $m): ?><option value="<?= $m->id ?>"><?= htmlspecialchars($m->getFullName()) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn-submit">Create Department</button>
            </form>
        </div>
    </div>
    <script>
        function openModal() { document.getElementById('deptModal').classList.add('active'); }
        function closeModal() { document.getElementById('deptModal').classList.remove('active'); }
        document.getElementById('deptModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>