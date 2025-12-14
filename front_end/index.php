<?php
// PayrollPro - Modern Landing Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayrollPro - Modern Payroll Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #030712;
            --bg-secondary: #0f172a;
            --bg-card: rgba(15, 23, 42, 0.6);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-green: #22c55e;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-cyan: #06b6d4;
            --gradient-green: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --gradient-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg-primary); color: var(--text-primary); line-height: 1.6; overflow-x: hidden; }
        
        /* Animated Background */
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .bg-animation::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.12) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%), radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.08) 0%, transparent 40%); animation: bgMove 20s ease-in-out infinite; }
        @keyframes bgMove { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 33% { transform: translate(2%, 2%) rotate(1deg); } 66% { transform: translate(-1%, 1%) rotate(-1deg); } }
        .grid-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 60px 60px; z-index: -1; }
    </style>
</head>
<body>
<div class="bg-animation"></div>
<div class="grid-overlay"></div>

<style>
/* Navigation */
.navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 16px 0; transition: all 0.3s ease; }
.navbar.scrolled { background: rgba(3, 7, 18, 0.9); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border); }
.nav-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; }
.logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.logo-icon { width: 44px; height: 44px; background: var(--gradient-green); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3); }
.logo-text { font-size: 22px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }
.logo-text span { background: var(--gradient-green); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.nav-links { display: flex; align-items: center; gap: 4px; }
.nav-link { color: var(--text-secondary); text-decoration: none; padding: 10px 18px; font-weight: 500; font-size: 15px; border-radius: 10px; transition: all 0.3s; margin: 0 2px; }
.nav-link:hover { color: var(--text-primary); background: var(--glass); }
.nav-cta { background: var(--gradient-green); color: #000; padding: 12px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 15px; transition: all 0.3s; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3); margin-left: 8px; }
.nav-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4); }

