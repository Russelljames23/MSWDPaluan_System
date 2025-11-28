<?php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'No ID provided.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM benefits WHERE id = :id");
    $stmt->execute(['id' => $input['id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
