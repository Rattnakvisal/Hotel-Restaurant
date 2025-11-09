<?php
require_once '../../config/connect.php';
// Fetch restaurant order items
$sql_order_items = "SELECT 
    oi.order_item_id, oi.order_id, oi.menu_id, oi.quantity, oi.price,
    o.user_id, o.order_date, o.status,
    m.name AS menu_name
  FROM order_items oi
  JOIN restaurant_orders o ON oi.order_id = o.order_id
  JOIN restaurant_menu m ON oi.menu_id = m.menu_id
  ORDER BY oi.order_item_id DESC";
$stmt_order_items = oci_parse($connection, $sql_order_items);
oci_execute($stmt_order_items);
$order_items = [];
while ($row = oci_fetch_assoc($stmt_order_items)) {
    $order_items[] = $row;
}

// Fetch room bookings
$sql_bookings = "SELECT 
    b.booking_id, b.user_id, b.room_id, b.check_in_date, b.check_out_date, b.status, b.created_at,
    r.room_name
  FROM bookings b
  JOIN rooms r ON b.room_id = r.room_id
  ORDER BY b.booking_id DESC";
$stmt_bookings = oci_parse($connection, $sql_bookings);
oci_execute($stmt_bookings);
$bookings = [];
while ($row = oci_fetch_assoc($stmt_bookings)) {
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Orders & Bookings</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef2ff;
            --secondary: #3f37c9;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
            margin-left: 250px;
            transition: all 0.3s ease;
        }

        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h1 {
            font-weight: 600;
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            font-size: 1.6rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            font-size: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header .badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
        }

        .table-container {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table thead th {
            background-color: var(--primary-light);
            color: var(--dark);
            font-weight: 600;
            padding: 16px 15px;
            border-bottom: 2px solid var(--border);
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody td {
            padding: 14px 15px;
            border-top: 1px solid var(--border);
            vertical-align: middle;
            color: #555;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning);
        }

        .status-completed {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .status-confirmed {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-view {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .btn-edit {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .btn-delete {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .icon-orders {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .icon-bookings {
            background-color: rgba(63, 55, 201, 0.15);
            color: var(--secondary);
        }

        .stat-content h3 {
            font-size: 1.8rem;
            margin: 0;
            color: var(--dark);
        }

        .stat-content p {
            margin: 5px 0 0;
            color: var(--gray);
            font-weight: 500;
        }

        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box,
        .filter-select {
            flex: 1;
            min-width: 200px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 0.95rem;
        }

        .search-box:focus,
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }

        .pagination {
            display: flex;
            gap: 8px;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: block;
            padding: 8px 16px;
            background-color: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .no-results {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d5db;
        }

        .no-results h4 {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--dark);
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .search-filter {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }

            .table thead {
                display: none;
            }

            .table,
            .table tbody,
            .table tr,
            .table td {
                display: block;
                width: 100%;
            }

            .table tr {
                margin-bottom: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                overflow: hidden;
            }

            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border-top: 1px solid #f0f0f0;
            }

            .table td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: calc(50% - 15px);
                text-align: left;
                font-weight: 600;
                color: var(--dark);
            }

            .status-badge {
                float: right;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php require_once '../include/Header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h1><i class="bi bi-clipboard-data"></i> Manage Orders & Bookings</h1>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon icon-orders">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($order_items) ?></h3>
                        <p>Total Order Items</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-bookings">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($bookings) ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span>Restaurant Order Items</span>
                    <span class="badge"><?= count($order_items) ?> items</span>
                </div>
                <div class="table-container">
                    <?php if (!empty($order_items)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order Item ID</th>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Menu Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item):
                                    $statusClass = strtolower($item['STATUS']) == 'pending' ? 'status-pending' : (strtolower($item['STATUS']) == 'completed' ? 'status-completed' : (strtolower($item['STATUS']) == 'cancelled' ? 'status-cancelled' : ''));
                                ?>
                                    <tr>
                                        <td data-label="Order Item ID"><?= htmlspecialchars($item['ORDER_ITEM_ID']) ?></td>
                                        <td data-label="Order ID"><?= htmlspecialchars($item['ORDER_ID']) ?></td>
                                        <td data-label="User ID"><?= htmlspecialchars($item['USER_ID']) ?></td>
                                        <td data-label="Menu Item"><?= htmlspecialchars($item['MENU_NAME']) ?></td>
                                        <td data-label="Quantity"><?= htmlspecialchars($item['QUANTITY']) ?></td>
                                        <td data-label="Price">$<?= number_format($item['PRICE'], 2) ?></td>
                                        <td data-label="Order Date"><?= htmlspecialchars($item['ORDER_DATE']) ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($item['STATUS']) ?></span>
                                        </td>
                                        <td data-label="Actions">
                                            <button class="action-btn btn-view"><i class="bi bi-eye"></i> View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="bi bi-cart-x"></i>
                            <h4>No Order Items Found</h4>
                            <p>There are currently no restaurant orders in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span>Room Bookings</span>
                    <span class="badge"><?= count($bookings) ?> bookings</span>
                </div>
                <div class="table-container">
                    <?php if (!empty($bookings)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>User ID</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $row):
                                    $statusClass = strtolower($row['STATUS']) == 'pending' ? 'status-pending' : (strtolower($row['STATUS']) == 'confirmed' ? 'status-confirmed' : (strtolower($row['STATUS']) == 'cancelled' ? 'status-cancelled' : ''));
                                ?>
                                    <tr>
                                        <td data-label="Booking ID"><?= htmlspecialchars($row['BOOKING_ID']) ?></td>
                                        <td data-label="User ID"><?= htmlspecialchars($row['USER_ID']) ?></td>
                                        <td data-label="Room"><?= htmlspecialchars($row['ROOM_NAME']) ?></td>
                                        <td data-label="Check-in"><?= htmlspecialchars(date('Y-m-d', strtotime($row['CHECK_IN_DATE']))) ?></td>
                                        <td data-label="Check-out"><?= htmlspecialchars(date('Y-m-d', strtotime($row['CHECK_OUT_DATE']))) ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['STATUS']) ?></span>
                                        </td>
                                        <td data-label="Created At"><?= htmlspecialchars(date('Y-m-d', strtotime($row['CREATED_AT']))) ?></td>
                                        <td data-label="Actions">
                                            <button class="action-btn btn-view"><i class="bi bi-eye"></i> View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="bi bi-calendar-x"></i>
                            <h4>No Bookings Found</h4>
                            <p>There are currently no room bookings in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pagination-container">
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Add hover effect to action buttons
        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Add responsive behavior for tables
        function setupResponsiveTables() {
            const tables = document.querySelectorAll('.table');

            tables.forEach(table => {
                const headers = [];
                table.querySelectorAll('thead th').forEach(header => {
                    headers.push(header.innerText);
                });

                table.querySelectorAll('tbody tr').forEach((row, index) => {
                    const cells = row.querySelectorAll('td');
                    cells.forEach((cell, cellIndex) => {
                        if (cellIndex < headers.length) {
                            cell.setAttribute('data-label', headers[cellIndex]);
                        }
                    });
                });
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupResponsiveTables();
        });

        // Add window resize listener
        window.addEventListener('resize', setupResponsiveTables);
    </script>
</body>

</html>