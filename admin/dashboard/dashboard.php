<?php
session_start();
// Only allow admins (not users) to access this page
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || strtolower($_SESSION['admin_role']) !== 'admin') {
  header("Location: /Hotel-Restaurant/auth/login.php");
  exit;
}
// Session timeout: 30 minutes
$timeout = 1800; // seconds
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $timeout)) {
  unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $_SESSION['admin_last_activity']);
  header("Location: /Hotel-Restaurant/auth/login.php?timeout=1");
  exit;
}
$_SESSION['admin_last_activity'] = time();

require_once '../../config/connect.php';

// Fetch rooms
$room_stmt = oci_parse($connection, "SELECT room_id, room_name, price_per_night, status, sleeps FROM rooms ORDER BY room_id DESC");
oci_execute($room_stmt);
$rooms = [];
while ($row = oci_fetch_assoc($room_stmt)) $rooms[] = $row;

// Fetch food menu
$food_stmt = oci_parse($connection, "SELECT menu_id, name, price, category FROM restaurant_menu ORDER BY menu_id DESC");
oci_execute($food_stmt);
$foods = [];
while ($row = oci_fetch_assoc($food_stmt)) $foods[] = $row;

// Fetch booking payments
$booking_payments = [];
$bp_stmt = oci_parse($connection, "SELECT payment_id, user_id, amount, method, status, payment_date, transaction_id FROM booking_payments ORDER BY payment_id DESC");
oci_execute($bp_stmt);
while ($row = oci_fetch_assoc($bp_stmt)) $booking_payments[] = $row;

// Fetch order payments
$order_payments = [];
$op_stmt = oci_parse($connection, "SELECT payment_id, user_id, amount, method, status, payment_date, transaction_id FROM order_payments ORDER BY payment_id DESC");
oci_execute($op_stmt);
while ($row = oci_fetch_assoc($op_stmt)) $order_payments[] = $row;

// Fetch restaurant orders
$orders = [];
$order_stmt = oci_parse($connection, "SELECT order_id, user_id, total_amount, status, order_date FROM restaurant_orders ORDER BY order_id DESC");
oci_execute($order_stmt);
$orders = [];
while ($row = oci_fetch_assoc($order_stmt)) $orders[] = $row;

// Fetch users
$user_stmt = oci_parse($connection, "SELECT user_id, name, email, role, status, created_at FROM users ORDER BY user_id DESC");
oci_execute($user_stmt);
$users = [];
while ($row = oci_fetch_assoc($user_stmt)) $users[] = $row;
$user_count = count($users);

// Get counts for dashboard cards
$room_count = count($rooms);
$menu_item_count = count($foods);
$order_count = count($orders);

// Get recent entries
$recent_rooms = array_slice($rooms, 0, 5);
$recent_orders = array_slice($orders, 0, 5);

// Calculate total revenue from order_payments (restaurant) and booking_payments (rooms)
// Only sum payments with status 'Paid' or 'Pending' for user payments
$order_payments_sum = 0;
$stmt = oci_parse($connection, "SELECT NVL(SUM(amount),0) AS SUM_AMT FROM order_payments WHERE LOWER(status) IN ('paid','pending')");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$order_payments_sum = $row['SUM_AMT'] ?? 0;

$booking_payments_sum = 0;
$stmt = oci_parse($connection, "SELECT NVL(SUM(amount),0) AS SUM_AMT FROM booking_payments WHERE LOWER(status) IN ('paid','pending')");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$booking_payments_sum = $row['SUM_AMT'] ?? 0;

$total_revenue_potential = $order_payments_sum + $booking_payments_sum;

// Calculate revenue sources percentages
$room_percent = $total_revenue_potential > 0 ? round($booking_payments_sum / $total_revenue_potential * 100) : 0;
$restaurant_percent = $total_revenue_potential > 0 ? round($order_payments_sum / $total_revenue_potential * 100) : 0;
$other_percent = 100 - $room_percent - $restaurant_percent;

