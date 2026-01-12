<?php
// Start session with proper context detection
@session_start();

// Force context based on referrer URL
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referrer, 'staff_generate_id.php') !== false) {
    $_SESSION['session_context'] = 'Staff';
    error_log("Setting context to STAFF based on referrer: " . $referrer);
} elseif (strpos($referrer, 'generate_id.php') !== false) {
    $_SESSION['session_context'] = 'Admin';
    error_log("Setting context to ADMIN based on referrer: " . $referrer);
} else {
    // Default to admin if can't determine
    $_SESSION['session_context'] = 'Admin';
    error_log("Defaulting context to ADMIN. Referrer: " . $referrer);
}

// Also check POST data for explicit context
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['user_context'])) {
    if ($input['user_context'] === 'Staff') {
        $_SESSION['session_context'] = 'Staff';
        error_log("Setting context to STAFF based on POST data");
    } elseif ($input['user_context'] === 'Admin') {
        $_SESSION['session_context'] = 'Admin';
        error_log("Setting context to ADMIN based on POST data");
    }
}

require_once "db.php";
require_once "id_generation_functions.php";

header('Content-Type: application/json');

if (!$input || !isset($input['seniors']) || !isset($input['osca_head']) || !isset($input['municipal_mayor'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// Get user info with proper context detection
$user_info = getUserInfoForLogging();

error_log("=== LOG_ID_GENERATION ===");
error_log("Final user context: " . $user_info['context']);
error_log("User ID: " . $user_info['id']);
error_log("User Name: " . $user_info['name']);
error_log("User Type: " . $user_info['user_type']);
error_log("Seniors count: " . count($input['seniors']));

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
    // Log the ID generation with proper user context
    $result = logIDGeneration($data, $user_info['id'], $user_info['name'], $user_info['context']);

    if ($result['success']) {
        $response = [
            'success' => true,
            'batch_number' => $result['batch_number'],
            'batch_id' => $result['batch_id'],
            'message' => 'ID generation logged successfully',
            'user_info' => [
                'id' => $user_info['id'],
                'name' => $user_info['name'],
                'type' => $user_info['user_type'],
                'context' => $user_info['context']
            ],
            'user_context' => $user_info['context']
        ];

        error_log("Success response: " . json_encode($response));
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'error' => 'Failed to log ID generation: ' . ($result['error'] ?? 'Unknown error'),
            'user_context' => $user_info['context']
        ];

        error_log("Error response: " . json_encode($response));
        echo json_encode($response);
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'user_context' => $user_info['context'] ?? 'unknown'
    ];

    error_log("Exception response: " . json_encode($response));
    echo json_encode($response);
}
