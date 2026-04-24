<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    header("Location: " . ($role === 'voter' ? 'voter-dashboard.php' : 'candidate-dashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-full);
            transition: all var(--transition-base);
        }
        
        .step.active {
            background: var(--primary);
        }
        
        .login-step {
            display: none;
        }
        
        .login-step.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .otp-container {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .otp-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid rgba(0, 240, 255, 0.3);
            border-radius: 0.5rem;
            background: rgba(26, 31, 58, 0.6);
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card glass-card">
            <div class="login-header">
                <h1 class="login-title">Voter Portal</h1>
                <p style="color: var(--text-secondary);">Login to cast your vote</p>
            </div>
            
            <div class="step-indicator">
                <div class="step active" id="step1Indicator"></div>
                <div class="step" id="step2Indicator"></div>
            </div>
            
            <!-- Step 1: Email & Password -->
            <div class="login-step active" id="step1">
                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="your.email@college.edu" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        Continue
                    </button>
                    
                    <p class="text-center mt-3">
                        Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register here</a>
                    </p>
                </form>
            </div>
            
            <!-- Step 2: OTP Verification -->
            <div class="login-step" id="step2">
                <div class="text-center mb-4">
                    <h3 style="color: var(--white); margin-bottom: 0.5rem;">Enter OTP</h3>
                    <p class="text-muted">We've sent a 6-digit code to your email/mobile</p>
                    <p class="text-muted" style="font-size: 0.875rem;">
                        Time remaining: <span id="otpTimer" class="fw-bold text-primary">5:00</span>
                    </p>
                </div>
                
                <div class="otp-container">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d">
                </div>
                
                <button type="button" id="verifyOtpBtn" class="btn btn-primary w-100 btn-lg mt-4">
                    Verify & Login
                </button>
                
                <div class="text-center mt-3">
                    <button type="button" id="resendOtpBtn" class="btn btn-outline btn-sm">
                        Resend OTP
                    </button>
                    <button type="button" id="backBtn" class="btn btn-outline btn-sm" style="margin-left: 0.5rem;">
                        Back
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <footer style="padding: 2rem; text-align: center; background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; width: 100%;">
        <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">© 2024 A.P. Shah Institute of Technology. All Rights Reserved.</p>
    </footer>
    <script src="js/main.js"></script>
    <script>
        let userEmail = '';
        let userMobile = '';
        let userPassword = '';
        
        // Step 1: Login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            showLoading(submitBtn);
            
            // First, validate credentials
            const loginResult = await makeRequest('backend_api/auth.php', {
                action: 'login',
                email: email,
                password: password,
                otp_verified: 'false'
            });
            
            hideLoading(submitBtn);
            
            if (loginResult.success && loginResult.data.require_otp) {
                // Credentials valid, now send OTP
                userEmail = email;
                userPassword = password;
                userMobile = loginResult.data.mobile || '';
                
                showLoading(submitBtn);
                const otpResult = await generateOTP(userEmail, userMobile, 'login');
                hideLoading(submitBtn);
                
                if (otpResult.success) {
                    // Move to OTP step
                    document.getElementById('step1').classList.remove('active');
                    document.getElementById('step2').classList.add('active');
                    document.getElementById('step1Indicator').classList.remove('active');
                    document.getElementById('step2Indicator').classList.add('active');
                    
                    // Focus first OTP input
                    document.querySelector('.otp-input').focus();
                } else {
                    showAlert(otpResult.message || 'Failed to send OTP', 'danger');
                }
            } else {
                showAlert(loginResult.message, 'danger');
            }
        });
        
        // Step 2: Verify OTP
        document.getElementById('verifyOtpBtn').addEventListener('click', async function() {
            const otp = getOTPValue();
            
            if (otp.length !== 6) {
                showAlert('Please enter complete 6-digit OTP', 'warning');
                return;
            }
            
            showLoading(this);
            
            // Verify OTP
            const verifyResult = await verifyOTP(userEmail, userMobile, otp, 'login');
            
            if (verifyResult.success) {
                // OTP verified, now complete login
                const loginResult = await makeRequest('backend_api/auth.php', {
                    action: 'login',
                    email: userEmail,
                    password: userPassword,
                    otp_verified: 'true'
                });
                
                hideLoading(this);
                
                if (loginResult.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = loginResult.data.redirect;
                    }, 1000);
                } else {
                    showAlert(loginResult.message, 'danger');
                }
            } else {
                hideLoading(this);
                showAlert(verifyResult.message, 'danger');
                clearOTPInputs();
            }
        });
        
        // Resend OTP
        document.getElementById('resendOtpBtn').addEventListener('click', async function() {
            showLoading(this);
            const result = await generateOTP(userEmail, userMobile, 'login');
            hideLoading(this);
            
            if (result.success) {
                showAlert('OTP resent successfully!', 'success');
                clearOTPInputs();
            } else {
                showAlert(result.message || 'Failed to resend OTP', 'danger');
            }
        });
        
        // Back button
        document.getElementById('backBtn').addEventListener('click', function() {
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step1').classList.add('active');
            document.getElementById('step2Indicator').classList.remove('active');
            document.getElementById('step1Indicator').classList.add('active');
            
            stopOTPTimer();
            clearOTPInputs();
        });
    </script>
</body>
</html>
