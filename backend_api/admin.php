<?php
/**
 * Admin API
 * E-Voting System
 */

// Start output buffering to prevent any output before JSON
ob_start();

// Suppress PHP errors from being output as HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Clear any output that might have been generated
ob_clean();

// Require admin to be logged in
if (!isAdminLoggedIn()) {
    sendJsonResponse(false, 'Unauthorized. Admin access required.');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if (empty($action)) {
    sendJsonResponse(false, 'Invalid action specified');
}

if ($method === 'POST') {
    
    switch ($action) {
        case 'get_dashboard_stats':
            getDashboardStats();
            break;
        case 'get_all_users':
            getAllUsers();
            break;
        case 'update_user_status':
            updateUserStatus();
            break;
        case 'delete_user':
            deleteUser();
            break;
        case 'update_election_status':
            updateElectionStatus();
            break;
        case 'get_audit_logs':
            getAuditLogs();
            break;
        case 'get_voting_analytics':
            getVotingAnalytics();
            break;
        case 'reset_votes':
            resetVotes();
            break;
        case 'create_election':
            createElection();
            break;
        case 'get_all_elections':
            getAllElections();
            break;
        case 'update_election':
            updateElection();
            break;
        case 'delete_election':
            deleteElection();
            break;
        case 'get_election_candidates':
            getElectionCandidates();
            break;
        case 'get_election_results':
            getElectionResults();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    $totalVoters = getTotalVoters();
    $totalCandidates = getTotalCandidates();
    $totalVotes = getTotalVotesCast();
    $turnout = getVoterTurnout();
    $election = getElectionStatus();
    
    // Get latest election stats
    $latestElection = getElectionStatus();
    $electionId = $latestElection ? $latestElection['id'] : 0;
    
    // Get recent activity
    $recentActivity = fetchResults(
        "SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10"
    );
    
    // Get top candidates for current/latest election
    $topCandidates = fetchResults(
        "SELECT * FROM vote_summary WHERE election_id = ? ORDER BY vote_count DESC LIMIT 5",
        "i",
        [$electionId]
    );
    
    sendJsonResponse(true, 'Dashboard stats retrieved', [
        'statistics' => [
            'total_voters' => $totalVoters,
            'total_candidates' => $totalCandidates,
            'total_votes' => $totalVotes,
            'turnout_percentage' => $turnout,
            'pending_voters' => $totalVoters - $totalVotes
        ],
        'election' => $election,
        'recent_activity' => $recentActivity['success'] ? $recentActivity['data'] : [],
        'top_candidates' => $topCandidates['success'] ? $topCandidates['data'] : []
    ]);
}

/**
 * Get all users
 */
function getAllUsers() {
    $role = sanitizeInput($_REQUEST['role'] ?? '');
    
    if ($role === 'all') {
        $result = fetchResults("SELECT * FROM users ORDER BY created_at DESC");
    } else {
        $result = fetchResults("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC", "s", [$role]);
    }
    
    if ($result['success']) {
        // Remove passwords from response
        foreach ($result['data'] as &$user) {
            unset($user['password']);
        }
        
        sendJsonResponse(true, 'Users retrieved successfully', [
            'users' => $result['data'],
            'total' => count($result['data'])
        ]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve users');
    }
}

/**
 * Update user status
 */
function updateUserStatus() {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($userId <= 0) {
        sendJsonResponse(false, 'Invalid user ID');
    }
    
    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        sendJsonResponse(false, 'Invalid status');
    }
    
    $result = executeQuery(
        "UPDATE users SET status = ? WHERE id = ?",
        "si",
        [$status, $userId]
    );
    
    if ($result['success']) {
        logAudit(getCurrentAdminId(), 'admin', 'USER_STATUS_UPDATE', "User ID $userId status changed to $status");
        sendJsonResponse(true, 'User status updated successfully');
    } else {
        sendJsonResponse(false, 'Failed to update user status');
    }
}

/**
 * Delete user
 */
function deleteUser() {
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        sendJsonResponse(false, 'Invalid user ID');
    }
    
    // Get user details before deletion
    $user = getUserById($userId);
    if (!$user) {
        sendJsonResponse(false, 'User not found');
    }
    
    // Delete user's photo if not default
    if ($user['photo'] && !str_contains($user['photo'], 'default')) {
        deleteImage($user['photo'], $user['role'] . 's');
    }
    
    $result = executeQuery("DELETE FROM users WHERE id = ?", "i", [$userId]);
    
    if ($result['success']) {
        logAudit(getCurrentAdminId(), 'admin', 'USER_DELETED', "User ID $userId ({$user['name']}) deleted");
        sendJsonResponse(true, 'User deleted successfully');
    } else {
        sendJsonResponse(false, 'Failed to delete user');
    }
}

/**
 * Update election status
 */
function updateElectionStatus() {
    $status = sanitizeInput($_POST['status'] ?? '');
    $showResults = isset($_POST['show_results']) ? (bool)$_POST['show_results'] : null;
    
    if (!in_array($status, ['not_started', 'active', 'paused', 'completed'])) {
        sendJsonResponse(false, 'Invalid election status');
    }
    
    // Get current election
    $election = getElectionStatus();
    if (!$election || !is_array($election)) {
        sendJsonResponse(false, 'No election found');
    }
    
    // If starting a new election (from completed/not_started to active), reset votes
    $currentStatus = $election['election_status'] ?? '';
    if ($status === 'active' && in_array($currentStatus, ['completed', 'not_started'])) {
        try {
            // Delete all votes - this is sufficient as the system checks the votes table
            $deleteVotes = executeQuery("DELETE FROM votes");
            
            // Log the reset
            $adminId = getCurrentAdminId();
            if ($adminId) {
                logAudit($adminId, 'admin', 'VOTES_RESET', 'All votes reset for new election');
            }
        } catch (Exception $e) {
            // Continue even if reset fails
            error_log("Vote reset error: " . $e->getMessage());
        }
    }
    
    $query = "UPDATE election_settings SET election_status = ?";
    $types = "s";
    $params = [$status];
    
    if ($showResults !== null) {
        $query .= ", show_results = ?";
        $types .= "i";
        $params[] = $showResults ? 1 : 0;
    }
    
    // Set start/end dates
    if ($status === 'active' && !$election['start_date']) {
        $query .= ", start_date = NOW()";
    }
    if ($status === 'completed' && !$election['end_date']) {
        $query .= ", end_date = NOW()";
    }
    
    $query .= " WHERE id = ?";
    $types .= "i";
    $params[] = $election['id'];
    
    $result = executeQuery($query, $types, $params);
    
    if ($result['success']) {
        $adminId = getCurrentAdminId();
        if ($adminId) {
            logAudit($adminId, 'admin', 'ELECTION_STATUS_UPDATE', "Election status changed to $status");
        }
        sendJsonResponse(true, 'Election status updated successfully');
    } else {
        sendJsonResponse(false, 'Failed to update election status');
    }
}

/**
 * Get audit logs
 */
function getAuditLogs() {
    $limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? $_POST['offset'] ?? 0);
    
    $result = fetchResults(
        "SELECT * FROM audit_log ORDER BY created_at DESC LIMIT ? OFFSET ?",
        "ii",
        [$limit, $offset]
    );
    
    if ($result['success']) {
        sendJsonResponse(true, 'Audit logs retrieved', [
            'logs' => $result['data'],
            'total' => count($result['data'])
        ]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve audit logs');
    }
}

/**
 * Get voting analytics
 */
function getVotingAnalytics() {
    // Get votes by department
    $votesByDept = fetchResults(
        "SELECT u.department, COUNT(v.id) as vote_count 
         FROM users u 
         LEFT JOIN votes v ON u.id = v.voter_id 
         WHERE u.role = 'voter' 
         GROUP BY u.department"
    );
    
    // Get votes by year
    $votesByYear = fetchResults(
        "SELECT u.year, COUNT(v.id) as vote_count 
         FROM users u 
         LEFT JOIN votes v ON u.id = v.voter_id 
         WHERE u.role = 'voter' 
         GROUP BY u.year"
    );
    
    // Get hourly voting pattern
    $votingPattern = fetchResults(
        "SELECT HOUR(voted_at) as hour, COUNT(*) as count 
         FROM votes 
         GROUP BY HOUR(voted_at) 
         ORDER BY hour"
    );
    
    sendJsonResponse(true, 'Analytics retrieved', [
        'votes_by_department' => $votesByDept['success'] ? $votesByDept['data'] : [],
        'votes_by_year' => $votesByYear['success'] ? $votesByYear['data'] : [],
        'voting_pattern' => $votingPattern['success'] ? $votingPattern['data'] : []
    ]);
}

/**
 * Reset all votes (use with caution!)
 */
function resetVotes() {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm !== 'RESET_ALL_VOTES') {
        sendJsonResponse(false, 'Confirmation required');
    }
    
    $result = executeQuery("DELETE FROM votes");
    
    if ($result['success']) {
        logAudit(getCurrentAdminId(), 'admin', 'VOTES_RESET', 'All votes have been reset');
        sendJsonResponse(true, 'All votes have been reset successfully');
    } else {
        sendJsonResponse(false, 'Failed to reset votes');
    }
}

