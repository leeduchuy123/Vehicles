<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Missing owner ID']);
    exit;
}

$owner_id = (int)$_GET['id'];
$owner = get_owner_by_id($conn, $owner_id);
if (!$owner) {
    echo json_encode(['error' => 'Owner not found']);
    exit;
}

// Lấy danh sách xe
$vehicles = [];
$sql = "SELECT * FROM vehicles WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['violations'] = get_violations_by_vehicle_id($conn, $row['vehicle_id']);
    $vehicles[] = $row;
}

echo json_encode([
    'owner' => $owner,
    'vehicles' => $vehicles
]);
?>