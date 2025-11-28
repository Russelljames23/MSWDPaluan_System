<?php
// update_benefit.php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$benefit_name = trim($input['benefit_name'] ?? '');

if (!$id || !$benefit_name) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE benefits SET benefit_name = :benefit_name, updated_at = NOW() WHERE id = :id");
    $stmt->execute(['benefit_name' => $benefit_name, 'id' => $id]);

    // Must return proper JSON
    echo json_encode(['success' => true, 'message' => 'Benefit updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
