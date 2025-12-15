<?php
/**
 * Announcements Page - View for All Users (Public Access)
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/Announcement.php';
require_once __DIR__ . '/../app/Models/Employee.php';

// Check if user is logged in (page is public but shows more for logged in users)
$user = null;
$role = 'guest';
$isLoggedIn = false;
$employee = null;

try {
    if (AuthMiddleware::check()) {
        $user = AuthMiddleware::user();
        $role = AuthMiddleware::role();
        $isLoggedIn = true;
        
        // Get employee info if user is employee or manager
        if ($role === 'employee' || $role === 'manager') {
            $employee = Employee::findByUserId($user->id);
        }
    }
} catch (Exception $e) {
    // Not logged in, that's fine - show public announcements
}

// Get published announcements for current user role
$announcements = Announcement::getPublished($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.15), transparent 35%), radial-gradient(circle at 80% 10%, rgba(8,145,178,.12), transparent 38%), radial-gradient(circle at 50% 80%, rgba(14,116,144,.08), transparent 40%), #0b1320; position: relative; overflow-x: hidden; }
        .shell::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 300px; background: linear-gradient(180deg, rgba(6,182,212,.05), transparent); pointer-events: none; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(11,19,32,.95); backdrop-filter: blur(20px); box-shadow: 0 4px 20px rgba(0,0,0,.3); position: sticky; top: 0; z-index: 100; }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.4); transition: all .3s; }
        .brand:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(6,182,212,.5); }
        .links { display: flex; gap: 20px; }
        .links a { color: #9fb4c7; text-decoration: none; font-weight: 600; font-size: 14px; transition: color .3s; }
        .links a:hover { color: #67e8f9; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.06); border-radius: 10px; border: 1px solid rgba(255,255,255,.05); transition: all .3s; }
        .user-mini:hover { background: rgba(255,255,255,.1); transform: translateY(-1px); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; animation: pulse 2s infinite; box-shadow: 0 0 10px rgba(6,182,212,.6); }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.08); padding: 20px; background: rgba(255,255,255,.02); animation: slideIn 0.5s ease-out; }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: linear-gradient(135deg, rgba(6,182,212,.12), rgba(8,145,178,.08)); border: 1px solid rgba(6,182,212,.25); border-radius: 14px; margin-bottom: 20px; transition: all .4s; position: relative; overflow: hidden; }
        .user-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.03), transparent); animation: shimmer 3s infinite; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(6,182,212,.25); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.4); transition: all .3s; }
        .user-card:hover .avatar { transform: scale(1.05); }
        .user-card .label { font-size: 11px; color: #67e8f9; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        .user-card .code { font-size: 11px; color: #9fb4c7; margin-top: 2px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .3s; position: relative; }
        .nav-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 0; height: 60%; background: linear-gradient(90deg, #06b6d4, transparent); border-radius: 0 4px 4px 0; transition: width .3s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; transform: translateX(4px); }
        .nav-link:hover::before { width: 4px; }
        .nav-link.active { background: rgba(6,182,212,.14); color: #a5f3fc; box-shadow: 0 4px 12px rgba(6,182,212,.15); }
        .nav-link.active::before { width: 4px; }
        .nav-icon { font-size: 18px; }
        
        .main { padding: 32px; overflow-y: auto; animation: fadeIn 0.6s ease-out; }
        
        .page-header { margin-bottom: 32px; animation: fadeInUp 0.6s ease-out; }
        .page-header h1 { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #67e8f9, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 16px; }
        
        .announcement-list { display: flex; flex-direction: column; gap: 20px; animation: fadeInUp 0.6s ease-out 0.1s both; }
        
        .announcement-card { background: linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 28px; transition: all .4s; position: relative; overflow: hidden; }
        .announcement-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #22c55e, #16a34a); transform: scaleY(0); transition: transform .3s; }
        .announcement-card:hover { border-color: rgba(34,197,94,.3); transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .announcement-card:hover::before { transform: scaleY(1); }
        .announcement-card.urgent::before { background: linear-gradient(180deg, #ef4444, #dc2626); transform: scaleY(1); }
        .announcement-card.high::before { background: linear-gradient(180deg, #f59e0b, #d97706); transform: scaleY(1); }
        
        .announcement-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; gap: 16px; flex-wrap: wrap; }
        .announcement-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .announcement-title { font-size: 20px; font-weight: 700; color: #e6edf5; }
        
        .priority-badge { padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid; }
        .priority-badge.urgent { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }
        .priority-badge.high { background: rgba(251,191,36,.15); color: #fde047; border-color: rgba(251,191,36,.3); }
        .priority-badge.normal { background: rgba(59,130,246,.15); color: #93c5fd; border-color: rgba(59,130,246,.3); }
        .priority-badge.low { background: rgba(100,116,139,.15); color: #cbd5e1; border-color: rgba(100,116,139,.3); }
        
        .announcement-date { color: #64748b; font-size: 13px; font-weight: 500; }
        .announcement-content { color: #9fb4c7; line-height: 1.8; font-size: 15px; margin-bottom: 16px; }
        .announcement-footer { padding-top: 16px; border-top: 1px solid rgba(255,255,255,.06); color: #64748b; font-size: 13px; }
        
        .empty-state { text-align: center; padding: 80px 20px; color: #9fb4c7; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.2; }
        .empty-state h3 { font-size: 22px; margin-bottom: 12px; color: #e6edf5; font-weight: 700; }
        
        @media (max-width: 900px) { 
            .layout { grid-template-columns: 1fr; } 
            .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
            .links { display: none; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="links">
                <a href="index.html">Home</a>
                <a href="support.php">Support</a>
                <a href="announcement.php">News</a>
            </div>
            <?php if ($isLoggedIn): ?>
                <div class="user-mini"><span class="dot"></span><span><?= htmlspecialchars($user->getFullName()) ?></span></div>
            <?php else: ?>
                <a href="../login.php" class="user-mini" style="text-decoration:none;">Login →</a>
            <?php endif; ?>
        </header>

        <div class="layout">
            <aside class="sidenav">
                <?php if ($isLoggedIn): ?>
                <div class="user-card">
                    <div class="avatar"><?= strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) ?></div>
                    <div>
                        <p class="label"><?= $role === 'manager' ? 'Manager' : ($role === 'employee' ? 'Employee' : 'User') ?></p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                        <?php if ($employee): ?>
                            <p class="code"><?= htmlspecialchars($employee->employee_code) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <nav>
                    <?php if ($role === 'employee'): ?>
                        <a class="nav-link" href="employee/dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                        <a class="nav-link" href="employee/profile.php"><span class="nav-icon">👤</span> My Profile</a>
                        <a class="nav-link" href="employee/payroll.php"><span class="nav-icon">💰</span> Payslips</a>
                        <a class="nav-link" href="employee/leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                        <a class="nav-link" href="employee/attendance.php"><span class="nav-icon">📋</span> Attendance</a>
                        <a class="nav-link active" href="announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                        <a class="nav-link" href="../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                    <?php elseif ($role === 'manager'): ?>
                        <a class="nav-link" href="../manager/dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                        <a class="nav-link" href="../manager/employees.php"><span class="nav-icon">👥</span> Employees</a>
                        <a class="nav-link" href="../manager/attendance.php"><span class="nav-icon">📋</span> Attendance</a>
                        <a class="nav-link" href="../manager/leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                        <a class="nav-link active" href="announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                        <a class="nav-link" href="../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                    <?php else: ?>
                        <a class="nav-link active" href="announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                        <a class="nav-link" href="../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                    <?php endif; ?>
                </nav>
                <?php else: ?>
                <div class="user-card">
                    <div class="avatar">👋</div>
                    <div>
                        <p class="label">Welcome</p>
                        <p class="name">Guest</p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link active" href="announcement.php"><span class="nav-icon">📢</span> Announcements</a>
                    <a class="nav-link" href="support.php"><span class="nav-icon">💬</span> Support</a>
                    <a class="nav-link" href="../login.php"><span class="nav-icon">🔐</span> Login</a>
                </nav>
                <?php endif; ?>
            </aside>

            <main class="main">
                <div class="page-header">
                    <h1>📢 Announcements</h1>
                    <p class="lede">Stay updated with company news and important information</p>
                </div>
                
                <?php if (empty($announcements)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📢</div>
                        <h3>No Announcements</h3>
                        <p>There are no announcements at this time. Check back later!</p>
                    </div>
                <?php else: ?>
                    <div class="announcement-list">
                        <?php foreach ($announcements as $ann): ?>
                            <article class="announcement-card <?= htmlspecialchars($ann->priority) ?>">
                                <div class="announcement-header">
                                    <div class="announcement-title-row">
                                        <h2 class="announcement-title"><?= htmlspecialchars($ann->title) ?></h2>
                                        <span class="priority-badge <?= htmlspecialchars($ann->priority) ?>">
                                            <?= htmlspecialchars($ann->priority) ?>
                                        </span>
                                    </div>
                                    <span class="announcement-date">
                                        <?= date('M d, Y', strtotime($ann->published_at ?? $ann->created_at)) ?>
                                    </span>
                                </div>
                                <p class="announcement-content"><?= nl2br(htmlspecialchars($ann->content)) ?></p>
                                <div class="announcement-footer">
                                    Posted by <?= htmlspecialchars($ann->author_name) ?>
                                    <?php if ($ann->expires_at && strtotime($ann->expires_at) > time()): ?>
                                        • Valid until <?= date('M d, Y', strtotime($ann->expires_at)) ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
