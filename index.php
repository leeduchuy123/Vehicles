<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu phương tiện vi phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Tra cứu phương tiện vi phạm</h3>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" method="post" action="search_result.php">
                            <div class="mb-4">
                                <label for="license_plate" class="form-label">Biển số xe</label>
                                <input type="text" class="form-control form-control-lg" id="license_plate" name="license_plate" 
                                    placeholder="Nhập biển số xe (VD: 30A-12345)" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Tra cứu</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4 shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Hướng dẫn tra cứu</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Nhập đầy đủ biển số xe bao gồm cả ký tự đặc biệt (VD: 30A-12345)</li>
                            <li class="list-group-item">Hệ thống sẽ hiển thị thông tin về phương tiện và các vi phạm (nếu có)</li>
                            <li class="list-group-item">Bạn có thể thanh toán trực tuyến các khoản phạt qua mã QR</li>
                            <li class="list-group-item">Để biết thêm thông tin, vui lòng liên hệ hotline: <strong>1900 xxxx</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
