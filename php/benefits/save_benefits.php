<?php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Read JSON input from fetch
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['benefits']) || !is_array($input['benefits']) || count($input['benefits']) === 0) {
    echo json_encode(['success' => false, 'message' => 'No benefits provided.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO benefits (benefit_name) VALUES (:benefit_name)");
    foreach ($input['benefits'] as $benefit) {
        $stmt->execute(['benefit_name' => $benefit]);
    }

    echo json_encode(['success' => true, 'message' => 'Benefits added successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save benefits: ' . $e->getMessage()]);
}
