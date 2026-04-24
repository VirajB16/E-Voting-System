# E-Voting System - Portable Setup Guide

This guide contains everything you need to run the **E-Voting System** on a new machine.

## 📋 Prerequisites
- **Web Server**: XAMPP, WAMP, or any PHP-enabled server (PHP 7.4+ recommended).
- **Database**: MySQL/MariaDB.

## 🚀 Setup Instructions

### 1. Database Import
1. Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`).
2. Create a new database named `evoting_system`.
3. Click on the `evoting_system` database, then go to the **Import** tab.
4. Choose the file: `database/evoting_system_final_backup.sql` from this project.
5. Click **Go** or **Import**.

### 2. Configuration
1. Ensure the database credentials in `config/database.php` match your local setup:
   - Default: Host=`localhost`, User=`root`, Password=`` (empty), DB=`evoting_system`.

### 3. Running the App
1. Move the entire `Voting System` folder to your server's root (e.g., `C:\xampp\htdocs\`).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Access the app at: `http://localhost/Voting%20System/`

## 🔑 Access Credentials

### Admin Dashboard
- **URL**: `http://localhost/Voting%20System/admin-login.php`
- **Username**: `admin`
- **Password**: `admin@Apsit`

### Test Users (Voters)
- **Viraj (AIML)**: `viru@apsit.edu.in` / `password`
- **Rahul (CS)**: `rahul.sharma@college.edu` / `password`

### Test Candidates
- **Prafull (AIML)**
- **Sneha (CS)**
- **Vikram (Mech)**

## 📂 Project Structure
- `/api`: Backend logic and endpoints.
- `/includes`: Helper functions and session management.
- `/uploads`: Student profile pictures (includes AI-generated defaults).
- `/js`: Frontend logic (`main.js` handles all AJAX calls).
- `/database`: Contains the final SQL backup.

---
*Created on March 19, 2026 - Final "Brutal" Fix Version.*
