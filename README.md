# 🚲 UniCycle - Campus Bike Rental System

A modern, user-friendly bike rental system designed for university campuses. Built with PHP, MySQL, and vanilla CSS.

![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Table of Contents

- [Features](#-features)
- [Demo Accounts](#-demo-accounts)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Project Structure](#-project-structure)
- [Pages Overview](#-pages-overview)
- [Database Tables](#-database-tables)
- [Email Configuration](#-email-configuration)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)

---

## ✨ Features

### For Users
- 🔐 **User Authentication** - Register, login, and password reset via email
- 🚴 **Browse Bikes** - View available mountain and city bikes
- 📅 **Rent Bikes** - Select duration (1-8 hours) and make payments
- 📊 **Rental Summary** - Track current and past rentals
- 🔄 **Return Bikes** - Return with condition feedback
- 💳 **Pay Penalties** - Handle late return fees
- 📝 **File Complaints** - Report issues with rentals
- ⚙️ **Settings** - Update profile info and upload profile picture

### For Admins
- 📈 **Dashboard** - Overview of system statistics
- 🚲 **Manage Bikes** - Add, edit, delete bikes
- 📋 **View Rentals** - Monitor all rental activities
- 👥 **Manage Users** - View users and change roles
- 📢 **Handle Complaints** - Resolve user complaints

---

## 👤 Demo Accounts

| Role | Username | Email | Password |
|------|----------|-------|----------|
| User | Ahmad Firdaus | ahmad@student.uthm.edu.my | password123 |
| Admin | Sarah Lim | sarah@staff.uthm.edu.my | password123 |

---

## 🚀 Installation

### Prerequisites
- **XAMPP** (or similar with PHP 8.0+ and MySQL 5.7+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Git** (optional, for cloning)

### Step 1: Clone or Download

```bash
# Clone the repository
git clone https://github.com/your-username/unicycle.git

# Or download and extract the ZIP file
```

### Step 2: Move to Web Directory

```bash
# For XAMPP on Mac
mv unicycle /Applications/XAMPP/xamppfiles/htdocs/webproject

# For XAMPP on Windows
move unicycle C:\xampp\htdocs\webproject
```

### Step 3: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** (web server)
3. Start **MySQL** (database)

### Step 4: Import Database
1. Open browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** to create a database
3. Name it `unicycle_db` → Click **Create**
4. Click **"Import"** tab
5. Choose file: `database_tempo/unicycle_db.sql`
6. Click **"Go"**

### Step 5: Access the App
Open your browser and go to:
```
http://localhost/webproject
```

---

## 🗄️ Database Setup

### Creating the Database Manually

If you prefer to create tables manually, here's the structure:

```sql
-- Create database
CREATE DATABASE unicycle_db;
USE unicycle_db;

-- Import the SQL file
SOURCE /path/to/database_tempo/unicycle_db.sql;
```

### Database Configuration

The database connection is configured in `config.php`:

```php
$host = 'localhost';
$dbname = 'unicycle_db';
$username = 'root';
$password = '';
```

For production (InfinityFree), use `config_infinityfree.php`.

---

## 📁 Project Structure

```
webproject/
├── 📄 index.php              # Landing page (redirects to login)
├── 📄 login.php              # User login page
├── 📄 register.php           # User registration page
├── 📄 forgot-password.php    # Request password reset
├── 📄 reset-password.php     # Set new password
├── 📄 logout.php             # Logout handler
│
├── 📄 dashboard.php          # User dashboard (home)
├── 📄 available-bikes.php    # Browse and select bikes
├── 📄 rent-instructions.php  # Rental instructions
├── 📄 rent-confirm.php       # Confirm rental details
├── 📄 payment.php            # Payment page
├── 📄 payment-success.php    # Payment confirmation
├── 📄 active-rental.php      # View current rental
├── 📄 return-bike.php        # Return bike process
├── 📄 rental-summary.php     # Rental history
├── 📄 complaints.php         # File complaints
├── 📄 pay-penalty.php        # Pay late fees
├── 📄 settings.php           # User settings
│
├── 📁 admin/                 # Admin panel
│   ├── 📄 dashboard.php      # Admin dashboard
│   ├── 📄 bikes.php          # Manage bikes
│   ├── 📄 rentals.php        # View all rentals
│   ├── 📄 users.php          # Manage users
│   ├── 📄 complaints.php     # Handle complaints
│   └── 📁 includes/          # Admin components
│
├── 📁 assets/                # Static files
│   ├── 📁 uploads/           # User profile pictures
│   └── 📄 campus-cycling.mp4 # Background video
│
├── 📁 vendor/                # External libraries
│   └── 📁 PHPMailer/         # Email library
│
├── 📁 database_tempo/        # SQL files
│   ├── 📄 unicycle_db.sql           # Local database
│   └── 📄 unicycle_db_infinityfree.sql  # Production database
│
├── 📄 config.php             # Local database config
├── 📄 config_infinityfree.php # Production database config
├── 📄 mail_config.php        # Email SMTP settings
├── 📄 style.css              # Main styles
└── 📄 auth.css               # Authentication page styles
```

---

## 📄 Pages Overview

### 🔓 Authentication Pages

| Page | URL | Description |
|------|-----|-------------|
| **Login** | `/login.php` | Sign in with username and password |
| **Register** | `/register.php` | Create new account with email |
| **Forgot Password** | `/forgot-password.php` | Request password reset email |
| **Reset Password** | `/reset-password.php?token=xxx` | Set new password using token |

### 👤 User Pages

| Page | URL | Description |
|------|-----|-------------|
| **Dashboard** | `/dashboard.php` | Home page with stats and recent activity |
| **Available Bikes** | `/available-bikes.php` | Browse available bikes, filter by type |
| **Rent Instructions** | `/rent-instructions.php` | View rental rules before confirming |
| **Rent Confirm** | `/rent-confirm.php` | Confirm bike selection and duration |
| **Payment** | `/payment.php` | Make payment for rental |
| **Active Rental** | `/active-rental.php` | View current rental with countdown timer |
| **Return Bike** | `/return-bike.php` | Return bike and provide feedback |
| **Rental Summary** | `/rental-summary.php` | View all past and current rentals |
| **Complaints** | `/complaints.php` | Submit and view complaints |
| **Pay Penalty** | `/pay-penalty.php` | Pay fees for late returns |
| **Settings** | `/settings.php` | Update profile, password, and picture |

### 🛡️ Admin Pages

| Page | URL | Description |
|------|-----|-------------|
| **Admin Dashboard** | `/admin/dashboard.php` | System overview with charts |
| **Manage Bikes** | `/admin/bikes.php` | Add, edit, delete bikes |
| **All Rentals** | `/admin/rentals.php` | View and filter all rentals |
| **Manage Users** | `/admin/users.php` | View users, change roles |
| **Complaints** | `/admin/complaints.php` | View and resolve complaints |

---

## 🗃️ Database Tables

### `students` - All Users
Stores all users (students, admins, superadmins).

| Column | Type | Description |
|--------|------|-------------|
| `student_id` | INT | Primary key, auto-increment |
| `name` | VARCHAR(100) | Username |
| `email` | VARCHAR(100) | Email address (unique) |
| `password` | VARCHAR(255) | Hashed password |
| `role` | ENUM | `user`, `admin`, or `superadmin` |
| `phone` | VARCHAR(20) | Phone number (optional) |
| `profile_pic` | VARCHAR(255) | Profile picture filename |
| `created_at` | TIMESTAMP | Registration date |

### `bikes` - Bicycle Inventory
Stores all bikes available for rent.

| Column | Type | Description |
|--------|------|-------------|
| `bike_id` | INT | Primary key |
| `bike_code` | VARCHAR(30) | Unique bike code (e.g., MTB-001) |
| `bike_name` | VARCHAR(80) | Display name |
| `bike_type` | ENUM | `mountain`, `city`, or `other` |
| `status` | ENUM | `available`, `rented`, `maintenance` |
| `location` | VARCHAR(100) | Pickup location |
| `last_maintained_date` | DATE | Last maintenance date |

### `rentals` - Rental Records
Tracks all bike rentals.

| Column | Type | Description |
|--------|------|-------------|
| `rental_id` | INT | Primary key |
| `rental_code` | VARCHAR(30) | Unique rental code |
| `student_id` | INT | User who rented (FK) |
| `bike_id` | INT | Bike rented (FK) |
| `start_time` | DATETIME | Rental start time |
| `expected_return_time` | DATETIME | When bike should be returned |
| `return_time` | DATETIME | Actual return time (null if active) |
| `status` | ENUM | `active`, `completed`, or `late` |
| `hourly_rate` | DECIMAL | Rate per hour (default: RM 3.00) |
| `planned_hours` | INT | Hours rented (1-8) |

### `payments` - Payment Records
Stores payment information.

| Column | Type | Description |
|--------|------|-------------|
| `payment_id` | INT | Primary key |
| `rental_id` | INT | Associated rental (FK) |
| `amount` | DECIMAL | Payment amount |
| `method` | ENUM | `cashless`, `card`, `ewallet` |
| `status` | ENUM | `pending`, `paid`, `failed` |
| `paid_at` | DATETIME | Payment timestamp |

### `penalties` - Late Fees
Tracks penalties for late returns.

| Column | Type | Description |
|--------|------|-------------|
| `penalty_id` | INT | Primary key |
| `rental_id` | INT | Associated rental (FK) |
| `minutes_late` | INT | How many minutes late |
| `amount` | DECIMAL | Penalty amount |
| `status` | ENUM | `unpaid` or `paid` |

### `complaints` - User Complaints
Stores user feedback and issues.

| Column | Type | Description |
|--------|------|-------------|
| `complaint_id` | INT | Primary key |
| `complaint_code` | VARCHAR(30) | Unique complaint code |
| `student_id` | INT | User who complained (FK) |
| `rental_id` | INT | Related rental (optional, FK) |
| `message` | TEXT | Complaint message |
| `status` | ENUM | `open` or `resolved` |

### `password_resets` - Reset Tokens
Temporary tokens for password reset.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `student_id` | INT | User requesting reset (FK) |
| `token` | VARCHAR(64) | Unique reset token |
| `expires_at` | DATETIME | Token expiration (1 hour) |

### `bike_feedback` - Return Feedback
Stores condition reports when returning bikes.

| Column | Type | Description |
|--------|------|-------------|
| `feedback_id` | INT | Primary key |
| `rental_id` | INT | Associated rental (FK) |
| `bike_id` | INT | Bike returned (FK) |
| `condition_status` | ENUM | `good`, `minor_issue`, `needs_repair` |
| `note` | TEXT | Additional comments |

---

## 📧 Email Configuration

The system uses Gmail SMTP for sending password reset emails.

### Setup Steps:

1. **Enable 2-Factor Authentication** on your Gmail account
2. Go to [Google App Passwords](https://myaccount.google.com/apppasswords)
3. Generate a new App Password for "Mail"
4. Update `mail_config.php`:

```php
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'xxxx xxxx xxxx xxxx');  // App Password
define('MAIL_FROM_EMAIL', 'your-email@gmail.com');
```

---

## 🎨 Screenshots

### Login Page
Modern split-screen design with video background.

### Dashboard
Clean interface showing rental stats and recent activity.

### Available Bikes
Card-based layout with filtering options.

### Admin Panel
Comprehensive management interface.

---

## 🔧 Troubleshooting

### Common Issues

**Database Connection Error**
- Make sure MySQL is running in XAMPP
- Check credentials in `config.php`

**Emails Not Sending**
- Verify Gmail App Password is correct
- Check `mail_config.php` settings

**Page Not Found**
- Ensure files are in the correct directory
- Check Apache is running

**Permission Denied (Uploads)**
- Make sure `assets/uploads/` folder exists
- Set folder permissions to 755

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👏 Credits

- **Developer**: UniCycle Team
- **University**: UTHM (Universiti Tun Hussein Onn Malaysia)
- **Libraries**: [PHPMailer](https://github.com/PHPMailer/PHPMailer)

---

Made with ❤️ for UTHM Students