/**
 * Create a new election
 */
function createElection() {
    $electionName = sanitizeInput($_POST['election_name'] ?? '');
    $electionScope = sanitizeInput($_POST['election_scope'] ?? 'institute');
    $targetDepartment = sanitizeInput($_POST['target_department'] ?? null);
    $targetYear = sanitizeInput($_POST['target_year'] ?? null);
    $positionName = sanitizeInput($_POST['position_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($electionName) || empty($positionName)) {
        sendJsonResponse(false, 'Election name and position name are required');
    }
    
    // Validate scope
    if (!in_array($electionScope, ['class', 'institute'])) {
        sendJsonResponse(false, 'Invalid election scope');
    }
    
    // For class-level elections, department and year are required
    if ($electionScope === 'class' && (empty($targetDepartment) || empty($targetYear))) {
        sendJsonResponse(false, 'Department and year are required for class-level elections');
    }
    
    $result = executeQuery(
        "INSERT INTO election_settings (election_name, election_scope, target_department, target_year, position_name, description, election_status) 
         VALUES (?, ?, ?, ?, ?, ?, 'not_started')",
        "ssssss",
        [$electionName, $electionScope, $targetDepartment, $targetYear, $positionName, $description]
    );
    
    if ($result['success']) {
        $electionId = $result['insert_id'];
        logAudit(getCurrentAdminId(), 'admin', 'ELECTION_CREATED', "Created election: $electionName (ID: $electionId)");
        sendJsonResponse(true, 'Election created successfully', ['election_id' => $electionId]);
    } else {
        sendJsonResponse(false, 'Failed to create election');
    }
}

/**
 * Get all elections
 */
function getAllElections() {
    $result = fetchResults("SELECT * FROM election_settings ORDER BY created_at DESC");
    
    if ($result['success']) {
        sendJsonResponse(true, 'Elections retrieved', ['elections' => $result['data']]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve elections');
    }
}

/**
 * Update an election
 */
function updateElection() {
    $electionId = intval($_POST['election_id'] ?? 0);
    $electionName = sanitizeInput($_POST['election_name'] ?? '');
    $electionScope = sanitizeInput($_POST['election_scope'] ?? '');
    $targetDepartment = sanitizeInput($_POST['target_department'] ?? null);
    $targetYear = sanitizeInput($_POST['target_year'] ?? null);
    $positionName = sanitizeInput($_POST['position_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $electionStatus = sanitizeInput($_POST['election_status'] ?? '');
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    // Build update query dynamically
    $updates = [];
    $types = '';
    $params = [];
    
    if (!empty($electionName)) {
        $updates[] = 'election_name = ?';
        $types .= 's';
        $params[] = $electionName;
    }
    
    if (!empty($electionScope)) {
        $updates[] = 'election_scope = ?';
        $types .= 's';
        $params[] = $electionScope;
    }
    
    if ($targetDepartment !== null) {
        $updates[] = 'target_department = ?';
        $types .= 's';
        $params[] = $targetDepartment;
    }
    
    if ($targetYear !== null) {
        $updates[] = 'target_year = ?';
        $types .= 's';
        $params[] = $targetYear;
    }
    
    if (!empty($positionName)) {
        $updates[] = 'position_name = ?';
        $types .= 's';
        $params[] = $positionName;
    }
    
    if (!empty($description)) {
        $updates[] = 'description = ?';
        $types .= 's';
        $params[] = $description;
    }
    
    if (!empty($electionStatus)) {
        $updates[] = 'election_status = ?';
        $types .= 's';
        $params[] = $electionStatus;
    }
    
    if (empty($updates)) {
        sendJsonResponse(false, 'No fields to update');
    }
    
    $types .= 'i';
    $params[] = $electionId;
    
    $sql = "UPDATE election_settings SET " . implode(', ', $updates) . " WHERE id = ?";
    $result = executeQuery($sql, $types, $params);
    
    if ($result['success']) {
        logAudit(getCurrentAdminId(), 'admin', 'ELECTION_UPDATED', "Updated election ID: $electionId");
        sendJsonResponse(true, 'Election updated successfully');
    } else {
        sendJsonResponse(false, 'Failed to update election');
    }
}

/**
 * Delete an election
 */
function deleteElection() {
    $electionId = intval($_POST['election_id'] ?? 0);
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    // Check if election has votes
    $voteCheck = fetchSingle("SELECT COUNT(*) as count FROM votes WHERE election_id = ?", "i", [$electionId]);
    if ($voteCheck['success'] && $voteCheck['data']['count'] > 0) {
        sendJsonResponse(false, 'Cannot delete election with existing votes. Please reset votes first.');
    }
    
    $result = executeQuery("DELETE FROM election_settings WHERE id = ?", "i", [$electionId]);
    
    if ($result['success']) {
        logAudit(getCurrentAdminId(), 'admin', 'ELECTION_DELETED', "Deleted election ID: $electionId");
        sendJsonResponse(true, 'Election deleted successfully');
    } else {
        sendJsonResponse(false, 'Failed to delete election');
    }
}

/**
 * Get candidates for a specific election
 */
function getElectionCandidates() {
    $electionId = intval($_GET['election_id'] ?? $_POST['election_id'] ?? 0);
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    $result = fetchResults(
        "SELECT c.*, u.name, u.email, u.photo, u.department, u.year 
         FROM candidates c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.election_id = ? 
         ORDER BY c.created_at DESC",
        "i",
        [$electionId]
    );
    
    if ($result['success']) {
        sendJsonResponse(true, 'Candidates retrieved', $result['data']);
    } else {
        sendJsonResponse(false, 'Failed to retrieve candidates');
    }
}

/**
 * Get results for a specific election
 */
function getElectionResults() {
    $electionId = intval($_GET['election_id'] ?? $_POST['election_id'] ?? 0);
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    $result = fetchResults(
        "SELECT u.id, u.name, u.photo, u.department, u.year, COUNT(v.id) as vote_count
         FROM users u
         LEFT JOIN votes v ON u.id = v.candidate_id AND v.election_id = ?
         WHERE u.role = 'candidate'
         AND EXISTS (SELECT 1 FROM candidates c WHERE c.user_id = u.id AND c.election_id = ?)
         GROUP BY u.id
         ORDER BY vote_count DESC",
        "ii",
        [$electionId, $electionId]
    );
    
    if ($result['success']) {
        // Calculate total votes for this election
        $totalVotes = fetchSingle("SELECT COUNT(*) as count FROM votes WHERE election_id = ?", "i", [$electionId]);
        
        sendJsonResponse(true, 'Results retrieved', [
            'candidates' => $result['data'],
            'total_votes' => $totalVotes['success'] ? $totalVotes['data']['count'] : 0
        ]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve results');
    }
}
?>