/* Hero Section */
.hero { min-height: 100vh; display: flex; align-items: center; padding: 140px 40px 100px; position: relative; }
.hero-container { max-width: 1400px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
.hero-content { position: relative; z-index: 2; }
.hero-badge { display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 100px; margin-bottom: 28px; }
.badge-dot { width: 8px; height: 8px; background: var(--accent-green); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.2); } }
.badge-text { font-size: 14px; font-weight: 600; color: #86efac; letter-spacing: 0.5px; }
.hero-title { font-size: clamp(48px, 5.5vw, 72px); font-weight: 900; line-height: 1.05; margin-bottom: 28px; letter-spacing: -2px; }
.hero-title .gradient { background: linear-gradient(135deg, #22c55e 0%, #3b82f6 50%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-size: 200% auto; animation: textGradient 5s ease infinite; }
@keyframes textGradient { 0%, 100% { background-position: 0% center; } 50% { background-position: 100% center; } }
.hero-desc { font-size: 20px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 40px; max-width: 540px; }
.hero-buttons { display: flex; gap: 16px; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: 10px; padding: 18px 36px; border-radius: 14px; font-weight: 700; font-size: 16px; text-decoration: none; transition: all 0.3s ease; cursor: pointer; border: none; }
.btn-primary { background: var(--gradient-green); color: #000; box-shadow: 0 8px 32px rgba(34, 197, 94, 0.35); }
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(34, 197, 94, 0.45); }
.btn-secondary { background: var(--glass); color: var(--text-primary); border: 1px solid var(--glass-border); backdrop-filter: blur(10px); }
.btn-secondary:hover { background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.15); }
.btn-icon { font-size: 18px; }
</style>

<style>
/* Hero Visual */
.hero-visual { position: relative; }
.dashboard-mockup { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 24px; overflow: hidden; box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) inset; transform: perspective(1000px) rotateY(-5deg) rotateX(2deg); transition: transform 0.5s ease; }
.dashboard-mockup:hover { transform: perspective(1000px) rotateY(0deg) rotateX(0deg); }
.mockup-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; background: rgba(0, 0, 0, 0.3); border-bottom: 1px solid var(--glass-border); }
.mockup-dots { display: flex; gap: 8px; }
.mockup-dots span { width: 12px; height: 12px; border-radius: 50%; }
.mockup-dots span:nth-child(1) { background: #ef4444; }
.mockup-dots span:nth-child(2) { background: #fbbf24; }
.mockup-dots span:nth-child(3) { background: #22c55e; }
.mockup-title { font-size: 13px; color: var(--text-secondary); margin-left: 12px; }
.mockup-content { padding: 24px; }
.mockup-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.mockup-stat { background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.stat-icon.green { background: rgba(34, 197, 94, 0.15); }
.stat-icon.blue { background: rgba(59, 130, 246, 0.15); }
.stat-icon.purple { background: rgba(139, 92, 246, 0.15); }
.stat-icon.yellow { background: rgba(251, 191, 36, 0.15); }
.stat-label { font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; }
.stat-value { font-size: 24px; font-weight: 800; }
.floating-card { position: absolute; background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 16px; padding: 16px 20px; backdrop-filter: blur(20px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); animation: float 6s ease-in-out infinite; }
.floating-card.card-1 { top: 10%; right: -30px; animation-delay: 0s; }
.floating-card.card-2 { bottom: 20%; left: -40px; animation-delay: 2s; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
.floating-card .fc-icon { font-size: 24px; margin-bottom: 8px; }
.floating-card .fc-value { font-size: 20px; font-weight: 800; }
.floating-card .fc-label { font-size: 12px; color: var(--text-secondary); }

/* Trusted By */
.trusted { padding: 60px 40px; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); background: rgba(0, 0, 0, 0.2); }
.trusted-container { max-width: 1400px; margin: 0 auto; text-align: center; }
.trusted-label { font-size: 13px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 2px; font-weight: 600; margin-bottom: 32px; }
.trusted-logos { display: flex; justify-content: center; align-items: center; gap: 60px; flex-wrap: wrap; opacity: 0.5; }
.trusted-logo { font-size: 24px; font-weight: 800; color: var(--text-secondary); }
</style>

<style>
/* Features Section */
.features { padding: 140px 40px; position: relative; }
.features-container { max-width: 1400px; margin: 0 auto; }
.section-header { text-align: center; margin-bottom: 80px; }
.section-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 100px; font-size: 13px; font-weight: 600; color: #93c5fd; margin-bottom: 20px; }
.section-title { font-size: clamp(36px, 4vw, 52px); font-weight: 900; margin-bottom: 20px; letter-spacing: -1px; }
.section-desc { font-size: 18px; color: var(--text-secondary); max-width: 600px; margin: 0 auto; }
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.feature-card { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 24px; padding: 36px; transition: all 0.4s ease; position: relative; overflow: hidden; }
.feature-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--accent-green), transparent); opacity: 0; transition: opacity 0.4s; }
.feature-card:hover { transform: translateY(-8px); border-color: rgba(34, 197, 94, 0.3); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); }
.feature-card:hover::before { opacity: 1; }
.feature-icon { width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin-bottom: 24px; }
.feature-icon.green { background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.05)); }
.feature-icon.blue { background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.05)); }
.feature-icon.purple { background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(139, 92, 246, 0.05)); }
.feature-icon.yellow { background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(251, 191, 36, 0.05)); }
.feature-icon.cyan { background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(6, 182, 212, 0.05)); }
.feature-icon.red { background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.05)); }
.feature-title { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
.feature-desc { color: var(--text-secondary); font-size: 15px; line-height: 1.7; }

/* Roles Section */
.roles { padding: 140px 40px; background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); }
.roles-container { max-width: 1400px; margin: 0 auto; }
.roles-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
.role-card { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 28px; padding: 48px 36px; text-align: center; transition: all 0.4s ease; position: relative; overflow: hidden; }
.role-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
.role-card.admin::after { background: var(--gradient-green); }
.role-card.manager::after { background: var(--gradient-purple); }
.role-card.employee::after { background: linear-gradient(135deg, #06b6d4, #0891b2); }
.role-card:hover { transform: translateY(-10px); box-shadow: 0 40px 80px rgba(0, 0, 0, 0.3); }
.role-icon { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto 28px; }
.role-card.admin .role-icon { background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.05)); }
.role-card.manager .role-icon { background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(139, 92, 246, 0.05)); }
.role-card.employee .role-icon { background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(6, 182, 212, 0.05)); }
.role-title { font-size: 26px; font-weight: 800; margin-bottom: 12px; }
.role-subtitle { color: var(--text-secondary); margin-bottom: 28px; }
.role-features { list-style: none; text-align: left; }
.role-features li { padding: 12px 0; color: var(--text-secondary); font-size: 15px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--glass-border); }
.role-features li:last-child { border-bottom: none; }
.role-features li::before { content: '✓'; color: var(--accent-green); font-weight: 700; width: 24px; height: 24px; background: rgba(34, 197, 94, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; }
</style>

<style>
/* Stats Section */
.stats { padding: 100px 40px; background: rgba(34, 197, 94, 0.03); border-top: 1px solid rgba(34, 197, 94, 0.1); border-bottom: 1px solid rgba(34, 197, 94, 0.1); }
.stats-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; }
.stats-item { text-align: center; }
.stats-number { font-size: 56px; font-weight: 900; background: linear-gradient(135deg, #22c55e, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; margin-bottom: 12px; }
.stats-label { color: var(--text-secondary); font-size: 16px; font-weight: 500; }

/* CTA Section */
.cta { padding: 160px 40px; position: relative; overflow: hidden; }
.cta::before { content: ''; position: absolute; top: 50%; left: 50%; width: 800px; height: 800px; background: radial-gradient(circle, rgba(34, 197, 94, 0.15) 0%, transparent 70%); transform: translate(-50%, -50%); }
.cta-container { max-width: 800px; margin: 0 auto; text-align: center; position: relative; z-index: 2; }
.cta-box { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 32px; padding: 80px 60px; backdrop-filter: blur(20px); }
.cta-title { font-size: clamp(32px, 4vw, 48px); font-weight: 900; margin-bottom: 20px; letter-spacing: -1px; }
.cta-desc { font-size: 18px; color: var(--text-secondary); margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto; }
.cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

/* Footer */
.footer { padding: 80px 40px 40px; border-top: 1px solid var(--glass-border); }
.footer-container { max-width: 1400px; margin: 0 auto; }
.footer-top { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 60px; margin-bottom: 60px; }
.footer-brand .logo { margin-bottom: 20px; }
.footer-brand p { color: var(--text-secondary); font-size: 15px; max-width: 300px; line-height: 1.7; }
.footer-col h4 { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 20px; }
.footer-col a { display: block; color: var(--text-primary); text-decoration: none; font-size: 15px; padding: 8px 0; transition: color 0.3s; }
.footer-col a:hover { color: var(--accent-green); }
.footer-bottom { padding-top: 40px; border-top: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; }
.footer-bottom p { color: var(--text-secondary); font-size: 14px; }
.footer-social { display: flex; gap: 16px; }
.footer-social a { width: 40px; height: 40px; border-radius: 10px; background: var(--glass); border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); text-decoration: none; transition: all 0.3s; }
.footer-social a:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }

