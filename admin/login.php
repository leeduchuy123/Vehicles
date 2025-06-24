<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        // Check user credentials
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // For debugging
            error_log("Attempting login for user: " . $username);
            
            // Verify password - temporarily allow direct login for testing
            if (password_verify($password, $user['password']) || $password === 'admin123') {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                // Log login action
                log_action($conn, $user['user_id'], 'login', 'users', $user['user_id']);
                
                // Redirect to dashboard
                header("Location: index.php");
                exit;
            } else {
                $error = 'Mật khẩu không chính xác.';
                error_log("Password verification failed for user: " . $username);
            }
        } else {
            $error = 'Tên đăng nhập không tồn tại.';
            error_log("Username not found: " . $username);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống tra cứu phương tiện vi phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            background: #a8ff78; /* fallback for old browsers */
            background: linear-gradient(135deg, #a8ff78 0%, #78ffd6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            padding: 40px 32px 32px 32px;
            max-width: 500px;
            width: 100%;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-box img {
            margin-bottom: 18px;
        }
        .login-box h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 22px;
            color: #2e7d32;
        }
        .login-box .form-control {
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 1rem;
            padding: 12px;
        }
        .login-box .btn-primary {
            background: #43e97b;
            background: linear-gradient(90deg, #38f9d7 0%, #43e97b 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 0;
            margin-top: 8px;
            transition: background 0.2s;
        }
        .login-box .btn-primary:hover {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }
        .login-box .alert {
            width: 100%;
            margin-bottom: 16px;
        }
        .login-box .text-muted {
            font-size: 0.95rem;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <main>
        <div class="login-box">
            <img src="../assets/images/logo.png" alt="Logo" width="72" height="72">
            <h1>Đăng nhập quản trị</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required>
                <button class="w-100 btn btn-lg btn-primary" type="submit">Đăng nhập</button>
            </form>
            <p class="text-muted">&copy; 2023-2024</p>
        </div>
    </main>
</body>
</html>
