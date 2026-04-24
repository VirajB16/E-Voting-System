<?php
/**
 * Public Elections API
 * E-Voting System
 * This API provides public access to election data for candidate registration
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_active_elections':
            getActiveElections();
            break;
        default:
            sendJsonResponse(false, 'Invalid action');
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}

/**
 * Get active and not_started elections for candidate registration
 */
function getActiveElections() {
    $result = fetchResults("SELECT * FROM election_settings WHERE election_status IN ('active', 'not_started') ORDER BY created_at DESC");
    
    if ($result['success']) {
        sendJsonResponse(true, 'Elections retrieved', ['elections' => $result['data']]);
    } else {
        sendJsonResponse(false, 'Failed to retrieve elections');
    }
}
?>
