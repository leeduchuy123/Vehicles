<header class="bg-danger text-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center">
                <img src="assets/images/logo.png" alt="Logo" height="40" class="me-2">
                <h1 class="h5 mb-0">Hệ thống tra cứu phương tiện vi phạm</h1>
            </div>
            <ul class="nav">
                <li class="nav-item"><a href="home.php" class="nav-link px-2 text-white">Trang chủ</a></li>
                <li class="nav-item"><a href="index.php" class="nav-link px-2 text-white">Tra Cứu Vi Phạm</a></li>
                <li class="nav-item"><a href="news_detail.php" class="nav-link px-2 text-white">Tin Tức </a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a href="admin/index.php" class="nav-link px-2 text-white">Quản trị</a></li>
                    <li class="nav-item"><a href="admin/logout.php" class="nav-link px-2 text-white">Đăng xuất</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="admin/login.php" class="nav-link px-2 text-white">Đăng nhập</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>
