<?php
// ID Generation Functions for Senior Citizen ID System
require_once "db.php"; // Make sure this includes your database connection

/**
 * Detect user context from session and URL
 * @return string 'admin' or 'staff'
 */
function detectUserContext()
{
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // Check if we're coming from staff page (check referrer or session)
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';

    error_log("=== CONTEXT DETECTION ===");
    error_log("Referrer: " . $referrer);
    error_log("Script: " . $script_name);
    error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
    error_log("Session user_type: " . ($_SESSION['user_type'] ?? 'none'));
    error_log("Session role: " . ($_SESSION['role'] ?? 'none'));
    error_log("Session usertype: " . ($_SESSION['usertype'] ?? 'none'));

    // Priority 1: Check explicit session context
    if (isset($_SESSION['session_context'])) {
        error_log("Using session_context: " . $_SESSION['session_context']);
        return $_SESSION['session_context'];
    }

    // Priority 2: Check user_type in session
    if (isset($_SESSION['user_type'])) {
        $user_type = strtolower($_SESSION['user_type']);
        if ($user_type === 'Staff') {
            error_log("Using user_type from session: Staff");
            return 'Staff';
        } elseif ($user_type === 'admin') {
            error_log("Using user_type from session: admin");
            return 'admin';
        }
    }

    // Priority 3: Check other session variables
    $session_vars = ['role', 'usertype', 'user_role'];
    foreach ($session_vars as $var) {
        if (isset($_SESSION[$var])) {
            $value = strtolower($_SESSION[$var]);
            if (strpos($value, 'Staff') !== false) {
                error_log("Using $var from session: Staff");
                return 'Staff';
            } elseif (strpos($value, 'admin') !== false) {
                error_log("Using $var from session: admin");
                return 'admin';
            }
        }
    }

    // Priority 4: Check URL/referrer
    if (
        strpos($referrer, 'staff_generate_id.php') !== false ||
        strpos($script_name, 'staff_generate_id.php') !== false ||
        strpos($referrer, '/staff/') !== false
    ) {
        error_log("Using URL detection: Staff");
        return 'Staff';
    }

    // Priority 5: Default to admin
    error_log("Defaulting to: admin");
    return 'admin';
}
/**
 * Get user info with proper context detection
 * @param int|null $user_id
 * @return array
 */
