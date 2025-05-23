<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get news ID from URL or get latest news if no ID provided
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$news_id) {
    // Get the latest news ID
    $latest = $conn->query("SELECT id FROM news ORDER BY created_at DESC LIMIT 1");
    if ($latest && $row = $latest->fetch_assoc()) {
        $news_id = $row['id'];
    } else {
        // Redirect to home if no news exists
        header('Location: index.php');
        exit;
    }
}

// Get current news
$sql = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $news_id);
$stmt->execute();
$current_news = $stmt->get_result()->fetch_assoc();

if (!$current_news) {
    header('Location: index.php');
    exit;
}

// Get all news for sidebar
$sql = "SELECT id, title, image, created_at FROM news ORDER BY created_at DESC";
$result = $conn->query($sql);
$all_news = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_news['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .news-sidebar .news-item {
            transition: all 0.3s ease;
        }
        .news-sidebar .news-item:hover {
            background-color: #f8f9fa;
        }
        .news-content img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar - All News -->
            <div class="col-md-4">
                <div class="card shadow news-sidebar">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Danh sách tin tức</h5>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 800px; overflow-y: auto;">
                        <?php foreach ($all_news as $news): ?>
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" 
                           class="list-group-item list-group-item-action news-item <?php echo ($news['id'] == $news_id) ? 'active' : ''; ?>">
                            <div class="row g-0 align-items-center">
                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($news['image']); ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>"
                                         style="height: 60px; object-fit: cover;">
                                </div>
                                <div class="col-8 ps-3">
                                    <h6 class="mb-1" style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content - News Detail -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-3">
                            <?php echo htmlspecialchars($current_news['title']); ?>
                        </h2>
                        
                        <div class="text-muted mb-4">
                            <i class="bi bi-calendar"></i>
                            <?php echo date('d/m/Y H:i', strtotime($current_news['created_at'])); ?>
                        </div>

                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($current_news['image']); ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($current_news['title']); ?>">
                        </div>

                        <div class="news-description mb-4">
                            <strong class="d-block mb-2">Tóm tắt:</strong>
                            <?php echo nl2br(htmlspecialchars($current_news['description'])); ?>
                        </div>

                        <div class="news-content">
                            <?php echo $current_news['content']; ?>
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