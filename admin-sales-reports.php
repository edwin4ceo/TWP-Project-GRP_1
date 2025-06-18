<?php
// Database connection
require_once 'db_connection.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get the earliest and latest order dates from database to set reasonable limits
$stmt = $conn->prepare("SELECT MIN(DATE(order_date)) as min_date, MAX(DATE(order_date)) as max_date FROM orders");
$stmt->execute();
$date_result = $stmt->get_result();
$date_limits = $date_result->fetch_assoc();
$min_order_date = $date_limits['min_date'] ?? date('Y-m-d');
$max_order_date = $date_limits['max_date'] ?? date('Y-m-d');

// Default date range (last 7 days or available data range)
$end_date = min(date('Y-m-d'), $max_order_date);
$start_date = date('Y-m-d', strtotime('-7 days'));
if (strtotime($start_date) < strtotime($min_order_date)) {
    $start_date = $min_order_date;
}

// Process date filter form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start-date']) && isset($_POST['end-date'])) {
    $start_date = $_POST['start-date'];
    $end_date = $_POST['end-date'];
    
    // Validate date range
    if (strtotime($start_date) > strtotime($end_date)) {
        $error = "Start date cannot be after end date";
        // Reset to default range
        $end_date = min(date('Y-m-d'), $max_order_date);
        $start_date = date('Y-m-d', strtotime('-7 days'));
        if (strtotime($start_date) < strtotime($min_order_date)) {
            $start_date = $min_order_date;
        }
    }
    
    // Ensure dates are within available data range
    if (strtotime($start_date) < strtotime($min_order_date)) {
        $start_date = $min_order_date;
    }
    if (strtotime($end_date) > strtotime($max_order_date)) {
        $end_date = $max_order_date;
    }
}

// Calculate previous period for comparison (same duration before start date)
$prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) . ' days'));
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));

// Get sales report data
$report_data = [];
$total_sales = 0;
$total_orders = 0;
$prev_total_sales = 0;
$prev_total_orders = 0;
$top_product = '';
$top_product_qty = 0;
$daily_sales = [];

