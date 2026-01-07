<?php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the benefits
    $stmt = $pdo->query("SELECT id, benefit_name FROM benefits ORDER BY created_at DESC");
    $benefits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'benefits' => $benefits,
        'count' => count($benefits)
    ]);
} catch (PDOException $e) {
    // Simple error response
    error_log("Benefits fetch error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load benefits. Please check if the benefits table exists.'
    ]);
}