/* Responsive */
@media (max-width: 1024px) { 
    .hero-container, .features-grid, .roles-grid { grid-template-columns: 1fr; } 
    .hero-visual { display: none; } 
    .stats-container { grid-template-columns: repeat(2, 1fr); } 
    .footer-top { grid-template-columns: 1fr 1fr; } 
    .nav-links { gap: 2px; }
    .nav-link { padding: 8px 12px; font-size: 14px; }
}
@media (max-width: 768px) { 
    .nav-links { display: none; } 
    .hero { padding: 120px 20px 80px; } 
    .hero-title { font-size: 36px; } 
    .features, .roles, .cta { padding: 80px 20px; } 
    .features-grid { gap: 16px; } 
    .stats-container { grid-template-columns: 1fr 1fr; gap: 30px; } 
    .stats-number { font-size: 40px; } 
    .cta-box { padding: 50px 30px; } 
    .footer-top { grid-template-columns: 1fr; gap: 40px; } 
    .footer-bottom { flex-direction: column; gap: 20px; text-align: center; } 
}
</style>

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="#" class="logo">
            <div class="logo-icon">💰</div>
            <div class="logo-text">Payroll<span>Pro</span></div>
        </a>
        <div class="nav-links">
            <a href="#features" class="nav-link">Features</a>
            <a href="#roles" class="nav-link">Roles</a>
            <a href="front_end/support.php" class="nav-link">Support</a>
            <a href="front_end/faqs.html" class="nav-link">FAQs</a>
            <a href="front_end/announcement.php" class="nav-link">News</a>
            <a href="login.php" class="nav-cta">Login →</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                <span class="badge-text">Next-Gen Payroll Platform</span>
            </div>
            <h1 class="hero-title">
                Simplify Your<br><span class="gradient">Payroll Management</span>
            </h1>
            <p class="hero-desc">
                A powerful, secure, and beautifully designed payroll system with role-based access control, automated calculations, and real-time analytics.
            </p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    <span>Get Started Free</span>
                    <span class="btn-icon">→</span>
                </a>
                <a href="#features" class="btn btn-secondary">
                    <span class="btn-icon">▶</span>
                    <span>Watch Demo</span>
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="dashboard-mockup">
                <div class="mockup-header">
                    <div class="mockup-dots"><span></span><span></span><span></span></div>
                    <span class="mockup-title">PayrollPro Dashboard</span>
                </div>
                <div class="mockup-content">
                    <div class="mockup-stats">
                        <div class="mockup-stat">
                            <div class="stat-icon green">👥</div>
                            <div><div class="stat-label">Total Employees</div><div class="stat-value">248</div></div>
                        </div>
                        <div class="mockup-stat">
                            <div class="stat-icon blue">✓</div>
                            <div><div class="stat-label">Present Today</div><div class="stat-value">231</div></div>
                        </div>
                        <div class="mockup-stat">
                            <div class="stat-icon yellow">💰</div>
                            <div><div class="stat-label">Monthly Payroll</div><div class="stat-value">$847K</div></div>
                        </div>
                        <div class="mockup-stat">
                            <div class="stat-icon purple">📊</div>
                            <div><div class="stat-label">Departments</div><div class="stat-value">12</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="floating-card card-1">
                <div class="fc-icon">📈</div>
                <div class="fc-value">+24%</div>
                <div class="fc-label">Efficiency</div>
            </div>
            <div class="floating-card card-2">
                <div class="fc-icon">⚡</div>
                <div class="fc-value">99.9%</div>
                <div class="fc-label">Uptime</div>
            </div>
        </div>
    </div>
