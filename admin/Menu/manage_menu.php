<?php
require_once '../../config/connect.php';

// --- Fetch All Food Items ---
$foods = [];
$sql = "SELECT * FROM restaurant_menu ORDER BY menu_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    if (isset($row['DESCRIPTION']) && is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob) {
        $row['DESCRIPTION'] = $row['DESCRIPTION']->load();
    }
    $foods[] = $row;
}

// --- Date Filter Logic ---
$today = date('Y-m-d');
if (isset($_GET['filter_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['filter_date'])) {
    $today = $_GET['filter_date'];
}

// --- Today's Orders ---
$sql = "SELECT COUNT(*) AS CNT FROM restaurant_orders WHERE TRUNC(order_date) = TO_DATE(:today_val, 'YYYY-MM-DD')";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today_val', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$today_orders = $row['CNT'] ?? 0;

// --- Staff Members ---
$sql = "SELECT COUNT(*) AS CNT FROM users WHERE LOWER(role) = 'staff'";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$staff_count = $row['CNT'] ?? 0;

// --- User Orders Today ---
$current_user_id = $_SESSION['user_id'] ?? null;
$user_orders_today = 0;
if ($current_user_id) {
    $sql = "SELECT COUNT(*) AS CNT FROM restaurant_orders WHERE user_id = :uid_val AND TRUNC(order_date) = TO_DATE(:today_val, 'YYYY-MM-DD')";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':uid_val', $current_user_id);
    oci_bind_by_name($stmt, ':today_val', $today);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    $user_orders_today = $row['CNT'] ?? 0;
}

// --- User's Today's Order Summary ---
$user_order_items = [];
$user_order_payment = null;
if ($current_user_id) {
    // Get today's order for user
    $sql = "SELECT order_id FROM restaurant_orders WHERE user_id = :uid_val AND TRUNC(order_date) = TO_DATE(:today_val, 'YYYY-MM-DD')";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':uid_val', $current_user_id);
    oci_bind_by_name($stmt, ':today_val', $today);
    oci_execute($stmt);
    $order_row = oci_fetch_assoc($stmt);
    $user_order_id = $order_row['ORDER_ID'] ?? null;

    if ($user_order_id) {
        // Fetch order items
        $sql = "SELECT oi.*, rm.name FROM order_items oi JOIN restaurant_menu rm ON oi.menu_id = rm.menu_id WHERE oi.order_id = :oid_val";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':oid_val', $user_order_id);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $user_order_items[] = $row;
        }
        // Fetch payment info
        $sql = "SELECT * FROM order_payments WHERE order_id = :oid_val";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':oid_val', $user_order_id);
        oci_execute($stmt);
        $user_order_payment = oci_fetch_assoc($stmt);
    }
}

// --- Export CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="food_menu.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Image', 'Price', 'Category']);
    foreach ($foods as $food) {
        fputcsv($output, [
            $food['MENU_ID'],
            $food['NAME'],
            $food['IMAGE_URL'],
            $food['PRICE'],
            $food['CATEGORY']
        ]);
    }
    fclose($output);
    exit;
}

// --- Users Purchased Today ---
$sql = "SELECT COUNT(DISTINCT u.user_id) AS CNT
        FROM users u
        JOIN restaurant_orders ro ON u.user_id = ro.user_id
        WHERE LOWER(u.role) != 'staff'
          AND TRUNC(ro.order_date) = TO_DATE(:today_val, 'YYYY-MM-DD')";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today_val', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$users_purchased_today = $row['CNT'] ?? 0;

// --- Users Today's Total Price ---
$sql = "SELECT NVL(SUM(op.amount),0) AS TOTAL
        FROM order_payments op
        JOIN users u ON op.user_id = u.user_id
        WHERE LOWER(u.role) != 'staff'
          AND TRUNC(op.payment_date) = TO_DATE(:today_val, 'YYYY-MM-DD')
          AND LOWER(op.status) = 'paid'";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':today_val', $today);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$users_total_today = $row['TOTAL'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/Css/admin.css" />
</head>
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

