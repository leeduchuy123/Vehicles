<?php
require_once '../config/database.php';

// Check if the users table exists and has data
$sql = "SELECT * FROM users LIMIT 1";
$result = $conn->query($sql);

if ($result === false) {
    echo "Error: The users table does not exist. " . $conn->error;
} else {
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Found user: " . $user['username'] . "<br>";
        
        // Test password verification
        $test_password = 'admin123';
        $stored_hash = $user['password'];
        
        echo "Stored password hash: " . $stored_hash . "<br>";
        
        if (password_verify($test_password, $stored_hash)) {
            echo "Password verification successful!";
        } else {
            echo "Password verification failed!<br>";
            
            // Create a new hash for comparison
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "New hash for 'admin123': " . $new_hash . "<br>";
            
            // Update the password in the database
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $new_hash, $user['user_id']);
            
            if ($stmt->execute()) {
                echo "Password updated successfully. Please try logging in again.";
            } else {
                echo "Failed to update password: " . $stmt->error;
            }
        }
    } else {
        echo "No users found in the database. Creating admin user...<br>";
        
        // Create admin user
        $username = 'admin';
        $password = 'admin123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashed_password);
        
        if ($stmt->execute()) {
            echo "Admin user created successfully. Username: admin, Password: admin123";
        } else {
            echo "Failed to create admin user: " . $stmt->error;
        }
    }
}
?>
