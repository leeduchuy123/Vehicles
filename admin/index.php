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

// Get statistics for dashboard
$total_vehicles = get_total_count($conn, 'vehicles');
$total_violations = get_total_count($conn, 'violations');
$total_owners = get_total_count($conn, 'owners');
$unpaid_violations = get_unpaid_violations_count($conn);
$recent_violations = get_recent_violations($conn, 5);
$payment_stats = get_payment_stats($conn);

// Get top violating vehicles
$top_vehicles = get_top_violating_vehicles($conn, 5);
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
                    <h1 class="h2">Bảng điều khiển</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Xuất báo cáo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Chia sẻ</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="bi bi-calendar"></i> Tuần này
                        </button>
                    </div>
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
                    <div class="col-lg-6 mb-4">
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

                    <!-- Top Violating Vehicles -->
                    <div class="col-lg-6 mb-4">
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
                    data: [0, 10, 5, 15, 10, 20, 15, 25, 20, 30, 25, 40],
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
