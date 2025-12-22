<?php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class Part7to9ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
        // Ensure tables exist
        $this->createTablesIfNotExist();
    }

    private function createTablesIfNotExist()
    {
        try {
            // Create report_statistics table
            $sql = "CREATE TABLE IF NOT EXISTS report_statistics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_type ENUM('philhealth', 'booklets') NOT NULL,
                month INT NOT NULL,
                year INT NOT NULL,
                count INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_statistic (report_type, month, year),
                INDEX idx_month_year (month, year),
                INDEX idx_report_type (report_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conn->exec($sql);

            // Create report_activities table
            $sql = "CREATE TABLE IF NOT EXISTS report_activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                description TEXT NOT NULL,
                month INT NOT NULL,
                year INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_month_year (month, year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conn->exec($sql);

            return true;
        } catch (PDOException $e) {
            error_log("Error creating tables: " . $e->getMessage());
            return false;
        }
    }

    public function getStatistics($year = null, $month = null)
    {
        try {
            // Get PhilHealth count
            $philhealthCount = $this->getStatisticCount('philhealth', $year, $month);

            // Get booklets count
            $bookletsCount = $this->getStatisticCount('booklets', $year, $month);

            // Get activities
            $activities = $this->getActivities($year, $month);

            return [
                'success' => true,
                'philhealth_count' => $philhealthCount['count'] ?? 0,
                'philhealth_updated' => $philhealthCount['last_updated'] ?? null,
                'booklets_count' => $bookletsCount['count'] ?? 0,
                'booklets_updated' => $bookletsCount['last_updated'] ?? null,
                'activities' => $activities,
                'available_years' => $this->getAvailableYears(),
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getStatistics: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    private function getStatisticCount($reportType, $year = null, $month = null)
    {
        try {
            $sql = "SELECT count, last_updated 
                    FROM report_statistics 
                    WHERE report_type = ?";

            $params = [$reportType];

            if ($year !== null) {
                $sql .= " AND year = ?";
                $params[] = $year;
            }

            if ($month !== null) {
                $sql .= " AND month = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY last_updated DESC LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'count' => (int)$result['count'],
                    'last_updated' => $this->formatDate($result['last_updated'])
                ];
            }

            return ['count' => 0, 'last_updated' => null];
        } catch (PDOException $e) {
            error_log("Error in getStatisticCount: " . $e->getMessage());
            return ['count' => 0, 'last_updated' => null];
        }
    }

    public function updateStatistic($reportType, $count, $year, $month)
    {
        try {
            // Check if record exists
            $sql = "SELECT id FROM report_statistics 
                    WHERE report_type = ? AND year = ? AND month = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$reportType, $year, $month]);

            if ($stmt->rowCount() > 0) {
                // Update existing record
                $sql = "UPDATE report_statistics 
                        SET count = ?, last_updated = NOW() 
                        WHERE report_type = ? AND year = ? AND month = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$count, $reportType, $year, $month]);
            } else {
                // Insert new record
                $sql = "INSERT INTO report_statistics (report_type, month, year, count) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$reportType, $month, $year, $count]);
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error in updateStatistic: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getActivities($year = null, $month = null)
    {
        try {
            $sql = "SELECT * FROM report_activities WHERE 1=1";

            $params = [];

            if ($year !== null) {
                $sql .= " AND year = ?";
                $params[] = $year;
            }

            if ($month !== null) {
                $sql .= " AND month = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getActivities: " . $e->getMessage());
            return [];
        }
    }

    public function addActivity($description, $month, $year)
    {
        try {
            $sql = "INSERT INTO report_activities (description, month, year, created_at) 
                    VALUES (?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$description, $month, $year]);

            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Error in addActivity: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteActivity($activityId)
    {
        try {
            $sql = "DELETE FROM report_activities WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$activityId]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error in deleteActivity: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function clearActivities($month, $year)
    {
        try {
            $sql = "DELETE FROM report_activities WHERE month = ? AND year = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month, $year]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error in clearActivities: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getAvailableYears()
    {
        try {
            $years = [];

            // Get years from report_statistics
            $sql = "SELECT DISTINCT year 
                    FROM report_statistics 
                    WHERE year > 1900
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $statisticsYears = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Get years from report_activities
            $sql = "SELECT DISTINCT year 
                    FROM report_activities 
                    WHERE year > 1900
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $activitiesYears = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Get current year
            $currentYear = date('Y');

            // Combine all years
            $allYears = array_unique(array_merge($statisticsYears, $activitiesYears, [$currentYear]));

            // Sort descending
            rsort($allYears);

            return array_values($allYears);
        } catch (PDOException $e) {
            error_log("Error in getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    private function formatDate($dateString)
    {
        if (!$dateString) return null;

        try {
            $date = new DateTime($dateString);
            return $date->format('M j, Y g:i A');
        } catch (Exception $e) {
            return $dateString;
        }
    }
}

try {
    $api = new Part7to9ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get_activities') {
            $year = isset($_GET['year']) ? intval($_GET['year']) : null;
            $month = isset($_GET['month']) ? intval($_GET['month']) : null;

            $activities = $api->getActivities($year, $month);
            echo json_encode(['success' => true, 'activities' => $activities]);
        } else {
            $year = isset($_GET['year']) ? intval($_GET['year']) : null;
            $month = isset($_GET['month']) ? intval($_GET['month']) : null;

            $result = $api->getStatistics($year, $month);
            echo json_encode($result);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_philhealth':
                $count = isset($_POST['count']) ? intval($_POST['count']) : 0;
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                if ($count < 0) {
                    echo json_encode(['success' => false, 'message' => 'Count must be a positive number']);
                    break;
                }

                $result = $api->updateStatistic('philhealth', $count, $year, $month);
                echo json_encode($result);
                break;

            case 'update_booklets':
                $count = isset($_POST['count']) ? intval($_POST['count']) : 0;
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                if ($count < 0) {
                    echo json_encode(['success' => false, 'message' => 'Count must be a positive number']);
                    break;
                }

                $result = $api->updateStatistic('booklets', $count, $year, $month);
                echo json_encode($result);
                break;

            case 'add_activity':
                $description = $_POST['description'] ?? '';
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                if (empty($description)) {
                    echo json_encode(['success' => false, 'message' => 'Activity description is required']);
                    break;
                }

                $result = $api->addActivity($description, $month, $year);
                echo json_encode($result);
                break;

            case 'delete_activity':
                $activityId = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;

                if ($activityId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
                    break;
                }

                $result = $api->deleteActivity($activityId);
                echo json_encode($result);
                break;

            case 'clear_activities':
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                $result = $api->clearActivities($month, $year);
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
