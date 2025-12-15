<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"></p>
 

<h1 align="center">💰 PayrollPro</h1>
<p align="center"><strong>Modern Employee Payroll Management System</strong></p>
<p align="center">A secure, feature-rich payroll system with role-based access control, automated salary calculations, and a stunning dark-themed UI built with PHP and MySQL.</p>

<table style="margin: auto; width: 75%; text-align: center; 
              font-family: Arial, sans-serif; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
  
  <tr style="background-color: #a29f9fff; color:black;">
    <th style="border: 2px solid #8b8282ff; padding: 12px; text-align:center">No.</th>
    <th style="border: 2px solid #8d8a8aff; padding: 12px; text-align:center">Member Name</th>
    <th style="border: 2px solid #727070ff; padding: 12px;" text-align:center>ID No</th>
  </tr>

  <tr style="background-color: #414141ff;">
    <td style="border: 1px solid #444; padding: 10px;">1</td>
    <td style="border: 1px solid #444; padding: 10px;">🌸 Feven Aynalem</td>
    <td style="border: 1px solid #444; padding: 10px;">1259/16</td>
  </tr>

  <tr>
    <td style="border: 1px solid #444; padding: 10px;">2</td>
    <td style="border: 1px solid #444; padding: 10px;">✨ Bestelot Awraris</td>
    <td style="border: 1px solid #444; padding: 10px;">0869/16</td>
  </tr>

  <tr style="background-color: #414141ff">
    <td style="border: 1px solid #444; padding: 10px;">3</td>
    <td style="border: 1px solid #444; padding: 10px;">📘 Tinsae Birhanu</td>
    <td style="border: 1px solid #444; padding: 10px;">2574/16</td>
  </tr>

  <tr>
    <td style="border: 1px solid #444; padding: 10px;">4</td>
    <td style="border: 1px solid #444; padding: 10px;">💻 Orniya Zeidan</td>
    <td style="border: 1px solid #444; padding: 10px;">2246/16</td>
  </tr>

  <tr style="background-color: #414141ff">
    <td style="border: 1px solid #444; padding: 10px;">5</td>
    <td style="border: 1px solid #444; padding: 10px;">🌼 Yordanos Tesfaye</td>
    <td style="border: 1px solid #444; padding: 10px;">2761/16</td>
  </tr>

  <tr>
    <td style="border: 1px solid #444; padding: 10px;">6</td>
    <td style="border: 1px solid #444; padding: 10px;">🚀 Nuhamin Atomsa</td>
    <td style="border: 1px solid #444; padding: 10px;">1378/16</td>
  </tr>

</table>


---

## ✨ Features

### 🔐 Security First
- **BCrypt Password Hashing** - Industry-standard password encryption
- **CSRF Protection** - Token-based form security
- **PDO Prepared Statements** - SQL injection prevention
- **Session Management** - Secure session handling with regeneration
- **Account Lockout** - Auto-lock after 5 failed login attempts

### 👥 Role-Based Access Control
| Role | Access Level | Color Theme |
|------|--------------|-------------|
| **Super Admin** | Full system access | 🟢 Green |
| **Manager** | Team management | 🟣 Purple |
| **Employee** | Self-service portal | 🔵 Cyan |

### 💵 Payroll Management
- **Automated salary calculations** with progressive tax brackets
- **Allowances Management** - Assign housing, transport, meal, communication, medical, education allowances
- **Deductions Management** - Manage pension, insurance, loans, tax adjustments, union dues
- **Bonuses Management** - Award one-time performance bonuses
- **Payroll Runs** - Create, calculate, approve, and process payroll batches
- **Payslip Generation** - Automated HTML payslip generation
- **Manager Payroll Control** - Managers can assign allowances, deductions, and bonuses to their team

### 📊 Additional Features
- **Leave Management** - Request, approve, track leaves
- **Attendance Tracking** - Clock in/out system
- **Department Management** - Organize employees
- **Announcements** - Company-wide notifications
- **Support Center** - Help desk system

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP / WAMP / LAMP stack
- Web browser

### Installation

