<?php
require_once '../../config/connect.php';
// --- Fetch All Rooms ---
$rooms = [];
$sql = "SELECT * FROM rooms ORDER BY room_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    if (isset($row['DESCRIPTION']) && is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob) {
        $row['DESCRIPTION'] = $row['DESCRIPTION']->load();
    }
    $rooms[] = $row;
}

// --- Date Filter Logic ---
$today = date('Y-m-d');
if (isset($_GET['filter_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['filter_date'])) {
    $today = $_GET['filter_date'];
}

// --- Today's Bookings ---
$sql = "SELECT COUNT(*) AS CNT FROM bookings WHERE TRUNC(check_in_date) = TO_DATE(:today, 'YYYY-MM-DD')";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$today_bookings = $row['CNT'] ?? 0;

// --- Available Rooms ---
$sql = "SELECT COUNT(*) AS CNT FROM rooms WHERE LOWER(status) = 'available'";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$available_rooms = $row['CNT'] ?? 0;

// --- Users Booked Today ---
$sql = "SELECT COUNT(DISTINCT user_id) AS CNT FROM bookings WHERE TRUNC(check_in_date) = TO_DATE(:today, 'YYYY-MM-DD')";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$users_booked_today = $row['CNT'] ?? 0;

// --- Revenue Today ---
$sql = "SELECT NVL(SUM(amount),0) AS TOTAL FROM booking_payments WHERE TRUNC(payment_date) = TO_DATE(:today, 'YYYY-MM-DD') AND LOWER(status) = 'paid'";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$revenue_today = $row['TOTAL'] ?? 0;

// --- Export CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="rooms_list.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Image', 'Price/Night', 'Status', 'Sleeps']);
    foreach ($rooms as $room) {
        fputcsv($output, [
            $room['ROOM_ID'],
            $room['ROOM_NAME'],
            $room['IMAGE_URL'],
            $room['PRICE_PER_NIGHT'],
            $room['STATUS'],
            $room['SLEEPS']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Rooms - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/Css/admin.css" />
    <style>
        /* Enhanced Restaurant Menu Management Styles */
        :root {
            --primary: #c36a2d;
            --primary-light: #e0a77d;
            --primary-dark: #9d531f;
            --secondary: #d4af37;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --dark: #2c1a1d;
            --light: #f8f4e9;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 10px 25px rgba(195, 106, 45, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #faf6f0;
            color: #333;
        }

        .main-content {
            padding: 30px;
        }

        .dashboard-title {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .dashboard-title i {
            color: var(--primary);
            background: rgba(195, 106, 45, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(195, 106, 45, 0.2);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: rgba(195, 106, 45, 0.1);
            color: var(--primary);
            flex-shrink: 0;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 5px 0;
            color: var(--dark);
        }

        .stat-label {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* Filter Form */
        .filter-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .filter-container:hover {
            box-shadow: 0 12px 30px rgba(195, 106, 45, 0.2);
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .filter-form input[type="date"] {
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
            font-size: 1rem;
            transition: var(--transition);
            flex: 1;
            max-width: 250px;
        }

        .filter-form input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(195, 106, 45, 0.2);
        }

        .filter-form .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            background: var(--primary);
            color: white;
        }

        .filter-form .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(195, 106, 45, 0.3);
        }

        /* Table styling */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 40px;
            transition: var(--transition);
        }

        .table-container:hover {
            box-shadow: 0 12px 30px rgba(195, 106, 45, 0.2);
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(248, 244, 233, 0.8);
        }

        .table-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h3 i {
            color: var(--primary);
        }

        .table-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            background: var(--primary);
            color: white;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(195, 106, 45, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(248, 244, 233, 0.8);
        }

        th {
            padding: 18px 25px;
            text-align: left;
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid var(--light-gray);
            font-size: 1.05rem;
        }

        td {
            padding: 16px 25px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 1rem;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background: rgba(195, 106, 45, 0.05);
        }

        .food-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .food-image:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .edit-btn {
            background: rgba(41, 128, 185, 0.15);
            color: #2980b9;
            border: 1px solid rgba(41, 128, 185, 0.3);
        }

        .edit-btn:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-3px);
        }

        .delete-btn {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .delete-btn:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-3px);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }

        .no-data p {
            margin: 10px 0;
            font-size: 1.1rem;
        }

        /* User Cart Summary */
        .user-cart-summary {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            padding: 30px;
            transition: var(--transition);
        }

        .user-cart-summary:hover {
            box-shadow: 0 12px 30px rgba(195, 106, 45, 0.2);
        }

        .cart-summary-title {
            font-size: 1.4rem;
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-summary-title i {
            background: rgba(195, 106, 45, 0.1);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-summary-list {
            margin-bottom: 20px;
            list-style: none;
        }

        .cart-summary-list li {
            margin-bottom: 10px;
            font-size: 1.05rem;
            padding: 12px;
            background: rgba(248, 244, 233, 0.5);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            border-left: 3px solid var(--primary);
        }

        .cart-payment-status {
            font-weight: 700;
            color: var(--success);
            padding: 10px 15px;
            background: rgba(39, 174, 96, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        .cart-payment-status.failed {
            color: var(--accent);
            background: rgba(231, 76, 60, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 20px;
            }

            .dashboard-title {
                font-size: 1.8rem;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .table-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-form input[type="date"] {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 1.5rem;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .stat-value {
                font-size: 1.8rem;
            }

            .user-cart-summary {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .dashboard-title {
                font-size: 1.3rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-container {
                padding: 20px;
            }

            .table-header h3 {
                font-size: 1.3rem;
            }

            .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            th,
            td {
                padding: 14px 15px;
            }

            .action-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>
        <!-- Main Content -->
        <div class="col-lg-10 col-md-9 main-content">
            <h2 class="dashboard-title"><i class="fas fa-bed me-2"></i> Manage Rooms</h2>

            <!-- Date Filter -->
            <form method="get" style="margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                <label for="filter_date" style="font-weight:600;">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($today); ?>" style="padding:8px 14px; border-radius:6px; border:1px solid #ccc;">
                <button type="submit" class="btn btn-outline-primary" style="padding:8px 18px;">Apply</button>
            </form>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(52, 152, 219, 0.15); color: var(--secondary);">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($rooms); ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(46, 204, 113, 0.15); color: #27ae60;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $available_rooms; ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(155, 89, 182, 0.15); color: #9b59b6;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $today_bookings; ?></div>
                        <div class="stat-label">Today's Bookings</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(241, 196, 15, 0.15); color: var(--warning);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">$<?php echo number_format($revenue_today, 2); ?></div>
                        <div class="stat-label">Revenue (Today)</div>
                    </div>
                </div>
            </div>

            <!-- Rooms Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list me-2"></i> Room List</h3>
                    <div class="table-actions">
                        <a href="?export=csv" class="btn btn-primary">
                            <i class="fas fa-file-export me-2"></i> Export CSV
                        </a>
                        <a href="add_room.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add New Room
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Price/Night</th>
                                <th>Status</th>
                                <th>Sleeps</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="no-data">
                                            <i class="fas fa-bed"></i>
                                            <p>No rooms found</p>
                                            <p>Add new rooms to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo $room['ROOM_ID']; ?></td>
                                        <td><?php echo htmlspecialchars($room['ROOM_NAME']); ?></td>
                                        <td>
                                            <?php if (!empty($room['IMAGE_URL'])): ?>
                                                <img src="<?php echo htmlspecialchars($room['IMAGE_URL']); ?>" alt="Room Image" style="width:80px;height:60px;object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <div style="color:#aaa; font-size:0.9rem;">
                                                    <i class="fas fa-image"></i> No image
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($room['PRICE_PER_NIGHT'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($room['STATUS']); ?></td>
                                        <td><?php echo htmlspecialchars($room['SLEEPS']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="Add_Room.php?edit=<?php echo $room['ROOM_ID']; ?>" class="action-btn edit-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="Add_Room.php?delete=<?php echo $room['ROOM_ID']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this room?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Confirm before deleting
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>