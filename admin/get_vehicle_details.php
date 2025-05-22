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

// Get owner data
$owner = get_owner_by_id($conn, $vehicle['owner_id']);

// Get violations with payment info
$sql = "SELECT v.*, p.payment_id, p.status, p.payment_method, p.payment_date 
        FROM violations v 
        LEFT JOIN payments p ON v.violation_id = p.violation_id 
        WHERE v.vehicle_id = ? 
        ORDER BY v.violation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

$violations = [];
while ($row = $result->fetch_assoc()) {
    $payment = null;
    if ($row['payment_id']) {
        $payment = [
            'payment_id' => $row['payment_id'],
            'status' => $row['status'],
            'payment_method' => $row['payment_method'],
            'payment_date' => $row['payment_date']
        ];
    }
    
    $violation = [
        'violation_id' => $row['violation_id'],
        'description' => $row['description'],
        'fine' => $row['fine'],
        'violation_date' => $row['violation_date'],
        'location' => $row['location'],
        'created_at' => $row['created_at'],
        'payment' => $payment
    ];
    
    $violations[] = $violation;
}

// Prepare response
$response = [
    'vehicle' => $vehicle,
    'owner' => $owner,
    'violations' => $violations
];

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
