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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            // Delete news
            $news_id = (int)$_POST['news_id'];
            
            // Get current image
            $stmt = $conn->prepare("SELECT image FROM news WHERE id = ?");
            $stmt->bind_param("i", $news_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Delete image file
                if (file_exists('../' . $row['image'])) {
                    unlink('../' . $row['image']);
                }
            }
            
            // Delete record
            $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
            $stmt->bind_param("i", $news_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Không thể xóa tin tức');
            }
            exit;
        }

        // Add/Edit news
        $news_id = isset($_POST['news_id']) ? (int)$_POST['news_id'] : null;
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $content = sanitize_input($_POST['content']);
        
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/news/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('news_') . '.' . $ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_path = 'uploads/news/' . $filename;
                
                // Delete old image if updating
                if ($news_id && !empty($_POST['current_image'])) {
                    if (file_exists('../' . $_POST['current_image'])) {
                        unlink('../' . $_POST['current_image']);
                    }
                }
            }
        } else if (!empty($_POST['current_image'])) {
            $image_path = $_POST['current_image'];
        }

        if ($news_id) {
            // Update
            $sql = "UPDATE news SET title = ?, description = ?, content = ?";
            $sql .= $image_path ? ", image = ?" : "";
            $sql .= " WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if ($image_path) {
                $stmt->bind_param("ssssi", $title, $description, $content, $image_path, $news_id);
            } else {
                $stmt->bind_param("sssi", $title, $description, $content, $news_id);
            }
        } else {
            // Insert
            $sql = "INSERT INTO news (title, description, content, image) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $title, $description, $content, $image_path);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Không thể lưu tin tức');
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}