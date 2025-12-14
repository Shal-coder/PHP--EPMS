<?php
/**
 * Admin Announcements Management
 */

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Models/Announcement.php';

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
                Announcement::create([
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'priority' => $_POST['priority'],
                    'target_audience' => $_POST['target_audience'],
                    'created_by' => $user->id,
                    'is_published' => isset($_POST['is_published']) ? 1 : 0,
                    'expires_at' => $_POST['expires_at'] ?: null
                ]);
                $message = 'Announcement created successfully.';
            } catch (Exception $e) {
                $error = 'Failed to create announcement: ' . $e->getMessage();
            }
        } elseif ($action === 'update') {
            try {
                $ann = Announcement::find((int)$_POST['announcement_id']);
                if ($ann) {
                    $ann->update([
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'priority' => $_POST['priority'],
                        'target_audience' => $_POST['target_audience'],
                        'is_published' => isset($_POST['is_published']) ? 1 : 0,
                        'expires_at' => $_POST['expires_at'] ?: null
                    ]);
                    $message = 'Announcement updated successfully.';
                }
            } catch (Exception $e) {
                $error = 'Failed to update announcement: ' . $e->getMessage();
            }
        } elseif ($action === 'delete') {
            try {
                $ann = Announcement::find((int)$_POST['announcement_id']);
                if ($ann) {
                    $ann->delete();
                    $message = 'Announcement deleted.';
                }
            } catch (Exception $e) {
                $error = 'Failed to delete announcement.';
            }
        }
    }
}

$announcements = Announcement::getAll();