<body>
    <!-- Main Content -->
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>

        <main class="main-content">
            <h2 class="dashboard-title"><i class="fas fa-chart-bar"></i> Restaurant Dashboard</h2>

            <!-- Date Filter -->
            <form method="get" style="margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                <label for="filter_date" style="font-weight:600;">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($today); ?>" style="padding:8px 14px; border-radius:6px; border:1px solid #ccc;">
                <button type="submit" class="btn btn-outline-primary" style="padding:8px 18px;">Apply</button>
            </form>

            <!-- User Cart Summary Section -->
            <?php if ($current_user_id && $user_order_id): ?>
                <div class="user-cart-summary">
                    <div class="cart-summary-title">
                        <i class="fas fa-shopping-cart"></i> Your Today's Order Summary
                    </div>
                    <?php if (!empty($user_order_items)): ?>
                        <ul class="cart-summary-list">
                            <?php foreach ($user_order_items as $item): ?>
                                <li>
                                    <?php echo htmlspecialchars($item['NAME']); ?> x<?php echo $item['QUANTITY']; ?> - $<?php echo number_format($item['PRICE'], 2); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div style="color:var(--gray);">No items in your order for today.</div>
                    <?php endif; ?>
                    <?php if ($user_order_payment): ?>
                        <div>
                            Payment: <span class="cart-payment-status<?php echo strtolower($user_order_payment['STATUS']) !== 'paid' ? ' failed' : ''; ?>">
                                <?php echo htmlspecialchars($user_order_payment['STATUS']); ?> ($<?php echo number_format($user_order_payment['AMOUNT'], 2); ?>)
                            </span>
                        </div>
                    <?php else: ?>
                        <div style="color:var(--gray);">No payment recorded for today's order.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(212, 175, 55, 0.15); color: var(--secondary);">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($foods); ?></div>
                        <div class="stat-label">Menu Items</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(39, 174, 96, 0.15); color: var(--success);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $today_orders; ?></div>
                        <div class="stat-label">Orders (<?php echo htmlspecialchars($today); ?>)</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(52, 152, 219, 0.15); color: var(--info);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $users_purchased_today; ?></div>
                        <div class="stat-label">Users Purchased (<?php echo htmlspecialchars($today); ?>)</div>
                    </div>
                </div>

                <!-- Total Revenue Today Card -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(241, 196, 15, 0.15); color: var(--warning);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">$<?php echo number_format($users_total_today, 2); ?></div>
                        <div class="stat-label">Total Revenue (Today)</div>
                    </div>
                </div>
            </div>

            <!-- Food Menu Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Food Menu Items</h3>
                    <div class="table-actions">
                        <a href="?export=csv" class="btn btn-primary" style="margin-bottom:16px;">
                            <i class="fas fa-download"></i> Export
                        </a>
                        <a href=" ../Room/Add_Food.php" class="btn btn-primary" style="margin-bottom:16px;"><i class="fas fa-plus"></i> Add Food</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($foods)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="no-data">
                                            <i class="fas fa-utensils"></i>
                                            <p>No food items found</p>
                                            <p>Add new menu items to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $total_menu_price = 0;
                                foreach ($foods as $food):
                                    $total_menu_price += floatval($food['PRICE']);
                                ?>
                                    <tr>
                                        <td><?php echo $food['MENU_ID']; ?></td>
                                        <td><?php echo htmlspecialchars($food['NAME']); ?></td>
                                        <td>
                                            <?php if (!empty($food['IMAGE_URL'])): ?>
                                                <img src="<?php echo htmlspecialchars($food['IMAGE_URL']); ?>"
                                                    alt="Food Image"
                                                    class="food-image">
                                            <?php else: ?>
                                                <div style="color:#aaa; font-size:0.9rem;">
                                                    <i class="fas fa-image"></i> No image
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($food['PRICE'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($food['CATEGORY']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../Room/Add_Food.php?edit=<?php echo $food['MENU_ID']; ?>" class="action-btn edit-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../Room/Add_Food.php?delete=<?php echo $food['MENU_ID']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this food item?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Total Menu Price Row -->
                                <tr style="background:#f8f6f2;font-weight:700;">
                                    <td colspan="3" style="text-align:right;">Total Menu Price:</td>
                                    <td>$<?php echo number_format($total_menu_price, 2); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/Js/app.js"></script>
</body>

</html>
</div>