try {
    // Get current period data
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.total_amount, o.status, 
               c.name AS customer_name, 
               COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $orders_result = $stmt->get_result();
    $report_data = $orders_result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate current period totals
    $stmt = $conn->prepare("
        SELECT COUNT(id) AS order_count, SUM(total_amount) AS sales_total
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $totals_result = $stmt->get_result();
    $totals = $totals_result->fetch_assoc();
    
    $total_orders = $totals['order_count'] ?? 0;
    $total_sales = $totals['sales_total'] ?? 0;
    
    // Calculate previous period totals for comparison
    $stmt = $conn->prepare("
        SELECT COUNT(id) AS order_count, SUM(total_amount) AS sales_total
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $prev_start_date, $prev_end_date);
    $stmt->execute();
    $prev_totals_result = $stmt->get_result();
    $prev_totals = $prev_totals_result->fetch_assoc();
    
    $prev_total_orders = $prev_totals['order_count'] ?? 0;
    $prev_total_sales = $prev_totals['sales_total'] ?? 0;
    
    // Calculate percentage changes
    $sales_change = 0;
    $orders_change = 0;
    $avg_order_change = 0;
    
    if ($prev_total_sales > 0) {
        $sales_change = (($total_sales - $prev_total_sales) / $prev_total_sales) * 100;
    }
    
    if ($prev_total_orders > 0) {
        $orders_change = (($total_orders - $prev_total_orders) / $prev_total_orders) * 100;
        $current_avg = $total_orders > 0 ? ($total_sales / $total_orders) : 0;
        $prev_avg = $prev_total_orders > 0 ? ($prev_total_sales / $prev_total_orders) : 0;
        if ($prev_avg > 0) {
            $avg_order_change = (($current_avg - $prev_avg) / $prev_avg) * 100;
        }
    }
    
    // Get top selling product
  $stmt = $conn->prepare("
      SELECT p.name, SUM(oi.quantity) AS total_quantity
      FROM order_items oi
      JOIN products p ON oi.product_id = p.id
      JOIN orders o ON oi.order_id = o.id
      WHERE DATE(o.order_date) BETWEEN ? AND ?
      GROUP BY p.id
      ORDER BY total_quantity DESC
      LIMIT 1
  ");
  $stmt->bind_param("ss", $start_date, $end_date);
  $stmt->execute();
  $top_result = $stmt->get_result();
  if ($top_result->num_rows > 0) {
      $top = $top_result->fetch_assoc();
      $top_product = $top['name'];
      $top_product_qty = $top['total_quantity'];
  } else {
      $top_product = '';
      $top_product_qty = 0;
  }
    
    // Get daily sales for chart
    $stmt = $conn->prepare("
        SELECT DATE(order_date) AS day, SUM(total_amount) AS daily_sales
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
        GROUP BY DATE(order_date)
        ORDER BY DATE(order_date)
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $daily_result = $stmt->get_result();
    $daily_sales = $daily_result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = "Error generating report: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Admin Sales Reports</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="sidebar.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --brown: #5a3921;
      --light: #f9f5f0;
      --white: #ffffff;
      --gray: #f0f0f0;
      --dark-gray: #777;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: var(--light);
      color: #333;
      display: flex;
      min-height: 100vh;
      flex-direction: column;
      margin-top: 20px;
    }

    .admin-header {
      background-color: var(--white);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
    }

    .logo-container {
      display: flex;
      align-items: center;
    }

    .logo-container img {
      height: 50px;
      margin-right: 15px;
    }

    .logo-text h1 {
      color: var(--brown);
      margin: 0;
      font-size: 1.8rem;
    }

    .logo-text span {
      color: var(--primary);
      font-size: 0.9rem;
    }

    .admin-nav .profile {
      display: flex;
      align-items: center;
      cursor: pointer;
    }

    .admin-nav .profile img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .admin-content {
      flex: 1;
      padding: 30px;
      margin-left: 250px;
      padding-top: 80px;
    }

    .management-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .management-header h2 {
      font-size: 1.8rem;
      color: var(--brown);
      margin-bottom: 5px;
    }

    .date-selection-form {
      max-width: 600px;
      margin: 50px auto;
      background-color: var(--white);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }

    .date-selection-form h3 {
      color: var(--brown);
      margin-bottom: 20px;
    }

    .date-range-selector {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    .date-range-selector label {
      font-weight: 500;
      color: var(--brown);
    }

    .date-range-selector input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
    }

    .date-range-selector button {
      padding: 10px 20px;
      background-color: var(--primary);
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 1rem;
    }

    .date-range-selector button:hover {
      background-color: var(--primary-dark);
    }

    .report-container {
      display: <?php echo ($_SERVER['REQUEST_METHOD'] == 'POST' || $total_orders > 0) ? 'block' : 'none'; ?>;
    }

    .search-filter {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 30px;
      margin-top: 0;
    }

    .report-summary {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .summary-card {
      background-color: var(--white);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      text-align: center;
    }

    .summary-card h3 {
      color: var(--dark-gray);
      font-size: 0.9rem;
      margin-bottom: 10px;
    }

    .summary-card .value {
      font-size: 1.8rem;
      font-weight: 600;
      color: var(--brown);
    }

    .summary-card .change {
      font-size: 0.8rem;
      margin-top: 5px;
    }

    .positive {
      color: #2ecc71;
    }

    .negative {
      color: #e74c3c;
    }

    .chart-container {
      background-color: var(--white);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      height: 400px;
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px 15px;
      border: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: var(--primary);
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .action-btn {
      background-color: #e67e22;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      margin-right: 5px;
      transition: background-color 0.3s;
      font-size: 0.85rem;
    }

    .action-btn:hover {
      background-color: var(--primary-dark);
    }

    .btn-logout {
      font-size: 1rem;
      padding: 10px 20px;
      background-color: var(--primary);
      color: var(--white);
      border: none;
      cursor: pointer;
      border-radius: 5px;
      display: flex;
      align-items: center;
      transition: background-color 0.3s;
    }

    .btn-logout i {
      margin-right: 8px;
    }

    .btn-logout:hover {
      background-color: var(--primary-dark);
    }

    .logo-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: inherit;
    }

    .logo-container img {
      height: 50px;
      margin-right: 15px;
    }

    .logo-text h1 {
      color: var(--brown);
      margin: 0;
      font-size: 1.8rem;
    }

    .logo-text span {
      color: var(--primary);
      font-size: 0.9rem;
    }

    /* Footer */
    .admin-footer {
      text-align: center;
      padding: 20px;
      background-color: var(--white);
      color: #666;
      font-size: 0.9rem;
      border-top: 1px solid #eee;
      margin-left: 250px;
    }

    .export-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-bottom: 20px;
    }

    .export-buttons button, .export-buttons a {
      padding: 8px 15px;
      background-color: var(--white);
      border: 1px solid var(--primary);
      color: var(--primary);
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .export-buttons button:hover, .export-buttons a:hover {
      background-color: var(--primary);
      color: white;
    }

    .error-message {
      color: #e74c3c;
      background-color: #fdecea;
      padding: 15px;
      border-radius: 5px;
      margin: 20px auto;
      max-width: 800px;
      text-align: center;
      border: 1px solid #ef9a9a;
    }

    .no-data {
      text-align: center;
      padding: 30px;
      color: var(--dark-gray);
      font-size: 1.1rem;
    }
  </style>
</head>
<body>
  <header class="admin-header">
    <div class="logo-container">
      <a href="admin-dashboard.php" class="logo-link">
        <img src="images/logo.png" alt="BakeEase Logo">
        <div class="logo-text">
          <h1>BakeEase</h1>
          <span>Admin</span>
        </div>
      </a>
    </div>
    <nav class="admin-nav">
      <button class="btn-logout" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </nav>
  </header>

  <aside class="admin-sidebar">
    <ul class="sidebar-menu">
      <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="admin-manage-staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
      <li><a href="admin-manage-member.php"><i class="fas fa-users"></i> Manage Members</a></li>
      <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> Categories</a></li>
      <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> Products</a></li>
      <li><a href="admin-manage-orders.php"><i class="fas fa-shopping-basket"></i> Orders</a></li>
      <li><a href="admin-sales-reports.php" class="active"><i class="fas fa-chart-line"></i> Sales Reports</a></li>
    </ul>
  </aside>

  <main class="admin-content">
    <div class="management-header">
      <h2>Sales Reports</h2>
    </div>

    <!-- Date Selection Form -->
    <form method="post" class="date-selection-form">
      <h3>Select Date Range</h3>
      <div class="date-range-selector">
        <div>
          <label for="start-date">From:</label>
          <input type="date" id="start-date" name="start-date" value="<?php echo $start_date; ?>" required>
        </div>
        <div>
          <label for="end-date">To:</label>
          <input type="date" id="end-date" name="end-date" value="<?php echo $end_date; ?>" required>
        </div>
      </div>
      <button type="submit" class="action-btn" style="padding: 10px 20px; font-size: 1rem;">
        <i class="fas fa-filter"></i> Generate Report
      </button>
    </form>

    <?php if (isset($error)): ?>
      <div class="error-message">
        <?php echo $error; ?>
      </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' || $total_orders > 0): ?>
      <!-- Report Container -->
      <div class="report-container" id="report-container">
        <div class="export-buttons">
          <a href="export-report.php?type=pdf&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="export-btn">
            <i class="fas fa-file-pdf"></i> Export PDF
          </a>
          <a href="export-report.php?type=excel&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="export-btn">
            <i class="fas fa-file-excel"></i> Export Excel
          </a>
        </div>

        <div class="report-summary">
          <div class="summary-card">
            <h3>Total Sales</h3>
            <div class="value">RM <?php echo number_format($total_sales, 2); ?></div>
            <div class="change <?php echo ($sales_change >= 0) ? 'positive' : 'negative'; ?>">
              <?php echo ($sales_change >= 0 ? '+' : '') . number_format($sales_change, 1) ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Total Orders</h3>
            <div class="value"><?php echo $total_orders; ?></div>
            <div class="change <?php echo ($orders_change >= 0) ? 'positive' : 'negative'; ?>">
              <?php echo ($orders_change >= 0 ? '+' : '') . number_format($orders_change, 1) ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Average Order Value</h3>
            <div class="value">RM <?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?></div>
            <div class="change <?php echo ($avg_order_change >= 0) ? 'positive' : 'negative'; ?>">
              <?php echo ($avg_order_change >= 0 ? '+' : '') . number_format($avg_order_change, 1) ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Top Selling Product</h3>
            <div class="value"><?php echo $top_product ? htmlspecialchars($top_product) : 'N/A'; ?></div>
            <div class="change"><?php echo $top_product_qty ? $top_product_qty . ' sold' : 'No data'; ?></div>
          </div>
        </div>

        <?php if (count($daily_sales) > 0): ?>
          <div class="chart-container">
            <canvas id="salesChart"></canvas>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $order): ?>
                  <tr>
                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                    <td>ORD-<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo $order['item_count']; ?></td>
                    <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><?php echo ucfirst($order['status']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align: center;">No orders found for the selected date range</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="no-data">
        <i class="fas fa-chart-bar" style="font-size: 3rem; color: var(--dark-gray); margin-bottom: 15px;"></i>
        <p>Select a date range to generate sales report</p>
      </div>
    <?php endif; ?>
  </main>

  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($daily_sales) && count($daily_sales) > 0): ?>
        // Generate chart with actual data
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        const labels = [
          <?php foreach ($daily_sales as $day): ?>
            '<?php echo date('d/m', strtotime($day['day'])); ?>',
          <?php endforeach; ?>
        ];
        
        const salesData = [
          <?php foreach ($daily_sales as $day): ?>
            <?php echo $day['daily_sales'] ?: '0'; ?>,
          <?php endforeach; ?>
        ];
        
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Daily Sales (RM)',
              data: salesData,
              backgroundColor: 'rgba(230, 126, 34, 0.2)',
              borderColor: 'rgba(230, 126, 34, 1)',
              borderWidth: 2,
              tension: 0.1,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              title: {
                display: true,
                text: 'Sales Report (<?php echo $start_date; ?> to <?php echo $end_date; ?>)',
                font: {
                  size: 16
                }
              },
              legend: {
                position: 'top',
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    return 'RM ' + value;
                  }
                }
              }
            }
          }
        });
      <?php endif; ?>
    });
  </script>
</body>
</html>