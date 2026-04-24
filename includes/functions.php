<?php
/**
 * Helper Functions
 * E-Voting System
 */

require_once __DIR__ . '/../config/database.php';

// Upload image file
function uploadImage($file, $targetDir, $prefix = '') {
    $uploadDir = __DIR__ . '/../uploads/' . $targetDir . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

// Delete image file
function deleteImage($filename, $targetDir) {
    $filePath = __DIR__ . '/../uploads/' . $targetDir . '/' . $filename;
    
    if (file_exists($filePath) && $filename !== 'default-voter.jpg' && $filename !== 'default-candidate.jpg') {
        return unlink($filePath);
    }
    
    return false;
}

// Format date
function formatDate($date, $format = 'd M Y, h:i A') {
    return date($format, strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y', $timestamp);
    }
}

// Get election status
function getElectionStatus() {
    $result = fetchSingle("SELECT * FROM election_settings ORDER BY id DESC LIMIT 1");
    
    if ($result['success'] && $result['data']) {
        return $result['data'];
    }
    
    return null;
}

// Check if election is active
function isElectionActive() {
    $election = getElectionStatus();
    return $election && $election['election_status'] === 'active';
}

// Check if user has voted
function hasUserVoted($userId, $electionId = null) {
    if ($electionId) {
        $result = fetchSingle("SELECT id FROM votes WHERE voter_id = ? AND election_id = ?", "ii", [$userId, $electionId]);
    } else {
        $result = fetchSingle("SELECT id FROM votes WHERE voter_id = ?", "i", [$userId]);
    }
    return $result['success'] && $result['data'] !== null;
}

// Get vote count for candidate
function getVoteCount($candidateId) {
    $result = fetchSingle("SELECT COUNT(*) as count FROM votes WHERE candidate_id = ?", "i", [$candidateId]);
    
    if ($result['success'] && $result['data']) {
        return $result['data']['count'];
    }
    
    return 0;
}

// Get all candidates
function getAllCandidates() {
    $result = fetchResults("SELECT * FROM users WHERE role = 'candidate' AND status = 'active' ORDER BY name ASC");
    
    if ($result['success']) {
        return $result['data'];
    }
    
    return [];
}

// Get candidate by ID
function getCandidateById($id) {
    $result = fetchSingle("SELECT * FROM users WHERE id = ? AND role = 'candidate'", "i", [$id]);
    
    if ($result['success']) {
        return $result['data'];
    }
    
    return null;
}

// Get user by ID
function getUserById($id) {
    $result = fetchSingle("SELECT * FROM users WHERE id = ?", "i", [$id]);
    
    if ($result['success']) {
        return $result['data'];
    }
    
    return null;
}

// Get total voters
function getTotalVoters() {
    $result = fetchSingle("SELECT COUNT(*) as count FROM users WHERE role = 'voter' AND status = 'active'");
    
    if ($result['success'] && $result['data']) {
        return $result['data']['count'];
    }
    
    return 0;
}

// Get total candidates
function getTotalCandidates() {
    $result = fetchSingle("SELECT COUNT(*) as count FROM users WHERE role = 'candidate' AND status = 'active'");
    
    if ($result['success'] && $result['data']) {
        return $result['data']['count'];
    }
    
    return 0;
}

// Get total votes cast
function getTotalVotesCast() {
    $result = fetchSingle("SELECT COUNT(*) as count FROM votes");
    
    if ($result['success'] && $result['data']) {
        return $result['data']['count'];
    }
    
    return 0;
}

// Get voter turnout percentage
function getVoterTurnout() {
    $totalVoters = getTotalVoters();
    $votesCast = getTotalVotesCast();
    
    if ($totalVoters > 0) {
        return round(($votesCast / $totalVoters) * 100, 2);
    }
    
    return 0;
}

// Log audit trail
function logAudit($userId, $userType, $action, $description = '') {
    $ip = getClientIP();
    $userAgent = getUserAgent();
    
    executeQuery(
        "INSERT INTO audit_log (user_id, user_type, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
        "isssss",
        [$userId, $userType, $action, $description, $ip, $userAgent]
    );
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . $suffix;
    }
    return $text;
}

// Generate random color for avatar
function generateAvatarColor($name) {
    $colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
        '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52B788'
    ];
    
    $index = ord(strtoupper($name[0])) % count($colors);
    return $colors[$index];
}

// Get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return substr($initials, 0, 2);
}

// Validate student ID format
function validateStudentId($studentId) {
    // Format: CS2023001 (Department code + Year + Number)
    return preg_match('/^[A-Z]{2}\d{7}$/', $studentId);
}

// Send JSON response
function sendJsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Create avatar placeholder HTML
function createAvatarPlaceholder($name) {
    $initials = getInitials($name);
    $color = generateAvatarColor($name);
    
    return '<div class="user-avatar" style="background: ' . $color . ';">' . htmlspecialchars($initials) . '</div>';
}
?>
