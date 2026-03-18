<?php
// test_age_update.php - Manual test for age updates

// Include the update function
require_once 'cron_update_ages.php';

// Alternatively, create a simple test
echo "<h3>Age Update Test</h3>";
echo "<p>Run this script to test age calculations:</p>";

// Connect to database
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test calculation function
    function testAgeCalculation($birthDate, $expectedAge) {
        if (empty($birthDate)) {
            return ['result' => 'N/A', 'status' => 'skipped'];
        }
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        
        $age = $today->diff($birth)->y;
        
        // Adjust if birthday hasn't occurred yet this year
        if ((int)$today->format('md') < (int)$birth->format('md')) {
            $age--;
        }
        
        $status = ($age == $expectedAge) ? '✓ PASS' : '✗ FAIL';
        
        return [
            'calculated' => $age,
            'expected' => $expectedAge,
            'status' => $status,
            'today' => $today->format('Y-m-d')
        ];
    }
    
    // Test cases
    echo "<h4>Test Calculations:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Birth Date</th><th>Expected Age</th><th>Calculated</th><th>Status</th><th>Today</th></tr>";
    
    $testCases = [
        ['1950-03-15', null], // Will calculate actual
        ['1960-07-20', null],
        ['1945-12-31', null],
        ['2000-02-29', null],
    ];
    
    foreach ($testCases as $test) {
        $result = testAgeCalculation($test[0], $test[1]);
        echo "<tr>";
        echo "<td>{$test[0]}</td>";
        echo "<td>" . ($test[1] ?? 'auto') . "</td>";
        echo "<td>{$result['calculated']}</td>";
        echo "<td>{$result['status']}</td>";
        echo "<td>{$result['today']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show current state
    echo "<h4>Current Database State:</h4>";
    
    $query = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN current_age IS NULL THEN 1 END) as null_ages,
            COUNT(CASE WHEN age_last_updated = CURDATE() THEN 1 END) as updated_today,
            MIN(birth_date) as oldest_birth,
            MAX(birth_date) as youngest_birth
        FROM applicants 
        WHERE status = 'Active'
    ";
    
    $stmt = $pdo->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    echo "<tr><td>Total Active Applicants</td><td>{$stats['total']}</td></tr>";
    echo "<tr><td>Applicants with NULL current_age</td><td>{$stats['null_ages']}</td></tr>";
    echo "<tr><td>Updated Today</td><td>{$stats['updated_today']}</td></tr>";
    echo "<tr><td>Oldest Birth Date</td><td>{$stats['oldest_birth']}</td></tr>";
    echo "<tr><td>Youngest Birth Date</td><td>{$stats['youngest_birth']}</td></tr>";
    echo "</table>";
    
    // Sample of applicants needing update
    echo "<h4>Applicants Needing Age Update:</h4>";
    
    $needsUpdateQuery = "
        SELECT applicant_id, first_name, last_name, birth_date, current_age, age_last_updated
        FROM applicants 
        WHERE status = 'Active' 
        AND birth_date IS NOT NULL 
        AND birth_date != '0000-00-00'
        AND (age_last_updated IS NULL OR age_last_updated < CURDATE())
        ORDER BY last_name, first_name
        LIMIT 10
    ";
    
    $needsUpdateStmt = $pdo->query($needsUpdateQuery);
    $needsUpdate = $needsUpdateStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($needsUpdate) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Birth Date</th><th>Current Age</th><th>Last Updated</th></tr>";
        
        foreach ($needsUpdate as $applicant) {
            echo "<tr>";
            echo "<td>{$applicant['applicant_id']}</td>";
            echo "<td>{$applicant['last_name']}, {$applicant['first_name']}</td>";
            echo "<td>{$applicant['birth_date']}</td>";
            echo "<td>{$applicant['current_age']}</td>";
            echo "<td>{$applicant['age_last_updated']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><a href='#' onclick='runUpdate()'>Run Age Update Now</a></p>";
        echo "<script>
        function runUpdate() {
            if(confirm('Update ages for all applicants?')) {
                window.location.href = 'run_age_update.php';
            }
        }
        </script>";
    } else {
        echo "<p>All applicant ages are up to date!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}