<?php
/**
 * Voting API
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
        'message' => 'Server error: ' . $errstr
    ]);
    exit;
});

// Set exception handler
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $exception->getMessage()
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


// Get action from request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if (empty($action)) {
    sendJsonResponse(false, 'Invalid action');
}

// Check authentication - allow admins to view candidates and results
$publicActions = ['get_candidates', 'get_results'];
$isPublicAction = in_array($action, $publicActions);

if (!$isPublicAction && !isLoggedIn()) {
    sendJsonResponse(false, 'Unauthorized. Please login first.');
}

// Allow admins to access all actions
if (isAdminLoggedIn()) {
    // Admin is logged in, allow all actions
} else if (!isLoggedIn() && !$isPublicAction) {
    sendJsonResponse(false, 'Unauthorized. Please login first.');
}

if ($method === 'POST') {
    switch ($action) {
        case 'generate_vote_otp':
            generateVoteOtp();
            break;
        case 'cast_vote':
            castVote();
            break;
        case 'get_candidates':
            getCandidates();
            break;
        case 'get_results':
            getResults();
            break;
        case 'check_vote_status':
            checkVoteStatus();
            break;
        case 'get_eligible_elections':
            getEligibleElections();
            break;
        case 'get_election_candidates':
            getElectionCandidates();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else if ($method === 'GET') {
    switch ($action) {
        case 'get_candidates':
            getCandidates();
            break;
        case 'get_results':
            getResults();
            break;
        case 'check_vote_status':
            checkVoteStatus();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}

/**
 * Generate OTP for vote verification
 */
