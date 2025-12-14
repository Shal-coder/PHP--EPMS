<?php
/**
 * Employee Profile Page - Uses New Backend System
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';

RoleMiddleware::require('employee');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);

if (!$employee) {
    die('Employee profile not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
        .shell { min-height: 100vh; display: flex; flex-direction: column; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.12), transparent 35%), radial-gradient(circle at 80% 10%, rgba(59,130,246,.11), transparent 38%), radial-gradient(circle at 50% 50%, rgba(139,92,246,.06), transparent 50%), #0b1320; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.9); backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 100; }
        .brand { font-weight: 800; letter-spacing: .08em; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.3); }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; transition: all .3s; }
        .user-mini:hover { background: rgba(255,255,255,.08); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; animation: pulse 2s infinite; }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; margin-bottom: 20px; transition: all .3s; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(6,182,212,.2); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,.3); }
        .user-card .label { margin: 0; font-size: 11px; color: #67e8f9; letter-spacing: .1em; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin: 4px 0 0; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; border: 1px solid transparent; transition: all .3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; transform: translateX(4px); }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; border-color: rgba(6,182,212,.28); }
        .nav-icon { width: 20px; text-align: center; }
        
        .main { padding: 28px; overflow-y: auto; animation: fadeIn 0.6s ease-out; }
        
        .page-header { margin-bottom: 32px; position: relative; }
        .page-header h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px; background: linear-gradient(135deg, #67e8f9, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .profile-grid { display: grid; grid-template-columns: 350px 1fr; gap: 28px; }
        
        .profile-card { background: linear-gradient(135deg, rgba(6,182,212,.08), rgba(59,130,246,.06)); border: 1px solid rgba(6,182,212,.2); border-radius: 20px; padding: 40px 30px; text-align: center; position: relative; overflow: hidden; transition: all .4s; animation: fadeIn 0.6s ease-out 0.1s both; }
        .profile-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,.03), transparent); animation: shimmer 3s infinite; }
        .profile-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(6,182,212,.25); }
        
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 42px; color: #fff; margin: 0 auto 24px; box-shadow: 0 8px 24px rgba(6,182,212,.4), 0 0 0 4px rgba(6,182,212,.1); position: relative; transition: all .4s; }
        .profile-card:hover .profile-avatar { transform: scale(1.05); box-shadow: 0 12px 32px rgba(6,182,212,.5), 0 0 0 6px rgba(6,182,212,.15); }
        .profile-avatar::after { content: '✓'; position: absolute; bottom: 5px; right: 5px; width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid #0b1320; }
        
        .profile-name { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .profile-code { color: #67e8f9; font-size: 14px; margin-bottom: 16px; font-weight: 600; letter-spacing: .05em; }
        .profile-dept { display: inline-block; padding: 8px 16px; background: rgba(59,130,246,.2); color: #93c5fd; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid rgba(59,130,246,.3); }
        
        .stats-mini { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
        .stat-mini { background: rgba(255,255,255,.04); border-radius: 12px; padding: 16px; text-align: center; transition: all .3s; }
        .stat-mini:hover { background: rgba(255,255,255,.08); transform: translateY(-2px); }
        .stat-mini .label { font-size: 11px; color: #9fb4c7; text-transform: uppercase; font-weight: 600; margin-bottom: 6px; }
        .stat-mini .value { font-size: 18px; font-weight: 800; color: #67e8f9; }
        
        .salary-highlight { background: linear-gradient(135deg, rgba(6,182,212,.15), rgba(59,130,246,.1)); border: 1px solid rgba(6,182,212,.3); border-radius: 16px; padding: 24px; margin-top: 24px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; transition: all .3s; }
        .salary-highlight:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(6,182,212,.2); }
        .salary-highlight::before { content: '💰'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 48px; opacity: 0.1; }
        .salary-highlight .label { color: #9fb4c7; font-size: 13px; text-transform: uppercase; font-weight: 600; letter-spacing: .05em; }
        .salary-highlight .value { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #67e8f9, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        .info-section { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 20px; padding: 32px; transition: all .4s; animation: fadeIn 0.6s ease-out 0.2s both; }
        .info-section:hover { border-color: rgba(6,182,212,.2); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .info-section h3 { font-size: 18px; font-weight: 700; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid rgba(6,182,212,.2); color: #67e8f9; display: flex; align-items: center; gap: 10px; }
        .info-section h3::before { content: ''; width: 4px; height: 24px; background: linear-gradient(135deg, #06b6d4, #0891b2); border-radius: 2px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .info-item { background: rgba(255,255,255,.02); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,.04); transition: all .3s; }
        .info-item:hover { background: rgba(255,255,255,.05); border-color: rgba(6,182,212,.2); transform: translateY(-2px); }
        .info-item label { display: block; font-size: 11px; color: #9fb4c7; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; font-weight: 700; }
        .info-item span { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .info-item .icon { font-size: 18px; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.active { background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        .status-badge.active::before { content: '●'; color: #22c55e; animation: pulse 2s infinite; }
        
        @media (max-width: 1100px) { .profile-grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidenav { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); } .info-grid { grid-template-columns: 1fr; } }
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
                    <a class="nav-link" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
                    <a class="nav-link active" href="profile.php"><span class="nav-icon">👤</span> My Profile</a>
                    <a class="nav-link" href="payroll.php"><span class="nav-icon">💰</span> Payslips</a>
                    <a class="nav-link" href="leaves.php"><span class="nav-icon">📅</span> Leave Requests</a>
                    <a class="nav-link" href="attendance.php"><span class="nav-icon">📋</span> Attendance</a>
                    <a class="nav-link" href="../../logout.php"><span class="nav-icon">🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <h1>👤 My Profile</h1>
                    <p class="lede">View your personal and employment information</p>
                </div>

                <div class="profile-grid">
                    <div class="profile-card">
                        <div class="profile-avatar"><?= strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) ?></div>
                        <div class="profile-name"><?= htmlspecialchars($user->getFullName()) ?></div>
                        <div class="profile-code"><?= htmlspecialchars($employee->employee_code) ?></div>
                        <div class="profile-dept">🏢 <?= htmlspecialchars($employee->department_name ?? 'No Department') ?></div>
                        
                        <div class="stats-mini">
                            <div class="stat-mini">
                                <div class="label">Hire Date</div>
                                <div class="value"><?= date('Y', strtotime($employee->hire_date)) ?></div>
                            </div>
                            <div class="stat-mini">
                                <div class="label">Tax Class</div>
                                <div class="value"><?= htmlspecialchars($employee->tax_class) ?></div>
                            </div>
                        </div>
                        
                        <div class="salary-highlight">
                            <div>
                                <div class="label">Base Salary</div>
                                <div class="value">$<?= number_format($employee->base_salary, 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="info-section">
                            <h3>📋 Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>👤 Full Name</label>
                                    <span><?= htmlspecialchars($user->getFullName()) ?></span>
                                </div>
                                <div class="info-item">
                                    <label>📧 Email Address</label>
                                    <span><?= htmlspecialchars($user->email) ?></span>
                                </div>
                                <div class="info-item">
                                    <label>📱 Phone Number</label>
                                    <span><?= htmlspecialchars($user->phone ?? 'Not provided') ?></span>
                                </div>
                                <div class="info-item">
                                    <label>🆔 Employee Code</label>
                                    <span><?= htmlspecialchars($employee->employee_code) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="info-section" style="margin-top: 24px;">
                            <h3>💼 Employment Details</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>🏢 Department</label>
                                    <span><?= htmlspecialchars($employee->department_name ?? 'Unassigned') ?></span>
                                </div>
                                <div class="info-item">
                                    <label>👔 Manager</label>
                                    <span><?= htmlspecialchars($employee->manager_name ?? 'None') ?></span>
                                </div>
                                <div class="info-item">
                                    <label>📅 Hire Date</label>
                                    <span><?= date('F d, Y', strtotime($employee->hire_date)) ?></span>
                                </div>
                                <div class="info-item">
                                    <label>📊 Tax Class</label>
                                    <span>Class <?= htmlspecialchars($employee->tax_class) ?></span>
                                </div>
                                <div class="info-item">
                                    <label>🏦 Bank Account</label>
                                    <span><?= $employee->bank_account ? '****' . substr($employee->bank_account, -4) : 'Not provided' ?></span>
                                </div>
                                <div class="info-item">
                                    <label>⚡ Status</label>
                                    <span><span class="status-badge <?= $employee->status ?>"><?= ucfirst($employee->status) ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