// Calculate statistics
$totalAnnouncements = count($announcements);
$published = count(array_filter($announcements, fn($a) => $a->is_published == 1));
$drafts = $totalAnnouncements - $published;
$urgent = count(array_filter($announcements, fn($a) => $a->priority === 'urgent'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | PayrollPro Admin</title>
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
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; animation: fadeInUp 0.6s ease-out 0.1s both; }
        .stat-card { padding: 20px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; transition: all .4s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(34,197,94,.05), transparent); animation: shimmer 3s infinite; }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(34,197,94,.3); box-shadow: 0 8px 24px rgba(34,197,94,.15); }
        .stat-card .label { color: #9fb4c7; font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: .05em; }
        .stat-card .value { font-size: 28px; font-weight: 800; color: #86efac; margin-top: 8px; }
        
        .announcements-list { display: flex; flex-direction: column; gap: 16px; animation: fadeInUp 0.6s ease-out 0.2s both; }
        .announcement-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 24px; transition: all .3s; position: relative; overflow: hidden; }
        .announcement-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #22c55e, #16a34a); transform: scaleY(0); transition: transform .3s; }
        .announcement-card:hover { border-color: rgba(34,197,94,.3); transform: translateX(4px); }
        .announcement-card:hover::before { transform: scaleY(1); }
        .announcement-card.urgent::before { background: linear-gradient(180deg, #ef4444, #dc2626); transform: scaleY(1); }
        .announcement-card.high::before { background: linear-gradient(180deg, #f59e0b, #d97706); transform: scaleY(1); }
        
        .announcement-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 16px; }
        .announcement-title { font-size: 18px; font-weight: 700; color: #e6edf5; }
        .announcement-meta { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        
        .priority-badge { padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid; }
        .priority-badge.urgent { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }
        .priority-badge.high { background: rgba(251,191,36,.15); color: #fde047; border-color: rgba(251,191,36,.3); }
        .priority-badge.normal { background: rgba(59,130,246,.15); color: #93c5fd; border-color: rgba(59,130,246,.3); }
        .priority-badge.low { background: rgba(100,116,139,.15); color: #cbd5e1; border-color: rgba(100,116,139,.3); }
        
        .audience-badge { padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; background: rgba(139,92,246,.15); color: #c4b5fd; border: 1px solid rgba(139,92,246,.3); }
        .status-badge { padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .status-badge.published { background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        .status-badge.draft { background: rgba(100,116,139,.15); color: #cbd5e1; border: 1px solid rgba(100,116,139,.3); }
        
        .announcement-content { color: #9fb4c7; line-height: 1.7; margin-bottom: 16px; }
        .announcement-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.06); }
        .announcement-author { color: #64748b; font-size: 13px; }
        
        .btn-edit { padding: 8px 14px; background: rgba(251,191,36,.15); color: #fde047; border: 1px solid rgba(251,191,36,.3); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all .3s; margin-right: 8px; }
        .btn-edit:hover { background: rgba(251,191,36,.25); transform: translateY(-1px); }
        .btn-delete { padding: 8px 14px; background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.3); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all .3s; }
        .btn-delete:hover { background: rgba(239,68,68,.25); transform: translateY(-1px); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal.active { display: flex; animation: fadeIn 0.3s ease-out; }
        .modal-content { background: linear-gradient(135deg, #1a2332, #151e2b); border: 1px solid rgba(34,197,94,.2); border-radius: 20px; padding: 32px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.5); animation: fadeInUp 0.3s ease-out; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .modal-header h2 { font-size: 22px; font-weight: 800; color: #86efac; }
        .modal-close { background: rgba(255,255,255,.05); border: none; color: #9fb4c7; font-size: 24px; cursor: pointer; width: 36px; height: 36px; border-radius: 50%; transition: all .3s; }
        .modal-close:hover { background: rgba(239,68,68,.15); color: #fca5a5; transform: rotate(90deg); }
        
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-size: 13px; color: #9fb4c7; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 16px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; color: #e6edf5; font-size: 14px; font-weight: 500; transition: all .3s; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: rgba(34,197,94,.5); background: rgba(255,255,255,.08); box-shadow: 0 0 0 3px rgba(34,197,94,.1); }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 10px; }
        .checkbox-label { display: flex; align-items: center; color: #e6edf5; font-weight: 500; text-transform: none; }
        
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all .3s; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        
        .empty-state { padding: 80px 20px; text-align: center; color: #9fb4c7; animation: fadeInUp 0.6s ease-out 0.3s both; }
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
                    <a class="nav-link" href="allowances.php"><span>➕</span> Allowances</a>
                    <a class="nav-link" href="deductions.php"><span>➖</span> Deductions</a>
                    <a class="nav-link" href="bonuses.php"><span>🎁</span> Bonuses</a>
                    <a class="nav-link" href="users.php"><span>🔐</span> Users</a>
                    <a class="nav-link active" href="announcement.php"><span>📢</span> Announcements</a>
                    <a class="nav-link" href="leaves.php"><span>📅</span> Leaves</a>
                    <a class="nav-link" href="../../logout.php"><span>🚪</span> Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <div>
                        <h1>📢 Announcements</h1>
                        <p class="lede">Create and manage company announcements</p>
                    </div>
                    <button class="btn-add" onclick="openModal()">+ New Announcement</button>
                </div>
                
                <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="label">📋 Total</div>
                        <div class="value"><?= $totalAnnouncements ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">✅ Published</div>
                        <div class="value"><?= $published ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">📝 Drafts</div>
                        <div class="value"><?= $drafts ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">🚨 Urgent</div>
                        <div class="value"><?= $urgent ?></div>
                    </div>
                </div>
                
                <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📢</div>
                    <p>No announcements yet. Click "New Announcement" to create one.</p>
                </div>
                <?php else: ?>
                <div class="announcements-list">
                    <?php foreach ($announcements as $ann): ?>
                    <article class="announcement-card <?= htmlspecialchars($ann->priority) ?>">
                        <div class="announcement-header">
                            <h3 class="announcement-title"><?= htmlspecialchars($ann->title) ?></h3>
                            <div class="announcement-meta">
                                <span class="priority-badge <?= htmlspecialchars($ann->priority) ?>"><?= htmlspecialchars($ann->priority) ?></span>
                                <span class="audience-badge"><?= htmlspecialchars(ucfirst($ann->target_audience)) ?></span>
                                <span class="status-badge <?= $ann->is_published ? 'published' : 'draft' ?>">
                                    <?= $ann->is_published ? 'Published' : 'Draft' ?>
                                </span>
                            </div>
                        </div>
                        <p class="announcement-content"><?= nl2br(htmlspecialchars($ann->content)) ?></p>
                        <div class="announcement-footer">
                            <span class="announcement-author">
                                By <?= htmlspecialchars($ann->author_name) ?> • 
                                <?= date('M d, Y', strtotime($ann->created_at)) ?>
                                <?php if ($ann->expires_at): ?>
                                    • Expires: <?= date('M d, Y', strtotime($ann->expires_at)) ?>
                                <?php endif; ?>
                            </span>
                            <div>
                                <button type="button" class="btn-edit" onclick='openEditModal(<?= json_encode([
                                    "id" => $ann->id,
                                    "title" => $ann->title,
                                    "content" => $ann->content,
                                    "priority" => $ann->priority,
                                    "target_audience" => $ann->target_audience,
                                    "is_published" => $ann->is_published,
                                    "expires_at" => $ann->expires_at
                                ]) ?>)'>Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                                    <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?= $ann->id ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <div class="modal" id="announcementModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Announcement</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Holiday Schedule 2025">
                </div>
                
                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" required placeholder="Write your announcement here..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Priority *</label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Target Audience *</label>
                    <select name="target_audience" required>
                        <option value="all" selected>All Users</option>
                        <option value="managers">Managers Only</option>
                        <option value="employees">Employees Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" checked>
                        Publish immediately
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">Create Announcement</button>
            </form>
        </div>
    </div>
    
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Announcement</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?= AuthMiddleware::csrfToken() ?>">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                
                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" id="edit_content" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Priority *</label>
                    <select name="priority" id="edit_priority" required>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Target Audience *</label>
                    <select name="target_audience" id="edit_target_audience" required>
                        <option value="all">All Users</option>
                        <option value="managers">Managers Only</option>
                        <option value="employees">Employees Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" id="edit_expires_at">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" id="edit_is_published">
                        Published
                    </label>
                </div>
                
                <button type="submit" class="btn-submit">Update Announcement</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() { 
            document.getElementById('announcementModal').classList.add('active'); 
        }
        
        function closeModal() { 
            document.getElementById('announcementModal').classList.remove('active'); 
        }
        
        function openEditModal(data) {
            document.getElementById('edit_announcement_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_content').value = data.content;
            document.getElementById('edit_priority').value = data.priority;
            document.getElementById('edit_target_audience').value = data.target_audience;
            document.getElementById('edit_is_published').checked = data.is_published == 1;
            
            // Format expires_at for datetime-local input
            if (data.expires_at) {
                const date = new Date(data.expires_at);
                const formatted = date.getFullYear() + '-' + 
                    String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(date.getDate()).padStart(2, '0') + 'T' + 
                    String(date.getHours()).padStart(2, '0') + ':' + 
                    String(date.getMinutes()).padStart(2, '0');
                document.getElementById('edit_expires_at').value = formatted;
            } else {
                document.getElementById('edit_expires_at').value = '';
            }
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() { 
            document.getElementById('editModal').classList.remove('active'); 
        }
        
        document.getElementById('announcementModal').addEventListener('click', function(e) { 
            if (e.target === this) closeModal(); 
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) { 
            if (e.target === this) closeEditModal(); 
        });
    </script>
</body>
</html>