function generateVoteOtp() {
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    $candidateId = intval($_POST['candidate_id'] ?? 0);
    $electionId = intval($_POST['election_id'] ?? 0);
    
    // Only voters can vote
    if ($userRole !== 'voter') {
        sendJsonResponse(false, 'Only voters can cast votes');
    }
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    // Check if user has already voted
    if (hasUserVoted($userId, $electionId)) {
        sendJsonResponse(false, 'You have already cast your vote in this election');
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $email = $_SESSION['user_email'];
    
    // Store OTP in database with 5-minute expiration
    $result = executeQuery(
        "INSERT INTO otp_verification (email, otp, purpose, expires_at) VALUES (?, ?, 'vote_verification', DATE_ADD(NOW(), INTERVAL 5 MINUTE))",
        "ss",
        [$email, $otp]
    );
    
    if ($result['success']) {
        sendJsonResponse(true, 'OTP generated successfully', [
            'otp' => $otp, // In production, this would be sent via email/SMS
            'expires_in' => 300 // 5 minutes
        ]);
    } else {
        sendJsonResponse(false, 'Failed to generate OTP');
    }
}

/**
 * Cast vote with OTP verification
 */
function castVote() {
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    $candidateId = intval($_POST['candidate_id'] ?? 0);
    $electionId = intval($_POST['election_id'] ?? 0);
    $otp = $_POST['otp'] ?? '';
    
    // Only voters can cast votes
    if ($userRole !== 'voter') {
        sendJsonResponse(false, 'Only voters can cast votes');
    }
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    // Verify OTP
    if (empty($otp) || strlen($otp) !== 6) {
        sendJsonResponse(false, 'Invalid OTP');
    }
    
    $email = $_SESSION['user_email'];
    $otpResult = fetchSingle(
        "SELECT * FROM otp_verification WHERE email = ? AND otp = ? AND purpose = 'vote_verification' AND verified = FALSE AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1",
        "ss",
        [$email, $otp]
    );
    
    if (!$otpResult['success'] || !$otpResult['data']) {
        sendJsonResponse(false, 'Invalid or expired OTP');
    }
    
    // Mark OTP as verified
    executeQuery(
        "UPDATE otp_verification SET verified = TRUE WHERE id = ?",
        "i",
        [$otpResult['data']['id']]
    );
    
    // Check if election is active
    if (!isElectionActive()) {
        sendJsonResponse(false, 'Election is not currently active');
    }
    
    // Validate candidate ID
    if ($candidateId <= 0) {
        sendJsonResponse(false, 'Invalid candidate selected');
    }
    
    // Check if candidate exists and is active
    $candidate = getCandidateById($candidateId);
    if (!$candidate || $candidate['status'] !== 'active') {
        sendJsonResponse(false, 'Invalid candidate selected');
    }
    
    // Check if user has already voted
    if (hasUserVoted($userId, $electionId)) {
        sendJsonResponse(false, 'You have already cast your vote in this election');
    }
    
    // Get IP and user agent for audit
    $ipAddress = getClientIP();
    $userAgent = getUserAgent();

    // Cast vote
    $result = executeQuery(
        "INSERT INTO votes (voter_id, candidate_id, election_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
        "iiiss",
        [$userId, $candidateId, $electionId, $ipAddress, $userAgent]
    );
    
    if ($result['success']) {
        // Log audit
        logAudit($userId, 'voter', 'VOTE_CAST', "Vote cast for candidate ID: $candidateId");
        
        // Get updated vote count
        $voteCount = getVoteCount($candidateId);
        
        sendJsonResponse(true, 'Your vote has been recorded successfully!', [
            'vote_id' => getDB()->insert_id,
            'candidate_id' => $candidateId,
            'candidate_name' => $candidate['name'],
            'new_vote_count' => $voteCount
        ]);
    } else {
        sendJsonResponse(false, 'Failed to record vote. Please try again.');
    }
}

/**
 * Get all candidates with vote counts
 */
function getCandidates() {
    $candidates = getAllCandidates();
    
    // Add vote count to each candidate
    foreach ($candidates as &$candidate) {
        $candidate['vote_count'] = getVoteCount($candidate['id']);
        
        // Remove sensitive information
        unset($candidate['password']);
        unset($candidate['mobile']);
    }
    
    sendJsonResponse(true, 'Candidates retrieved successfully', [
        'candidates' => $candidates,
        'total_candidates' => count($candidates)
    ]);
}

/**
 * Get election results
 */
function getResults() {
    $electionId = intval($_GET['election_id'] ?? $_POST['election_id'] ?? 0);
    
    // If no election ID provided, get latest active election
    if ($electionId <= 0) {
        $latest = getElectionStatus();
        if ($latest) $electionId = $latest['id'];
    }
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Election not found');
    }
    
    // Check if results should be shown
    $election = fetchSingle("SELECT * FROM election_settings WHERE id = ?", "i", [$electionId]);
    if (!$election['success'] || !$election['data'] || (!$election['data']['show_results'] && !isAdminLoggedIn())) {
        sendJsonResponse(false, 'Results are not available yet');
    }
    
    // Get results for specific election
    $result = fetchResults("SELECT * FROM vote_summary WHERE election_id = ? ORDER BY vote_count DESC", "i", [$electionId]);
    
    if ($result['success']) {
        $totalVotes = getTotalVotesCast();
        $totalVoters = getTotalVoters();
        $turnout = getVoterTurnout();
        
        sendJsonResponse(true, 'Results retrieved successfully', [
            'results' => $result['data'],
            'statistics' => [
                'total_votes' => $totalVotes,
                'total_voters' => $totalVoters,
                'turnout_percentage' => $turnout,
                'total_candidates' => getTotalCandidates()
            ]
        ]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve results');
    }
}

/**
 * Check if current user has voted
 */
function checkVoteStatus() {
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    $electionId = intval($_GET['election_id'] ?? $_POST['election_id'] ?? 0);
    
    if ($userRole !== 'voter') {
        sendJsonResponse(true, 'Not a voter', [
            'has_voted' => false,
            'can_vote' => false
        ]);
    }
    
    $hasVoted = hasUserVoted($userId, $electionId > 0 ? $electionId : null);
    $electionActive = isElectionActive();
    
    $voteDetails = null;
    if ($hasVoted) {
        $query = "SELECT v.*, u.name as candidate_name, u.photo as candidate_photo 
                 FROM votes v 
                 JOIN users u ON v.candidate_id = u.id 
                 WHERE v.voter_id = ?";
        $params = [$userId];
        $types = "i";
        
        if ($electionId > 0) {
            $query .= " AND v.election_id = ?";
            $params[] = $electionId;
            $types .= "i";
        }
        
        $result = fetchSingle($query, $types, $params);
        
        if ($result['success'] && $result['data']) {
            $voteDetails = $result['data'];
        }
    }
    
    sendJsonResponse(true, 'Vote status retrieved', [
        'has_voted' => $hasVoted,
        'can_vote' => !$hasVoted && $electionActive,
        'election_active' => $electionActive,
        'vote_details' => $voteDetails
    ]);
}

/**
 * Get elections that the current voter is eligible for
 */
function getEligibleElections() {
    $userId = getCurrentUserId();
    
    // Get voter details
    $voter = fetchSingle("SELECT * FROM users WHERE id = ?", "i", [$userId]);
    if (!$voter['success'] || !$voter['data']) {
        sendJsonResponse(false, 'Voter not found');
    }
    
    $voterData = $voter['data'];
    $voterDept = $voterData['department'];
    $voterYear = $voterData['year'];
    
    // Get all active elections
    $allElections = fetchResults(
        "SELECT * FROM election_settings WHERE election_status = 'active' ORDER BY created_at DESC"
    );
    
    if (!$allElections['success']) {
        sendJsonResponse(false, 'Failed to retrieve elections');
    }
    
    $eligibleElections = [];
    
    foreach ($allElections['data'] as $election) {
        $isEligible = false;
        
        if ($election['election_scope'] === 'institute') {
            // All voters can participate in institute-level elections
            $isEligible = true;
        } elseif ($election['election_scope'] === 'class') {
            // Check if voter matches department and year
            if ($voterDept === $election['target_department'] && $voterYear === $election['target_year']) {
                $isEligible = true;
            }
        }
        
        if ($isEligible) {
            // Check if voter has already voted in this election
            $voteCheck = fetchSingle(
                "SELECT id FROM votes WHERE voter_id = ? AND election_id = ?",
                "ii",
                [$userId, $election['id']]
            );
            
            $election['has_voted'] = ($voteCheck['success'] && $voteCheck['data']) ? true : false;
            
            // Get candidate count for this election
            $candidateCount = fetchSingle(
                "SELECT COUNT(*) as count FROM candidates WHERE election_id = ?",
                "i",
                [$election['id']]
            );
            $election['candidate_count'] = $candidateCount['success'] ? $candidateCount['data']['count'] : 0;
            
            $eligibleElections[] = $election;
        }
    }
    
    sendJsonResponse(true, 'Eligible elections retrieved', [
        'elections' => $eligibleElections,
        'voter_info' => [
            'department' => $voterDept,
            'year' => $voterYear
        ]
    ]);
}

/**
 * Get candidates for a specific election
 */
function getElectionCandidates() {
    $electionId = intval($_GET['election_id'] ?? $_POST['election_id'] ?? 0);
    
    if ($electionId <= 0) {
        sendJsonResponse(false, 'Invalid election ID');
    }
    
    // Check if election exists and is active
    $election = fetchSingle("SELECT * FROM election_settings WHERE id = ?", "i", [$electionId]);
    if (!$election['success'] || !$election['data']) {
        sendJsonResponse(false, 'Election not found');
    }
    
    $electionData = $election['data'];
    
    // If user is logged in, check eligibility
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $voter = fetchSingle("SELECT * FROM users WHERE id = ?", "i", [$userId]);
        
        if ($voter['success'] && $voter['data']) {
            $voterData = $voter['data'];
            $isEligible = false;
            
            if ($electionData['election_scope'] === 'institute') {
                $isEligible = true;
            } elseif ($electionData['election_scope'] === 'class') {
                if ($voterData['department'] === $electionData['target_department'] && 
                    $voterData['year'] === $electionData['target_year']) {
                    $isEligible = true;
                }
            }
            
            if (!$isEligible) {
                sendJsonResponse(false, 'You are not eligible to view this election');
            }
        }
    }
    
    // Get candidates for this election
    $candidates = fetchResults(
        "SELECT u.id, u.name, u.email, u.photo, u.department, u.year, c.party_name, c.manifesto
         FROM candidates c
         JOIN users u ON c.user_id = u.id
         WHERE c.election_id = ? AND c.status = 'approved'
         ORDER BY u.name ASC",
        "i",
        [$electionId]
    );
    
    if ($candidates['success']) {
        sendJsonResponse(true, 'Candidates retrieved', [
            'election' => $electionData,
            'candidates' => $candidates['data']
        ]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve candidates');
    }
}
?>