// For chart.js
$revenueSourcesChartData = [
  'labels' => ['Room Bookings', 'Restaurant', 'Other'],
  'data' => [$room_percent, $restaurant_percent, $other_percent]
];

// Top selling food items (by quantity sold)
$top_food_items = [];
$sql = "SELECT m.name, SUM(oi.quantity) AS sold
FROM order_items oi
JOIN restaurant_menu m ON oi.menu_id = m.menu_id
GROUP BY m.name
ORDER BY sold DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
  $top_food_items[] = ['name' => $row['NAME'], 'value' => intval($row['SOLD'])];
}

// Top selling rooms (by booking count)
$top_room_items = [];
$sql = "SELECT r.room_name, COUNT(b.booking_id) AS booked
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
GROUP BY r.room_name
ORDER BY booked DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
  $top_room_items[] = ['name' => $row['ROOM_NAME'], 'value' => intval($row['BOOKED'])];
}

// Merge top 5 rooms and top 5 food items for chart
$topSellingItems = array_slice($top_room_items, 0, 5);
$topSellingItems = array_merge($topSellingItems, array_slice($top_food_items, 0, 5));

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RoyalNest Sales Analytics Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="./dashboard.css">
</head>

<body>
  <div class="dashboard-container">
    <?php require_once '../include/header.php'; ?>
    <div class="main-content">
      <!-- Dashboard Header -->
      <div class="dashboard-header">
        <h1 class="dashboard-title">Sales Analytics Dashboard</h1>
        <p class="dashboard-subtitle">Unlock insights, boost revenue, and drive business growth</p>
      </div>

      <!-- Summary Cards -->
      <div class="dashboard-cards">
        <div class="card">
          <div class="card-body">
            <div class="stat-card">
              <div class="stat-icon bg-revenue">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="stat-content">
                <div class="stat-number">$<?= number_format($total_revenue_potential, 2) ?></div>
                <div class="stat-title">Total Revenue Potential</div>
                <div class="trend-indicator trend-up">
                  <i class="fas fa-arrow-up"></i> 12.5% from last month
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="stat-card">
              <div class="stat-icon bg-rooms">
                <i class="fas fa-bed"></i>
              </div>
              <div class="stat-content">
                <div class="stat-number"><?= $room_count ?></div>
                <div class="stat-title">Available Rooms</div>
                <div class="trend-indicator trend-up">
                  <i class="fas fa-arrow-up"></i> 5.2% occupancy increase
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="stat-card">
              <div class="stat-icon bg-users">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-content">
                <div class="stat-number"><?= $user_count ?></div>
                <div class="stat-title">Registered Users</div>
                <div class="trend-indicator trend-up">
                  <i class="fas fa-arrow-up"></i> 15.7% from last quarter
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="analytics-section">
        <div class="section-header">
          <h2 class="section-title">Performance Analytics</h2>
        </div>

        <div class="row">
          <div class="col-lg-6 mb-4">
            <div class="chart-container">
              <div class="chart-header">
                <h3 class="chart-title">Revenue Overview (Total)</h3>
              </div>
              <canvas id="revenueChart"></canvas>
              <div style="margin-top:18px;font-size:1.2rem;color:#4361ee;">
                <strong>Total Revenue:</strong> $<?= number_format($total_revenue_potential, 2) ?>
              </div>
            </div>
          </div>

          <div class="col-lg-4 mb-4">
            <div class="chart-container">
              <div class="chart-header">
                <h3 class="chart-title">Revenue Sources</h3>
              </div>
              <canvas id="revenueSourcesChart"></canvas>
              <div style="margin-top:18px;font-size:1.2rem;color:#4361ee;">
                <strong>Total Revenue:</strong> $<?= number_format($total_revenue_potential, 2) ?>
              </div>
            </div>
          </div>
        </div> <!-- End of first charts row -->

        <!-- All Data Tables -->
        <div class="row mt-4">
          <div class="col-12 mt-4">
            <div class="chart-container">
              <div class="chart-header">
                <h3 class="chart-title">All Restaurant Orders</h3>
              </div>
              <div class="data-table-container" style="overflow-x:auto;">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>User ID</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $o): ?>
                      <tr>
                        <td><?= htmlspecialchars($o['ORDER_ID']) ?></td>
                        <td><?= htmlspecialchars($o['USER_ID']) ?></td>
                        <td>$<?= number_format($o['TOTAL_AMOUNT'], 2) ?></td>
                        <td><?= htmlspecialchars($o['STATUS']) ?></td>
                        <td><?= htmlspecialchars($o['ORDER_DATE']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-12 mt-4">
            <div class="chart-container">
              <div class="chart-header">
                <h3 class="chart-title">All User Booking Payments</h3>
              </div>
              <div class="data-table-container" style="overflow-x:auto;">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Payment ID</th>
                      <th>User ID</th>
                      <th>Amount</th>
                      <th>Method</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Transaction ID</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($booking_payments as $p): ?>
                      <tr>
                        <td><?= htmlspecialchars($p['PAYMENT_ID']) ?></td>
                        <td><?= htmlspecialchars($p['USER_ID']) ?></td>
                        <td>$<?= number_format($p['AMOUNT'], 2) ?></td>
                        <td><?= htmlspecialchars($p['METHOD']) ?></td>
                        <td>
                          <span class="status-badge <?= strtolower($p['STATUS']) == 'paid' ? 'status-active' : (strtolower($p['STATUS']) == 'pending' ? 'status-pending' : 'status-inactive') ?>">
                            <?= htmlspecialchars($p['STATUS']) ?>
                          </span>
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
          <div class="col-12 mt-4">
            <div class="chart-container">
              <div class="chart-header">
                <h3 class="chart-title">All User Order Payments</h3>
              </div>
              <div class="data-table-container" style="overflow-x:auto;">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Payment ID</th>
                      <th>User ID</th>
                      <th>Amount</th>
                      <th>Method</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Transaction ID</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($order_payments as $p): ?>
                      <tr>
                        <td><?= htmlspecialchars($p['PAYMENT_ID']) ?></td>
                        <td><?= htmlspecialchars($p['USER_ID']) ?></td>
                        <td>$<?= number_format($p['AMOUNT'], 2) ?></td>
                        <td><?= htmlspecialchars($p['METHOD']) ?></td>
                        <td>
                          <span class="status-badge <?= strtolower($p['STATUS']) == 'paid' ? 'status-active' : (strtolower($p['STATUS']) == 'pending' ? 'status-pending' : 'status-inactive') ?>">
                            <?= htmlspecialchars($p['STATUS']) ?>
                          </span>
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
        </div> <!-- End of data tables row -->
      </div> <!-- End analytics section -->

      <!-- Bottom Row -->
      <div class="row">
        <div class="col-lg-6 mb-4">
          <div class="chart-container">
            <div class="chart-header">
              <h3 class="chart-title">Top Selling Restaurant Items</h3>
            </div>
            <div class="chart-canvas-container">
              <canvas id="topSellingFoodChart"></canvas>
            </div>
            <div class="mini-cards">
              <?php foreach (array_slice($top_food_items, 0, 2) as $item): ?>
                <div class="mini-card">
                  <div class="mini-card-icon" style="background: rgba(212, 175, 55, 0.12); color: var(--royal-gold);">
                    <i class="fas fa-utensils"></i>
                  </div>
                  <div class="mini-card-content">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p><?= intval($item['value']) ?> sold</p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-6 mb-4">
          <div class="chart-container">
            <div class="chart-header">
              <h3 class="chart-title">Top Selling Rooms</h3>
            </div>
            <div class="chart-canvas-container">
              <canvas id="topSellingRoomChart"></canvas>
            </div>
            <div class="mini-cards">
              <?php foreach (array_slice($top_room_items, 0, 2) as $item): ?>
                <div class="mini-card">
                  <div class="mini-card-icon" style="background: rgba(67, 97, 238, 0.12); color: var(--primary);">
                    <i class="fas fa-bed"></i>
                  </div>
                  <div class="mini-card-content">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p><?= intval($item['value']) ?> bookings</p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          // === Revenue Chart (single bar) ===
          const revenueCtx = document.getElementById('revenueChart').getContext('2d');
          new Chart(revenueCtx, {
            type: 'bar',
            data: {
              labels: ['Total Revenue'],
              datasets: [{
                label: 'Total Revenue ($)',
                data: [<?= $total_revenue_potential ?>],
                backgroundColor: '#4361ee',
                borderRadius: 8
              }]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {
                  display: false
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                  },
                  ticks: {
                    callback: function(value) {
                      return '$' + value;
                    }
                  }
                },
                x: {
                  grid: {
                    display: false
                  }
                }
              }
            }
          });

          // === Revenue Sources Chart (doughnut) ===
          const sourcesCtx = document.getElementById('revenueSourcesChart').getContext('2d');
          new Chart(sourcesCtx, {
            type: 'doughnut',
            data: {
              labels: <?= json_encode($revenueSourcesChartData['labels']) ?>,
              datasets: [{
                data: <?= json_encode($revenueSourcesChartData['data']) ?>,
                backgroundColor: ['#4361ee', '#d4af37', '#2ecc71'],
                borderWidth: 0
              }]
            },
            options: {
              responsive: true,
              cutout: '70%',
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                  }
                }
              }
            }
          });

          // Top Selling Food Chart
          const foodLabels = <?= json_encode(array_column($top_food_items, 'name')) ?>;
          const foodData = <?= json_encode(array_column($top_food_items, 'value')) ?>;
          const foodCtx = document.getElementById('topSellingFoodChart').getContext('2d');
          new Chart(foodCtx, {
            type: 'bar',
            data: {
              labels: foodLabels,
              datasets: [{
                label: 'Items Sold',
                data: foodData,
                backgroundColor: [
                  'rgba(212, 175, 55, 0.7)',
                  'rgba(212, 175, 55, 0.6)',
                  'rgba(212, 175, 55, 0.5)',
                  'rgba(212, 175, 55, 0.4)',
                  'rgba(212, 175, 55, 0.3)'
                ],
                borderColor: 'rgba(212, 175, 55, 1)',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  grid: {
                    display: false
                  },
                  ticks: {
                    stepSize: 50
                  }
                },
                x: {
                  grid: {
                    display: false
                  }
                }
              },
              plugins: {
                legend: {
                  display: false
                }
              }
            }
          });

          // Top Selling Room Chart
          const roomLabels = <?= json_encode(array_column($top_room_items, 'name')) ?>;
          const roomData = <?= json_encode(array_column($top_room_items, 'value')) ?>;
          const roomCtx = document.getElementById('topSellingRoomChart').getContext('2d');
          new Chart(roomCtx, {
            type: 'bar',
            data: {
              labels: roomLabels,
              datasets: [{
                label: 'Bookings',
                data: roomData,
                backgroundColor: [
                  'rgba(67, 97, 238, 0.7)',
                  'rgba(67, 97, 238, 0.6)',
                  'rgba(67, 97, 238, 0.5)',
                  'rgba(67, 97, 238, 0.4)',
                  'rgba(67, 97, 238, 0.3)'
                ],
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  grid: {
                    display: false
                  },
                  ticks: {
                    stepSize: 30
                  }
                },
                x: {
                  grid: {
                    display: false
                  }
                }
              },
              plugins: {
                legend: {
                  display: false
                }
              }
            }
          });
        });
      </script>
</body>

</html>