**1. Clone or download to your web server**
```bash
cd c:\xampp\htdocs\
git clone https://github.com/Shal-coder/PHP--EPMS.git epms

```

**2. Create the database**
```sql
CREATE DATABASE payroll_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. Run one-click installation**

Visit in your browser:
```
http://localhost/epms/install.php
```
This will automatically:
- Create all 13 database tables with proper relationships
- Insert sample departments (4)
- Create demo user accounts (6)
- Set up employees with salaries (5)
- Add sample allowances, deductions, and attendance records
- Verify database integrity

**To reset database:** Add `?reset=1` to the URL

**4. Start using the system**
```http://localhost/epms/index.php
```

---

## 🔑 Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| 👑 Super Admin | `admin@payrollpro.com` | `password123` |
| 👔 Manager (Engineering) | `manager.eng@payrollpro.com` | `password123` |
| 👔 Manager (HR) | `manager.hr@payrollpro.com` | `password123` |
| 👤 Employee | `emp1@payrollpro.com` | `password123` |
| 👤 Employee | `emp2@payrollpro.com` | `password123` |
| 👤 Employee | `emp3@payrollpro.com` | `password123` |

---

## 📁 Project Structure

```
epms/
│
├── 📂 app/                          # Application core
│   ├── 📂 Config/
│   │   ├── Database.php             # PDO singleton connection
│   │   └── env.php                  # Environment loader
│   │
│   ├── 📂 Controllers/
│   │   ├── AuthController.php       # Login/logout/password
│   │   ├── EmployeeController.php   # Employee CRUD
│   │   ├── LeaveController.php      # Leave management
│   │   └── PayrollController.php    # Payroll processing
│   │
│   ├── 📂 Middleware/
│   │   ├── AuthMiddleware.php       # Session & CSRF
│   │   └── RoleMiddleware.php       # Role-based access
│   │
│   ├── 📂 Models/
│   │   ├── User.php                 # User authentication
│   │   ├── Employee.php             # Employee profiles
│   │   ├── Department.php           # Departments
│   │   ├── Attendance.php           # Clock in/out
│   │   ├── Leave.php                # Leave requests
│   │   └── Payroll.php              # Payroll runs
|   |   ├──Announcement.php          # company announcements 
│   │
│   ├── 📂 Payroll/
│   │   └── PayrollCalculator.php    # Salary calculations
│   │
│   └── 📂 Services/
│       └── PayslipGenerator.php     # HTML payslip output
│
├── 📂 database/
│   ├── 📂 migrations/               # 13 SQL migration files
│   │   ├── 001_create_departments_table.sql
│   │   ├── 002_create_users_table.sql
│   │   ├── 003_create_employees_table.sql
│   │   ├── 004_create_allowances_table.sql
│   │   ├── 005_create_deductions_table.sql
│   │   ├── 006_create_bonuses_table.sql
│   │   ├── 007_create_attendance_table.sql
│   │   ├── 008_create_leaves_table.sql
│   │   ├── 009_create_payroll_runs_table.sql
│   │   ├── 010_create_payroll_items_table.sql
│   │   ├── 011_create_settings_table.sql
│   │   ├── 012_create_audit_logs_table.sql
│   │   └── 013_create_sessions_table.sql
|   |   └── 014_create_announcement_table.sql
│   └── seed.sql                     # Sample data
│
├── 📂 front_end/
│   ├── 📂 admin/                    # Super Admin pages
│   │   ├── dashboard.php
│   │   ├── employees.php
│   │   ├── addEmp.php
│   │   ├── editEmp.php
│   │   ├── viewEmployee.php
│   │   ├── users.php
│   │   ├── addUser.php
│   │   ├── editUser.php
│   │   ├── departments.php
│   │   ├── payrolls.php
│   │   ├── viewPayroll.php
│   │   ├── allowances.php           # 🆕 Manage employee allowances
│   │   ├── deductions.php           # 🆕 Manage employee deductions
│   │   ├── bonuses.php              # 🆕 Award employee bonuses
│   │   └── leaves.php
│   │
│   ├── 📂 employee/                 # Employee self-service
│   │   ├── dashboard.php
│   │   ├── profile.php
│   │   ├── payroll.php
│   │   ├── leaves.php
│   │   ├── attendance.php
│   │   └── message.php
│   │
│   ├── index.html                   # Landing page
│   ├── announcement.php             # Announcements
│   └── support.php                  # Support center
│
├── 📂 manager/                      # Manager pages
│   ├── dashboard.php
│   ├── employees.php
│   ├── payroll.php                  # 🆕 Manage team payroll (allowances, deductions, bonuses)
│   ├── leaves.php
│   └── attendance.php
│
├── 📂 public/
│   └── 📂 errors/
│       ├── 403.php                  # Forbidden
│       └── 404.php                  # Not found
│
├── .env                             # Environment config
├── .env.example                     # Config template
├── install.php                      # 🆕 One-click installation
├── login.php                        # Login page
├── logout.php                       # Logout handler
└── README.md                        # This file
```

---

## 🎨 UI Design

### Color Scheme
| Element | Color | Hex |
|---------|-------|-----|
| Background | Dark Navy | `#0b1320` |
| Card Background | Dark Blue | `#1a2332` |
| Text Primary | Light Gray | `#e6edf5` |
| Text Secondary | Muted Gray | `#9fb4c7` |
| Admin Accent | Green | `#22c55e` |
| Manager Accent | Purple | `#8b5cf6` |
| Employee Accent | Cyan | `#06b6d4` |