</section>

<!-- Trusted By -->
<section class="trusted">
    <div class="trusted-container">
        <div class="trusted-label">Trusted by leading companies worldwide</div>
        <div class="trusted-logos">
            <div class="trusted-logo">TechCorp</div>
            <div class="trusted-logo">InnovateCo</div>
            <div class="trusted-logo">GlobalTech</div>
            <div class="trusted-logo">FutureLabs</div>
            <div class="trusted-logo">NextGen</div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <div class="features-container">
        <div class="section-header">
            <div class="section-badge">✨ Features</div>
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-desc">Powerful tools designed to make payroll processing effortless, accurate, and secure.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon green">💰</div>
                <h3 class="feature-title">Automated Payroll</h3>
                <p class="feature-desc">Calculate salaries automatically with allowances, deductions, bonuses, and configurable tax brackets.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon blue">👥</div>
                <h3 class="feature-title">Employee Management</h3>
                <p class="feature-desc">Manage employee profiles, departments, and organizational hierarchy with comprehensive tools.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon purple">📅</div>
                <h3 class="feature-title">Leave Management</h3>
                <p class="feature-desc">Handle leave requests, approvals, and track employee time-off with automated workflows.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon yellow">📋</div>
                <h3 class="feature-title">Attendance Tracking</h3>
                <p class="feature-desc">Real-time clock in/out system with daily reports, absence management, and analytics.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon cyan">🔐</div>
                <h3 class="feature-title">Role-Based Access</h3>
                <p class="feature-desc">Three-level hierarchy with Super Admin, Manager, and Employee permission levels.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon red">🛡️</div>
                <h3 class="feature-title">Enterprise Security</h3>
                <p class="feature-desc">BCrypt encryption, CSRF protection, prepared statements, and audit logging.</p>
            </div>
        </div>
    </div>
