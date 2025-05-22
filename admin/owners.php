<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Search
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy danh sách chủ sở hữu
$sql = "SELECT * FROM owners WHERE 1=1";
$params = [];
$types = "";
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
$sql .= " ORDER BY owner_id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$owners = [];
while ($row = $result->fetch_assoc()) {
    $owners[] = $row;
}

// Đếm tổng số chủ sở hữu
$stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM owners WHERE (name LIKE ? OR phone LIKE ?)");
$searchLike = "%$search%";
$stmt2->bind_param("ss", $searchLike, $searchLike);
$stmt2->execute();
$total_owners = $stmt2->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_owners / $limit);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý chủ sở hữu - Hệ thống tra cứu phương tiện vi phạm</title>
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
                    <h1 class="h2">Quản lý chủ sở hữu</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addOwnerModal">
                            <i class="bi bi-plus-circle"></i> Thêm chủ sở hữu
                        </button>
                    </div>
                </div>
                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="" method="get" id="searchForm">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Tìm theo tên hoặc SĐT..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Owners Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tên chủ sở hữu</th>
                                <th>Số điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="ownersTableBody">
                            <?php foreach ($owners as $owner): ?>
                                <tr data-id="<?php echo $owner['owner_id']; ?>">
                                    <td><?php echo $owner['owner_id']; ?></td>
                                    <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['address']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info view-owner" data-id="<?php echo $owner['owner_id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary edit-owner" data-id="<?php echo $owner['owner_id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-owner" data-id="<?php echo $owner['owner_id']; ?>">
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
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Trước</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Tiếp</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Owner Modal -->
    <div class="modal fade" id="addOwnerModal" tabindex="-1" aria-labelledby="addOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addOwnerForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addOwnerModalLabel">Thêm chủ sở hữu mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div id="ownerMessage"></div>
                        <div class="mb-3">
                            <label class="form-label">Tên chủ sở hữu</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Owner Modal -->
    <div class="modal fade" id="editOwnerModal" tabindex="-1" aria-labelledby="editOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editOwnerForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editOwnerModalLabel">Chỉnh sửa chủ sở hữu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editOwnerMessage"></div>
                        <input type="hidden" name="owner_id" id="edit_owner_id">
                        <div class="mb-3">
                            <label class="form-label">Tên chủ sở hữu</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Owner Modal -->
    <div class="modal fade" id="deleteOwnerModal" tabindex="-1" aria-labelledby="deleteOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteOwnerForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteOwnerModalLabel">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc chắn muốn xóa chủ sở hữu này?</p>
                        <p>Tên: <strong id="delete_owner_name"></strong></p>
                        <input type="hidden" name="owner_id" id="delete_owner_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Owner Modal -->
    <div class="modal fade" id="viewOwnerModal" tabindex="-1" aria-labelledby="viewOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOwnerModalLabel">Chi tiết chủ sở hữu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <strong>Tên:</strong> <span id="owner_name"></span><br>
                        <strong>SĐT:</strong> <span id="owner_phone"></span><br>
                        <strong>Địa chỉ:</strong> <span id="owner_address"></span><br>
                    </div>
                    <hr>
                    <h5>Danh sách xe</h5>
                    <div id="owner_vehicles"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(function() {
        // Xem chi tiết chủ sở hữu
        $(document).on('click', '.view-owner', function() {
            const ownerId = $(this).data('id');
            $.get('get_owner_details.php', { id: ownerId }, function(data) {
                $('#owner_name').text(data.owner.name);
                $('#owner_phone').text(data.owner.phone);
                $('#owner_address').text(data.owner.address);

                let html = '';
                if (data.vehicles.length === 0) {
                    html = '<div>Không có xe nào.</div>';
                } else {
                    data.vehicles.forEach(function(vehicle) {
                        html += `<div class="card mb-3">
                            <div class="card-header">
                                <strong>Biển số:</strong> ${vehicle.license_plate} - <strong>Loại:</strong> ${vehicle.type}
                            </div>
                            <div class="card-body">
                                <h6>Danh sách vi phạm:</h6>`;
                        if (vehicle.violations.length === 0) {
                            html += '<div>Không có vi phạm.</div>';
                        } else {
                            html += '<ul>';
                            vehicle.violations.forEach(function(v) {
                                html += `<li>${v.description} - Ngày: ${new Date(v.violation_date).toLocaleDateString('vi-VN')}</li>`;
                            });
                            html += '</ul>';
                        }
                        html += `</div></div>`;
                    });
                }
                $('#owner_vehicles').html(html);
                $('#viewOwnerModal').modal('show');
            }, 'json');
        });

        // Thêm chủ sở hữu bằng Ajax
        $('#addOwnerForm').on('submit', function(e) {
            e.preventDefault();
            const name = $('input[name="name"]').val().trim();
            const phone = $('input[name="phone"]').val().trim();
            const address = $('input[name="address"]').val().trim();

            // Validate phone phía client
            const phoneRegex = /^(0|\+84)[1-9][0-9]{8}$/;
            if (!phoneRegex.test(phone)) {
                $('#ownerMessage').html('<div class="alert alert-danger">Số điện thoại không hợp lệ (phải là số Việt Nam, VD: 0987654321 hoặc +84987654321).</div>');
                return false;
            }

            $.ajax({
                url: 'process_owner.php',
                type: 'POST',
                data: { action: 'add', name, phone, address },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#ownerMessage').html('<div class="alert alert-success">' + res.message + '</div>');
                        setTimeout(() => { location.reload(); }, 1200);
                    } else {
                        $('#ownerMessage').html('<div class="alert alert-danger">' + res.message + '</div>');
                    }
                },
                error: function() {
                    $('#ownerMessage').html('<div class="alert alert-danger">Có lỗi xảy ra, vui lòng thử lại.</div>');
                }
            });
        });

        // Sửa chủ sở hữu
        $(document).on('click', '.edit-owner', function() {
            const ownerId = $(this).data('id');
            $.get('get_owner_details.php', { id: ownerId }, function(data) {
                $('#edit_owner_id').val(data.owner.owner_id);
                $('#edit_name').val(data.owner.name);
                $('#edit_phone').val(data.owner.phone);
                $('#edit_address').val(data.owner.address);
                $('#editOwnerMessage').html('');
                $('#editOwnerModal').modal('show');
            }, 'json');
        });

        $('#editOwnerForm').on('submit', function(e) {
            e.preventDefault();
            const owner_id = $('#edit_owner_id').val();
            const name = $('#edit_name').val().trim();
            const phone = $('#edit_phone').val().trim();
            const address = $('#edit_address').val().trim();

            const phoneRegex = /^(0|\+84)[1-9][0-9]{8}$/;
            if (!phoneRegex.test(phone)) {
                $('#editOwnerMessage').html('<div class="alert alert-danger">Số điện thoại không hợp lệ (phải là số Việt Nam, VD: 0987654321 hoặc +84987654321).</div>');
                return false;
            }

            $.ajax({
                url: 'process_owner.php',
                type: 'POST',
                data: { action: 'edit', owner_id, name, phone, address },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#editOwnerMessage').html('<div class="alert alert-success">' + res.message + '</div>');
                        setTimeout(() => { location.reload(); }, 1200);
                    } else {
                        $('#editOwnerMessage').html('<div class="alert alert-danger">' + res.message + '</div>');
                    }
                },
                error: function() {
                    $('#editOwnerMessage').html('<div class="alert alert-danger">Có lỗi xảy ra, vui lòng thử lại.</div>');
                }
            });
        });

        // Xóa chủ sở hữu
        $(document).on('click', '.delete-owner', function() {
            const ownerId = $(this).data('id');
            const ownerName = $(this).closest('tr').find('td:eq(1)').text();
            $('#delete_owner_id').val(ownerId);
            $('#delete_owner_name').text(ownerName);
            $('#deleteOwnerModal').modal('show');
        });

        $('#deleteOwnerForm').on('submit', function(e) {
            e.preventDefault();
            const owner_id = $('#delete_owner_id').val();
            $.ajax({
                url: 'process_owner.php',
                type: 'POST',
                data: { action: 'delete', owner_id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#deleteOwnerModal').modal('hide');
                        setTimeout(() => { location.reload(); }, 800);
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            });
        });

        // Reset form và thông báo khi mở modal
        $('#addOwnerModal').on('show.bs.modal', function () {
            $('#ownerMessage').html('');
            $('#addOwnerForm')[0].reset();
        });
        $('#editOwnerModal').on('show.bs.modal', function () {
            $('#editOwnerMessage').html('');
        });
    });
    </script>
</body>
</html>
