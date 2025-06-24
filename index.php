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
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Tra cứu vi phạm giao thông</h1>
                    <p class="lead mb-4">Tra cứu nhanh chóng, thanh toán dễ dàng, thông tin minh bạch</p>
                    <form id="searchForm" method="post" action="search_result.php" class="search-form">
                        <div class="input-group input-group-lg mb-3">
                            <input type="text" class="form-control" id="license_plate" name="license_plate" 
                                placeholder="Nhập biển số xe (VD: 30A-12345)" required>
                            <button type="submit" class="btn btn-light">
                                <i class="bi bi-search"></i> Tra cứu
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6 d-none d-lg-block d-flex justify-content-center align-items-center"> 
                    <img src="assets/images/traffic-illustration.png" alt="Traffic Illustration" class="img-fluid" style="max-width: 50%">
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                            <i class="bi bi-search fs-4"></i>
                        </div>
                        <h3 class="h5">Tra cứu nhanh chóng</h3>
                        <p class="text-muted mb-0">Tìm kiếm thông tin vi phạm chỉ với biển số xe</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle mb-3">
                            <i class="bi bi-qr-code fs-4"></i>
                        </div>
                        <h3 class="h5">Thanh toán QR</h3>
                        <p class="text-muted mb-0">Thanh toán nhanh chóng qua mã QR</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle mb-3">
                            <i class="bi bi-telephone fs-4"></i>
                        </div>
                        <h3 class="h5">Hỗ trợ 24/7</h3>
                        <p class="text-muted mb-0">Hotline hỗ trợ: <strong>1900 xxxx</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- News Section -->
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h2 class="h3 mb-4 text-center">Tin tức mới nhất</h2>
            </div>
        </div>
        <div class="row g-4">
            <?php
            $sql = "SELECT * FROM news ORDER BY created_at DESC LIMIT 5";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($news = $result->fetch_assoc()) {
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="<?php echo htmlspecialchars($news['image']); ?>" 
                                class="card-img-top" 
                                alt="<?php echo htmlspecialchars($news['title']); ?>"
                                style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                                    </small>
                                </div>
                                <h5 class="card-title">
                                    <a href="news_detail.php?id=<?php echo $news['id']; ?>" 
                                        class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted">
                                    <?php 
                                    echo strlen($news['description']) > 100 ? 
                                        substr($news['description'], 0, 100) . '...' : 
                                        $news['description']; 
                                    ?>
                                </p>
                                <a href="news_detail.php?id=<?php echo $news['id']; ?>" 
                                    class="btn btn-outline-primary btn-sm">Đọc thêm</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12 text-center"><p>Chưa có tin tức nào.</p></div>';
            }
            ?>
        </div>
    </div>

    <!-- Quick Guide Section -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-4 text-center">Hướng dẫn tra cứu</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="feature-icon bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-1-circle-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="h6 mb-2">Nhập biển số</h5>
                                        <p class="text-muted small mb-0">Nhập đầy đủ biển số xe bao gồm cả ký tự đặc biệt</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="feature-icon bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-2-circle-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="h6 mb-2">Xem thông tin</h5>
                                        <p class="text-muted small mb-0">Hệ thống hiển thị thông tin phương tiện và vi phạm</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="feature-icon bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-3-circle-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="h6 mb-2">Thanh toán</h5>
                                        <p class="text-muted small mb-0">Thanh toán trực tuyến qua mã QR</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="feature-icon bg-light rounded-circle p-2 me-3">
                                        <i class="bi bi-telephone-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="h6 mb-2">Hỗ trợ</h5>
                                        <p class="text-muted small mb-0">Liên hệ hotline: <strong>1900 xxxx</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Add Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 80px 0;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .search-form .form-control {
            border-radius: 50px 0 0 50px;
            border: none;
            padding-left: 25px;
        }
        .search-form .btn {
            border-radius: 0 50px 50px 0;
            padding-left: 30px;
            padding-right: 30px;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
