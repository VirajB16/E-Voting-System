# E-Voting System - College Elections

A comprehensive web-based online voting system for college student council elections featuring OTP authentication, role-based dashboards, real-time vote counting, and administrative controls.

## 🌟 Features

### For Voters
- **Secure Registration** with student ID validation
- **OTP Authentication** for enhanced security
- **One Vote Per User** with duplicate prevention
- **Real-time Vote Counts** and statistics
- **Mobile Responsive** design

### For Candidates
- **Campaign Profile** with photo and statement
- **Live Vote Tracking** with percentage share
- **Leaderboard** showing current rankings
- **Real-time Updates** every 10 seconds

### For Administrators
- **Election Control** (start, pause, end elections)
- **User Management** (activate, suspend users)
- **Results Management** (show/hide results)
- **Audit Logging** of all activities
- **Analytics Dashboard** with comprehensive statistics

## 🛠️ Technologies Used

- **Frontend**: HTML5, CSS3 (Modern Glassmorphism Design), JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: OTP-based two-factor authentication
- **Security**: Bcrypt password hashing, prepared statements, CSRF protection

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Modern web browser (Chrome, Firefox, Edge, Safari)

## 🚀 Installation

### Step 1: Setup Database

1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Or manually run the SQL file in phpMyAdmin

3. The database `evoting_system` will be created with:
   - Sample admin user (username: `admin`, password: `admin123`)
   - Sample voters and candidates for testing
   - Default election configuration

### Step 2: Configure Database Connection

1. Open `config/database.php`
2. Update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'evoting_system');
   ```

### Step 3: Set Permissions

Ensure the `uploads` directory is writable:
```bash
chmod -R 755 uploads/
```

### Step 4: Start the Server

Using PHP built-in server:
```bash
php -S localhost:8000
```

Or configure Apache/Nginx to point to the project directory.

### Step 5: Access the Application

Open your browser and navigate to:
- **Home**: http://localhost:8000/
- **Admin Panel**: http://localhost:8000/admin-login.php

## 👥 Default Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin@Apsit`

### Test Voter Accounts
All test accounts use password: `password`
- rahul.sharma@college.edu
- priya.patel@college.edu
- amit.kumar@college.edu

### Test Candidate Accounts
All test accounts use password: `password`
- sneha.reddy@college.edu
- vikram.singh@college.edu
- anjali.verma@college.edu

## 📱 Usage Guide

### For Voters

1. **Register**
   - Click "Register" on the home page
   - Select "Voter" role
   - Fill in your details (name, email, mobile, student ID, etc.)
   - Upload a profile photo (optional)
   - Create a password

2. **Login**
   - Enter your email and password
   - Verify OTP sent to your email/mobile
   - Access your voter dashboard

3. **Cast Vote**
   - View all candidates with their profiles
   - Click "Vote" on your preferred candidate
   - Confirm your selection
   - Vote is recorded (cannot be changed)

### For Candidates

1. **Register**
   - Select "Candidate" role during registration
   - Add your campaign statement
   - Upload a professional photo

2. **Monitor Campaign**
   - View your current vote count
   - Check your ranking on the leaderboard
   - Track vote percentage share
   - Monitor real-time updates

### For Administrators

1. **Login**
   - Access admin panel at `/admin-login.php`
   - Use admin credentials

2. **Manage Election**
   - Start/Pause/End election
   - Toggle results visibility
   - Monitor voting activity

3. **Manage Users**
   - View all voters and candidates
   - Activate or suspend accounts
   - Review user details

4. **View Analytics**
   - Check voting statistics
   - View audit logs
   - Export results

## 🔒 Security Features

- **Password Hashing**: Bcrypt with cost factor 10
- **OTP Verification**: Time-based OTPs with 5-minute expiration
- **SQL Injection Protection**: Prepared statements throughout
- **Session Management**: Secure session handling with timeout
- **CSRF Protection**: Token-based validation
- **Input Sanitization**: All user inputs sanitized
- **Audit Logging**: Complete activity trail

## 🎨 Design Features

- **Modern UI**: Glassmorphism effects and gradient backgrounds
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Smooth Animations**: Fade-in effects and transitions
- **Color Coded**: Intuitive status indicators
- **Real-time Updates**: Auto-refresh for live data

## 📊 Database Schema

### Main Tables
- `users` - Voters and candidates
- `votes` - Vote records
- `otp_verification` - OTP tokens
- `admin_users` - Admin accounts
- `election_settings` - Election configuration
- `audit_log` - Activity tracking

## 🔧 Configuration

### OTP Service
The system currently uses a simulated OTP service (displays in console). For production:

1. Edit `backend_api/otp.php`
2. Integrate with SMS gateway (Twilio, MSG91) or email service
3. Update the `sendOTPMessage()` function

### File Uploads
- Maximum file size: 5MB
- Allowed formats: JPG, PNG, GIF
- Upload directory: `uploads/voters/` and `uploads/candidates/`

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database exists

### Upload Directory Error
- Check folder permissions: `chmod -R 755 uploads/`
- Verify folder exists

### Session Issues
- Clear browser cookies
- Check PHP session configuration

## 📝 Future Enhancements

- Email/SMS integration for real OTP delivery
- Multi-election support
- Vote encryption
- Blockchain integration
- Mobile app
- Advanced analytics with charts
- Export results to PDF/CSV
- Voter verification via Aadhaar/college ID

## 👨‍💻 Development

### Project Structure
```
voting-system/
├── backend_api/              # Backend APIs
├── config/           # Configuration files
├── css/              # Stylesheets
├── database/         # SQL schema
├── includes/         # PHP includes
├── js/               # JavaScript files
├── uploads/          # User uploads
├── index.php         # Landing page
├── login.php         # Login page
├── register.php      # Registration page
├── voter-dashboard.php
├── candidate-dashboard.php
├── admin-dashboard.php
└── README.md
```

## 📄 License

This project is created for educational purposes as a college mini-project.

## 🤝 Support

For issues or questions, please contact your college administrator.

---

**Made with ❤️ for College Elections**