function getUserInfoForLogging($user_id = null)
{
    global $conn;

    // Detect context first
    $context = detectUserContext();

    // Store context in session for consistency
    $_SESSION['session_context'] = $context;

    $actualUserId = $user_id;
    $userName = 'System User';
    $userType = 'Admin';

    error_log("=== GET USER INFO ===");
    error_log("Detected context: " . $context);
    error_log("Requested user_id: " . ($user_id ?? 'null'));

    // If no user_id provided, try to get from session
    if ($actualUserId === null || $actualUserId === 0) {
        if ($context === 'Staff') {
            // Look for staff-specific session variables first
            if (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
                $actualUserId = $_SESSION['staff_user_id'];
                error_log("Using staff_user_id from session: " . $actualUserId);
            } elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $actualUserId = $_SESSION['user_id'];
                error_log("Using user_id from session: " . $actualUserId);
            }
        } else {
            // Look for admin-specific session variables
            if (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
                $actualUserId = $_SESSION['admin_user_id'];
                error_log("Using admin_user_id from session: " . $actualUserId);
            } elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $actualUserId = $_SESSION['user_id'];
                error_log("Using user_id from session: " . $actualUserId);
            }
        }
    }

    // Default to admin if no user found
    if (!$actualUserId || $actualUserId === 0) {
        $actualUserId = 57;
        $userName = 'System Administrator';
        $userType = 'Admin';
        $context = 'admin';
        error_log("Using default admin user_id: " . $actualUserId);
    }

    // Get user info from database
    try {
        $sql = "SELECT id, firstname, lastname, CONCAT(firstname, ' ', lastname) as fullname, 
                       user_type, username, status 
                FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$actualUserId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Use context from detection, but verify with database
            $dbUserType = strtolower($user['user_type'] ?? '');

            // Override context based on database if needed
            if ($context === 'Staff' && (strpos($dbUserType, 'admin') !== false || $dbUserType === 'admin')) {
                error_log("Warning: Context is Staff but DB says admin. Keeping Staff context.");
                // Keep Staff context since that's what the user is acting as
            } elseif ($context === 'Admin' && strpos($dbUserType, 'Staff') !== false) {
                error_log("Warning: Context is Admin but DB says staff. Keeping Admin context.");
                // Keep Admin context since that's what the user is acting as
            }

            $userType = ucfirst($dbUserType);
            if (empty($userType)) {
                $userType = ($context === 'Staff') ? 'Staff' : 'Admin';
            }

            $user_info = [
                'id' => $user['id'],
                'firstname' => $user['firstname'] ?? '',
                'lastname' => $user['lastname'] ?? '',
                'name' => $user['fullname'] ? trim($user['fullname']) : ($user['username'] ?? 'Unknown User'),
                'username' => $user['username'] ?? '',
                'user_type' => $userType,
                'status' => $user['status'] ?? 'Unknown',
                'context' => $context,
                'db_user_type' => $user['user_type'] ?? ''
            ];

            error_log("Final user info: " . print_r($user_info, true));
            return $user_info;
        } else {
            error_log("User not found in database with ID: " . $actualUserId);
        }
    } catch (Exception $e) {
        error_log("Error fetching user info: " . $e->getMessage());
    }

    // Return user info based on context
    if ($context === 'Staff') {
        $userName = 'Staff User';
        $userType = 'Staff';
    } else {
        $userName = 'System Administrator';
        $userType = 'Admin';
    }

    $default_info = [
        'id' => $actualUserId,
        'name' => $userName,
        'user_type' => $userType,
        'context' => $context,
        'db_user_type' => $userType
    ];

    error_log("Returning default user info: " . print_r($default_info, true));
    return $default_info;
}


/**
 * Log activity to activity_logs table with proper context
 * @param int $user_id
 * @param string $activity_type
 * @param string $description
 * @param array|null $activity_details
 * @return bool
 */
