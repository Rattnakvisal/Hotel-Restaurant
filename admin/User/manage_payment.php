<?php
require_once '../../config/connect.php';

// Fetch room booking payments
$sql_room_payments = "SELECT payment_id, booking_id, user_id, amount, method, status, payment_date, transaction_id FROM booking_payments ORDER BY payment_id DESC";
$stmt_room_payments = oci_parse($connection, $sql_room_payments);
oci_execute($stmt_room_payments);
$room_payments = [];
$total_room_amount = 0;
while ($row = oci_fetch_assoc($stmt_room_payments)) {
    $room_payments[] = $row;
    $total_room_amount += floatval($row['AMOUNT']);
}

// Fetch restaurant order payments
$sql_order_payments = "SELECT payment_id, order_id, user_id, amount, method, status, payment_date, transaction_id FROM order_payments ORDER BY payment_id DESC";
$stmt_order_payments = oci_parse($connection, $sql_order_payments);
oci_execute($stmt_order_payments);
$order_payments = [];
$total_order_amount = 0;
while ($row = oci_fetch_assoc($stmt_order_payments)) {
    $order_payments[] = $row;
    $total_order_amount += floatval($row['AMOUNT']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Management - RoyalNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #d4af37;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-icon {
            width: 45px;
            height: 45px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--dark);
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-content {
            flex: 1;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-details {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .bg-room {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .bg-order {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .bg-total {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        /* Table Styling */
        .section-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-weight: 500;
            padding: 15px 20px;
            text-align: left;
        }

        .table thead th:first-child {
            border-top-left-radius: 10px;
        }

        .table thead th:last-child {
            border-top-right-radius: 10px;
        }

        .table tbody td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .badge-paid {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning);
        }

        .badge-failed {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .btn-view {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .btn-view:hover {
            background: rgba(67, 97, 238, 0.2);
        }

        .btn-edit {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .btn-edit:hover {
            background: rgba(255, 193, 7, 0.2);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }

            .brand-header h1 span,
            .nav-link span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 0;
                padding: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2 class="page-title">
                    <i class="fas fa-credit-card"></i>
                    Payment Management
                </h2>
                <div class="user-info">
                    <div class="user-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name">Admin</div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon bg-room">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Room Booking Payments</div>
                        <div class="stat-value">$<?= number_format($total_room_amount, 2) ?></div>
                        <div class="stat-details"><?= count($room_payments) ?> transactions</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-order">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Restaurant Payments</div>
                        <div class="stat-value">$<?= number_format($total_order_amount, 2) ?></div>
                        <div class="stat-details"><?= count($order_payments) ?> transactions</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-total">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">$<?= number_format($total_room_amount + $total_order_amount, 2) ?></div>
                        <div class="stat-details">All payment types</div>
                    </div>
                </div>
            </div>

            <!-- Room Booking Payments -->
            <div class="section-container">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-bed"></i>
                        Room Booking Payments
                    </h3>
                    <div>
                        <button class="btn btn-primary">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Booking ID</th>
                                <th>User ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($room_payments as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['PAYMENT_ID']) ?></td>
                                    <td><?= htmlspecialchars($p['BOOKING_ID']) ?></td>
                                    <td><?= htmlspecialchars($p['USER_ID']) ?></td>
                                    <td><strong>$<?= number_format($p['AMOUNT'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($p['METHOD']) ?></td>
                                    <td>
                                        <?php if (strtolower($p['STATUS']) == 'paid'): ?>
                                            <span class="badge badge-paid"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php elseif (strtolower($p['STATUS']) == 'pending'): ?>
                                            <span class="badge badge-pending"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-failed"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($p['PAYMENT_DATE']))) ?></td>
                                    <td><?= htmlspecialchars($p['TRANSACTION_ID']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Restaurant Order Payments -->
            <div class="section-container">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-utensils"></i>
                        Restaurant Order Payments
                    </h3>
                    <div>
                        <button class="btn btn-primary">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>User ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_payments as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['PAYMENT_ID']) ?></td>
                                    <td><?= htmlspecialchars($p['ORDER_ID']) ?></td>
                                    <td><?= htmlspecialchars($p['USER_ID']) ?></td>
                                    <td><strong>$<?= number_format($p['AMOUNT'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($p['METHOD']) ?></td>
                                    <td>
                                        <?php if (strtolower($p['STATUS']) == 'paid'): ?>
                                            <span class="badge badge-paid"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php elseif (strtolower($p['STATUS']) == 'pending'): ?>
                                            <span class="badge badge-pending"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-failed"><?= htmlspecialchars($p['STATUS']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($p['PAYMENT_DATE']))) ?></td>
                                    <td><?= htmlspecialchars($p['TRANSACTION_ID']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate dynamic data loading
            setTimeout(function() {
                document.querySelectorAll('.stat-card').forEach(card => {
                    card.classList.add('loaded');
                });
            }, 300);

            // Add row hover effect
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>

</html>