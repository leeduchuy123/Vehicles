<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get news list
$sql = "SELECT * FROM news ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$news_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total records
$total_records = $conn->query("SELECT COUNT(*) as count FROM news")->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tin tức - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý tin tức</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewsModal">
                        <i class="bi bi-plus-lg"></i> Thêm tin tức mới
                    </button>
                </div>

                <!-- News List -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($news_list as $news): ?>
                            <tr>
                                <td><?php echo $news['id']; ?></td>
                                <td>
                                    <img src="../<?php echo htmlspecialchars($news['image']); ?>" 
                                         alt="News thumbnail" 
                                         style="height: 50px; width: 80px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($news['title']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($news['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-news" 
                                                data-id="<?php echo $news['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary edit-news"
                                                data-id="<?php echo $news['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-news"
                                                data-id="<?php echo $news['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add News Modal -->
    <div class="modal fade" id="addNewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tin tức mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addNewsForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả ngắn *</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nội dung chi tiết *</label>
                            <textarea class="form-control" name="content" rows="5" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện *</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" form="addNewsForm" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View News Modal -->
    <div class="modal fade" id="viewNewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết tin tức</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="viewNewsImage" src="" alt="News image" style="max-height: 300px;">
                    </div>
                    <h4 id="viewNewsTitle"></h4>
                    <p class="text-muted">
                        <small>Ngày đăng: <span id="viewNewsDate"></span></small>
                    </p>
                    <div class="mb-3">
                        <label class="fw-bold">Mô tả:</label>
                        <p id="viewNewsDescription"></p>
                    </div>
                    <div>
                        <label class="fw-bold">Nội dung:</label>
                        <div id="viewNewsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit News Modal -->
    <div class="modal fade" id="editNewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa tin tức</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editNewsForm" enctype="multipart/form-data">
                        <input type="hidden" name="news_id" id="edit_news_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả ngắn *</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nội dung chi tiết *</label>
                            <textarea class="form-control" name="content" id="edit_content" rows="5" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="current_image_preview" class="mt-2">
                                <img src="" alt="Current image" style="max-height: 100px">
                            </div>
                            <small class="text-muted">Chỉ chọn ảnh nếu muốn thay đổi ảnh hiện tại</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" form="editNewsForm" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add news
                $('#addNewsForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                $.ajax({
                    url: 'process_news.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Thêm tin tức thành công!');
                            $('#addNewsModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra, vui lòng thử lại.');
                    }
                });
            });


                    // Edit news - load data
            $('.edit-news').click(function() {
                const id = $(this).data('id');
                
                $.get('get_news.php', {id: id}, function(news) {
                    $('#edit_news_id').val(news.id);
                    $('#edit_title').val(news.title);
                    $('#edit_description').val(news.description);
                    $('#edit_content').val(news.content);
                    $('#current_image').val(news.image);
                    $('#current_image_preview img').attr('src', '../' + news.image);
                    $('#editNewsModal').modal('show');
                });
            });

            // Submit edit form
            $('#editNewsForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit');
                
                $.ajax({
                    url: 'process_news.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Cập nhật tin tức thành công!');
                            $('#editNewsModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    }
                });
            });

            // Delete news
            $('.delete-news').click(function() {
                if (confirm('Bạn có chắc chắn muốn xóa tin tức này?')) {
                    const id = $(this).data('id');
                    $.post('process_news.php', {
                        action: 'delete',
                        news_id: id
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    });
                }
            });

            // View news
            $('.view-news').click(function() {
                const id = $(this).data('id');
                
                $.get('get_news.php', {id: id}, function(news) {
                    $('#viewNewsImage').attr('src', '../' + news.image);
                    $('#viewNewsTitle').text(news.title);
                    $('#viewNewsDate').text(new Date(news.created_at).toLocaleString('vi-VN'));
                    $('#viewNewsDescription').text(news.description);
                    $('#viewNewsContent').html(news.content);
                    
                    $('#viewNewsModal').modal('show');
                });
            });
        });
    </script>
</body>
</html>