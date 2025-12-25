# 🚲 UniCycle Rental System

A modern, responsive bike rental management system designed for university campuses. Students can browse available bikes, rent them with simulated payments, and manage their rentals through an intuitive dashboard.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## ✨ Features

### For Students
- 🔐 **User Authentication** - Secure login and registration system
- 🚴 **Browse Bikes** - View available Mountain and City bikes with real-time availability
- 📝 **Easy Rental Process** - Step-by-step rental flow with duration selection
- 💳 **Simulated Payments** - Cashless payment simulation (RM 3.00/hour)
- ⏱️ **Active Rental Tracking** - Real-time countdown timer for active rentals
- 🔄 **Return Bike** - Report bike condition and complete return
- ⚠️ **Penalty System** - Automatic penalty calculation for late returns
- 💬 **Complaints** - Submit and track complaints/feedback
- 📊 **Dashboard** - Overview of rentals, activity, and bike preferences

### System Features
- 🎨 **Modern UI** - Clean, responsive design with glassmorphism effects
- 🌙 **Consistent Theme** - Blue gradient theme across all pages
- 📱 **Responsive Design** - Works on desktop and mobile devices
- 🔒 **Session Management** - Secure session handling with rental guards
- 🕐 **Malaysian Timezone** - Configured for UTC+8

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.0+ |
| **Database** | MySQL 8.0+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Server** | Apache (XAMPP) |
| **Styling** | Vanilla CSS with CSS Variables |

---

## 📁 Project Structure

```
webproject/
├── api/                    # API endpoints (if any)
├── assets/                 # Images and static assets
├── database_tempo/         # Database SQL files
│   └── unicycle_db.sql     # Main database schema
├── config.php              # Database configuration
├── dashboard.php           # Main student dashboard
├── dashboard.css           # Global styles
├── login.php               # Student login
├── register.php            # Student registration
├── available-bikes.php     # Browse available bikes
├── rent-instructions.php   # Rental instructions
├── rent-confirm.php        # Confirm rental details
├── payment.php             # Payment page
├── payment-success.php     # Payment confirmation
├── active-rental.php       # Active rental view
├── return-form.php         # Return bike form
├── return-bike.php         # Process bike return
├── rental-summary.php      # Rental history
├── complaints.php          # Submit complaints
├── pay-penalty.php         # View/pay penalties
└── README.md               # This file
```

---

## 🚀 Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (or any Apache + MySQL + PHP stack)
- PHP 8.0 or higher
- MySQL 8.0 or higher

### Setup Steps

1. **Clone the repository**
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs  # macOS
   # or C:\xampp\htdocs on Windows
   
   git clone https://github.com/afuqaero/unicycle-rental-system.git webproject
   ```

2. **Start XAMPP**
   - Start Apache and MySQL services

3. **Import the database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database or import directly
   - Import `database_tempo/unicycle_db.sql`

4. **Configure database connection**
   - Edit `config.php` with your database credentials:
   ```php
   $host = "localhost";
   $port = 3306;  // or 3307 if different
   $db = "unicycle_db";
   $user = "root";
   $pass = "";
   ```

5. **Access the application**
   - Open: `http://localhost/webproject`
   - Login or register a new account

---

## 💰 Pricing & Penalties

| Type | Rate |
|------|------|
| Rental Rate | RM 3.00 / hour |
| Grace Period | 10 minutes |
| Late Fee (first 2 hours) | RM 5.00 / hour |
| Late Fee (after 2 hours) | RM 10.00 / hour |

---

## 🎨 UI Preview

The system features a modern dashboard with:
- Blue gradient header banners
- Card-based layouts
- Smooth animations and hover effects
- Responsive sidebar navigation
- Real-time activity feed

---

## 📝 Database Schema

Key tables:
- `students` - User accounts
- `bikes` - Bike inventory (Mountain & City types)
- `rentals` - Rental records
- `payments` - Payment transactions
- `penalties` - Late return penalties
- `complaints` - User complaints
- `admin` - Admin accounts (for admin panel)

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**afuqaero**

---

Made with ❤️ for university campus bike sharing
