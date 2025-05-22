<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    
    // Add new vehicle
    if ($action === 'add') {
        $license_plate = strtoupper(trim($_POST['license_plate']));

        // Kiểm tra định dạng biển số xe Việt Nam
        if (!preg_match('/^([0-9]{2}[A-Z]-[0-9]{3,4}\.[0-9]{2}|[0-9]{2}[A-Z][0-9]-[0-9]{4,5})$/i', $license_plate)) {
            $_SESSION['error'] = 'Biển số xe không đúng định dạng Việt Nam!';
            header('Location: vehicles.php');
            exit;
        }

        // Kiểm tra trùng biển số xe
        $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE license_plate = ?");
        $stmt->bind_param("s", $license_plate);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = 'Biển số xe đã tồn tại!';
            header('Location: vehicles.php');
            exit;
        }

        $type = sanitize_input($_POST['type']);
        $brand = sanitize_input($_POST['brand']);
        $model = sanitize_input($_POST['model']);
        $color = sanitize_input($_POST['color']);
        $owner_id = (int)$_POST['owner_id'];
        
        // Insert new vehicle
        $sql = "INSERT INTO vehicles (owner_id, license_plate, type, brand, model, color, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $owner_id, $license_plate, $type, $brand, $model, $color);
        
        if ($stmt->execute()) {
            $vehicle_id = $conn->insert_id;
            
            // Log action
            log_action($conn, $_SESSION['user_id'], 'add', 'vehicles', $vehicle_id);
            
            $_SESSION['success'] = "Thêm phương tiện mới thành công.";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
        }
    }
    
    // Edit vehicle
    else if ($action === 'edit') {
        $vehicle_id = (int)$_POST['vehicle_id'];
        $license_plate = strtoupper(trim($_POST['license_plate']));

        // Kiểm tra định dạng biển số xe Việt Nam
        if (!preg_match('/^([0-9]{2}[A-Z]-[0-9]{3,4}\.[0-9]{2}|[0-9]{2}[A-Z][0-9]-[0-9]{4,5})$/i', $license_plate)) {
            $_SESSION['error'] = 'Biển số xe không đúng định dạng Việt Nam!';
            header('Location: vehicles.php');
            exit;
        }

        // Kiểm tra trùng biển số xe
        $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE license_plate = ?");
        $stmt->bind_param("s", $license_plate);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = 'Biển số xe đã tồn tại!';
            header('Location: vehicles.php');
            exit;
        }

        $type = sanitize_input($_POST['type']);
        $brand = sanitize_input($_POST['brand']);
        $model = sanitize_input($_POST['model']);
        $color = sanitize_input($_POST['color']);
        $owner_id = (int)$_POST['owner_id'];
        
        // Update vehicle
        $sql = "UPDATE vehicles SET owner_id = ?, license_plate = ?, type = ?, brand = ?, model = ?, color = ? 
                WHERE vehicle_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssi", $owner_id, $license_plate, $type, $brand, $model, $color, $vehicle_id);
        
        if ($stmt->execute()) {
            // Log action
            log_action($conn, $_SESSION['user_id'], 'edit', 'vehicles', $vehicle_id);
            
            $_SESSION['success'] = "Cập nhật phương tiện thành công.";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
        }
    }
    
    // Delete vehicle
    else if ($action === 'delete') {
        $vehicle_id = (int)$_POST['vehicle_id'];
        
        // Delete vehicle (cascade will delete related violations and payments)
        $sql = "DELETE FROM vehicles WHERE vehicle_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $vehicle_id);
        
        if ($stmt->execute()) {
            // Log action
            log_action($conn, $_SESSION['user_id'], 'delete', 'vehicles', $vehicle_id);
            
            $_SESSION['success'] = "Xóa phương tiện thành công.";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
        }
    }
    
    // Redirect back to vehicles page
    header("Location: vehicles.php");
    exit;
} else {
    // If not POST request, redirect to vehicles page
    header("Location: vehicles.php");
    exit;
}
?>