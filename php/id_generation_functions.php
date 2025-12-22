<?php
// ID Generation Functions for Senior Citizen ID System
require_once "db.php"; // Make sure this includes your database connection

/**
 * Log ID generation after successful print
 * @param int $applicant_id
 * @param array $data - Contains seniors array, osca_head, municipal_mayor
 * @param int $user_id
 * @param string $user_name
 * @return array
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
 */
function logIDGeneration($applicant_id, $data, $user_id, $user_name)
{
    global $conn;

    try {
        // Generate batch number
        $batch_number = 'BATCH-' . date('Ymd') . '-' . strtoupper(uniqid());

        // Insert into batch generation
        $batch_sql = "INSERT INTO id_batch_generation 
                     (batch_number, total_ids, generated_by, generated_by_name, generation_date, osca_head, municipal_mayor, status) 
                     VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Printed')";
        $batch_stmt = $conn->prepare($batch_sql);
        $batch_stmt->execute([
            $batch_number,
            count($data['seniors']),
            $user_id,
            $user_name,
            $data['osca_head'],
            $data['municipal_mayor']
        ]);

        $batch_id = $conn->lastInsertId();

        // Insert individual ID logs
        foreach ($data['seniors'] as $senior) {
            // Generate a proper ID number (not 'N/A')
            $id_number = generateUniqueIDNumber(
                $senior['id'],
                $senior['idNumber'] ?? '',
                $senior['localControl'] ?? ''
            );

            $id_sql = "INSERT INTO id_generation_logs 
                      (applicant_id, id_number, local_control_number, osca_head, municipal_mayor, 
                       generation_date, print_date, print_count, generated_by, generated_by_name, 
                       status, batch_number, validity_date, expiry_date, is_active, printed_by, printed_by_name) 
                      VALUES (?, ?, ?, ?, ?, CURDATE(), NOW(), 1, ?, ?, 'Printed', ?, DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 
                              DATE_ADD(CURDATE(), INTERVAL 5 YEAR), 1, ?, ?)";

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
                $user_name
            ]);

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

        return ['success' => true, 'batch_id' => $batch_id, 'batch_number' => $batch_number];
    } catch (PDOException $e) {
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
 * @return array
 */
function reissueID($original_log_id, $applicant_id, $reason, $details, $user_id, $user_name)
{
    global $conn;

    try {
        $conn->beginTransaction();

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

        // Log reissuance
        $reissue_sql = "INSERT INTO id_reissuance_logs 
                       (original_log_id, applicant_id, reason, reason_details, old_id_number, new_id_number, 
                        reissued_by, reissued_by_name, reissue_date) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
        $reissue_stmt = $conn->prepare($reissue_sql);
        $reissue_stmt->execute([
            $original_log_id,
            $applicant_id,
            $reason,
            $details,
            $original['id_number'],
            $new_id_number,
            $user_id,
            $user_name
        ]);

        // Create new ID record
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
            "Reissued from ID #{$original['id_number']} - Reason: {$reason}"
        ]);

        $conn->commit();
        return ['success' => true, 'new_id_number' => $new_id_number];
    } catch (Exception $e) {
        $conn->rollBack();
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
