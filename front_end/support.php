<?php
/**
 * Support Page - Public Access
 */

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

// Check if user is logged in (optional - page is public)
$user = null;
$isLoggedIn = false;
try {
    if (AuthMiddleware::check()) {
        $user = AuthMiddleware::user();
        $isLoggedIn = true;
    }
} catch (Exception $e) {
    // Not logged in, that's fine
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In production, save to database or send email
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support | PayrollPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0b1320; color: #e6edf5; min-height: 100vh; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .back-btn { padding: 10px 20px; background: rgba(255,255,255,.1); color: #e6edf5; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all .2s; }
        .back-btn:hover { background: rgba(255,255,255,.15); }
        
        .support-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        
        .support-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 24px; text-align: center; }
        .support-card .icon { font-size: 32px; margin-bottom: 12px; }
        .support-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .support-card p { color: #64748b; font-size: 14px; line-height: 1.5; }
        
        .contact-form { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 32px; }
        .contact-form h2 { font-size: 20px; font-weight: 600; margin-bottom: 24px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #94a3b8; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #fff; font-size: 15px; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #22c55e; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group select option { background: #1a2332; }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #22c55e, #16a34a); border: none; border-radius: 8px; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(34,197,94,.3); }
        
        .success-msg { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; }
        
        .faq { margin-top: 40px; }
        .faq h2 { font-size: 20px; font-weight: 600; margin-bottom: 20px; }
        .faq-item { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 20px; margin-bottom: 12px; }
        .faq-item h4 { font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #e6edf5; }
        .faq-item p { color: #94a3b8; font-size: 14px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛟 Support Center</h1>
            <a href="javascript:history.back()" class="back-btn">← Back</a>
        </div>
        
        <div class="support-grid">
            <div class="support-card">
                <div class="icon">📧</div>
                <h3>Email Support</h3>
                <p>support@payrollpro.com</p>
            </div>
            <div class="support-card">
                <div class="icon">📞</div>
                <h3>Phone Support</h3>
                <p>+1 (555) 123-4567</p>
            </div>
            <div class="support-card">
                <div class="icon">💬</div>
                <h3>Live Chat</h3>
                <p>Available 9 AM - 5 PM</p>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Submit a Support Request</h2>
            
            <?php if ($submitted): ?>
                <div class="success-msg">
                    ✓ Your support request has been submitted. We'll get back to you within 24 hours.
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select a category</option>
                        <option value="payroll">Payroll Issues</option>
                        <option value="leave">Leave Management</option>
                        <option value="attendance">Attendance</option>
                        <option value="account">Account Access</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" placeholder="Brief description of your issue" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" placeholder="Please describe your issue in detail..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Request</button>
            </form>
        </div>
        
        <div class="faq">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-item">
                <h4>How do I reset my password?</h4>
                <p>Click on "Forgot Password" on the login page and enter your email address. You'll receive a reset link.</p>
            </div>
            <div class="faq-item">
                <h4>How do I apply for leave?</h4>
                <p>Go to the Leaves section in your dashboard, click "Apply for Leave", fill in the details and submit.</p>
            </div>
            <div class="faq-item">
                <h4>When is payroll processed?</h4>
                <p>Payroll is typically processed on the last working day of each month.</p>
            </div>
        </div>
    </div>
</body>
</html>
