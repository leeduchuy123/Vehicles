<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get and sanitize search query
$search = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';

// Require minimum 2 characters to search
if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

// Search owners by name or phone
$sql = "SELECT * FROM owners WHERE name LIKE ? OR phone LIKE ? LIMIT 10";
$search_param = "%$search%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

// Format results
$owners = [];
while ($row = $result->fetch_assoc()) {
    $owners[] = [
        'id' => $row['owner_id'],
        'name' => $row['name'],
        'phone' => $row['phone'],
        'address' => $row['address']
    ];
}

// Return JSON response
echo json_encode($owners);
exit;