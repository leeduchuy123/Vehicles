<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_plate'])) {
    $license_plate = sanitize_input($_POST['license_plate']);
    
    // Get vehicle information
    $vehicle = get_vehicle_by_license_plate($conn, $license_plate);
    
    if (!$vehicle) {
        $_SESSION['error'] = "Không tìm thấy thông tin phương tiện với biển số: $license_plate";
        header("Location: index.php");
        exit;
    }
    
    // Get owner information
    $owner = get_owner_by_id($conn, $vehicle['owner_id']);
    
    // Get violations
    $violations = get_violations_by_vehicle_id($conn, $vehicle['vehicle_id']);
} else {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tra cứu - <?php echo htmlspecialchars($license_plate); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Kết quả tra cứu</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Thông tin phương tiện</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="license-plate-display">
                                <?php echo htmlspecialchars($vehicle['license_plate']); ?>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Loại phương tiện:</span>
                                <strong><?php echo htmlspecialchars($vehicle['type'] == 'Car' ? 'Ô tô' : 'Xe máy'); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Hãng xe:</span>
                                <strong><?php echo htmlspecialchars($vehicle['brand']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Mẫu xe:</span>
                                <strong><?php echo htmlspecialchars($vehicle['model']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Màu sắc:</span>
                                <strong><?php echo htmlspecialchars($vehicle['color']); ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Thông tin chủ sở hữu</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($owner['name']); ?></p>
                                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($owner['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($owner['address']); ?></p>
                                <p><strong>Ngày đăng ký:</strong> <?php echo date('d/m/Y', strtotime($vehicle['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Danh sách vi phạm</h4>
                        <span class="badge bg-light text-danger"><?php echo count($violations); ?> vi phạm</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($violations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ngày vi phạm</th>
                                            <th>Mô tả vi phạm</th>
                                            <th>Địa điểm</th>
                                            <th>Số tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($violations as $violation): ?>
                                            <?php 
                                                $payment = get_payment_by_violation_id($conn, $violation['violation_id']);
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                if (!$payment) {
                                                    $status_class = 'bg-warning text-dark';
                                                    $status_text = 'Chưa thanh toán';
                                                } else if ($payment['status'] == 'Pending') {
                                                    $status_class = 'bg-info text-white';
                                                    $status_text = 'Đang xử lý';
                                                } else if ($payment['status'] == 'Completed') {
                                                    $status_class = 'bg-success text-white';
                                                    $status_text = 'Đã thanh toán';
                                                } else {
                                                    $status_class = 'bg-danger text-white';
                                                    $status_text = 'Thanh toán thất bại';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($violation['violation_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($violation['description']); ?></td>
                                                <td><?php echo htmlspecialchars($violation['location']); ?></td>
                                                <td><?php echo number_format($violation['fine'], 0, ',', '.'); ?> VNĐ</td>
                                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                                <td>
                                                    <?php if (!$payment || $payment['status'] != 'Completed'): ?>
                                                        <button class="btn btn-sm btn-primary pay-button" data-bs-toggle="modal" data-bs-target="#paymentModal" data-id="<?php echo $violation['violation_id']; ?>" data-amount="<?php echo $violation['fine']; ?>">
                                                            <i class="bi bi-credit-card"></i> Thanh toán
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                            <i class="bi bi-check-circle"></i> Đã thanh toán
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i> Phương tiện này không có vi phạm nào.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="paymentModalLabel">Thanh toán vi phạm</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h4>Số tiền: <span id="paymentAmount">0</span> VNĐ</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="methodMomo" value="momo" checked>
                                <label class="form-check-label" for="methodMomo">
                                    <img src="assets/images/momo.png" alt="MoMo" height="30"> MoMo
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="methodVietQR" value="vietqr">
                                <label class="form-check-label" for="methodVietQR">
                                    <img src="assets/images/vietqr.png" alt="VietQR" height="30"> VietQR
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <div class="qr-code-container">
                            <img src="assets/images/qr-sample.png" alt="QR Code" class="img-fluid">
                        </div>
                        <p class="text-muted mt-2">Quét mã QR để thanh toán</p>
                    </div>
                    
                    <input type="hidden" id="violationId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="confirmPayment">Xác nhận đã thanh toán</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Set payment amount and violation ID when modal is opened
            $('.pay-button').click(function() {
                const violationId = $(this).data('id');
                const amount = $(this).data('amount');
                
                $('#violationId').val(violationId);
                $('#paymentAmount').text(amount.toLocaleString('vi-VN'));
            });
            
            // Handle payment confirmation
            $('#confirmPayment').click(function() {
                const violationId = $('#violationId').val();
                const paymentMethod = $('input[name="paymentMethod"]:checked').val();
                
                $.ajax({
                    url: 'process_payment.php',
                    type: 'POST',
                    data: {
                        violation_id: violationId,
                        payment_method: paymentMethod
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Thanh toán thành công! Trạng thái thanh toán sẽ được cập nhật sau khi xác nhận.');
                            location.reload();
                        } else {
                            alert('Có lỗi xảy ra: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi xử lý thanh toán.');
                    }
                });
            });
        });
    </script>
</body>
</html>
