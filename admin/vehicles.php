<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Get vehicles with pagination, search and filter
$vehicles = get_vehicles($conn, $limit, $offset, $search, $filter_type, $filter_status);
$total_vehicles = get_vehicles_count($conn, $search, $filter_type, $filter_status);
$total_pages = ceil($total_vehicles / $limit);

// Get all owners for the add/edit form
$owners = get_all_owners($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phương tiện - Hệ thống tra cứu phương tiện vi phạm</title>
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
                    <h1 class="h2">Quản lý phương tiện</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportCSV">Xuất CSV</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportPDF">Xuất PDF</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                            <i class="bi bi-plus-circle"></i> Thêm phương tiện
                        </button>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="" method="get" id="searchForm">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Tìm kiếm biển số, hãng xe..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <select class="form-select me-2" style="width: auto;" id="typeFilter" name="type">
                                <option value="">Tất cả loại xe</option>
                                <option value="Car" <?php echo $filter_type == 'Car' ? 'selected' : ''; ?>>Ô tô</option>
                                <option value="Motorcycle" <?php echo $filter_type == 'Motorcycle' ? 'selected' : ''; ?>>Xe máy</option>
                            </select>
                            <select class="form-select" style="width: auto;" id="statusFilter" name="status">
                                <option value="">Tất cả trạng thái</option>
                                <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Đã nộp phạt</option>
                                <option value="unpaid" <?php echo $filter_status == 'unpaid' ? 'selected' : ''; ?>>Chưa nộp phạt</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Vehicles Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Biển số</th>
                                <th>Loại xe</th>
                                <th>Hãng xe</th>
                                <th>Mẫu xe</th>
                                <th>Chủ sở hữu</th>
                                <th>Số vi phạm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="vehiclesTableBody">
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php 
                                    $owner = get_owner_by_id($conn, $vehicle['owner_id']);
                                    $violations_count = get_violations_count_by_vehicle($conn, $vehicle['vehicle_id']);
                                    $unpaid_count = get_unpaid_violations_count_by_vehicle($conn, $vehicle['vehicle_id']);
                                    
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    if ($violations_count == 0) {
                                        $status_class = 'bg-success';
                                        $status_text = 'Không vi phạm';
                                    } else if ($unpaid_count == 0) {
                                        $status_class = 'bg-info';
                                        $status_text = 'Đã nộp phạt';
                                    } else {
                                        $status_class = 'bg-warning text-dark';
                                        $status_text = 'Chưa nộp phạt';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $vehicle['vehicle_id']; ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                                    <td><?php echo $vehicle['type'] == 'Car' ? 'Ô tô' : 'Xe máy'; ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                    <td><?php echo $violations_count; ?></td>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info view-vehicle" data-id="<?php echo $vehicle['vehicle_id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary edit-vehicle" data-id="<?php echo $vehicle['vehicle_id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-vehicle" data-id="<?php echo $vehicle['vehicle_id']; ?>">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>">Trước</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>">Tiếp</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addVehicleForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm phương tiện mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="vehicleMessage"></div>
                        <div class="mb-3">
                            <label for="license_plate" class="form-label">Biển số xe *</label>
                            <input type="text" class="form-control" id="license_plate" name="license_plate" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Loại xe *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="Car">Ô tô</option>
                                <option value="Motorcycle">Xe máy</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="brand" class="form-label">Hãng xe *</label>
                            <input type="text" class="form-control" id="brand" name="brand" required>
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label">Mẫu xe *</label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                        <div class="mb-3">
                            <label for="color" class="form-label">Màu sắc *</label>
                            <input type="text" class="form-control" id="color" name="color" required>
                        </div>
                        <div class="mb-3">
                            <label for="owner_search" class="form-label">Tìm chủ sở hữu *</label>
                            <input type="text" class="form-control" id="owner_search" placeholder="Nhập tên hoặc số điện thoại...">
                            <input type="hidden" name="owner_id" id="owner_id" required>
                            <div id="owner_search_results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">
                            </div>
                            <div id="selected_owner_info" class="mt-2"></div>
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
    
    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editVehicleForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Thay đổi chủ sở hữu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editVehicleMessage"></div>
                        
                        <!-- Thông tin xe - Chỉ readonly -->
                        <div class="mb-3">
                            <label class="form-label">Biển số xe</label>
                            <input type="text" class="form-control" id="edit_license_plate" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Loại xe</label>
                            <input type="text" class="form-control" id="edit_type" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thông tin xe</label>
                            <input type="text" class="form-control" id="edit_info" readonly>
                        </div>

                        <!-- Chọn chủ sở hữu mới -->
                        <div class="mb-3">
                            <label class="form-label">Tìm chủ sở hữu mới *</label>
                            <input type="text" class="form-control" id="edit_owner_search" placeholder="Nhập tên hoặc số điện thoại...">
                            <input type="hidden" name="owner_id" id="edit_owner_id" required>
                            <div id="edit_owner_search_results" class="list-group mt-2"></div>
                            <div id="edit_selected_owner_info" class="mt-2"></div>
                        </div>

                        <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                        <input type="hidden" name="action" value="edit">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Vehicle Modal -->
    <div class="modal fade" id="viewVehicleModal" tabindex="-1" aria-labelledby="viewVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewVehicleModalLabel">Chi tiết phương tiện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin phương tiện</h5>
                            <table class="table">
                                <tr>
                                    <th>ID:</th>
                                    <td id="view_vehicle_id"></td>
                                </tr>
                                <tr>
                                    <th>Biển số:</th>
                                    <td id="view_license_plate"></td>
                                </tr>
                                <tr>
                                    <th>Loại xe:</th>
                                    <td id="view_type"></td>
                                </tr>
                                <tr>
                                    <th>Hãng xe:</th>
                                    <td id="view_brand"></td>
                                </tr>
                                <tr>
                                    <th>Mẫu xe:</th>
                                    <td id="view_model"></td>
                                </tr>
                                <tr>
                                    <th>Màu sắc:</th>
                                    <td id="view_color"></td>
                                </tr>
                                <tr>
                                    <th>Ngày tạo:</th>
                                    <td id="view_created_at"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin chủ sở hữu</h5>
                            <table class="table">
                                <tr>
                                    <th>Tên:</th>
                                    <td id="view_owner_name"></td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td id="view_owner_phone"></td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ:</th>
                                    <td id="view_owner_address"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Danh sách vi phạm</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Mô tả</th>
                                    <th>Số tiền</th>
                                    <th>Ngày vi phạm</th>
                                    <th>Địa điểm</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="view_violations">
                                <!-- Violations will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="exportVehicleJSON">Xuất JSON</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteVehicleModal" tabindex="-1" aria-labelledby="deleteVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteVehicleModalLabel">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa phương tiện này? Hành động này không thể hoàn tác.</p>
                    <p>Biển số: <strong id="delete_license_plate"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <form id="deleteVehicleForm" action="process_vehicle.php" method="post">
                        <input type="hidden" name="vehicle_id" id="delete_vehicle_id">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Filter change event
            $('#typeFilter, #statusFilter').change(function() {
                const type = $('#typeFilter').val();
                const status = $('#statusFilter').val();
                const search = $('input[name="search"]').val();
                window.location.href = `?search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}&status=${encodeURIComponent(status)}`;
            });

            // Sử dụng .on() thay vì .click() cho các nút thao tác
            $(document).on('click', '.edit-vehicle', function() {
                const vehicleId = $(this).data('id');
                $.get('get_vehicle_details.php', {id: vehicleId}, function(data) {
                    // Hiển thị thông tin xe (readonly)
                    $('#edit_vehicle_id').val(data.vehicle.vehicle_id);
                    $('#edit_license_plate').val(data.vehicle.license_plate);
                    $('#edit_type').val(data.vehicle.type === 'Car' ? 'Ô tô' : 'Xe máy');
                    $('#edit_info').val(`${data.vehicle.brand} ${data.vehicle.model} - ${data.vehicle.color}`);
                    
                    // Hiển thị thông tin chủ sở hữu hiện tại
                    $('#edit_selected_owner_info').html(`

                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Chủ sở hữu hiện tại:</h6>
                                <p class="card-text">
                                    <strong>${data.owner.name}</strong><br>
                                    SĐT: ${data.owner.phone}<br>
                                    Địa chỉ: ${data.owner.address}
                                </p>
                            </div>
                        </div>
                    `);
                    
                    $('#edit_owner_id').val(data.owner.owner_id);
                    $('#editVehicleModal').modal('show');
                });
            });

            $(document).on('click', '.view-vehicle', function() {
                const vehicleId = $(this).data('id');
                $.ajax({
                    url: 'get_vehicle_details.php',
                    type: 'GET',
                    data: { id: vehicleId },
                    dataType: 'json',
                    success: function(data) {
                        // Fill vehicle info
                        $('#view_vehicle_id').text(data.vehicle.vehicle_id);
                        $('#view_license_plate').text(data.vehicle.license_plate);
                        $('#view_type').text(data.vehicle.type === 'Car' ? 'Ô tô' : 'Xe máy');
                        $('#view_brand').text(data.vehicle.brand);
                        $('#view_model').text(data.vehicle.model);
                        $('#view_color').text(data.vehicle.color);
                        $('#view_created_at').text(new Date(data.vehicle.created_at).toLocaleDateString('vi-VN'));

                        // Fill owner info
                        $('#view_owner_name').text(data.owner.name);
                        $('#view_owner_phone').text(data.owner.phone);
                        $('#view_owner_address').text(data.owner.address);

                        // Fill violations
                        let violationsHtml = '';
                        if (data.violations.length > 0) {
                            data.violations.forEach(function(violation) {
                                let statusClass = '';
                                let statusText = '';
                                if (!violation.payment) {
                                    statusClass = 'bg-warning text-dark';
                                    statusText = 'Chưa thanh toán';
                                } else if (violation.payment.status === 'Pending') {
                                    statusClass = 'bg-info text-white';
                                    statusText = 'Đang xử lý';
                                } else if (violation.payment.status === 'Completed') {
                                    statusClass = 'bg-success text-white';
                                    statusText = 'Đã thanh toán';
                                } else {
                                    statusClass = 'bg-danger text-white';
                                    statusText = 'Thanh toán thất bại';
                                }
                                violationsHtml += `
                                    <tr>
                                        <td>${violation.violation_id}</td>
                                        <td>${violation.description}</td>
                                        <td>${Number(violation.fine).toLocaleString('vi-VN')} VNĐ</td>
                                        <td>${new Date(violation.violation_date).toLocaleDateString('vi-VN')}</td>
                                        <td>${violation.location}</td>
                                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                                    </tr>
                                `;
                            });
                        } else {
                            violationsHtml = '<tr><td colspan="6" class="text-center">Không có vi phạm nào</td></tr>';
                        }
                        $('#view_violations').html(violationsHtml);

                        // Store vehicle data for JSON export
                        $('#exportVehicleJSON').data('vehicle', data);

                        $('#viewVehicleModal').modal('show');
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi tải dữ liệu phương tiện.');
                    }
                });
            });

            $(document).on('click', '.delete-vehicle', function() {
                const vehicleId = $(this).data('id');
                const licensePlate = $(this).closest('tr').find('td:eq(1)').text();
                $('#delete_vehicle_id').val(vehicleId);
                $('#delete_license_plate').text(licensePlate);
                $('#deleteVehicleModal').modal('show');
            });

            // Export vehicle as JSON
            $('#exportVehicleJSON').click(function() {
                const data = $(this).data('vehicle');
                const jsonString = JSON.stringify(data, null, 2);
                const blob = new Blob([jsonString], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `vehicle_${data.vehicle.license_plate.replace(/\s+/g, '_')}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });

            // Export all vehicles as CSV
            $('#exportCSV').click(function() {
                window.location.href = 'export.php?format=csv&' + $('#searchForm').serialize();
            });

            // Export all vehicles as PDF
            $('#exportPDF').click(function() {
                window.location.href = 'export.php?format=pdf&' + $('#searchForm').serialize();
            });

            // Validate biển số xe khi submit form thêm phương tiện
            $('#addVehicleForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=add';

                // Validate biển số xe
                const plate = $('#license_plate').val().trim();
                const plateRegex = /^([0-9]{2}[A-Z]-[0-9]{3,4}\.[0-9]{2}|[0-9]{2}[A-Z][0-9]-[0-9]{4,5})$/i;
                if (!plateRegex.test(plate)) {
                    $('#vehicleMessage').html('<div class="alert alert-danger">Biển số xe không đúng định dạng Việt Nam!</div>');
                    return false;
                }

                // Check if owner is selected
                if (!$('#owner_id').val()) {
                    alert('Vui lòng chọn chủ sở hữu!');
                    $('#owner_search').focus();
                    e.preventDefault();
                    return false;
                }

                $.ajax({
                    url: 'process_vehicle.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#vehicleMessage').html('<div class="alert alert-success">' + res.message + '</div>');
                            setTimeout(() => { location.reload(); }, 1200);
                        } else {
                            $('#vehicleMessage').html('<div class="alert alert-danger">' + res.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#vehicleMessage').html('<div class="alert alert-danger">Có lỗi xảy ra, vui lòng thử lại.</div>');
                    }
                });
            });

            // Reset form và thông báo khi mở modal
            $('#addVehicleModal').on('show.bs.modal', function () {
                $('#vehicleMessage').html('');
                $('#addVehicleForm')[0].reset();
            });

            // Thêm vào phần script của vehicles.php
            let searchTimeout = null;

            $('#owner_search').on('input', function() {
                const searchText = $(this).val().trim();
                
                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Clear previous results if search is empty
                if (searchText.length < 2) {
                    $('#owner_search_results').empty();
                    return;
                }
                
                // Set new timeout to prevent too many requests
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: 'search_owners.php',
                        type: 'GET',
                        data: { q: searchText },
                        success: function(owners) {
                            let html = '';
                            owners.forEach(function(owner) {
                                html += `
                                    <div class="list-group-item owner-result" 
                                         data-id="${owner.id}"
                                         data-name="${owner.name}"
                                         data-phone="${owner.phone}"
                                         data-address="${owner.address}">
                                        <div class="fw-bold">${owner.name}</div>
                                        <small>${owner.phone}</small>
                                    </div>
                                `;
                            });
                            $('#owner_search_results').html(html);
                        },
                        error: function() {
                            $('#owner_search_results').html('<div class="list-group-item text-danger">Có lỗi xảy ra</div>');
                        }
                    });
                }, 300); // Delay 300ms
            });

            // Handle owner selection
            $(document).on('click', '.owner-result', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const phone = $(this).data('phone');
                const address = $(this).data('address');
                
                // Set hidden input value
                $('#owner_id').val(id);
                
                // Clear search and results
                $('#owner_search').val('');
                $('#owner_search_results').empty();
                
                // Show selected owner info
                $('#selected_owner_info').html(`
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Chủ sở hữu đã chọn:</h6>
                            <p class="card-text">
                                <strong>${name}</strong><br>
                                SĐT: ${phone}<br>
                                Địa chỉ: ${address}
                            </p>
                        </div>
                    </div>
                `);
            });

            // Clear results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#owner_search, #owner_search_results').length) {
                    $('#owner_search_results').empty();
                }
            });

            // Reset owner selection when opening add modal
            $('#addVehicleModal').on('show.bs.modal', function() {
                $('#owner_id').val('');
                $('#owner_search').val('');
                $('#owner_search_results').empty();
                $('#selected_owner_info').empty();
            });

            // Xử lý tìm kiếm chủ sở hữu khi edit
            let editSearchTimeout = null;
            $('#edit_owner_search').on('input', function() {
                const searchText = $(this).val().trim();
                
                if (editSearchTimeout) {
                    clearTimeout(editSearchTimeout);
                }
                
                if (searchText.length < 2) {
                    $('#edit_owner_search_results').empty();
                    return;
                }
                
                editSearchTimeout = setTimeout(function() {
                    $.get('search_owners.php', {q: searchText}, function(owners) {
                        let html = '';
                        owners.forEach(function(owner) {
                            html += `
                                <div class="list-group-item owner-result" 
                                     data-id="${owner.id}"
                                     data-name="${owner.name}"
                                     data-phone="${owner.phone}"
                                     data-address="${owner.address}">
                                    <div class="fw-bold">${owner.name}</div>
                                    <small>${owner.phone}</small>
                                </div>
                            `;
                        });
                        $('#edit_owner_search_results').html(html);
                    });
                }, 300);
            });

            // Xử lý chọn chủ sở hữu mới
            $(document).on('click', '#edit_owner_search_results .owner-result', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const phone = $(this).data('phone');
                const address = $(this).data('address');
                
                $('#edit_owner_id').val(id);
                $('#edit_owner_search').val('');
                $('#edit_owner_search_results').empty();
                
                $('#edit_selected_owner_info').html(`
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Chủ sở hữu mới:</h6>
                            <p class="card-text">
                                <strong>${name}</strong><br>
                                SĐT: ${phone}<br>
                                Địa chỉ: ${address}
                            </p>
                        </div>
                    </div>
                `);
            });

            // Submit form edit
            $('#editVehicleForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $.ajax({
                    url: 'process_vehicle.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#editVehicleMessage').html('<div class="alert alert-success">' + res.message + '</div>');
                            setTimeout(() => { location.reload(); }, 1200);
                        } else {
                            $('#editVehicleMessage').html('<div class="alert alert-danger">' + res.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#editVehicleMessage').html('<div class="alert alert-danger">Có lỗi xảy ra, vui lòng thử lại.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>