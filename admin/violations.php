<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total records for pagination
$total_sql = "SELECT COUNT(*) as total FROM violations v
              LEFT JOIN payments p ON v.violation_id = p.violation_id
              LEFT JOIN vehicles veh ON v.vehicle_id = veh.vehicle_id";
$total_result = $conn->query($total_sql);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Modified query with LIMIT and OFFSET
$sql = "SELECT v.*, p.*, veh.license_plate 
        FROM violations v
        LEFT JOIN payments p ON v.violation_id = p.violation_id
        LEFT JOIN vehicles veh ON v.vehicle_id = veh.vehicle_id
        ORDER BY v.violation_date DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$violations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý vi phạm - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Quản lý vi phạm</h1>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Biển số xe</th>
                                <th>Ngày vi phạm</th>
                                <th>Mô tả</th>
                                <th>Số tiền</th>
                                <th>Người nộp</th>
                                <th>PT thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violations as $violation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($violation['violation_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($violation['description']); ?></td>
                                    <td><?php echo number_format($violation['fine'], 0, ',', '.'); ?> VNĐ</td>
                                    <td><?php echo htmlspecialchars($violation['payer_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($violation['payment_method'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        if (!isset($violation['status'])) {
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = 'Chưa thanh toán';
                                        } else {
                                            switch ($violation['status']) {
                                                case 'Pending':
                                                    $status_class = 'bg-info text-white';
                                                    $status_text = 'Đang xử lý';
                                                    break;
                                                case 'Completed':
                                                    $status_class = 'bg-success text-white';
                                                    $status_text = 'Đã thanh toán';
                                                    break;
                                                case 'Failed':
                                                    $status_class = 'bg-danger text-white';
                                                    $status_text = 'Thất bại';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($violation['status']) && $violation['status'] == 'Pending'): ?>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-success confirm-payment"
                                                        data-id="<?php echo $violation['payment_id']; ?>">
                                                    <i class="bi bi-check-lg"></i> Xác nhận
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger reject-payment"
                                                        data-id="<?php echo $violation['payment_id']; ?>">
                                                    <i class="bi bi-x-lg"></i> Từ chối
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- table-responsive end -->
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <!-- Previous page -->
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next page -->
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div> <!-- card end -->
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Confirm payment
            $('.confirm-payment').click(function() {
                if (confirm('Xác nhận thanh toán này?')) {
                    const paymentId = $(this).data('id');
                    updatePaymentStatus(paymentId, 'Completed');
                }
            });

            // Reject payment
            $('.reject-payment').click(function() {
                if (confirm('Từ chối thanh toán này?')) {
                    const paymentId = $(this).data('id');
                    updatePaymentStatus(paymentId, 'Failed');
                }
            });

            function updatePaymentStatus(paymentId, status) {
                $.ajax({
                    url: 'process_payment_status.php',
                    type: 'POST',
                    data: {
                        payment_id: paymentId,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Cập nhật trạng thái thành công!');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra, vui lòng thử lại.');
                    }
                });
            }
        });
    </script>
</body>
</html>