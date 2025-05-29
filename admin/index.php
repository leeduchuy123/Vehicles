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
<body class="bg-gray-100">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header Section -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center py-4">
                    <div>
                        <h1 class="h2 mb-0">Báo cáo & Thống kê</h1>
                        <p class="text-muted mb-0">Năm <?php echo $selected_year; ?></p>
                    </div>
                    <div class="d-flex align-items-center">
                        <form method="get" class="d-flex align-items-center">
                            <label for="yearSelect" class="me-2 mb-0 fw-bold">Chọn năm:</label>
                            <select name="year" id="yearSelect" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                    echo '<option value="'.$y.'"'.($selected_year == $y ? ' selected' : '').'>'.$y.'</option>';
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                            <i class="bi bi-car-front fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Tổng số phương tiện</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_vehicles); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                            <i class="bi bi-exclamation-triangle fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Tổng số vi phạm</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_violations); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-info bg-opacity-10 text-info rounded-3 p-3">
                                            <i class="bi bi-people fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Tổng số chủ sở hữu</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_owners); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                            <i class="bi bi-cash-stack fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Vi phạm chưa nộp phạt</h6>
                                        <h3 class="mb-0"><?php echo number_format($unpaid_violations); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Violations Chart -->
                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 py-3">
                                <h5 class="card-title mb-0">Vi phạm theo thời gian</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-area" style="height: 300px;">
                                    <canvas id="violationsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Chart -->
                    <div class="col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 py-3">
                                <h5 class="card-title mb-0">Phương thức thanh toán</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie" style="height: 250px;">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                                <div class="mt-4 text-center">
                                    <div class="d-flex justify-content-center gap-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2">&nbsp;</span>
                                            <small>Online</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success me-2">&nbsp;</span>
                                            <small>Offline</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-info me-2">&nbsp;</span>
                                            <small>Chưa thanh toán</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row g-4">
                    <!-- Recent Violations -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 py-3">
                                <h5 class="card-title mb-0">Vi phạm gần đây</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0">Biển số</th>
                                                <th class="border-0">Mô tả</th>
                                                <th class="border-0">Ngày</th>
                                                <th class="border-0">Trạng thái</th>
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
                                                    <td class="text-nowrap"><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                                    <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($violation['description']); ?></td>
                                                    <td class="text-nowrap"><?php echo date('d/m/Y', strtotime($violation['violation_date'])); ?></td>
                                                    <td><span class="badge <?php echo $status_class; ?> rounded-pill"><?php echo $status_text; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Violation Behaviors -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 py-3">
                                <h5 class="card-title mb-0">Hành vi vi phạm nhiều nhất</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills nav-fill mb-3" id="topViolationTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="day-tab" data-bs-toggle="pill" data-bs-target="#day" type="button" role="tab">
                                            <i class="bi bi-calendar-day me-1"></i>Ngày
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="month-tab" data-bs-toggle="pill" data-bs-target="#month" type="button" role="tab">
                                            <i class="bi bi-calendar-month me-1"></i>Tháng
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button" role="tab">
                                            <i class="bi bi-calendar-all me-1"></i>Tất cả
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="topViolationTabContent">
                                    <div class="tab-pane fade show active" id="day" role="tabpanel">
                                        <?php if (empty($top_categories_day)): ?>
                                            <div class="text-center text-muted py-4">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_day as $i => $cat): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($cat['description']); ?></h6>
                                                        <span class="badge bg-success"><?php echo $cat['count']; ?> lần</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: <?php echo min(100, $cat['count'] * 10); ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="month" role="tabpanel">
                                        <?php if (empty($top_categories_month)): ?>
                                            <div class="text-center text-muted py-4">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_month as $i => $cat): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($cat['description']); ?></h6>
                                                        <span class="badge bg-info"><?php echo $cat['count']; ?> lần</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?php echo min(100, $cat['count'] * 10); ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="all" role="tabpanel">
                                        <?php if (empty($top_categories_all)): ?>
                                            <div class="text-center text-muted py-4">Không có dữ liệu.</div>
                                        <?php else: ?>
                                            <?php foreach ($top_categories_all as $i => $cat): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($cat['description']); ?></h6>
                                                        <span class="badge bg-primary"><?php echo $cat['count']; ?> lần</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar"
                                                            style="width: <?php echo min(100, $cat['count'] * 10); ?>%">
                                                        </div>
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
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 py-3">
                                <h5 class="card-title mb-0">Phương tiện vi phạm nhiều nhất</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($top_vehicles as $index => $vehicle): ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0">
                                                <i class="bi bi-car-front-fill me-2 text-<?php echo get_color_by_index($index); ?>"></i>
                                                <?php echo htmlspecialchars($vehicle['license_plate']); ?>
                                            </h6>
                                            <span class="badge bg-<?php echo get_color_by_index($index); ?>">
                                                <?php echo $vehicle['violation_count']; ?> vi phạm
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-<?php echo get_color_by_index($index); ?>" role="progressbar" 
                                                style="width: <?php echo min(100, $vehicle['violation_count'] * 10); ?>%">
                                            </div>
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

    <!-- Custom CSS -->
    <style>
        .bg-gray-100 {
            background-color: #f8f9fa;
        }
        .stats-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .nav-pills .nav-link {
            color: #6c757d;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .progress {
            background-color: #e9ecef;
        }
        .table > :not(caption) > * > * {
            padding: 1rem;
        }
        .badge {
            padding: 0.5em 0.75em;
        }
    </style>

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
                    backgroundColor: "rgba(13, 110, 253, 0.05)",
                    borderColor: "rgba(13, 110, 253, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(13, 110, 253, 1)",
                    pointBorderColor: "rgba(13, 110, 253, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(13, 110, 253, 1)",
                    pointHoverBorderColor: "rgba(13, 110, 253, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [<?php echo implode(',', $violations_by_month); ?>],
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
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10
                        },
                        grid: {
                            color: "rgba(0, 0, 0, 0.05)",
                            zeroLineColor: "rgba(0, 0, 0, 0.05)",
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
                        backgroundColor: "rgba(255, 255, 255, 0.9)",
                        bodyColor: "#6c757d",
                        titleColor: '#212529',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        borderColor: '#dee2e6',
                        borderWidth: 1,
                        padding: 12,
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
                    backgroundColor: ['#0d6efd', '#198754', '#0dcaf0'],
                    hoverBackgroundColor: ['#0b5ed7', '#157347', '#0aa2c0'],
                    hoverBorderColor: "rgba(255, 255, 255, 0.9)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: "rgba(255, 255, 255, 0.9)",
                        bodyColor: "#6c757d",
                        titleColor: '#212529',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        borderColor: '#dee2e6',
                        borderWidth: 1,
                        padding: 12,
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
