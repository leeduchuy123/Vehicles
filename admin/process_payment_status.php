<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if (!isset($_POST['payment_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required data');
    }

    $payment_id = (int)$_POST['payment_id'];
    $status = $_POST['status'];

    // Start transaction
    $conn->begin_transaction();

    // Update payment status
    $sql = "UPDATE payments p
            INNER JOIN violations v ON p.violation_id = v.violation_id
            SET p.status = ?
            WHERE p.payment_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $payment_id);

    if (!$stmt->execute()) {
        throw new Exception('Cannot update payment status');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}