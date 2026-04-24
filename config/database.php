<?php
/**
 * Database Configuration and Connection Handler
 * E-Voting System
 */

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'evoting_system');

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Create database connection
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to utf8mb4 for proper emoji and special character support
            $this->connection->set_charset("utf8mb4");
            
            // Set MySQL timezone to match PHP timezone
            $this->connection->query("SET time_zone = '+05:30'");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning of instance
    private function __clone() {}
    
    // Prevent unserialization of instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Helper function for prepared statements
function executeQuery($query, $types = "", $params = []) {
    $db = getDB();
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        return ['success' => false, 'error' => $db->error];
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        return ['success' => false, 'error' => $stmt->error];
    }
    
    return ['success' => true, 'stmt' => $stmt];
}

// Helper function to fetch results
function fetchResults($query, $types = "", $params = []) {
    $result = executeQuery($query, $types, $params);
    
    if (!$result['success']) {
        return ['success' => false, 'error' => $result['error']];
    }
    
    $stmt = $result['stmt'];
    $queryResult = $stmt->get_result();
    $data = [];
    
    while ($row = $queryResult->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    return ['success' => true, 'data' => $data];
}

// Helper function to fetch single row
function fetchSingle($query, $types = "", $params = []) {
    $result = fetchResults($query, $types, $params);
    
    if (!$result['success']) {
        return $result;
    }
    
    return [
        'success' => true, 
        'data' => !empty($result['data']) ? $result['data'][0] : null
    ];
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate mobile number (Indian format)
function validateMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

// Generate secure random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
