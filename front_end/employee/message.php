<?php
    include '../../connection.php';
    session_start();
    $id = $_SESSION['id'];
    $emp_id = $_SESSION['emp_id'];
    $username = $_SESSION['name'];

    if ($_SESSION['loggedin'] !== true) {
        header('location: ../../login.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PayrollPro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        .shell { min-height: 100vh; background: radial-gradient(circle at 15% 20%, rgba(59,130,246,.08), transparent 30%), #0b1320; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,.05); background: rgba(11,19,32,.8); }
        .brand { font-weight: 800; padding: 10px 14px; border-radius: 10px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
        .links a { color: #c9d7e6; text-decoration: none; padding: 8px 14px; font-weight: 600; }
        .user-mini { display: inline-flex; gap: 8px; align-items: center; color: #c9d7e6; font-weight: 600; padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #06b6d4; }
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: calc(100vh - 70px); }
        .sidenav { border-right: 1px solid rgba(255,255,255,.05); padding: 20px; }
        .user-card { display: flex; gap: 12px; align-items: center; padding: 16px; background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 14px; margin-bottom: 20px; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: #1a2332; border: 2px solid rgba(6,182,212,.3); }
        .user-card .label { font-size: 11px; color: #67e8f9; text-transform: uppercase; font-weight: 700; }
        .user-card .name { margin-top: 4px; font-weight: 700; }
        nav { display: flex; flex-direction: column; gap: 6px; }
        .nav-link { padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #c9d7e6; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover { background: rgba(255,255,255,.05); }
        .nav-link.active { background: rgba(6,182,212,.14); color: #67e8f9; }
        .main { padding: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .page-header .lede { color: #9fb4c7; margin-bottom: 28px; }
        .form-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 16px; padding: 28px; max-width: 600px; }
        .form-card h3 { font-size: 18px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #c9d7e6; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 14px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04); color: #e6edf5; font-size: 15px; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: rgba(6,182,212,.5); }
        .form-group textarea { min-height: 150px; resize: vertical; }
        .submit-btn { width: 100%; padding: 16px; border: none; border-radius: 12px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
        .submit-btn:hover { box-shadow: 0 10px 25px rgba(6,182,212,.3); }
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 12px; background: rgba(34,197,94,.9); color: #fff; font-weight: 600; }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">PayrollPro</div>
            <div class="links"><a href="../index.html">Home</a><a href="../support.php">Support</a></div>
            <div class="user-mini"><span class="dot"></span><span><?php echo ucfirst($username) ?></span></div>
        </header>
        <div class="layout">
            <aside class="sidenav">
                <div class="user-card">
                    <?php
                        $img = mysqli_query($con, "select picture from users where user_id = $id");
                        $row = mysqli_fetch_array($img);
                        echo !$row['picture'] ? '<img src="../images/user.png" class="avatar">' : '<img src="'.$row["picture"].'" class="avatar">';
                    ?>
                    <div><p class="label">Employee</p><p class="name"><?php echo ucfirst($username) ?></p></div>
                </div>
                <nav>
                    <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                    <a class="nav-link" href="profile.php">👤 Profile</a>
                    <a class="nav-link" href="payroll.php">💰 Payroll</a>
                    <a class="nav-link active" href="message.php">✉️ Messages</a>
                    <a class="nav-link" href="../../logout.php">🚪 Logout</a>
                </nav>
            </aside>
            <main class="main">
                <div class="page-header">
                    <h1>✉️ Send Message</h1>
                    <p class="lede">Contact HR or Admin for support</p>
                </div>
                <div class="form-card">
                    <h3>New Message</h3>
                    <form action="supportProc.php" method="post">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" name="name" value="<?php echo $username; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="your@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="What is this about?" required>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="description" placeholder="Describe your inquiry..." required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">📤 Send Message</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <?php if(isset($_GET['status']) && $_GET['status'] == 2): ?>
    <div class="toast" id="toast">✓ Message sent successfully!</div>
    <script>setTimeout(() => document.getElementById('toast').style.display = 'none', 3500);</script>
    <?php endif; ?>
</body>
</html>