<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    if (!isset($_POST['violation_id']) || empty($_POST['violation_id']) ||
        !isset($_POST['payment_method']) || empty($_POST['payment_method']) ||
        !isset($_POST['payer_name']) || empty($_POST['payer_name'])) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    $violation_id = (int)$_POST['violation_id'];
    $payment_method = sanitize_input($_POST['payment_method']);
    $payer_name = sanitize_input($_POST['payer_name']);
    $payer_phone = sanitize_input($_POST['payer_phone'] ?? null);
    $payer_email = sanitize_input($_POST['payer_email'] ?? null);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $note = sanitize_input($_POST['note'] ?? null);

    // Get violation amount
    $stmt = $conn->prepare("SELECT fine FROM violations WHERE violation_id = ?");
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $violation = $result->fetch_assoc();

    if (!$violation) {
        throw new Exception('Không tìm thấy thông tin vi phạm');
    }

    // Insert payment record
    $sql = "INSERT INTO payments (
        violation_id, amount, payment_method, status, 
        payer_name, payer_phone, payer_email, payment_date, note
    ) VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "idssssss",
        $violation_id,
        $violation['fine'],
        $payment_method,
        $payer_name,
        $payer_phone,
        $payer_email,
        $payment_date,
        $note
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Không thể lưu thông tin thanh toán');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}