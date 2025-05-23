<?php
/**
 * Sanitize user input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Get vehicle by license plate
 */
function get_vehicle_by_license_plate($conn, $license_plate) {
    $sql = "SELECT * FROM vehicles WHERE license_plate = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $license_plate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get owner by ID
 */
function get_owner_by_id($conn, $owner_id) {
    $sql = "SELECT * FROM owners WHERE owner_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get violations by vehicle ID
 */
function get_violations_by_vehicle_id($conn, $vehicle_id) {
    $sql = "SELECT * FROM violations WHERE vehicle_id = ? ORDER BY violation_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $violations = [];
    while ($row = $result->fetch_assoc()) {
        $violations[] = $row;
    }
    
    return $violations;
}

/**
 * Get payment by violation ID
 */
function get_payment_by_violation_id($conn, $violation_id) {
    $sql = "SELECT * FROM payments WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get total count from a table
 */
function get_total_count($conn, $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get unpaid violations count
 */
function get_unpaid_violations_count($conn) {
    $sql = "SELECT COUNT(*) as count FROM violations v 
            LEFT JOIN payments p ON v.violation_id = p.violation_id 
            WHERE p.payment_id IS NULL OR p.status != 'Completed'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get recent violations with vehicle info
 */
function get_recent_violations($conn, $limit = 5, $year = null) {
    $sql = "SELECT v.*, ve.license_plate 
            FROM violations v 
            JOIN vehicles ve ON v.vehicle_id = ve.vehicle_id";
    $params = [];
    $types = "";

    if ($year !== null) {
        $sql .= " WHERE YEAR(v.violation_date) = ?";
        $params[] = $year;
        $types .= "i";
    }

    $sql .= " ORDER BY v.violation_date DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $violations = [];
    while ($row = $result->fetch_assoc()) {
        $violations[] = $row;
    }
    return $violations;
}

/**
 * Get payment statistics
 */
function get_payment_stats($conn) {
    // Get total violations
    $total = get_total_count($conn, 'violations');
    
    // Get online payments
    $sql = "SELECT COUNT(*) as count FROM payments WHERE payment_method = 'Online' AND status = 'Completed'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $online = $row['count'];
    
    // Get offline payments
    $sql = "SELECT COUNT(*) as count FROM payments WHERE payment_method = 'Offline' AND status = 'Completed'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $offline = $row['count'];
    
    // Calculate unpaid
    $unpaid = $total - ($online + $offline);
    
    return [
        'online' => $online,
        'offline' => $offline,
        'unpaid' => $unpaid
    ];
}

/**
 * Get top violating vehicles
 */
function get_top_violating_vehicles($conn, $limit = 5, $year = null) {
    $sql = "SELECT v.vehicle_id, ve.license_plate, COUNT(*) as violation_count 
            FROM violations v 
            JOIN vehicles ve ON v.vehicle_id = ve.vehicle_id";
    $params = [];
    $types = "";

    if ($year !== null) {
        $sql .= " WHERE YEAR(v.violation_date) = ?";
        $params[] = $year;
        $types .= "i";
    }

    $sql .= " GROUP BY v.vehicle_id 
              ORDER BY violation_count DESC 
              LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    return $vehicles;
}

/**
 * Get color by index for charts
 */
function get_color_by_index($index) {
    $colors = ['primary', 'success', 'info', 'warning', 'danger'];
    return $colors[$index % count($colors)];
}

/**
 * Get vehicles with pagination, search and filter
 */
function get_vehicles($conn, $limit = 10, $offset = 0, $search = '', $filter_type = '', $filter_status = '') {
    $sql = "SELECT v.*, o.name AS owner_name FROM vehicles v
            LEFT JOIN owners o ON v.owner_id = o.owner_id";
    $params = [];
    $types = "";

    // Add status filter condition if needed
    if ($filter_status === 'paid' || $filter_status === 'unpaid') {
        $sql .= " LEFT JOIN (
                    SELECT vehicle_id, 
                           COUNT(*) as total_violations,
                           SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as paid_violations
                    FROM violations vi
                    LEFT JOIN payments p ON vi.violation_id = p.violation_id
                    GROUP BY vehicle_id
                  ) AS vp ON v.vehicle_id = vp.vehicle_id";
    }

    $sql .= " WHERE 1=1";

    // Add search condition (tìm cả chủ sở hữu)
    if (!empty($search)) {
        $search = "%$search%";
        $sql .= " AND (v.license_plate LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR o.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "ssss";
    }

    // Add type filter
    if (!empty($filter_type)) {
        $sql .= " AND v.type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }

    // Add status filter
    if ($filter_status === 'paid') {
        $sql .= " AND (vp.total_violations IS NULL OR vp.total_violations = vp.paid_violations)";
    } else if ($filter_status === 'unpaid') {
        $sql .= " AND (vp.total_violations > vp.paid_violations)";
    }

    // Add order and limit
    $sql .= " ORDER BY v.vehicle_id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }

    return $vehicles;
}

/**
 * Get vehicles count for pagination
 */
function get_vehicles_count($conn, $search = '', $filter_type = '', $filter_status = '') {
    $sql = "SELECT COUNT(*) as count FROM vehicles v
            LEFT JOIN owners o ON v.owner_id = o.owner_id";
    $params = [];
    $types = "";

    // Add status filter condition if needed
    if ($filter_status === 'paid' || $filter_status === 'unpaid') {
        $sql .= " LEFT JOIN (
                    SELECT vehicle_id, 
                           COUNT(*) as total_violations,
                           SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as paid_violations
                    FROM violations vi
                    LEFT JOIN payments p ON vi.violation_id = p.violation_id
                    GROUP BY vehicle_id
                  ) AS vp ON v.vehicle_id = vp.vehicle_id";
    }

    $sql .= " WHERE 1=1";

    // Add search condition (tìm cả chủ sở hữu)
    if (!empty($search)) {
        $search = "%$search%";
        $sql .= " AND (v.license_plate LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR o.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "ssss";
    }

    // Add type filter
    if (!empty($filter_type)) {
        $sql .= " AND v.type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }

    // Add status filter
    if ($filter_status === 'paid') {
        $sql .= " AND (vp.total_violations IS NULL OR vp.total_violations = vp.paid_violations)";
    } else if ($filter_status === 'unpaid') {
        $sql .= " AND (vp.total_violations > vp.paid_violations)";
    }

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['count'];
}

/**
 * Get all owners
 */
function get_all_owners($conn) {
    $sql = "SELECT * FROM owners ORDER BY name";
    $result = $conn->query($sql);
    
    $owners = [];
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }
    
    return $owners;
}

/**
 * Get violations count by vehicle
 */
function get_violations_count_by_vehicle($conn, $vehicle_id) {
    $sql = "SELECT COUNT(*) as count FROM violations WHERE vehicle_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Get unpaid violations count by vehicle
 */
function get_unpaid_violations_count_by_vehicle($conn, $vehicle_id) {
    $sql = "SELECT COUNT(*) as count FROM violations v 
            LEFT JOIN payments p ON v.violation_id = p.violation_id 
            WHERE v.vehicle_id = ? AND (p.payment_id IS NULL OR p.status != 'Completed')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Log admin action
 */
function log_action($conn, $user_id, $action, $target_table, $target_id) {
    $sql = "INSERT INTO operation_history (user_id, action, target_table, target_id, action_date) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $user_id, $action, $target_table, $target_id);
    $stmt->execute();
}

/**
 * Lấy top 4 hành vi vi phạm nhiều nhất theo ngày (trong ngày hiện tại)
 */
function get_top_violation_categories_by_day($conn, $date = null, $limit = 4, $year = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    $sql = "SELECT description, COUNT(*) as count
            FROM violations
            WHERE DATE(violation_date) = ?";
    $params = [$date];
    $types = "s";

    if ($year !== null) {
        $sql .= " AND YEAR(violation_date) = ?";
        $params[] = $year;
        $types .= "i";
    }

    $sql .= " GROUP BY description
              ORDER BY count DESC
              LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Lấy top 4 hành vi vi phạm nhiều nhất theo tháng (trong tháng hiện tại)
 */
function get_top_violation_categories_by_month($conn, $month = null, $year = null, $limit = 4) {
    if ($month === null) $month = date('m');
    if ($year === null) $year = date('Y');
    $sql = "SELECT description, COUNT(*) as count
            FROM violations
            WHERE MONTH(violation_date) = ? AND YEAR(violation_date) = ?
            GROUP BY description
            ORDER BY count DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $month, $year, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Lấy top N hành vi vi phạm nhiều nhất (tất cả)
 */
function get_top_violation_categories($conn, $limit = 4, $year = null) {
    $sql = "SELECT description, COUNT(*) as count
            FROM violations";
    $params = [];
    $types = "";

    if ($year !== null) {
        $sql .= " WHERE YEAR(violation_date) = ?";
        $params[] = $year;
        $types .= "i";
    }

    $sql .= " GROUP BY description
              ORDER BY count DESC
              LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Lấy top N loại xe vi phạm nhiều nhất
 */
function get_top_vehicle_types($conn, $limit = 4, $year = null) {
    $sql = "SELECT ve.type, COUNT(*) as count
            FROM violations v
            JOIN vehicles ve ON v.vehicle_id = ve.vehicle_id";
    $params = [];
    $types = "";

    if ($year !== null) {
        $sql .= " WHERE YEAR(v.violation_date) = ?";
        $params[] = $year;
        $types .= "i";
    }

    $sql .= " GROUP BY ve.type
              ORDER BY count DESC
              LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $typesArr = [];
    while ($row = $result->fetch_assoc()) {
        $typesArr[] = $row;
    }
    return $typesArr;
}

/**
 * Get violations count by month
 */
function get_violations_count_by_month($conn, $year = null) {
    if ($year === null) $year = date('Y');
    $sql = "SELECT MONTH(violation_date) as month, COUNT(*) as count
            FROM violations
            WHERE YEAR(violation_date) = ?
            GROUP BY MONTH(violation_date)
            ORDER BY month";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array_fill(1, 12, 0); // Khởi tạo 12 tháng = 0
    while ($row = $result->fetch_assoc()) {
        $data[(int)$row['month']] = (int)$row['count'];
    }
    return $data;
}

/**
 * Get violations with pagination, search and filter
 */
// function get_violations($conn, $limit = 10, $offset = 0, $search = '', $filter_status = '') {
//     $sql = "SELECT v.*, ve.license_plate 
//             FROM violations v 
//             JOIN vehicles ve ON v.vehicle_id = ve.vehicle_id";
//     $params = [];
//     $types = "";

//     $where = [];
//     if ($search !== '') {
//         $where[] = "(ve.license_plate LIKE ? OR v.description LIKE ?)";
//         $params[] = "%$search%";
//         $params[] = "%$search%";
//         $types .= "ss";
//     }
//     if ($filter_status === 'unpaid') {
//         $sql .= " LEFT JOIN payments p ON v.violation_id = p.violation_id";
//         $where[] = "(p.payment_id IS NULL OR p.status != 'Completed')";
//     } elseif ($filter_status === 'paid') {
//         $sql .= " LEFT JOIN payments p ON v.violation_id = p.violation_id";
//         $where[] = "p.status = 'Completed'";
//     }
//     if ($where) {
//         $sql .= " WHERE " . implode(" AND ", $where);
//     }
//     $sql .= " ORDER BY v.violation_date DESC LIMIT ? OFFSET ?";
//     $params[] = $limit;
//     $params[] = $offset;
//     $types .= "ii";

//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param($types, ...$params);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $violations = [];
//     while ($row = $result->fetch_assoc()) {
//         $violations[] = $row;
//     }

//     return $violations;
// }

// /**
//  * Get violations count
//  */
// function get_violations_count($conn, $search = '', $filter_status = '') {
//     $sql = "SELECT COUNT(*) as count
//             FROM violations v
//             JOIN vehicles ve ON v.vehicle_id = ve.vehicle_id";
//     $params = [];
//     $types = "";

//     $where = [];
//     if ($search !== '') {
//         $where[] = "(ve.license_plate LIKE ? OR v.description LIKE ?)";
//         $params[] = "%$search%";
//         $params[] = "%$search%";
//         $types .= "ss";
//     }
//     if ($filter_status === 'unpaid') {
//         $sql .= " LEFT JOIN payments p ON v.violation_id = p.violation_id";
//         $where[] = "(p.payment_id IS NULL OR p.status != 'Completed')";
//     } elseif ($filter_status === 'paid') {
//         $sql .= " LEFT JOIN payments p ON v.violation_id = p.violation_id";
//         $where[] = "p.status = 'Completed'";
//     }
//     if ($where) {
//         $sql .= " WHERE " . implode(" AND ", $where);
//     }

//     $stmt = $conn->prepare($sql);
//     if ($types) {
//         $stmt->bind_param($types, ...$params);
//     }
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $row = $result->fetch_assoc();
//     return $row['count'];
// }

function log_activity($user_id, $action, $table, $record_id, $details = null) {
    global $conn;
    
    $sql = "INSERT INTO operation_history (user_id, action, target_table, target_id, details, action_date) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $details_json = $details ? json_encode($details) : null;
    $stmt->bind_param("issss", $user_id, $action, $table, $record_id, $details_json);
    return $stmt->execute();
}
?>
