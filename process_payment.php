<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if request is AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['violation_id']) && isset($_POST['payment_method'])) {
    $violation_id = (int)$_POST['violation_id'];
    $payment_method = sanitize_input($_POST['payment_method']);
    
    // Validate violation ID
    $sql = "SELECT * FROM violations WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Vi phạm không tồn tại.']);
        exit;
    }
    
    $violation = $result->fetch_assoc();
    
    // Check if already paid
    $sql = "SELECT * FROM payments WHERE violation_id = ? AND status = 'Completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Vi phạm này đã được thanh toán.']);
        exit;
    }
    
    // Check if payment exists but not completed
    $sql = "SELECT * FROM payments WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing payment
        $sql = "UPDATE payments SET payment_method = ?, status = 'Pending', payment_date = NOW() WHERE violation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $payment_method, $violation_id);
    } else {
        // Create new payment
        $sql = "INSERT INTO payments (violation_id, amount, payment_method, status, payment_date) VALUES (?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $violation_id, $violation['fine'], $payment_method);
    }
    
    if ($stmt->execute()) {
        // In a real system, we would process the payment with a payment gateway here
        // For demo purposes, we'll simulate a successful payment after a delay
        
        // Update payment status to completed (in a real system, this would be done by a callback)
        $sql = "UPDATE payments SET status = 'Completed' WHERE violation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $violation_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xử lý thanh toán.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}
?>