### Design Features
- 🌙 Dark theme throughout
- 💎 Glassmorphism effects
- 📱 Fully responsive
- ✨ Smooth animations
- 🎯 Clean, modern typography (Inter font)

---

## 🔒 Role Permissions Matrix

| Feature | Super Admin | Manager | Employee |
|---------|:-----------:|:-------:|:--------:|
| **Dashboard** | ✅ Full stats | ✅ Team stats | ✅ Personal |
| **View All Employees** | ✅ | ❌ | ❌ |
| **View Team Employees** | ✅ | ✅ | ❌ |
| **Add/Edit Employees** | ✅ | ❌ | ❌ |
| **Manage Departments** | ✅ | ❌ | ❌ |
| **Manage Users** | ✅ | ❌ | ❌ |
| **Run Payroll** | ✅ | ❌ | ❌ |
| **View All Payrolls** | ✅ | ❌ | ❌ |
| **View Own Payslips** | ✅ | ✅ | ✅ |
| **Manage Allowances** | ✅ All | ✅ Team | ❌ |
| **Manage Deductions** | ✅ All | ✅ Team | ❌ |
| **Award Bonuses** | ✅ All | ✅ Team | ❌ |
| **Approve All Leaves** | ✅ | ❌ | ❌ |
| **Approve Team Leaves** | ✅ | ✅ | ❌ |
| **Request Leave** | ✅ | ✅ | ✅ |
| **View Attendance** | ✅ All | ✅ Team | ✅ Own |
| **Clock In/Out** | ❌ | ❌ | ✅ |

---

## 💰 Payroll Calculation

### Formula
```
Net Salary = Base Salary + Allowances + Bonuses - Deductions - Tax
```

### Tax Brackets
| Income Range | Tax Rate |
|--------------|----------|
| $0 - $1,000 | 10% |
| $1,001 - $3,000 | 20% |
| $3,001+ | 30% |

### Allowance Types
- **Housing** - Accommodation allowance
- **Transport** - Commute/vehicle allowance
- **Meal** - Food/lunch allowance
- **Communication** - Phone/internet allowance
- **Medical** - Health/wellness allowance
- **Education** - Training/education allowance
- **Other** - Custom allowances

### Deduction Types
- **Pension** - Retirement fund contributions
- **Insurance** - Health/life insurance premiums
- **Loan** - Loan repayments
- **Tax Adjustment** - Additional tax withholding
- **Union Dues** - Union membership fees
- **Garnishment** - Court-ordered deductions
- **Other** - Custom deductions

---

## 🎯 Payroll Management UI Features

### Super Admin Payroll Controls

