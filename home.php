<?php

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Xác định chế độ xem: day, month, all
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'day';

if ($mode === 'month') {
    $categories = get_top_violation_categories_by_month($conn);
    $title = "Top 4 hành vi vi phạm nhiều nhất trong tháng này";
} elseif ($mode === 'all') {
    $categories = get_top_violation_categories($conn, 4);
    $title = "Top 4 hành vi vi phạm nhiều nhất (tất cả)";
} else {
    $categories = get_top_violation_categories_by_day($conn);
    $title = "Top 4 hành vi vi phạm nhiều nhất hôm nay";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê hành vi vi phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5 ">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="mb-0"><?php echo $title; ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-4">
                            <a href="?mode=day" class="btn btn-outline-primary mx-1 <?php if($mode==='day') echo 'active'; ?>">Hôm nay</a>
                            <a href="?mode=month" class="btn btn-outline-primary mx-1 <?php if($mode==='month') echo 'active'; ?>">Tháng này</a>
                            <a href="?mode=all" class="btn btn-outline-primary mx-1 <?php if($mode==='all') echo 'active'; ?>">Tất cả</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 60px;">STT</th>
                                        <th>Hành vi vi phạm</th>
                                        <th style="width: 180px;">Số lần vi phạm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="3">Không có dữ liệu.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $i => $cat): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td class="text-start"><?php echo htmlspecialchars($cat['description']); ?></td>
                                                <td>
                                                    <span class="badge bg-info fs-6"><?php echo $cat['count']; ?></span>
                                                    <div class="progress mt-2" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: <?php echo min(100, $cat['count'] * 10); ?>%"
                                                            aria-valuenow="<?php echo $cat['count']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card mt-4 shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Tin tức mới nhất</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php
                            // Lấy 4 tin tức mới nhất từ CSDL
                            $sql = "SELECT * FROM news ORDER BY created_at DESC LIMIT 4";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($news = $result->fetch_assoc()) {
                                    ?>
                                    <div class="list-group-item">
                                        <div class="row g-0">
                                            <div class="col-md-3">
                                                <img src="<?php echo htmlspecialchars($news['image']); ?>" 
                                                     class="img-fluid rounded" 
                                                     alt="<?php echo htmlspecialchars($news['title']); ?>"
                                                     style="max-height: 120px; object-fit: cover;">
                                            </div>
                                            <div class="col-md-9">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" 
                                                           class="text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($news['title']); ?>
                                                        </a>
                                                    </h5>
                                                    <p class="card-text text-muted small">
                                                        <i class="bi bi-calendar"></i> 
                                                        <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                                                    </p>
                                                    <p class="card-text">
                                                        <?php 
                                                        echo strlen($news['description']) > 150 ? 
                                                             substr($news['description'], 0, 150) . '...' : 
                                                             $news['description']; 
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="list-group-item">Chưa có tin tức nào.</div>';
                            }
                            ?>
                        </div>
                        <div class="card-footer text-end bg-light">
                            <a href="news_detail.php" class="btn btn-primary btn-sm">
                                Xem tất cả tin tức <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>