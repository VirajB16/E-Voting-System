<?php
/**
 * OTP Generation and Verification API
 * E-Voting System
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

// Set exception handler
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit;
});

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/session.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: Unable to connect to database.'
    ]);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different OTP operations
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            generateOTP();
            break;
        case 'verify':
            verifyOTP();
            break;
        case 'resend':
            resendOTP();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}

/**
 * Generate OTP for user
 */
function generateOTP() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    $purpose = sanitizeInput($_POST['purpose'] ?? 'login');
    
    if (empty($email) && empty($mobile)) {
        sendJsonResponse(false, 'Email or mobile number required');
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Set expiration time (5 minutes from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Delete old OTPs for this user
    if (!empty($email)) {
        executeQuery("DELETE FROM otp_verification WHERE email = ? AND purpose = ?", "ss", [$email, $purpose]);
    }
    if (!empty($mobile)) {
        executeQuery("DELETE FROM otp_verification WHERE mobile = ? AND purpose = ?", "ss", [$mobile, $purpose]);
    }
    
    // Insert new OTP
    $result = executeQuery(
        "INSERT INTO otp_verification (email, mobile, otp, purpose, expires_at) VALUES (?, ?, ?, ?, ?)",
        "sssss",
        [$email, $mobile, $otp, $purpose, $expiresAt]
    );
    
    if ($result['success']) {
        // In production, send OTP via SMS/Email
        // For demo purposes, we'll return it in the response
        
        // Simulate sending OTP
        $sent = sendOTPMessage($otp, $email, $mobile, $purpose);
        
        if ($sent) {
            // Log audit
sendJsonResponse(true, 'OTP sent successfully', [
                'otp' => $otp, // Remove this in production!
                'expires_in' => 300 // 5 minutes in seconds
            ]);
        } else {
            sendJsonResponse(false, 'Failed to send OTP');
        }
    } else {
        sendJsonResponse(false, 'Failed to generate OTP');
    }
}

/**
 * Verify OTP
 */
function verifyOTP() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    $otp = sanitizeInput($_POST['otp'] ?? '');
    $purpose = sanitizeInput($_POST['purpose'] ?? 'login');
    
    if (empty($otp)) {
        sendJsonResponse(false, 'OTP required');
    }
    
    if (empty($email) && empty($mobile)) {
        sendJsonResponse(false, 'Email or mobile number required');
    }
    
    $now = date('Y-m-d H:i:s');
    
    // Build query based on what's provided
    if (!empty($email)) {
        $query = "SELECT * FROM otp_verification WHERE email = ? AND otp = ? AND purpose = ? AND verified = FALSE AND expires_at > ? ORDER BY created_at DESC LIMIT 1";
        $result = fetchSingle($query, "ssss", [$email, $otp, $purpose, $now]);
    } else {
        $query = "SELECT * FROM otp_verification WHERE mobile = ? AND otp = ? AND purpose = ? AND verified = FALSE AND expires_at > ? ORDER BY created_at DESC LIMIT 1";
        $result = fetchSingle($query, "ssss", [$mobile, $otp, $purpose, $now]);
    }
    
    if ($result['success'] && $result['data']) {
        // Mark OTP as verified
        $otpId = $result['data']['id'];
        executeQuery("UPDATE otp_verification SET verified = TRUE WHERE id = ?", "i", [$otpId]);
        
        // Log audit
sendJsonResponse(true, 'OTP verified successfully', [
            'verified' => true
        ]);
    } else {
        // Check if OTP expired
        if (!empty($email)) {
            $expiredCheck = fetchSingle(
                "SELECT * FROM otp_verification WHERE email = ? AND otp = ? AND purpose = ? ORDER BY created_at DESC LIMIT 1",
                "sss",
                [$email, $otp, $purpose]
            );
        } else {
            $expiredCheck = fetchSingle(
                "SELECT * FROM otp_verification WHERE mobile = ? AND otp = ? AND purpose = ? ORDER BY created_at DESC LIMIT 1",
                "sss",
                [$mobile, $otp, $purpose]
            );
        }
        
        if ($expiredCheck['success'] && $expiredCheck['data']) {
            if ($expiredCheck['data']['verified']) {
                sendJsonResponse(false, 'OTP already used');
            } else {
                sendJsonResponse(false, 'OTP expired. Please request a new one');
            }
        } else {
            sendJsonResponse(false, 'Invalid OTP');
        }
    }
}

/**
 * Resend OTP
 */
function resendOTP() {
    // Simply call generate OTP again
    generateOTP();
}

/**
 * Send OTP via Email to College Email Address
 */
function sendOTPMessage($otp, $email, $mobile, $purpose) {
    // Log OTP for development/testing
    error_log("=== OTP GENERATED ===");
    error_log("Email: $email");
    error_log("Mobile: $mobile");
    error_log("OTP: $otp");
    error_log("Purpose: $purpose");
    error_log("Expires: 5 minutes");
    error_log("====================");
    
    // For development: Skip email sending and just return true
    // The OTP will be displayed in the frontend response for testing
    return true;
    
    /* PRODUCTION: Uncomment below to enable email sending
    
    // Send OTP via email to college email address
    if (!empty($email)) {
        $to = $email;
        $subject = "E-Voting System - Your OTP Code";
        
        // Create HTML email
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px solid #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🗳️ E-Voting System</h1>
                    <p>College Student Council Elections</p>
                </div>
                <div class='content'>
                    <h2>Your One-Time Password (OTP)</h2>
                    <p>Hello,</p>
                    <p>You have requested to " . ucfirst($purpose) . " to the E-Voting System. Please use the following OTP to complete your authentication:</p>
                    
                    <div class='otp-box'>
                        <div style='color: #666; font-size: 14px; margin-bottom: 10px;'>Your OTP Code</div>
                        <div class='otp-code'>$otp</div>
                        <div style='color: #666; font-size: 12px; margin-top: 10px;'>Valid for 5 minutes</div>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong>
                        <ul style='margin: 5px 0; padding-left: 20px;'>
                            <li>This OTP will expire in 5 minutes</li>
                            <li>Do not share this code with anyone</li>
                            <li>Our staff will never ask for your OTP</li>
                            <li>If you didn't request this, please ignore this email</li>
                        </ul>
                    </div>
                    
                    <p style='margin-top: 20px;'>If you have any questions or concerns, please contact your college election administrator.</p>
                    
                    <p>Best regards,<br>
                    <strong>E-Voting System Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . date('Y') . " College E-Voting System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Set email headers for HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: E-Voting System <noreply@college.edu>" . "\r\n";
        $headers .= "Reply-To: support@college.edu" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send email
        $emailSent = mail($to, $subject, $message, $headers);
        
        if ($emailSent) {
            error_log("OTP email sent successfully to: $email");
            return true;
        } else {
            error_log("Failed to send OTP email to: $email");
            return false;
        }
    }
    
    return false;
    */
}
?>
