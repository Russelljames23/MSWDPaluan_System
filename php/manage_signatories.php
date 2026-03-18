<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Path to signatories JSON file
$signatoriesFile = __DIR__ . '/../data/signatories.json';

// Create data directory if it doesn't exist
if (!file_exists(dirname($signatoriesFile))) {
    mkdir(dirname($signatoriesFile), 0755, true);
}

// Initialize signatories JSON file if it doesn't exist
if (!file_exists($signatoriesFile)) {
    $defaultSignatories = [
        'osca_head' => [
            ['id' => 1, 'name' => 'EVELYN V. BELTRAN', 'status' => 'active'],
            ['id' => 2, 'name' => 'ROSALINA V. BARRALES', 'status' => 'active']
        ],
        'municipal_mayor' => [
            ['id' => 3, 'name' => 'MICHAEL D. DIAZ', 'status' => 'active'],
            ['id' => 4, 'name' => 'MERIAM E. LEYCANO-QUIJANO', 'status' => 'active']
        ]
    ];
    file_put_contents($signatoriesFile, json_encode($defaultSignatories, JSON_PRETTY_PRINT));
}

// Load signatories from JSON file
$signatories = json_decode(file_get_contents($signatoriesFile), true);

// Debug: Log what we're receiving
error_log("Signatories action received: " . print_r($_POST, true));

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// Debug specific variables for toggle/delete
if (in_array($action, ['toggle', 'delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    $position = $_POST['position'] ?? '';
    error_log("Action: $action, ID: $id, Position: $position");
}

try {
    switch ($action) {
        case 'get':
            // Return current signatories
            echo json_encode([
                'success' => true,
                'signatories' => $signatories
            ]);
            break;

        case 'add':
            // Add new signatory
            $position = $_POST['position'] ?? '';
            $name = $_POST['name'] ?? '';

            if (empty($position) || empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }

            // Determine which array to add to
            $positionKey = strtolower(str_replace(' ', '_', $position));
            if (!isset($signatories[$positionKey])) {
                $signatories[$positionKey] = [];
            }

            // Generate new ID
            $newId = 1;
            foreach ($signatories[$positionKey] as $signatory) {
                if ($signatory['id'] >= $newId) {
                    $newId = $signatory['id'] + 1;
                }
            }

            // Add new signatory
            $signatories[$positionKey][] = [
                'id' => $newId,
                'name' => $name,
                'status' => 'active'
            ];

            // Save to file
            file_put_contents($signatoriesFile, json_encode($signatories, JSON_PRETTY_PRINT));

            echo json_encode(['success' => true]);
            break;

        case 'toggle':
            // Toggle signatory status
            $id = (int)($_POST['id'] ?? 0);
            $position = $_POST['position'] ?? '';

            if (!$id || empty($position)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields. ID: ' . $id . ', Position: ' . $position]);
                exit;
            }

            $positionKey = strtolower(str_replace(' ', '_', $position));

            if (!isset($signatories[$positionKey])) {
                echo json_encode(['success' => false, 'error' => 'Invalid position: ' . $positionKey . '. Available: ' . implode(', ', array_keys($signatories))]);
                exit;
            }

            // Find and update signatory
            $updated = false;
            foreach ($signatories[$positionKey] as &$signatory) {
                if ($signatory['id'] == $id) {
                    $signatory['status'] = $signatory['status'] === 'active' ? 'inactive' : 'active';
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Save to file
                file_put_contents($signatoriesFile, json_encode($signatories, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Signatory not found with ID: ' . $id . ' in position: ' . $positionKey]);
            }
            break;

        case 'delete':
            // Delete signatory
            $id = (int)($_POST['id'] ?? 0);
            $position = $_POST['position'] ?? '';

            if (!$id || empty($position)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields. ID: ' . $id . ', Position: ' . $position]);
                exit;
            }

            $positionKey = strtolower(str_replace(' ', '_', $position));

            if (!isset($signatories[$positionKey])) {
                echo json_encode(['success' => false, 'error' => 'Invalid position: ' . $positionKey . '. Available: ' . implode(', ', array_keys($signatories))]);
                exit;
            }

            // Filter out the signatory to delete
            $newList = [];
            $deleted = false;
            foreach ($signatories[$positionKey] as $signatory) {
                if ($signatory['id'] != $id) {
                    $newList[] = $signatory;
                } else {
                    $deleted = true;
                }
            }

            if ($deleted) {
                $signatories[$positionKey] = $newList;
                // Save to file
                file_put_contents($signatoriesFile, json_encode($signatories, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Signatory not found with ID: ' . $id . ' in position: ' . $positionKey]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
