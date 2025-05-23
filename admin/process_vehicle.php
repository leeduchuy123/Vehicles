<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    
    // Add new vehicle
    if ($action === 'add') {
        $license_plate = strtoupper(trim($_POST['license_plate']));

        // Validate license plate
        if (!preg_match('/^([0-9]{2}[A-Z]-[0-9]{3,4}\.[0-9]{2}|[0-9]{2}[A-Z][0-9]-[0-9]{4,5})$/i', $license_plate)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Biển số xe không đúng định dạng Việt Nam!'
            ]);
            exit;
        }

        // Check duplicate license plate
        $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE license_plate = ?");
        $stmt->bind_param("s", $license_plate);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Biển số xe đã tồn tại!'
            ]);
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
            
            // Log activity
            log_activity(
                $_SESSION['user_id'],
                'add',
                'vehicles',
                $vehicle_id,
                [
                    'license_plate' => $license_plate,
                    'type' => $type,
                    'brand' => $brand,
                    'model' => $model,
                    'color' => $color,
                    'owner_id' => $owner_id
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Thêm phương tiện mới thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $conn->error
            ]);
        }
        exit;
    }
    
    // Edit vehicle
    else if ($action === 'edit') {
        $vehicle_id = (int)$_POST['vehicle_id'];
        $owner_id = (int)$_POST['owner_id'];
        
        // Chỉ cập nhật owner_id
        $sql = "UPDATE vehicles SET owner_id = ? WHERE vehicle_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $owner_id, $vehicle_id);
        
        if ($stmt->execute()) {
            // log_activity(
            //     $_SESSION['user_id'],
            //     'edit',
            //     'vehicles',
            //     $vehicle_id,
            //     ['action' => 'change_owner', 'new_owner_id' => $owner_id]
            // );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cập nhật chủ sở hữu thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Có lỗi xảy ra: ' . $conn->error
            ]);
        }
        exit;
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
            // log_action($conn, $_SESSION['user_id'], 'delete', 'vehicles', $vehicle_id);
            
            echo json_encode([
                'success' => true,
                'message' => "Xóa phương tiện thành công."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Có lỗi xảy ra: " . $conn->error
            ]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>