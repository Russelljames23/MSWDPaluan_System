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
    }

    public function getStatistics($year = null, $month = null)
    {
        try {
            // Part VII: Get PhilHealth registrations count
            $philhealthCount = $this->getPhilHealthCount($year, $month);

            // Part VIII: Get purchase booklets count
            $bookletsCount = $this->getBookletsCount($year, $month);

            // Part IX: Get activities
            $activities = $this->getActivities($year, $month);

            return [
                'success' => true,
                'philhealth_count' => $philhealthCount,
                'booklets_count' => $bookletsCount,
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

    private function getPhilHealthCount($year = null, $month = null)
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM applicants 
                    WHERE philhealth_member = 'Yes' 
                    AND validation = 'Validated'";

            $params = [];

            if ($year !== null) {
                $sql .= " AND YEAR(date_created) = ?";
                $params[] = $year;
            }

            if ($month !== null) {
                $sql .= " AND MONTH(date_created) = ?";
                $params[] = $month;
            }

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getPhilHealthCount: " . $e->getMessage());
            return 0;
        }
    }

    private function getBookletsCount($year = null, $month = null)
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM booklets 
                    WHERE status = 'Released'";

            $params = [];

            if ($year !== null) {
                $sql .= " AND YEAR(release_date) = ?";
                $params[] = $year;
            }

            if ($month !== null) {
                $sql .= " AND MONTH(release_date) = ?";
                $params[] = $month;
            }

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getBookletsCount: " . $e->getMessage());
            return 0;
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
            // Get years from applicants table for PhilHealth
            $sql = "SELECT DISTINCT YEAR(date_created) as year 
                    FROM applicants 
                    WHERE philhealth_member = 'Yes' 
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $years1 = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Get years from booklets table
            $sql = "SELECT DISTINCT YEAR(release_date) as year 
                    FROM booklets 
                    WHERE status = 'Released' 
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $years2 = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Combine and deduplicate
            $allYears = array_unique(array_merge($years1, $years2));

            // Filter valid years
            $validYears = array_filter($allYears, function ($year) {
                return $year > 1900;
            });

            // Sort descending
            rsort($validYears);

            return array_values($validYears);
        } catch (PDOException $e) {
            error_log("Error in getAvailableYears: " . $e->getMessage());
            return [];
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
            case 'add_activity':
                $description = $_POST['description'] ?? '';
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                $result = $api->addActivity($description, $month, $year);
                echo json_encode($result);
                break;

            case 'delete_activity':
                $activityId = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;

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
