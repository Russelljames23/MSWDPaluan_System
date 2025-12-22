<?php
require_once "db.php";
require_once "id_generation_functions.php";

header('Content-Type: application/json');

$applicant_id = isset($_GET['applicant_id']) ? intval($_GET['applicant_id']) : 0;

if ($applicant_id <= 0) {
    echo json_encode(['has_id' => false, 'error' => 'Invalid applicant ID']);
    exit;
}

$idInfo = checkIfIDPrinted($applicant_id);

if ($idInfo) {
    echo json_encode([
        'has_id' => true,
        'id_number' => $idInfo['id_number'],
        'generation_date' => $idInfo['generation_date'],
        'print_date' => $idInfo['print_date'],
        'status' => $idInfo['status'],
        'is_active' => $idInfo['is_active']
    ]);
} else {
    echo json_encode(['has_id' => false]);
}
?>