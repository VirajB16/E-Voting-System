@echo off
echo ========================================
echo E-Voting System - Quick Start
echo ========================================
echo.

echo Checking PHP installation...
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP 7.4+ and try again
    pause
    exit /b 1
)

echo PHP found!
echo.

echo Checking MySQL connection...
echo Please make sure MySQL is running (XAMPP/WAMP)
echo.

echo Starting PHP development server...
echo.
echo Server will start at: http://localhost:8000
echo.
echo IMPORTANT: Before accessing the application:
echo 1. Import database/schema.sql into MySQL
echo 2. Update config/database.php if needed
echo.
echo Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

cd /d "%~dp0"
php -S localhost:8000

pause
