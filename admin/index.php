<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Debug session information
error_log("User ID in session: " . $_SESSION['user_id']);
error_log("Username in session: " . $_SESSION['username']);

// Lấy năm từ query string, mặc định là năm hiện tại
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$violations_by_month = get_violations_count_by_month($conn, $selected_year);

// Get statistics for dashboard
$total_vehicles = get_total_count($conn, 'vehicles');
$total_violations = get_total_count($conn, 'violations' , $selected_year);
$total_owners = get_total_count($conn, 'owners');
$unpaid_violations = get_unpaid_violations_count($conn);

// Truyền $selected_year vào các function dưới đây
$recent_violations = get_recent_violations($conn, 5, $selected_year);
$payment_stats = get_payment_stats($conn, $selected_year);
$top_vehicles = get_top_violating_vehicles($conn, 5, $selected_year);
$top_categories_day = get_top_violation_categories_by_day($conn, null, 4, $selected_year);
$top_categories_month = get_top_violation_categories_by_month($conn, null, $selected_year, 4);
$top_categories_all = get_top_violation_categories($conn, 4, $selected_year);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Hệ thống tra cứu phương tiện vi phạm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">BÁO CÁO VÀ THỐNG KÊ</h1>
                </div>

                <div class="d-flex justify-content-end align-items-center mb-3">
                    <form method="get" class="d-flex align-items-center">
                        <label for="yearSelect" class="me-2 mb-0 fw-bold">Chọn năm:</label>
                        <select name="year" id="yearSelect" class="form-select form-select-sm me-2" style="width:auto;" onchange="this.form.submit()">
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                echo '<option value="'.$y.'"'.($selected_year == $y ? ' selected' : '').'>'.$y.'</option>';
                            }
                            ?>
                        </select>
                        <noscript><button type="submit" class="btn btn-sm btn-primary">Xem</button></noscript>
                    </form>
                </div>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Tổng số phương tiện</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_vehicles; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-car-front fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Tổng số vi phạm</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_violations; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Tổng số chủ sở hữu</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_owners; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Vi phạm chưa nộp phạt</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $unpaid_violations; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-cash-stack fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <!-- Violations Chart -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Vi phạm theo thời gian</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="violationsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Chart -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Phương thức thanh toán</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <span class="me-2">
                                        <i class="bi bi-circle-fill text-primary"></i> Online
                                    </span>
                                    <span class="me-2">
                                        <i class="bi bi-circle-fill text-success"></i> Offline
                                    </span>
                                    <span class="me-2">
                                        <i class="bi bi-circle-fill text-info"></i> Chưa thanh toán
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <!-- Recent Violations -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Vi phạm gần đây</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Biển số</th>
                                                <th>Mô tả</th>
                                                <th>Ngày vi phạm</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_violations as $violation): ?>
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
                                                    <td><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                                    <td><?php echo htmlspecialchars($violation['description']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($violation['violation_date'])); ?></td>
                                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Violation Behaviors -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow mb-4 h-70">
                            <div class="card-header py-3 text-center">
                                <h6 class="m-0 font-weight-bold text-primary">Hành vi vi phạm nhiều nhất</h6>
                            </div>
                            <div class="card-body p-2">
                                <ul class="nav nav-pills nav-justified mb-3" id="topViolationTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="day-tab" data-bs-toggle="pill" data-bs-target="#day" type="button" role="tab">Ngày</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="month-tab" data-bs-toggle="pill" data-bs-target="#month" type="button" role="tab">Tháng</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button" role="tab">Tất cả</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="topViolationTabContent">
                                    <div class="tab-pane fade show active" id="day" role="tabpanel">
                                        <?php if (empty($top_categories_day)): ?>
                                            <div class="text-center text-muted">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_day as $i => $cat): ?>
                                                <h6 class="small font-weight-bold mb-1"><?php echo htmlspecialchars($cat['description']); ?>
                                                    <span class="float-end"><?php echo $cat['count']; ?> lần</span>
                                                </h6>
                                                <div class="progress mb-2">
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                        style="width: <?php echo min(100, $cat['count'] * 10); ?>%"
                                                        aria-valuenow="<?php echo $cat['count']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="month" role="tabpanel">
                                        <?php if (empty($top_categories_month)): ?>
                                            <div class="text-center text-muted">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_month as $i => $cat): ?>
                                                <h6 class="small font-weight-bold mb-1"><?php echo htmlspecialchars($cat['description']); ?>
                                                    <span class="float-end"><?php echo $cat['count']; ?> lần</span>
                                                </h6>
                                                <div class="progress mb-2">
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                        style="width: <?php echo min(100, $cat['count'] * 10); ?>%"
                                                        aria-valuenow="<?php echo $cat['count']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="all" role="tabpanel">
                                        <?php if (empty($top_categories_all)): ?>
                                            <div class="text-center text-muted">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_all as $i => $cat): ?>
                                                <h6 class="small font-weight-bold mb-1"><?php echo htmlspecialchars($cat['description']); ?>
                                                    <span class="float-end"><?php echo $cat['count']; ?> lần</span>
                                                </h6>
                                                <div class="progress mb-2">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                        style="width: <?php echo min(100, $cat['count'] * 10); ?>%"
                                                        aria-valuenow="<?php echo $cat['count']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Violating Vehicles -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Phương tiện vi phạm nhiều nhất</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($top_vehicles as $index => $vehicle): ?>
                                    <h4 class="small font-weight-bold">
                                        <?php echo htmlspecialchars($vehicle['license_plate']); ?> 
                                        <span class="float-end"><?php echo $vehicle['violation_count']; ?> vi phạm</span>
                                    </h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-<?php echo get_color_by_index($index); ?>" role="progressbar" 
                                            style="width: <?php echo min(100, $vehicle['violation_count'] * 10); ?>%" 
                                            aria-valuenow="<?php echo $vehicle['violation_count']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Violations Chart
        var ctx = document.getElementById("violationsChart");
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "Vi phạm",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [
                        <?php echo implode(',', $violations_by_month); ?>
                    ],
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10
                    }
                }
            }
        });

        // Payment Methods Chart
        var ctx2 = document.getElementById("paymentMethodsChart");
        var myPieChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ["Online", "Offline", "Chưa thanh toán"],
                datasets: [{
                    data: [<?php echo $payment_stats['online']; ?>, <?php echo $payment_stats['offline']; ?>, <?php echo $payment_stats['unpaid']; ?>],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: false
                    }
                },
                cutout: '70%',
            },
        });
    </script>
</body>
</html>
