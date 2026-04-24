# E-Voting System - Project Launch Guide

## 🚀 Quick Start Instructions

### Step 1: Start XAMPP Services
1. Open XAMPP Control Panel
2. Click **Start** next to Apache
3. Click **Start** next to MySQL
4. Wait for both to show "Running" status (green)

### Step 2: Access the Application

#### For Voters/Candidates:
**Homepage**: http://localhost/Voting%20System/
**Login**: http://localhost/Voting%20System/login.php
**Register**: http://localhost/Voting%20System/register.php

#### For Administrators:
**Admin Login**: http://localhost/Voting%20System/admin-login.php
**Admin Dashboard**: http://localhost/Voting%20System/admin-dashboard.php

**Default Admin Credentials**:
- Username: `admin`
- Password: `admin123`

### Step 3: Initialize Test Data (First Time Only)

If this is your first time running the project:

1. **Create Test Elections**:
   ```
   Navigate to: http://localhost/Voting%20System/create-test-elections.php
   ```
   This will create 5 sample elections.

2. **Verify Database**:
   ```
   Open: http://localhost/phpmyadmin/
   Database: evoting_system
   Check tables: election_settings, users, votes
   ```

---

## 📋 Project Structure

```
C:\xampp\htdocs\Voting System\
├── index.php                 # Homepage
├── login.php                 # Voter login
├── register.php              # User registration
├── admin-login.php           # Admin login
├── admin-dashboard.php       # Admin dashboard
├── voter-dashboard.php       # Voter dashboard
├── logout.php                # Logout handler
│
├── backend_api/
│   ├── auth.php             # Authentication API
│   ├── vote.php             # Voting API
│   └── admin.php            # Admin API
│
├── includes/
│   ├── config.php           # Database configuration
│   ├── session.php          # Session management
│   └── functions.php        # Helper functions
│
├── css/
│   ├── style.css            # Main styles
│   └── dashboard.css        # Dashboard styles
│
├── js/
│   └── main.js              # JavaScript utilities
│
├── database/
│   ├── schema.sql           # Database schema
│   └── migration_multilevel.sql  # Multi-level elections
│
└── uploads/
    └── candidates/          # Candidate photos
```

---

## ✅ Verification Checklist

### XAMPP Services
- [ ] Apache is running (port 80)
- [ ] MySQL is running (port 3306)
- [ ] No port conflicts

### Database
- [ ] Database `evoting_system` exists
- [ ] All tables created (users, election_settings, votes, audit_log, admin_users)
- [ ] Admin user exists
- [ ] Test elections created (optional)

### Application Access
- [ ] Homepage loads: http://localhost/Voting%20System/
- [ ] Admin login works: http://localhost/Voting%20System/admin-login.php
- [ ] phpMyAdmin accessible: http://localhost/phpmyadmin/

---

## 🔧 Troubleshooting

### Apache Won't Start
**Problem**: Port 80 already in use

**Solution**:
1. Check if Skype or other apps are using port 80
2. Change Apache port in XAMPP config
3. Or stop conflicting application

### MySQL Won't Start
**Problem**: Port 3306 already in use

**Solution**:
1. Check if another MySQL instance is running
2. Stop other MySQL services
3. Restart XAMPP

### Database Connection Error
**Problem**: "Could not connect to database"

**Solution**:
1. Verify MySQL is running
2. Check `includes/config.php`:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "evoting_system";
   ```
3. Verify database exists in phpMyAdmin

### Page Not Found (404)
**Problem**: Files not in htdocs

**Solution**:
```powershell
# Copy files to htdocs
Copy-Item "C:\Users\Admin\Desktop\Voting System\*" -Destination "C:\xampp\htdocs\Voting System\" -Recurse -Force
```

---

## 🎯 Testing the Application

### Test 1: Admin Dashboard
1. Go to: http://localhost/Voting%20System/admin-login.php
2. Login with `admin` / `admin123`
3. Verify dashboard loads
4. Check election dropdown
5. Select an election
6. View statistics

### Test 2: Voter Registration
1. Go to: http://localhost/Voting%20System/register.php
2. Fill registration form:
   - Name: Test Student
   - Email: test@college.edu
   - Student ID: CS1234567
   - Department: Computer Science
   - Year: 3rd Year
   - Role: Voter
3. Submit and verify OTP screen

### Test 3: Voting Process
1. Login as registered voter
2. View candidate list
3. Cast a vote
4. Verify confirmation message
5. Try voting again (should be prevented)

### Test 4: Results
1. Login as admin
2. Go to Results tab
3. Verify vote counts
4. Check percentages

---

## 📊 Database Quick Access

### phpMyAdmin
**URL**: http://localhost/phpmyadmin/
**Database**: evoting_system

### Quick SQL Queries

**View all elections**:
```sql
SELECT * FROM election_settings;
```

**View all voters**:
```sql
SELECT * FROM users WHERE role = 'voter';
```

**View all candidates**:
```sql
SELECT * FROM users WHERE role = 'candidate';
```

**View vote counts**:
```sql
SELECT 
    u.name,
    COUNT(v.id) as votes
FROM users u
LEFT JOIN votes v ON u.id = v.candidate_id
WHERE u.role = 'candidate'
GROUP BY u.id
ORDER BY votes DESC;
```

---

## 🎬 Demo Preparation

### Before Showing to Professor

1. **Start XAMPP** - Ensure both services running
2. **Clear browser cache** - Ctrl + Shift + R
3. **Test all features** - Go through checklist
4. **Prepare test data** - Have elections and candidates ready
5. **Open key pages** - Keep tabs ready:
   - Admin dashboard
   - Voter login
   - Registration page

### Demo Flow (5 minutes)
1. **Show homepage** (30 sec)
2. **Admin dashboard** (2 min)
   - Login
   - Show statistics
   - Select election
   - Start/manage election
3. **Voter registration** (1 min)
4. **Voting process** (1 min)
5. **Results** (30 sec)

---

## 🔐 Security Notes

- Admin credentials should be changed in production
- OTP email configuration required for production
- SSL certificate needed for HTTPS
- Database credentials should be secured
- Regular backups recommended

---

## 📞 Support Commands

### Restart Services
```bash
# Stop
net stop Apache2.4
net stop MySQL

# Start
net start Apache2.4
net start MySQL
```

### Reset Admin Password
```sql
UPDATE admin_users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
```

### Create Backup
```bash
cd C:\xampp\mysql\bin
mysqldump -u root evoting_system > backup.sql
```

---

## ✅ Project is Ready!

Your E-Voting System is now running and accessible at:
**http://localhost/Voting%20System/**

**Happy Testing! 🎉**
