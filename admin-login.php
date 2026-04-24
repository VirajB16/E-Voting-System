<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header("Location: admin-dashboard.php");
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $result = fetchSingle("SELECT * FROM admin_users WHERE username = ? AND status = 'active'", "s", [$username]);
        
        if ($result['success'] && $result['data']) {
            $admin = $result['data'];
            
            if (verifyPassword($password, $admin['password'])) {
                // Set admin session
                setAdminSession($admin['id'], $admin['role'], $admin['full_name'], $admin['email']);
                
                // Update last login
                executeQuery("UPDATE admin_users SET last_login = NOW() WHERE id = ?", "i", [$admin['id']]);
                
                // Log audit
                logAudit($admin['id'], 'admin', 'ADMIN_LOGIN', 'Admin logged in successfully');
                
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please enter both username and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .admin-login-card {
            max-width: 450px;
            width: 100%;
        }
        
        .admin-icon {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .portal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .portal-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card glass-card">
            <div class="portal-header">
                <div class="admin-icon">👨‍💼</div>
                <h2>Admin Portal</h2>
                <p style="color: var(--text-secondary);">Secure Election Management</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="background: rgba(255, 0, 85, 0.1); border-color: #ff0055; color: #ff0055;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" placeholder="Enter admin username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter admin password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    Login to Admin Panel
                </button>
                
                <p class="text-center mt-3">
                    <a href="index.php" style="color: var(--primary); font-weight: 600;">← Back to Home</a>
                </p>
            </form>
        </div>
    </div>
</body>
</body>
</html>
