<?php
/**
 * Login Page - Uses New Auth System
 */

require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';

// Redirect if already logged in
if (AuthMiddleware::check()) {
    $base = '/payroll-management-system';
    $redirect = match(AuthMiddleware::role()) {
        'super_admin' => $base . '/front_end/admin/dashboard.php',
        'manager' => $base . '/manager/dashboard.php',
        'employee' => $base . '/front_end/employee/dashboard.php',
        default => $base . '/login.php'
    };
    header("Location: $redirect");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $result = AuthController::login($email, $password);
    
    if ($result['success']) {
        header("Location: " . $result['redirect']);
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0b1320 0%, #1a2332 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 420px; }
        .logo { text-align: center; margin-bottom: 40px; }
        .logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logo p { color: #64748b; margin-top: 8px; }
        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
        }
        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .form-group input::placeholder { color: #475569; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s;
        }
        .forgot-link:hover { color: #10b981; }
        .demo-accounts {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .demo-accounts h4 {
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .demo-account {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #94a3b8;
            font-size: 0.8rem;
        }
        .demo-account span:first-child { color: #10b981; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>PayrollPro</h1>
            <p>Employee Management System</p>
        </div>
        
        <div class="login-card">
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <a href="forget_password.php" class="forgot-link">Forgot your password?</a>
            
            <div class="demo-accounts">
                <h4>Demo Accounts (password: password123)</h4>
                <div class="demo-account">
                    <span>Super Admin</span>
                    <span>admin@payrollpro.com</span>
                </div>
                <div class="demo-account">
                    <span>Manager</span>
                    <span>manager.eng@payrollpro.com</span>
                </div>
                <div class="demo-account">
                    <span>Employee</span>
                    <span>emp1@payrollpro.com</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
