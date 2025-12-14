<?php
/**
 * Add Announcements Table to Existing Database
 * Run this if you already have data and just want to add announcements
 */

$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'payroll_pro';
$DB_USER = 'root';
$DB_PASS = '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Announcements Table</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: radial-gradient(circle at 15% 20%, rgba(34,197,94,.15), transparent 35%), #0b1320; 
            color: #e6edf5; 
            padding: 40px 20px; 
            min-height: 100vh;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { 
            font-size: 36px; 
            font-weight: 800; 
            background: linear-gradient(135deg, #86efac, #22c55e); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            margin-bottom: 10px;
        }
        .card { 
            background: rgba(255,255,255,.03); 
            border: 1px solid rgba(255,255,255,.08); 
            border-radius: 16px; 
            padding: 24px; 
            margin: 20px 0; 
        }
        .success { background: rgba(34,197,94,.15); color: #86efac; border-color: rgba(34,197,94,.3); padding: 16px; border-radius: 12px; margin: 16px 0; }
        .error { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); padding: 16px; border-radius: 12px; margin: 16px 0; }
        .info { background: rgba(59,130,246,.15); color: #93c5fd; border-color: rgba(59,130,246,.3); padding: 16px; border-radius: 12px; margin: 16px 0; }
        .btn { 
            display: inline-block; 
            padding: 14px 28px; 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: #fff; 
            text-decoration: none; 
            border-radius: 12px; 
            font-weight: 700; 
            margin: 10px 10px 10px 0; 
            transition: all .3s;
            box-shadow: 0 4px 12px rgba(34,197,94,.3);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34,197,94,.4); }
        code { background: rgba(255,255,255,.1); padding: 2px 8px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📢 Add Announcements Table</h1>
            <p style="color: #9fb4c7;">Add announcements functionality to your existing database</p>
        </div>

<?php
try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo '<div class="card">';
    echo '<div class="success">✓ Connected to database: <strong>' . htmlspecialchars($DB_NAME) . '</strong></div>';
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'announcements'");
    if ($stmt->rowCount() > 0) {
        echo '<div class="info">ℹ️ Announcements table already exists!</div>';
        
        // Count existing announcements
        $count = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
        echo '<div class="info">📊 Current announcements: <strong>' . $count . '</strong></div>';
        
    } else {
        // Create announcements table
        $sql = "CREATE TABLE announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
            target_audience ENUM('all', 'employees', 'managers') NOT NULL DEFAULT 'all',
            created_by INT UNSIGNED NOT NULL,
            is_published TINYINT(1) DEFAULT 1,
            published_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_published (is_published, published_at),
            INDEX idx_target (target_audience),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo '<div class="success">✓ Announcements table created successfully!</div>';
        
        // Add sample announcements
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO announcements (title, content, priority, target_audience, created_by, is_published, published_at) VALUES
            ('Welcome to PayrollPro', 'Welcome to our new payroll management system! This platform will help streamline all payroll operations. Feel free to explore all the features and reach out if you have any questions.', 'normal', 'all', 1, 1, '$now'),
            ('Holiday Schedule 2025', 'Please note the upcoming holiday schedule for 2025. The office will be closed on December 25th and January 1st. Make sure to submit any pending requests before the holidays.', 'high', 'all', 1, 1, '$now'),
            ('System Maintenance Notice', 'The payroll system will undergo scheduled maintenance this weekend (Saturday 2 AM - 6 AM). Please complete any pending tasks before Friday 5 PM. The system will be unavailable during this time.', 'urgent', 'all', 1, 1, '$now')");
        
        echo '<div class="success">✓ Added 3 sample announcements!</div>';
    }
    
    echo '</div>';
    
    // Show next steps
    echo '<div class="card">';
    echo '<h2 style="color: #86efac; margin-bottom: 16px;">✅ Setup Complete!</h2>';
    echo '<p style="color: #9fb4c7; margin-bottom: 20px;">The announcements system is ready to use.</p>';
    echo '<div style="margin-top: 20px;">';
    echo '<a href="front_end/admin/announcement.php" class="btn">Go to Admin Announcements →</a>';
    echo '<a href="front_end/announcement.php" class="btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">View Announcements →</a>';
    echo '</div>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="card">';
    echo '<div class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="info" style="margin-top: 20px;">';
    echo '<p><strong>Common issues:</strong></p>';
    echo '<ul style="margin-left: 20px; margin-top: 10px; color: #9fb4c7;">';
    echo '<li>Make sure XAMPP MySQL is running</li>';
    echo '<li>Verify database <code>payroll_pro</code> exists</li>';
    echo '<li>Check that you have a <code>users</code> table with at least one admin user</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
}
?>

    </div>
</body>
</html>
