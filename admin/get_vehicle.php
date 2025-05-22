<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing vehicle ID']);
    exit;
}

$vehicle_id = (int)$_GET['id'];

// Get vehicle data
$sql = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Vehicle not found']);
    exit;
}

$vehicle = $result->fetch_assoc();

// Get all owners for select box
$owners = [];
$sql2 = "SELECT owner_id, name, phone FROM owners ORDER BY name ASC";
$result2 = $conn->query($sql2);
while ($row = $result2->fetch_assoc()) {
    $owners[] = $row;
}

// Return vehicle data and owners as JSON
header('Content-Type: application/json');
echo json_encode([
    'vehicle' => $vehicle,
    'owners' => $owners
]);
?>