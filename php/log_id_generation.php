<?php
// Start session but don't require it
@session_start();
require_once "db.php";
require_once "id_generation_functions.php";

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['seniors']) || !isset($input['osca_head']) || !isset($input['municipal_mayor'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// Get user info - FIRST try from POST data, then from session
$user_id = null;
$user_name = 'System Administrator';

// Try to get user from session first (most reliable)
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = intval($_SESSION['id']);

    // Get user name from session or database
    if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
        $user_name = trim($_SESSION['fullname']);
    } elseif (isset($_SESSION['firstname']) || isset($_SESSION['lastname'])) {
        $user_name = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));
    }

    // If user name is still empty, get from database
    if (empty($user_name) || $user_name === ' ') {
        try {
            $user_sql = "SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data && !empty($user_data['fullname'])) {
                $user_name = trim($user_data['fullname']);
            }
        } catch (Exception $e) {
            // Silently fail and use default
        }
    }
}

// If still no valid user_id, use admin user (id 57 from your users table)
if (!$user_id || $user_id <= 0) {
    $user_id = 57; // Default to admin user from your users table

    // Get admin name from database
    try {
        $admin_sql = "SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?";
        $admin_stmt = $conn->prepare($admin_sql);
        $admin_stmt->execute([$user_id]);
        $admin_data = $admin_stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin_data && !empty($admin_data['fullname'])) {
            $user_name = trim($admin_data['fullname']);
        } else {
            $user_name = 'Admin'; // Fallback
        }
    } catch (Exception $e) {
        $user_name = 'Admin';
    }
}

// Validate user info
if (empty($user_name) || $user_name === ' ') {
    $user_name = 'System Administrator';
}

// Validate seniors data
if (!is_array($input['seniors']) || count($input['seniors']) === 0) {
    echo json_encode(['success' => false, 'error' => 'No seniors selected']);
    exit;
}

// Prepare data for logging
$data = [
    'seniors' => $input['seniors'],
    'osca_head' => $input['osca_head'],
    'municipal_mayor' => $input['municipal_mayor']
];

try {
    // Log the ID generation - WITHOUT triggering activity logs
    $result = logIDGeneration(0, $data, $user_id, $user_name);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'batch_number' => $result['batch_number'],
            'batch_id' => $result['batch_id'],
            'message' => 'ID generation logged successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to log ID generation: ' . ($result['error'] ?? 'Unknown error')]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
