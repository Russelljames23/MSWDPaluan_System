<?php
// cron_update_ages.php - Safe cron job for updating ages without SUPER privileges

// Set timezone
date_default_timezone_set('Asia/Manila');

// Database configuration
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

// Log file
$logFile = __DIR__ . '/logs/age_update.log';
$logDir = __DIR__ . '/logs';

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Log to file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Also echo for cron job output
    echo $logEntry;
}

try {
    logMessage("=== Age Update Job Started ===");
    
    // Connect to database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Function to calculate current age
    function calculateCurrentAge($birthDate) {
        if (empty($birthDate) || $birthDate == '0000-00-00') {
            return null;
        }
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        
        $age = $today->diff($birth)->y;
        
        // Check if birthday hasn't occurred yet this year
        if ((int)$today->format('md') < (int)$birth->format('md')) {
            $age--;
        }
        
        return $age;
    }
    
    // Get current date
    $currentDate = date('Y-m-d');
    
    // 1. First, update ages for applicants whose birthdays are today
    logMessage("Checking for birthdays today...");
    
    $birthdayQuery = "
        SELECT applicant_id, first_name, last_name, birth_date, current_age 
        FROM applicants 
        WHERE status = 'Active' 
        AND MONTH(birth_date) = MONTH(CURDATE()) 
        AND DAY(birth_date) = DAY(CURDATE())
        AND birth_date IS NOT NULL
        AND birth_date != '0000-00-00'
    ";
    
    $stmt = $pdo->query($birthdayQuery);
    $birthdayApplicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $birthdayCount = count($birthdayApplicants);
    logMessage("Found $birthdayCount applicants with birthdays today");
    
    // 2. Update all active applicants' ages (batch update - more efficient)
    logMessage("Updating ages for all active applicants...");
    
    // Use a single UPDATE query for efficiency
    $updateQuery = "
        UPDATE applicants 
        SET 
            current_age = 
                CASE 
                    WHEN birth_date IS NULL THEN current_age
                    WHEN birth_date = '0000-00-00' THEN current_age
                    ELSE 
                        TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) - 
                        (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(birth_date, '%m%d'))
                END,
            age_last_updated = CASE 
                WHEN (age_last_updated IS NULL OR age_last_updated < CURDATE()) 
                     AND status = 'Active' 
                     AND birth_date IS NOT NULL 
                     AND birth_date != '0000-00-00'
                THEN CURDATE()
                ELSE age_last_updated
            END,
            date_modified = NOW()
        WHERE status = 'Active' 
        AND birth_date IS NOT NULL 
        AND birth_date != '0000-00-00'
        AND (age_last_updated IS NULL OR age_last_updated < CURDATE())
    ";
    
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute();
    $rowCount = $updateStmt->rowCount();
    
    logMessage("Updated current_age for $rowCount applicants");
    
    // 3. Log details for debugging
    if ($rowCount > 0) {
        $detailQuery = "
            SELECT applicant_id, first_name, last_name, birth_date, current_age 
            FROM applicants 
            WHERE status = 'Active' 
            AND age_last_updated = CURDATE()
            AND birth_date IS NOT NULL
            ORDER BY last_name, first_name
            LIMIT 20
        ";
        
        $detailStmt = $pdo->query($detailQuery);
        $recentlyUpdated = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentlyUpdated) > 0) {
            logMessage("Sample of updated applicants:");
            foreach ($recentlyUpdated as $applicant) {
                logMessage("  - {$applicant['last_name']}, {$applicant['first_name']} (ID: {$applicant['applicant_id']}) - Birth: {$applicant['birth_date']}, Age: {$applicant['current_age']}");
            }
        }
    }
    
    // 4. Also update the age column (if it differs from current_age)
    $syncAgeQuery = "
        UPDATE applicants 
        SET age = current_age,
            date_modified = NOW()
        WHERE status = 'Active' 
        AND current_age IS NOT NULL 
        AND (age IS NULL OR age != current_age)
    ";
    
    $syncStmt = $pdo->prepare($syncAgeQuery);
    $syncStmt->execute();
    $syncedCount = $syncStmt->rowCount();
    
    if ($syncedCount > 0) {
        logMessage("Synced 'age' column for $syncedCount applicants");
    }
    
    // 5. Log any errors or inconsistencies
    $inconsistentQuery = "
        SELECT COUNT(*) as count 
        FROM applicants 
        WHERE status = 'Active' 
        AND birth_date IS NOT NULL 
        AND birth_date != '0000-00-00'
        AND current_age IS NULL
    ";
    
    $inconsistentStmt = $pdo->query($inconsistentQuery);
    $inconsistentCount = $inconsistentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($inconsistentCount > 0) {
        logMessage("WARNING: $inconsistentCount active applicants have birth dates but NULL current_age");
    }
    
    logMessage("=== Age Update Job Completed Successfully ===");
    
    // Return success for cron job monitoring
    exit(0);
    
} catch (PDOException $e) {
    $errorMessage = "Database Error: " . $e->getMessage();
    logMessage($errorMessage);
    logMessage("=== Age Update Job Failed ===");
    exit(1); // Return non-zero for cron job failure
} catch (Exception $e) {
    $errorMessage = "General Error: " . $e->getMessage();
    logMessage($errorMessage);
    logMessage("=== Age Update Job Failed ===");
    exit(1);
}