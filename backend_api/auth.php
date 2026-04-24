<?php
/**
 * Authentication API
 * E-Voting System
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly
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
        'message' => 'System error: Unable to connect to database. Please ensure MySQL is running.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            registerUser();
            break;
        case 'login':
            loginUser();
            break;
        case 'check_email':
            checkEmailExists();
            break;
        case 'check_mobile':
            checkMobileExists();
            break;
        case 'check_student_id':
            checkStudentIdExists();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}

/**
 * Register new user
 */
function registerUser() {
    // Get and sanitize input
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'voter');
    $studentId = sanitizeInput($_POST['student_id'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $year = sanitizeInput($_POST['year'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $electionId = intval($_POST['election_id'] ?? 0);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
        sendJsonResponse(false, 'All required fields must be filled');
    }
    
    // Validate election selection for candidates
    if ($role === 'candidate' && $electionId <= 0) {
        sendJsonResponse(false, 'Please select an election to register for');
    }
    
    // Validate email
    if (!validateEmail($email)) {
        sendJsonResponse(false, 'Invalid email format');
    }
    
    // Validate mobile
    if (!validateMobile($mobile)) {
        sendJsonResponse(false, 'Invalid mobile number. Must be 10 digits starting with 6-9');
    }
    
    // Validate student ID
    if (!empty($studentId) && !validateStudentId($studentId)) {
        sendJsonResponse(false, 'Invalid student ID format. Use format: CS2023001');
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        sendJsonResponse(false, 'Password must be at least 6 characters long');
    }
    
    // Check if email already exists
    $emailCheck = fetchSingle("SELECT id FROM users WHERE email = ?", "s", [$email]);
    if ($emailCheck['success'] && $emailCheck['data']) {
        sendJsonResponse(false, 'Email already registered');
    }
    
    // Check if mobile already exists
    $mobileCheck = fetchSingle("SELECT id FROM users WHERE mobile = ?", "s", [$mobile]);
    if ($mobileCheck['success'] && $mobileCheck['data']) {
        sendJsonResponse(false, 'Mobile number already registered');
    }
    
    // Check if student ID already exists
    if (!empty($studentId)) {
        $studentIdCheck = fetchSingle("SELECT id FROM users WHERE student_id = ?", "s", [$studentId]);
        if ($studentIdCheck['success'] && $studentIdCheck['data']) {
            sendJsonResponse(false, 'Student ID already registered');
        }
    }
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    // Handle photo upload
    $photoFilename = 'default-' . $role . '.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['photo'], $role . 's', $role . '_');
        if ($uploadResult['success']) {
            $photoFilename = $uploadResult['filename'];
        }
    }
    
    // Insert user
    $query = "INSERT INTO users (name, email, mobile, password, role, student_id, department, year, address, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $result = executeQuery(
        $query,
        "ssssssssss",
        [$name, $email, $mobile, $hashedPassword, $role, $studentId, $department, $year, $address, $photoFilename]
    );
    
    if ($result['success']) {
        $userId = getDB()->insert_id;
        
        // Log audit
        logAudit($userId, $role, 'USER_REGISTERED', "New $role registered: $name");
        
        // If candidate, insert into candidates table
        if ($role === 'candidate' && $electionId > 0) {
            executeQuery(
                "INSERT INTO candidates (user_id, election_id, party_name, manifesto, status) VALUES (?, ?, ?, ?, 'approved')",
                "iiss",
                [$userId, $electionId, 'Independent', $address]
            );
        }
        
        sendJsonResponse(true, 'Registration successful! Please login.', [
            'user_id' => $userId,
            'redirect' => 'login.php'
        ]);
    } else {
        sendJsonResponse(false, 'Registration failed. Please try again.');
    }
}

/**
 * Login user
 */
function loginUser() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $otpVerified = $_POST['otp_verified'] ?? 'false';
    
    // Validate input
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Email and password are required');
    }
    
    // Get user by email
    $result = fetchSingle("SELECT * FROM users WHERE email = ?", "s", [$email]);
    
    if (!$result['success'] || !$result['data']) {
        sendJsonResponse(false, 'Invalid email or password');
    }
    
    $user = $result['data'];
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        sendJsonResponse(false, 'Your account has been ' . $user['status'] . '. Please contact administrator.');
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        // Log failed login attempt
        error_log("Login failed for email: $email - Password verification failed");
        sendJsonResponse(false, 'Invalid email or password');
    }
    
    // Check if OTP is verified
    if ($otpVerified !== 'true') {
        // Credentials are valid, but need OTP verification
        sendJsonResponse(true, 'Please verify OTP', [
            'require_otp' => true,
            'email' => $email,
            'mobile' => $user['mobile']
        ]);
    }
    
    // Set session
    setUserSession($user['id'], $user['role'], $user['name'], $user['email']);
    
    // Update last login (you can add this field to users table if needed)
    
    // Determine redirect based on role
    $redirect = $user['role'] === 'voter' ? 'voter-dashboard.php' : 'candidate-dashboard.php';
    
    sendJsonResponse(true, 'Login successful!', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect' => $redirect
    ]);
}

/**
 * Check if email exists
 */
function checkEmailExists() {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        sendJsonResponse(false, 'Email required');
    }
    
    $result = fetchSingle("SELECT id FROM users WHERE email = ?", "s", [$email]);
    
    sendJsonResponse(true, '', [
        'exists' => ($result['success'] && $result['data'] !== null)
    ]);
}

/**
 * Check if mobile exists
 */
function checkMobileExists() {
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    
    if (empty($mobile)) {
        sendJsonResponse(false, 'Mobile number required');
    }
    
    $result = fetchSingle("SELECT id FROM users WHERE mobile = ?", "s", [$mobile]);
    
    sendJsonResponse(true, '', [
        'exists' => ($result['success'] && $result['data'] !== null)
    ]);
}

/**
 * Check if student ID exists
 */
function checkStudentIdExists() {
    $studentId = sanitizeInput($_POST['student_id'] ?? '');
    
    if (empty($studentId)) {
        sendJsonResponse(false, 'Student ID required');
    }
    
    $result = fetchSingle("SELECT id FROM users WHERE student_id = ?", "s", [$studentId]);
    
    sendJsonResponse(true, '', [
        'exists' => ($result['success'] && $result['data'] !== null)
    ]);
}
?>
