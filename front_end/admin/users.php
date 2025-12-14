<?php
/**
 * Admin Users Page - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/User.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();

// Get all users
$stmt = Database::query("SELECT * FROM users ORDER BY role, first_name");
$users = $stmt->fetchAll();

// Calculate category statistics
$categoryStats = [
    'all' => count($users),
    'super_admin' => 0,
    'manager' => 0,
    'employee' => 0,
    'active' => 0,
    'inactive' => 0
];
foreach ($users as $u) {
    if (isset($categoryStats[$u['role']])) {
        $categoryStats[$u['role']]++;
    }
    if ($u['status'] === 'active') {
        $categoryStats['active']++;
    } else {
        $categoryStats['inactive']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | PayrollPro Admin</title>
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
        
        .header-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-input { padding: 12px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.06); color: #e6edf5; font-size: 14px; width: 250px; transition: all .3s; }
        .search-input:focus { outline: none; border-color: rgba(34,197,94,.5); background: rgba(255,255,255,.08); box-shadow: 0 0 0 3px rgba(34,197,94,.1); }
        .btn-add { padding: 14px 24px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; animation: fadeInUp 0.6s ease-out 0.15s both; }
        .filter-tab { padding: 10px 18px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 10px; color: #9fb4c7; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .3s; display: flex; align-items: center; gap: 8px; }
        .filter-tab:hover { background: rgba(255,255,255,.06); color: #e6edf5; transform: translateY(-2px); }
        .filter-tab.active { background: linear-gradient(135deg, rgba(34,197,94,.2), rgba(16,185,129,.15)); border-color: rgba(34,197,94,.4); color: #86efac; box-shadow: 0 4px 12px rgba(34,197,94,.2); }
        .filter-tab .count { padding: 2px 8px; background: rgba(255,255,255,.1); border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; overflow: hidden; transition: all .4s; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .table-container:hover { border-color: rgba(34,197,94,.2); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .table-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 18px; font-weight: 700; color: #86efac; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(34,197,94,.2); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .08em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; font-weight: 500; }
        tbody tr { transition: all .3s; }
        tbody tr:hover { background: rgba(34,197,94,.05); transform: translateX(4px); }
        
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.2); transition: all .3s; }
        tbody tr:hover .user-avatar { transform: scale(1.08); }
        .user-avatar.super_admin { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .user-avatar.manager { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .user-avatar.employee { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .user-name { font-weight: 600; font-size: 15px; }
        .user-email { color: #9fb4c7; font-size: 12px; margin-top: 2px; }
        
        .role-badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; border: 1px solid; }
        .role-badge.super_admin { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.3); }
        .role-badge.manager { background: rgba(139,92,246,.15); color: #c4b5fd; border-color: rgba(139,92,246,.3); }
        .role-badge.employee { background: rgba(6,182,212,.15); color: #67e8f9; border-color: rgba(6,182,212,.3); }
        
        .status-active { color: #86efac; font-weight: 600; }
        .status-inactive { color: #fca5a5; font-weight: 600; }
        
        .action-btn { padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; margin-right: 6px; transition: all .3s; border: 1px solid; display: inline-block; }
        .action-btn.view { background: rgba(59,130,246,.15); color: #93c5fd; border-color: rgba(59,130,246,.3); }
        .action-btn.view:hover { background: rgba(59,130,246,.25); transform: translateY(-1px); }
        .action-btn.edit { background: rgba(251,191,36,.15); color: #fde047; border-color: rgba(251,191,36,.3); }
        .action-btn.edit:hover { background: rgba(251,191,36,.25); transform: translateY(-1px); }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } 
            .page-header { flex-direction: column; }
            table { display: block; overflow-x: auto; } 
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
                    <a class="nav-link" href="bonuses.php"><span>🎁</span> Bonuses</a>
                    <a class="nav-link active" href="users.php"><span>🔐</span> Users</a>
                    <a class="nav-link" href="announcement.php"><span>📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>🔐 Users</h1>
                        <p class="lede">Manage system users and access</p>
                    </div>
                    <div class="header-actions">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search users..." onkeyup="searchTable()">
                        <a href="addUser.php" class="btn-add">+ Add User</a>
                    </div>
                </div>

                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all" data-filter-type="all">
                        <span>📋 All Users</span>
                        <span class="count"><?= $categoryStats['all'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="super_admin" data-filter-type="role">
                        <span>🔐 Super Admins</span>
                        <span class="count"><?= $categoryStats['super_admin'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="manager" data-filter-type="role">
                        <span>👔 Managers</span>
                        <span class="count"><?= $categoryStats['manager'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="employee" data-filter-type="role">
                        <span>👤 Employees</span>
                        <span class="count"><?= $categoryStats['employee'] ?></span>
                    </div>
                    <div style="width: 100%; height: 1px; background: rgba(255,255,255,.05); margin: 8px 0;"></div>
                    <div class="filter-tab" data-filter="active" data-filter-type="status">
                        <span>✅ Active</span>
                        <span class="count"><?= $categoryStats['active'] ?></span>
                    </div>
                    <div class="filter-tab" data-filter="inactive" data-filter-type="status">
                        <span>❌ Inactive</span>
                        <span class="count"><?= $categoryStats['inactive'] ?></span>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Users</h3>
                        <span class="badge"><?= count($users) ?> users</span>
                    </div>
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <?php foreach ($users as $u): 
                                $initials = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
                                $roleClass = $u['role'];
                            ?>
                            <tr data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= htmlspecialchars($u['status']) ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar <?= $roleClass ?>"><?= $initials ?></div>
                                        <div>
                                            <div class="user-name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="role-badge <?= $roleClass ?>"><?= ucwords(str_replace('_', ' ', $u['role'])) ?></span></td>
                                <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                <td class="status-<?= $u['status'] === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($u['status']) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'employee'): ?>
                                    <a href="viewEmployee.php?user_id=<?= $u['id'] ?>" class="action-btn view">View</a>
                                    <?php endif; ?>
                                    <a href="editUser.php?id=<?= $u['id'] ?>" class="action-btn edit">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        let currentFilter = 'all';
        let currentFilterType = 'all';
        
        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matchesSearch = text.includes(input);
                const matchesFilter = applyFilter(row);
                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }
        
        function applyFilter(row) {
            if (currentFilter === 'all') return true;
            if (currentFilterType === 'role') {
                return row.dataset.role === currentFilter;
            } else if (currentFilterType === 'status') {
                return row.dataset.status === currentFilter;
            }
            return true;
        }
        
        // Category filtering
        const filterTabs = document.querySelectorAll('.filter-tab');
        const tableRows = document.querySelectorAll('#usersTable tr');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                currentFilterType = this.dataset.filterType;
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Apply both filter and search
                searchTable();
            });
        });
    </script>
</body>
</html>
