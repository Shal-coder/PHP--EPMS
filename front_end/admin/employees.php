<?php
/**
 * Admin Employees Page - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Department.php';

RoleMiddleware::require('super_admin');

$user = AuthMiddleware::user();
$employees = Employee::getAll();
$departments = Department::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees | PayrollPro Admin</title>
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
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .header-actions { display: flex; gap: 12px; align-items: center; }
        .search-input { padding: 12px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04); color: #e6edf5; font-size: 14px; width: 280px; }
        .search-input:focus { outline: none; border-color: rgba(34,197,94,.4); }
        .search-input::placeholder { color: #6b7c93; }
        .btn-add { padding: 12px 20px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all .2s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(34,197,94,.3); }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(34,197,94,.15); color: #86efac; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; letter-spacing: .05em; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        tr:last-child td { border-bottom: none; }
        
        .emp-info { display: flex; align-items: center; gap: 12px; }
        .emp-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; color: #fff; }
        .emp-name { font-weight: 600; }
        .emp-email { color: #9fb4c7; font-size: 12px; }
        .dept-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59,130,246,.15); color: #93c5fd; }
        .salary { font-weight: 700; color: #86efac; }
        .status-active { color: #86efac; }
        .status-inactive { color: #fca5a5; }
        
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; margin-right: 6px; transition: all .2s; }
        .action-btn.view { background: rgba(59,130,246,.15); color: #93c5fd; }
        .action-btn.edit { background: rgba(251,191,36,.15); color: #fde047; }
        .action-btn.delete { background: rgba(239,68,68,.15); color: #fca5a5; }
        .action-btn:hover { transform: translateY(-1px); }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        
        @media (max-width: 1200px) { table { display: block; overflow-x: auto; } }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } .links { display: none; } .search-input { width: 200px; } }
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
                    <a class="nav-link" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                    <a class="nav-link active" href="employees.php"><span class="nav-icon">👥</span> Employees</a>
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
                        <h1>👥 Employees</h1>
                        <p class="lede">Manage all employee records and profiles</p>
                    </div>
                    <div class="header-actions">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search employees..." onkeyup="searchTable()">
                        <a href="addEmp.php" class="btn-add">+ Add Employee</a>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Employee Directory</h3>
                        <span class="badge"><?= count($employees) ?> employees</span>
                    </div>
                    <table id="employeeTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Code</th>
                                <th>Department</th>
                                <th>Manager</th>
                                <th>Salary</th>
                                <th>Hire Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): 
                                $empUser = User::find($emp->user_id);
                                $initials = strtoupper(substr($empUser->first_name, 0, 1) . substr($empUser->last_name, 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="emp-info">
                                        <div class="emp-avatar"><?= $initials ?></div>
                                        <div>
                                            <div class="emp-name"><?= htmlspecialchars($empUser->getFullName()) ?></div>
                                            <div class="emp-email"><?= htmlspecialchars($empUser->email) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($emp->employee_code) ?></td>
                                <td><span class="dept-badge"><?= htmlspecialchars($emp->department_name ?? 'Unassigned') ?></span></td>
                                <td><?= htmlspecialchars($emp->manager_name ?? 'None') ?></td>
                                <td class="salary">$<?= number_format($emp->base_salary, 2) ?></td>
                                <td><?= date('M d, Y', strtotime($emp->hire_date)) ?></td>
                                <td class="status-<?= $emp->status === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($emp->status) ?></td>
                                <td>
                                    <a href="viewEmployee.php?id=<?= $emp->id ?>" class="action-btn view">View</a>
                                    <a href="editEmp.php?id=<?= $emp->id ?>" class="action-btn edit">Edit</a>
                                    <a href="deleteEmp.php?id=<?= $emp->id ?>" class="action-btn delete" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($employees)): ?>
                    <div class="empty-state">
                        <p>No employees found. <a href="addEmp.php" style="color: #86efac;">Add your first employee</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#employeeTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