#### 💵 Allowances Management (`/front_end/admin/allowances.php`)
- **View all employee allowances** in a comprehensive table
- **Add new allowances** with modal form:
  - Select employee from dropdown
  - Choose allowance type (housing, transport, meal, communication, medical, education, other)
  - Set amount and frequency (recurring monthly or one-time)
  - Define effective date range
  - Add optional description
- **Delete allowances** with confirmation
- **Filter and search** through allowance records
- **Visual indicators** for recurring vs one-time allowances

#### ➖ Deductions Management (`/front_end/admin/deductions.php`)
- **View all employee deductions** in organized table
- **Add new deductions** via modal:
  - Select employee
  - Choose deduction type (pension, insurance, loan, tax_adjustment, union_dues, garnishment, other)
  - Set amount and frequency
  - Define effective period
  - Add description
- **Delete deductions** with safety confirmation
- **Track recurring vs one-time** deductions

#### 🎁 Bonuses Management (`/front_end/admin/bonuses.php`)
- **View all awarded bonuses** with employee details
- **Award new bonuses**:
  - Select employee
  - Enter bonus amount
  - Specify reason (e.g., "Performance bonus Q4")
  - Set award date
  - Auto-tracks approver (logged-in admin)
- **Monthly bonus summary** - See total bonuses awarded this month
- **Delete bonuses** if needed
- **Audit trail** - Shows who approved each bonus

### Manager Payroll Controls

#### 💰 Team Payroll Management (`/manager/payroll.php`)
Managers can manage payroll components for their direct reports only:

- **Tabbed Interface**:
  - 🎁 **Bonuses Tab** - Award and manage team bonuses
  - 💵 **Allowances Tab** - Add and manage team allowances
  - ➖ **Deductions Tab** - Add and manage team deductions

- **Award Bonuses** to team members:
  - Select from your team members only
  - Enter amount and reason
  - Set award date
  - Auto-approval tracking

- **Add Allowances** for team:
  - Choose team member
  - Select allowance type
  - Set amount and frequency
  - Define effective period

- **Add Deductions** for team:
  - Select team member
  - Choose deduction type
  - Set amount and frequency
  - Define effective period

- **Security**: Managers can only manage payroll for employees under their supervision
- **Real-time counts**: Badge indicators show number of items in each category

### Key Features
- ✅ **Role-based access** - Admins see all employees, managers see only their team
- ✅ **Modal forms** - Clean, user-friendly popup forms for data entry
- ✅ **CSRF protection** - All forms secured with CSRF tokens
- ✅ **Validation** - Required fields and data type validation
- ✅ **Responsive design** - Works on desktop, tablet, and mobile
- ✅ **Visual feedback** - Success/error messages for all actions
- ✅ **Consistent UI** - Matches the dark theme across all portals

---

## 🗄️ Database Schema

### Core Tables
| Table | Description |
|-------|-------------|
| `users` | All user accounts (admin, manager, employee) |
| `employees` | Employee profiles linked to users |
| `departments` | Company departments |
| `attendance` | Daily clock in/out records |
| `leaves` | Leave requests and approvals |
| `allowances` | Employee allowances |
| `deductions` | Employee deductions |
| `bonuses` | One-time bonuses |
| `payroll_runs` | Payroll processing batches |
| `payroll_items` | Individual payslip records |
| `settings` | System configuration |
| `audit_logs` | Activity tracking |
| `sessions` | User sessions |

---

## ⚙️ Configuration

### Environment Variables (.env)
```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=payroll_pro
DB_USER=root
DB_PASS=

# Application
APP_NAME=PayrollPro
APP_URL=http://localhost/epms

APP_ENV=development
APP_DEBUG=true

# Security
SESSION_LIFETIME=120
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=300
```

---

## 🛠️ Troubleshooting

### Blank Page on Installation
1. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
2. Ensure MySQL is running in XAMPP
3. Verify database `payroll_pro` exists

### Login Not Working
1. Run installation: `http://localhost/epms/install.php`
2. Check users table has data (should have 6 demo accounts)
3. Verify password hash is BCrypt format
4. Try resetting: `install.php?reset=1`

### 404 Errors
- Ensure you're using the full path: `/payroll-management-system/...`
- Check file exists in the correct location

---



## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---


