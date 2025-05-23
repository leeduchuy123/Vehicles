<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    // Validate inputs
    if (!isset($_POST['violation_id']) || empty($_POST['violation_id'])) {
        throw new Exception('Thiếu thông tin vi phạm');
    }

    $violation_id = (int)$_POST['violation_id'];
    $payment_method = sanitize_input($_POST['payment_method']);
    $payer_name = sanitize_input($_POST['payer_name']);
    $payer_phone = sanitize_input($_POST['payer_phone']);
    $payer_email = sanitize_input($_POST['payer_email'] ?? null);
    $payment_date = sanitize_input($_POST['payment_date']);
    $notes = sanitize_input($_POST['notes'] ?? null);

    // Validate phone number
    if (!preg_match("/(84|0[3|5|7|8|9])+([0-9]{8})/", $payer_phone)) {
        throw new Exception('Số điện thoại không hợp lệ');
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert payment request
    $sql = "INSERT INTO payment_requests (
        violation_id, payment_method, payer_name, payer_phone, 
        payer_email, payment_date, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssss",
        $violation_id,
        $payment_method,
        $payer_name,
        $payer_phone,
        $payer_email,
        $payment_date,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi lưu thông tin thanh toán');
    }

    // Update violations status
    $sql = "UPDATE violations SET status = 'Pending' WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violation_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi cập nhật trạng thái vi phạm');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Yêu cầu xác nhận thanh toán đã được gửi thành công!'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}