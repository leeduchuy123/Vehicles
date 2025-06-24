<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);

    if (!preg_match('/^(0|\+84)[1-9][0-9]{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ (phải là số Việt Nam, VD: 0987654321 hoặc +84987654321).']);
        exit;
    }

    $stmt = $conn->prepare("SELECT owner_id FROM owners WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại!']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO owners (name, phone, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $address);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm chủ sở hữu thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
    }
    exit;
}

if ($action === 'edit') {
    $owner_id = (int)$_POST['owner_id'];
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);

    if (!preg_match('/^(0|\+84)[1-9][0-9]{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ (phải là số Việt Nam, VD: 0987654321 hoặc +84987654321).']);
        exit;
    }

    // Không cho phép trùng phone với owner khác
    $stmt = $conn->prepare("SELECT owner_id FROM owners WHERE phone = ? AND owner_id != ?");
    $stmt->bind_param("si", $phone, $owner_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại!']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE owners SET name = ?, phone = ?, address = ? WHERE owner_id = ?");
    $stmt->bind_param("sssi", $name, $phone, $address, $owner_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
    }
    exit;
}

if ($action === 'delete') {
    $owner_id = (int)$_POST['owner_id'];
    $stmt = $conn->prepare("DELETE FROM owners WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa chủ sở hữu!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
exit;
?>