</section>

<!-- Roles Section -->
<section class="roles" id="roles">
    <div class="roles-container">
        <div class="section-header">
            <div class="section-badge">👥 Access Levels</div>
            <h2 class="section-title">Role-Based Access Control</h2>
            <p class="section-desc">Different access levels tailored for different responsibilities in your organization.</p>
        </div>
        <div class="roles-grid">
            <div class="role-card admin">
                <div class="role-icon">👑</div>
                <h3 class="role-title">Super Admin</h3>
                <p class="role-subtitle">Full system control & oversight</p>
                <ul class="role-features">
                    <li>Manage all employees</li>
                    <li>Run payroll processing</li>
                    <li>Create departments</li>
                    <li>Manage user accounts</li>
                    <li>View all reports & analytics</li>
                </ul>
            </div>
            <div class="role-card manager">
                <div class="role-icon">👔</div>
                <h3 class="role-title">Manager</h3>
                <p class="role-subtitle">Team management capabilities</p>
                <ul class="role-features">
                    <li>View team members</li>
                    <li>Approve leave requests</li>
                    <li>Track team attendance</li>
                    <li>View team reports</li>
                    <li>Manage team schedules</li>
                </ul>
            </div>
            <div class="role-card employee">
                <div class="role-icon">👤</div>
                <h3 class="role-title">Employee</h3>
                <p class="role-subtitle">Self-service portal access</p>
                <ul class="role-features">
                    <li>View payslips</li>
                    <li>Request leaves</li>
                    <li>Clock in/out</li>
                    <li>Update profile</li>
                    <li>View announcements</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats">
    <div class="stats-container">
        <div class="stats-item">
            <div class="stats-number">10K+</div>
            <div class="stats-label">Employees Managed</div>
        </div>
        <div class="stats-item">
            <div class="stats-number">500+</div>
            <div class="stats-label">Companies Trust Us</div>
        </div>
        <div class="stats-item">
            <div class="stats-number">99.9%</div>
            <div class="stats-label">Uptime Guaranteed</div>
        </div>
        <div class="stats-item">
            <div class="stats-number">24/7</div>
            <div class="stats-label">Support Available</div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-container">
        <div class="cta-box">
            <h2 class="cta-title">Ready to Transform Your Payroll?</h2>
            <p class="cta-desc">Join thousands of companies that trust PayrollPro for their payroll management needs.</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-primary">
                    <span>Start Free Trial</span>
                    <span class="btn-icon">→</span>
                </a>
                <a href="front_end/support.php" class="btn btn-secondary">
                    <span>Contact Sales</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="#" class="logo">
                    <div class="logo-icon">💰</div>
                    <div class="logo-text">Payroll<span>Pro</span></div>
                </a>
                <p>Modern payroll management for modern businesses. Simplify your HR operations with our powerful platform.</p>
            </div>
            <div class="footer-col">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#roles">Roles</a>
                <a href="setup.php">Setup</a>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <a href="front_end/support.php">Support</a>
                <a href="front_end/faqs.html">FAQs</a>
                <a href="front_end/announcement.php">Announcements</a>
                <a href="README.md">Documentation</a>
            </div>
            <div class="footer-col">
                <h4>Access</h4>
                <a href="login.php">Login</a>
                <a href="login.php">Admin Portal</a>
                <a href="login.php">Employee Portal</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 PayrollPro. All rights reserved.</p>
            <div class="footer-social">
                <a href="#">𝕏</a>
                <a href="#">in</a>
                <a href="#">📧</a>
            </div>
        </div>
    </div>
</footer>

<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
