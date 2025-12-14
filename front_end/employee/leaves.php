<?php
/**
 * Employee Leave Requests Page
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Leave.php';
require_once __DIR__ . '/../../app/Controllers/LeaveController.php';

RoleMiddleware::require('employee');

$user = AuthMiddleware::user();
$employee = Employee::findByUserId($user->id);
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request') {
    if (!AuthMiddleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = LeaveController::request([
            'type' => $_POST['type'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'reason' => $_POST['reason']
        ]);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$leaves = LeaveController::getMyLeaves();
$balance = LeaveController::getBalance($employee->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(6,182,212,.08), transparent 30%), #0b1320; }
        
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); backdrop-filter: blur(10px); }
        .brand { font-weight: 800; font-size: 16px; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; background: rgba(255,255,255,.02); }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #0891b2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; }
        .user-card .label { font-size: 11px; color: #67e8f9; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; font-size: 15px; }
        
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: all .2s; }
        .nav-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; }
        
        .main { padding: 28px; overflow-y: auto; }
        
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-start; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; font-size: 15px; }
        
        .btn-request { padding: 12px 20px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn-request:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(6,182,212,.3); }
        
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert.error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        
        .balance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .balance-card { padding: 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.03); }
        .balance-card h4 { font-size: 12px; color: #9fb4c7; text-transform: uppercase; margin-bottom: 8px; }
        .balance-card .value { font-size: 28px; font-weight: 800; color: #67e8f9; }
        .balance-card .sub { font-size: 13px; color: #64748b; margin-top: 4px; }
        
        .table-container { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 14px; overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .table-header h3 { font-size: 16px; font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #9fb4c7; text-transform: uppercase; background: rgba(255,255,255,.02); border-bottom: 1px solid rgba(255,255,255,.06); }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 14px; }
        tr:hover { background: rgba(255,255,255,.02); }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.pending { background: rgba(251,191,36,.15); color: #fde047; }
        .status-badge.approved { background: rgba(34,197,94,.15); color: #86efac; }
        .status-badge.rejected { background: rgba(239,68,68,.15); color: #fca5a5; }
        
        .empty-state { padding: 60px 20px; text-align: center; color: #9fb4c7; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a2332; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 30px; width: 100%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h2 { font-size: 20px; }
        .modal-close { background: none; border: none; color: #9fb4c7; font-size: 24px; cursor: pointer; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #9fb4c7; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #e6edf5; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: rgba(6,182,212,.4); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; }
        
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
                        <p class="label">Employee</p>
                        <p class="name"><?= htmlspecialchars($user->getFullName()) ?></p>
                    </div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php"><span>📊</span> Dashboard</a>
                    <a class="nav-link" href="profile.php"><span>👤</span> My Profile</a>
                    <a class="nav-link" href="payroll.php"><span>💰</span> Payslips</a>
                    <a class="nav-link active" href="leaves.php"><span>📅</span> Leave Requests</a>
                    <a class="nav-link" href="attendance.php"><span>📋</span> Attendance</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>

            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>📅 Leave Requests</h1>
                        <p class="lede">Request and track your leave applications</p>
                    </div>
                    <button class="btn-request" onclick="openModal()">+ Request Leave</button>
                </div>

                <?php if ($message): ?>
                    <div class="alert success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="balance-grid">
                    <div class="balance-card">
                        <h4>Annual Leave</h4>
                        <div class="value"><?= $balance['annual']['remaining'] ?></div>
                        <div class="sub">of <?= $balance['annual']['total'] ?> days remaining</div>
                    </div>
                    <div class="balance-card">
                        <h4>Sick Leave</h4>
                        <div class="value"><?= $balance['sick']['remaining'] ?></div>
                        <div class="sub">of <?= $balance['sick']['total'] ?> days remaining</div>
                    </div>
                    <div class="balance-card">
                        <h4>Personal Leave</h4>
                        <div class="value"><?= $balance['personal']['remaining'] ?? 5 ?></div>
                        <div class="sub">of <?= $balance['personal']['total'] ?? 5 ?> days remaining</div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>My Leave History</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?= ucfirst($leave->type) ?></td>
                                <td><?= date('M d, Y', strtotime($leave->start_date)) ?></td>
                                <td><?= date('M d, Y', strtotime($leave->end_date)) ?></td>
                                <td><?= $leave->days ?></td>
                                <td><?= htmlspecialchars($leave->reason ?: '-') ?></td>
                                <td><span class="status-badge <?= $leave->status ?>"><?= ucfirst($leave->status) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($leaves)): ?>
                    <div class="empty-state">
                        <p>No leave requests yet. Click "Request Leave" to submit your first request.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Request Leave Modal -->
    <div class="modal" id="leaveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Request Leave</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="request">
                <?= AuthMiddleware::csrfField() ?>
                
                <div class="form-group">
                    <label>Leave Type</label>
                    <select name="type" required>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="personal">Personal Leave</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Reason (Optional)</label>
                    <textarea name="reason" placeholder="Briefly describe the reason for your leave..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('leaveModal').classList.add('active'); }
        function closeModal() { document.getElementById('leaveModal').classList.remove('active'); }
        document.getElementById('leaveModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