function logActivity($user_id, $activity_type, $description, $activity_details = null)
{
    global $conn;

    try {
        // Get complete user info including context
        $user_info = getUserInfoForLogging($user_id);

        // Force context prefix based on detected context
        $full_activity_type = $activity_type;
        if ($user_info['context'] === 'Staff') {
            $full_activity_type = 'STAFF_' . $activity_type;
        } elseif ($user_info['context'] === 'Admin') {
            $full_activity_type = 'ADMIN_' . $activity_type;
        }

        // Update description to include context
        $context_description = ($user_info['context'] === 'Staff' ? 'Staff ' : 'Admin ') . $description;

        // Get IP and user agent
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Add user context to activity_details
        if ($activity_details === null) {
            $activity_details = [];
        }

        $activity_details['user_context'] = $user_info['context'];
        $activity_details['user_info'] = [
            'id' => $user_info['id'],
            'name' => $user_info['name'],
            'type' => $user_info['user_type'],
            'context' => $user_info['context'],
            'db_user_type' => $user_info['db_user_type'] ?? ''
        ];

        $sql = "INSERT INTO activity_logs 
                (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $details_json = !empty($activity_details) ? json_encode($activity_details) : null;

        $result = $stmt->execute([
            $user_info['id'],
            $full_activity_type,
            $context_description,
            $details_json,
            $ip_address,
            $user_agent
        ]);

        error_log("Activity logged - Type: " . $full_activity_type . " - Desc: " . $context_description . " - User: " . $user_info['name'] . " (" . $user_info['context'] . ")");
        return $result;
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate unique ID number
 * @param int $applicant_id
 * @param string $existing_id_number
 * @param string $local_control_number
 * @return string
 */
function generateUniqueIDNumber($applicant_id, $existing_id_number, $local_control_number)
{
    // If there's already a valid ID number, use it
    if (!empty($existing_id_number) && $existing_id_number !== 'N/A') {
        return $existing_id_number;
    }

    // If there's a local control number, use it
    if (!empty($local_control_number) && $local_control_number !== 'N/A') {
        return $local_control_number;
    }

    // Generate a new ID number based on applicant ID and date
    return 'PALUAN-' . date('Y') . '-' . str_pad($applicant_id, 6, '0', STR_PAD_LEFT);
}

/**
 * Log ID generation after successful print
 * @param array $data - Contains seniors array, osca_head, municipal_mayor
 * @param int $user_id
 * @param string $user_name
 * @param string $user_context - 'admin' or 'staff'
 * @return array
 */
function logIDGeneration($data, $user_id = null, $user_name = null, $user_context = null)
{
    global $conn;

    try {
        // Get complete user info with context
        $user_info = getUserInfoForLogging($user_id);

        // Override context if explicitly provided
        if ($user_context && in_array($user_context, ['admin', 'Staff'])) {
            $user_info['context'] = $user_context;
            error_log("Overriding context to: " . $user_context);
        }

        // Use user info for name if not provided
        if ($user_name === null) {
            $user_name = $user_info['name'];
        }

        if ($user_id === null) {
            $user_id = $user_info['id'];
        }

        error_log("=== LOG ID GENERATION START ===");
        error_log("User ID: " . $user_id);
        error_log("User Name: " . $user_name);
        error_log("User Context: " . $user_info['context']);
        error_log("Seniors count: " . count($data['seniors']));

        // Start transaction
        $conn->beginTransaction();

        // Log activity - ID Generation Started with proper context
        $description_prefix = ($user_info['context'] === 'Staff') ? 'Staff ' : 'Admin ';
        $activity_details = [
            'action' => 'id_generation_started',
            'total_ids' => count($data['seniors']),
            'osca_head' => $data['osca_head'],
            'municipal_mayor' => $data['municipal_mayor'],
            'user_info' => $user_info,
            'user_context' => $user_info['context']
        ];

        logActivity(
            $user_id,
            'ID_GENERATION_START',
            $description_prefix . 'started ID generation for ' . count($data['seniors']) . ' seniors',
            $activity_details
        );

        // Generate batch number with context indicator in remarks
        $context_prefix = ($user_info['context'] === 'Staff') ? 'S-' : 'A-';
        $batch_number = 'BATCH-' . date('Ymd') . '-' . strtoupper(uniqid());
        $batch_remarks = "Generated by " . $user_info['context'] . " user: " . $user_name . " (" . $user_info['user_type'] . ")";

        // Insert into batch generation
        $batch_sql = "INSERT INTO id_batch_generation 
                     (batch_number, total_ids, generated_by, generated_by_name, generation_date, 
                      osca_head, municipal_mayor, status, remarks) 
                     VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Printed', ?)";

        $batch_stmt = $conn->prepare($batch_sql);
        $batch_stmt->execute([
            $batch_number,
            count($data['seniors']),
            $user_id,
            $user_name,
            $data['osca_head'],
            $data['municipal_mayor'],
            $batch_remarks
        ]);

        $batch_id = $conn->lastInsertId();
        error_log("Batch created: " . $batch_number . " (ID: " . $batch_id . ")");

        // Insert individual ID logs
        foreach ($data['seniors'] as $index => $senior) {
            // Generate a proper ID number (not 'N/A')
            $id_number = generateUniqueIDNumber(
                $senior['id'],
                $senior['idNumber'] ?? '',
                $senior['localControl'] ?? ''
            );

            // Create remarks with context
            $id_remarks = "Printed by " . $user_info['context'] . ": " . $user_name;
            if (!empty($senior['localControl']) && $senior['localControl'] !== 'N/A') {
                $id_remarks .= " | Local Control: " . $senior['localControl'];
            }

            $id_sql = "INSERT INTO id_generation_logs 
                      (applicant_id, id_number, local_control_number, osca_head, municipal_mayor, 
                       generation_date, print_date, print_count, generated_by, generated_by_name, 
                       status, batch_number, validity_date, expiry_date, is_active, 
                       printed_by, printed_by_name, remarks) 
                      VALUES (?, ?, ?, ?, ?, CURDATE(), NOW(), 1, ?, ?, 'Printed', ?, 
                              DATE_ADD(CURDATE(), INTERVAL 5 YEAR), DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 
                              1, ?, ?, ?)";

            $id_stmt = $conn->prepare($id_sql);
            $id_stmt->execute([
                $senior['id'],
                $id_number,
                $senior['localControl'] ?? '',
                $data['osca_head'],
                $data['municipal_mayor'],
                $user_id,
                $user_name,
                $batch_number,
                $user_id,
                $user_name,
                $id_remarks
            ]);

            $id_log_id = $conn->lastInsertId();
            error_log("ID " . ($index + 1) . " logged: " . $id_number . " (Log ID: " . $id_log_id . ")");

            // Insert batch item
            $item_sql = "INSERT INTO id_batch_items 
                        (batch_id, applicant_id, id_number, local_control_number, status, printed_at) 
                        VALUES (?, ?, ?, ?, 'Printed', NOW())";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->execute([
                $batch_id,
                $senior['id'],
                $id_number,
                $senior['localControl'] ?? ''
            ]);
        }

        // Update batch print info
        $update_batch = "UPDATE id_batch_generation 
                        SET printed_at = NOW(), printed_by = ?, printed_by_name = ?, print_count = 1 
                        WHERE batch_id = ?";
        $update_stmt = $conn->prepare($update_batch);
        $update_stmt->execute([$user_id, $user_name, $batch_id]);

        // Commit transaction
        $conn->commit();

        // Log activity - ID Generation Completed
        $activity_details = [
            'action' => 'id_generation_completed',
            'batch_id' => $batch_id,
            'batch_number' => $batch_number,
            'total_ids' => count($data['seniors']),
            'seniors_printed' => array_map(function ($senior) {
                return [
                    'id' => $senior['id'],
                    'name' => $senior['name'] ?? 'Unknown',
                    'id_number' => $senior['idNumber'] ?? 'N/A'
                ];
            }, $data['seniors']),
            'user_info' => $user_info,
            'user_context' => $user_info['context']
        ];

        logActivity(
            $user_id,
            'ID_GENERATION_COMPLETE',
            $description_prefix . 'completed ID generation - Batch ' . $batch_number,
            $activity_details
        );

        error_log("=== LOG ID GENERATION COMPLETE ===");
        error_log("Batch: " . $batch_number);
        error_log("Context: " . $user_info['context']);
        error_log("User: " . $user_name . " (" . $user_info['user_type'] . ")");

        return [
            'success' => true,
            'batch_id' => $batch_id,
            'batch_number' => $batch_number,
            'user_context' => $user_info['context'],
            'user_info' => $user_info
        ];
    } catch (PDOException $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log("ID Generation Error: " . $e->getMessage());
        error_log("Error Trace: " . $e->getTraceAsString());

        // Log error activity with context
        $user_info = getUserInfoForLogging($user_id);
        logActivity($user_id, 'ERROR', 'ID generation failed: ' . $e->getMessage(), [
            'error' => $e->getMessage(),
            'action' => 'id_generation_failed',
            'user_context' => $user_info['context'] ?? 'unknown',
            'user_info' => $user_info,
            'trace' => $e->getTraceAsString()
        ]);

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if ID has already been printed for an applicant
 * @param int $applicant_id
 * @return array|false
 */
function checkIfIDPrinted($applicant_id)
{
    global $conn;

    $sql = "SELECT * FROM id_generation_logs 
            WHERE applicant_id = ? AND status = 'Printed' AND is_active = 1
            AND id_number IS NOT NULL AND id_number != 'N/A'
            ORDER BY generation_date DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$applicant_id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Helper function to generate new ID number for reissuance
 * @return string
 */
function generateNewIDNumber()
{
    return 'PALUAN-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Reissue a lost/damaged ID
 * @param int $original_log_id
 * @param int $applicant_id
 * @param string $reason
 * @param string $details
 * @param int $user_id
 * @param string $user_name
 * @param string $user_context
 * @return array
 */
function reissueID($original_log_id, $applicant_id, $reason, $details, $user_id, $user_name, $user_context = null)
{
    global $conn;

    try {
        // Get user info with context
        $user_info = getUserInfoForLogging($user_id);

        // Override context if explicitly provided
        if ($user_context && in_array($user_context, ['admin', 'Staff'])) {
            $user_info['context'] = $user_context;
        }

        $conn->beginTransaction();

        // Log activity - Reissue Started with context
        $description_prefix = ($user_info['context'] === 'Staff') ? 'Staff ' : 'Admin ';

        logActivity(
            $user_id,
            'ID_REISSUE_START',
            $description_prefix . 'started ID reissue for applicant ID: ' . $applicant_id,
            [
                'action' => 'id_reissue_started',
                'original_log_id' => $original_log_id,
                'applicant_id' => $applicant_id,
                'user_info' => $user_info,
                'user_context' => $user_info['context']
            ]
        );

        // Get original ID info
        $original_sql = "SELECT * FROM id_generation_logs WHERE id = ?";
        $original_stmt = $conn->prepare($original_sql);
        $original_stmt->execute([$original_log_id]);
        $original = $original_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$original) {
            throw new Exception("Original ID not found");
        }

        // Deactivate old ID
        $deactivate_sql = "UPDATE id_generation_logs SET is_active = 0 WHERE id = ?";
        $deactivate_stmt = $conn->prepare($deactivate_sql);
        $deactivate_stmt->execute([$original_log_id]);

        // Generate new ID number
        $new_id_number = generateNewIDNumber();

        // Create remarks with context
        $reissue_remarks = "Reissued by " . $user_info['context'] . ": " . $user_name .
            " | Reason: " . $reason . " | Details: " . $details;

        // Log reissuance
        $reissue_sql = "INSERT INTO id_reissuance_logs 
                       (original_log_id, applicant_id, reason, reason_details, old_id_number, new_id_number, 
                        reissued_by, reissued_by_name, reissue_date, remarks) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
        $reissue_stmt = $conn->prepare($reissue_sql);
        $reissue_stmt->execute([
            $original_log_id,
            $applicant_id,
            $reason,
            $details,
            $original['id_number'],
            $new_id_number,
            $user_id,
            $user_name,
            $reissue_remarks
        ]);

        // Create new ID record
        $new_remarks = "Reissued by " . $user_info['context'] . " user: " . $user_name .
            " from ID #" . $original['id_number'] . " - Reason: " . $reason;

        $new_id_sql = "INSERT INTO id_generation_logs 
                      (applicant_id, id_number, local_control_number, osca_head, municipal_mayor, 
                       generation_date, print_date, print_count, generated_by, generated_by_name, 
                       status, batch_number, validity_date, expiry_date, is_active, remarks) 
                      VALUES (?, ?, ?, ?, ?, CURDATE(), NULL, 0, ?, ?, 'Reissued', 
                              CONCAT('REISSUE-', ?), DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 
                              DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 1, ?)";

        $new_id_stmt = $conn->prepare($new_id_sql);
        $new_id_stmt->execute([
            $applicant_id,
            $new_id_number,
            $original['local_control_number'],
            $original['osca_head'],
            $original['municipal_mayor'],
            $user_id,
            $user_name,
            $original_log_id,
            $new_remarks
        ]);

        $new_log_id = $conn->lastInsertId();

        $conn->commit();

        // Log activity - Reissue Completed
        logActivity(
            $user_id,
            'ID_REISSUE_COMPLETE',
            $description_prefix . 'completed ID reissue - New ID: ' . $new_id_number,
            [
                'action' => 'id_reissue_completed',
                'original_log_id' => $original_log_id,
                'new_log_id' => $new_log_id,
                'applicant_id' => $applicant_id,
                'old_id_number' => $original['id_number'],
                'new_id_number' => $new_id_number,
                'reason' => $reason,
                'details' => $details,
                'user_info' => $user_info,
                'user_context' => $user_info['context']
            ]
        );

        return [
            'success' => true,
            'new_id_number' => $new_id_number,
            'user_context' => $user_info['context']
        ];
    } catch (Exception $e) {
        $conn->rollBack();

        // Log error activity
        $user_info = getUserInfoForLogging($user_id);
        logActivity($user_id, 'ERROR', 'ID reissue failed: ' . $e->getMessage(), [
            'error' => $e->getMessage(),
            'action' => 'id_reissue_failed',
            'applicant_id' => $applicant_id,
            'original_log_id' => $original_log_id,
            'user_context' => $user_info['context'] ?? 'unknown'
        ]);

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get ID generation statistics
 * @return array
 */
function getIDStatistics()
{
    global $conn;

    $stats = [];

    // Total IDs printed
    $sql1 = "SELECT COUNT(*) as total FROM id_generation_logs WHERE status = 'Printed'";
    $stmt1 = $conn->query($sql1);
    $stats['total_printed'] = $stmt1->fetchColumn();

    // Unique seniors with IDs
    $sql2 = "SELECT COUNT(DISTINCT applicant_id) as unique_seniors FROM id_generation_logs WHERE status = 'Printed'";
    $stmt2 = $conn->query($sql2);
    $stats['unique_seniors'] = $stmt2->fetchColumn();

    // Total batches
    $sql3 = "SELECT COUNT(*) as batches FROM id_batch_generation WHERE status = 'Printed'";
    $stmt3 = $conn->query($sql3);
    $stats['total_batches'] = $stmt3->fetchColumn();

    // Reissued IDs
    $sql4 = "SELECT COUNT(*) as reissued FROM id_generation_logs WHERE status = 'Reissued'";
    $stmt4 = $conn->query($sql4);
    $stats['reissued'] = $stmt4->fetchColumn();

    return $stats;
}

/**
 * Get all printed IDs for a senior
 * @param int $applicant_id
 * @return array
 */
function getSeniorIDHistory($applicant_id)
{
    global $conn;

    $sql = "SELECT igl.*, bg.batch_number as batch_ref, bg.generation_date as batch_date
            FROM id_generation_logs igl
            LEFT JOIN id_batch_generation bg ON igl.batch_number = bg.batch_number
            WHERE igl.applicant_id = ?
            ORDER BY igl.generation_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$applicant_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent activity logs with user information
 * @param int $limit Number of logs to retrieve
 * @return array
 */
function getRecentActivityLogs($limit = 50)
{
    global $conn;

    $sql = "SELECT al.*, u.firstname, u.lastname, u.user_type,
                   CASE 
                       WHEN al.activity_type LIKE 'STAFF_%' THEN 'staff'
                       WHEN al.activity_type LIKE 'ADMIN_%' THEN 'admin'
                       ELSE 'system'
                   END as user_context
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse activity_details JSON to extract user context
    foreach ($logs as &$log) {
        if (!empty($log['activity_details'])) {
            $details = json_decode($log['activity_details'], true);
            if (is_array($details) && isset($details['user_context'])) {
                $log['user_context'] = $details['user_context'];
            }
        }
    }

    return $logs;
}

/**
 * Get activity logs by user
 * @param int $user_id
 * @param int $limit Number of logs to retrieve
 * @return array
 */
function getActivityLogsByUser($user_id, $limit = 100)
{
    global $conn;

    $sql = "SELECT al.*, 
                   CASE 
                       WHEN al.activity_type LIKE 'STAFF_%' THEN 'staff'
                       WHEN al.activity_type LIKE 'ADMIN_%' THEN 'admin'
                       ELSE 'system'
                   END as user_context
            FROM activity_logs al
            WHERE al.user_id = ?
            ORDER BY al.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $limit]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get activity logs by type
 * @param string $activity_type
 * @param int $limit Number of logs to retrieve
 * @return array
 */
function getActivityLogsByType($activity_type, $limit = 100)
{
    global $conn;

    $sql = "SELECT al.*, u.firstname, u.lastname, u.user_type,
                   CASE 
                       WHEN al.activity_type LIKE 'STAFF_%' THEN 'staff'
                       WHEN al.activity_type LIKE 'ADMIN_%' THEN 'admin'
                       ELSE 'system'
                   END as user_context
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.activity_type LIKE ?
            ORDER BY al.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$activity_type . '%', $limit]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
