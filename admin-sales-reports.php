<?php
// Database connection
require_once 'db_connection.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Default date range (last 7 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Process date filter form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start-date']) && isset($_POST['end-date'])) {
    $start_date = $_POST['start-date'];
    $end_date = $_POST['end-date'];
}

// Get sales report data
$report_data = [];
$total_sales = 0;
$total_orders = 0;
$top_product = '';
$top_product_qty = 0;
$sales_change = 0;
$orders_change = 0;
$avg_order_change = 0;

try {
    // Get previous period data for comparison
    $prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) . ' days'));
    $prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
    
    // Get current period orders
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.total_amount, o.status, o.delivery_address, 
              IFNULL(c.id, 0) AS customer_id, 
              COALESCE(c.name, o.customer_name, 'Guest') AS customer_name,
              IFNULL(c.email, 'N/A') AS customer_email, 
              IFNULL(c.phone, 'N/A') AS customer_phone,
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
    
    // Calculate current totals
    $stmt = $conn->prepare("
        SELECT COUNT(id) AS order_count, SUM(total_amount) AS sales_total
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
        AND status = 'completed'
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $totals_result = $stmt->get_result();
    $totals = $totals_result->fetch_assoc();
    
    $total_orders = $totals['order_count'] ?? 0;
    $total_sales = $totals['sales_total'] ?? 0;
    $current_avg = $total_orders > 0 ? $total_sales / $total_orders : 0;
    
    // Calculate previous period totals
    $stmt = $conn->prepare("
        SELECT COUNT(id) AS order_count, SUM(total_amount) AS sales_total
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
        AND status = 'completed'
    ");
    $stmt->bind_param("ss", $prev_start_date, $prev_end_date);
    $stmt->execute();
    $prev_totals_result = $stmt->get_result();
    $prev_totals = $prev_totals_result->fetch_assoc();
    
    $prev_orders = $prev_totals['order_count'] ?? 0;
    $prev_sales = $prev_totals['sales_total'] ?? 0;
    $prev_avg = $prev_orders > 0 ? $prev_sales / $prev_orders : 0;
    
    // Calculate percentage changes
    if ($prev_sales > 0) {
        $sales_change = round((($total_sales - $prev_sales) / $prev_sales) * 100, 1);
    }
    if ($prev_orders > 0) {
        $orders_change = round((($total_orders - $prev_orders) / $prev_orders) * 100, 1);
    }
    if ($prev_avg > 0) {
        $avg_order_change = round((($current_avg - $prev_avg) / $prev_avg) * 100, 1);
    }
    
    // Get top selling product
    $stmt = $conn->prepare("
        SELECT p.name, SUM(oi.quantity) AS total_quantity
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        AND o.status = 'completed'
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
    }
    
    // Get daily sales for chart
    $daily_sales = [];
    $dateRange = new DatePeriod(
        new DateTime($start_date),
        new DateInterval('P1D'), // 1-day interval
        new DateTime($end_date . ' +1 day') // Include end date
    );

    // Initialize all dates with 0 sales
    foreach ($dateRange as $date) {
        $formattedDate = $date->format('Y-m-d');
        $daily_sales[$formattedDate] = [
            'day' => $formattedDate,
            'daily_sales' => 0
        ];
    }

    // Fill in actual sales data
    $stmt = $conn->prepare("
        SELECT DATE(order_date) AS day, SUM(total_amount) AS daily_sales
        FROM orders
        WHERE DATE(order_date) BETWEEN ? AND ?
        AND status = 'completed'
        GROUP BY DATE(order_date)
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $daily_sales[$row['day']] = $row;
    }

    // Convert to sequential array for Chart.js
    $daily_sales = array_values($daily_sales);
    
} catch (Exception $e) {
    $error = "Error generating report: " . $e->getMessage();
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating order status: " . $conn->error;
    }
    $stmt->close();
    
    // Refresh the page to show updated status
    header("Location: admin-sales-reports.php?start-date=$start_date&end-date=$end_date");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Sales Reports</title>
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

    /* Header Styles */
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
      height: 65px;
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

    .admin-nav {
      display: flex;
      align-items: center;
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
    
    /* Main Content */
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

    /* Table Styles */
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

    .badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .badge-success {
      background-color: #e6f7ee;
      color: #2ecc71;
    }

    .badge-warning {
      background-color: #fff4e5;
      color: #e67e22;
    }

    .badge-danger {
      background-color: #ffebee;
      color: #e74c3c;
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

    /* Search and Filter Styles */
    .search-filter {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 15px;
      margin-top: 0;
    }

    .search-box {
      position: relative;
      flex: 1; 
      max-width: 400px;
    }

    .search-box i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--dark-gray);
      pointer-events: none;
      font-size: 1rem;
    }

    .search-box input {
      width: 100%;
      padding: 10px 12px 10px 36px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 5px var(--primary);
    }

    .filter-dropdown select {
      padding: 10px 15px;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: var(--white);
      cursor: pointer;
      transition: border-color 0.3s ease;
      min-width: 160px;
    }

    .filter-dropdown select:hover,
    .filter-dropdown select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 5px var(--primary);
    }

    /* Date Selection Form */
    .date-selection-form {
      background-color: var(--white);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }

    .date-selection-form h3 {
      color: var(--brown);
      margin-bottom: 15px;
      font-size: 1.2rem;
    }

    .date-range-selector {
      display: flex;
      gap: 20px;
      margin-bottom: 15px;
    }

    .date-range-selector div {
      flex: 1;
    }

    .date-range-selector label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: var(--brown);
    }

    .date-range-selector input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
    }

    /* Report Summary */
    .report-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .summary-card {
      background-color: var(--white);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .summary-card h3 {
      color: var(--brown);
      font-size: 1rem;
      margin-bottom: 10px;
    }

    .summary-card .value {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-dark);
      margin-bottom: 5px;
    }

    .summary-card .change {
      font-size: 0.9rem;
      color: var(--dark-gray);
    }

    .summary-card .change.positive {
      color: #2ecc71;
    }

    .summary-card .change.negative {
      color: #e74c3c;
    }

    /* Chart Container */
    .chart-container {
      background-color: var(--white);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      height: 400px;
    }

    /* Export Buttons */
    .export-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      padding: 10px 15px;
      background-color: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s;
    }

    .export-btn i {
      margin-right: 8px;
    }

    .export-btn:hover {
      background-color: var(--primary-dark);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1050;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(3px);
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        transform: translateY(-20%);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @keyframes modalSlideOut {
      from {
        transform: translateY(0);
        opacity: 1;
      }
      to {
        transform: translateY(-20%);
        opacity: 0;
      }
    }
    
    .modal-content {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 50%;
      max-width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      padding: 20px;
    }

    @media (max-width: 576px) {
      .modal-content {
        width: 95%;
        padding: 15px;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-title {
      font-size: 1.5rem;
      color: var(--brown);
      font-weight: 600;
      margin: 0; 
    }

    .close, .close-modal {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close:hover, .close-modal:hover {
      color: black;
    }
    
    .modal-body {
      padding: 25px;
    }
    
    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--brown);
    }
    
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
    }
    
    /* Button Styles */
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
    }
    
    .btn-cancel {
      padding: 12px 20px;
      background-color: var(--white);
      color: var(--dark-gray);
      border: 1px solid #ddd;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-cancel:hover {
      background-color: #f5f5f5;
    }
    
    .btn-submit {
      padding: 12px 20px;
      background-color: var(--primary);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s;
    }
    
    .btn-submit:hover {
      background-color: var(--primary-dark);
    }
    
    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    /* Alert Messages */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <!-- Admin Header -->
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
      <button class="btn-logout" onclick="window.location.href='admin-logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </nav>
  </header>

  <!-- Sidebar -->
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

  <!-- Main Content -->
  <main class="admin-content">
    <div class="management-header">
      <h2>Sales Reports</h2>
    </div>

    <!-- Display messages -->
    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <!-- Date Selection Form -->
    <form method="post" class="date-selection-form">
      <h3>Select Date Range</h3>
      <div class="date-range-selector">
        <div>
          <label for="start-date">From:</label>
          <input type="date" id="start-date" name="start-date" value="<?php echo $start_date; ?>" max="<?php echo $end_date; ?>">
        </div>
        <div>
          <label for="end-date">To:</label>
          <input type="date" id="end-date" name="end-date" value="<?php echo $end_date; ?>" min="<?php echo $start_date; ?>">
        </div>
      </div>
      <button type="submit" class="action-btn">
        <i class="fas fa-filter"></i> Generate Report
      </button>
    </form>

    <?php if (isset($error)): ?>
      <div class="alert alert-error">
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
            <div class="change <?= $sales_change >= 0 ? 'positive' : 'negative' ?>">
              <?= $sales_change >= 0 ? '+' : '' ?><?= $sales_change ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Total Orders</h3>
            <div class="value"><?php echo $total_orders; ?></div>
            <div class="change <?= $orders_change >= 0 ? 'positive' : 'negative' ?>">
              <?= $orders_change >= 0 ? '+' : '' ?><?= $orders_change ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Average Order Value</h3>
            <div class="value">RM <?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?></div>
            <div class="change <?= $avg_order_change >= 0 ? 'positive' : 'negative' ?>">
              <?= $avg_order_change >= 0 ? '+' : '' ?><?= $avg_order_change ?>% from previous period
            </div>
          </div>
          <div class="summary-card">
            <h3>Top Selling Product</h3>
            <div class="value"><?php echo $top_product ?: 'N/A'; ?></div>
            <div class="change"><?php echo $top_product_qty ? $top_product_qty . ' sold' : 'No data'; ?></div>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="salesChart"></canvas>
        </div>

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
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $order): ?>
                  <tr>
                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                    <td>ORD-<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                    </td>
                    <td><?php echo $order['item_count']; ?></td>
                    <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                      <span class="badge <?= $order['status'] === 'completed' ? 'badge-success' : ($order['status'] === 'processing' ? 'badge-warning' : 'badge-danger') ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </td>
                    <td>
                      <button class="action-btn" onclick="openOrderDetailsModal(
                            '<?= $order['id'] ?>',
                            '<?= date('Y-m-d', strtotime($order['order_date'])) ?>',
                            '<?= htmlspecialchars($order['customer_name']) ?>',
                            '<?= ($order['customer_id'] > 0) ? $order['customer_id'] : 'N/A' ?>',
                            '<?= htmlspecialchars($order['customer_email']) ?>',
                            '<?= htmlspecialchars($order['customer_phone']) ?>',
                            '<?= htmlspecialchars($order['delivery_address']) ?>',
                            '<?= $order['item_count'] ?>',
                            '<?= number_format($order['total_amount'], 2) ?>',
                            '<?= $order['status'] ?>'
                        )">
                        <i class="fas fa-eye"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" style="text-align: center;">No orders found for the selected date range</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <!-- Order Details Modal -->
  <div id="orderDetailsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Order Details - #<span id="modalOrderId"></span></h3>
        <span class="close" onclick="closeModal('orderDetailsModal')">&times;</span>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Order Date</label>
          <input type="text" id="modalOrderDate" readonly>
        </div>
        
        <div class="form-group">
          <label>Customer Name</label>
          <input type="text" id="modalCustomerName" readonly>
        </div>
        
        <div class="form-group">
          <label>Customer ID</label>
          <input type="text" id="modalCustomerId" readonly>
        </div>
        
        <div class="form-group">
          <label>Customer Email</label>
          <input type="text" id="modalCustomerEmail" readonly>
        </div>
        
        <div class="form-group">
          <label>Customer Phone</label>
          <input type="text" id="modalCustomerPhone" readonly>
        </div>
        
        <div class="form-group">
          <label>Delivery Address</label>
          <textarea id="modalDeliveryAddress" rows="3" readonly style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
        </div>
        
        <div class="form-group">
          <label>Total Items</label>
          <input type="text" id="modalTotalItems" readonly>
        </div>
        
        <div class="form-group">
          <label>Total Amount</label>
          <input type="text" id="modalTotalAmount" readonly>
        </div>
        
        <form method="POST" action="admin-sales-reports.php">
          <input type="hidden" id="modalOrderIdInput" name="order_id">
          <div class="form-group">
            <label for="modalOrderStatus">Order Status</label>
            <select id="modalOrderStatus" name="new_status" class="form-control">
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('orderDetailsModal')">Close</button>
            <button type="submit" class="btn-submit" name="update_status">Update Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Date range validation
      const startDateInput = document.getElementById('start-date');
      const endDateInput = document.getElementById('end-date');
      
      startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (new Date(endDateInput.value) < new Date(this.value)) {
          endDateInput.value = this.value;
        }
      });
      
      endDateInput.addEventListener('change', function() {
        startDateInput.max = this.value;
        if (new Date(startDateInput.value) > new Date(this.value)) {
          startDateInput.value = this.value;
        }
      });

      <?php if (isset($daily_sales) && count($daily_sales) > 0): ?>
          const ctx = document.getElementById('salesChart').getContext('2d');
          
          const labels = [
              <?php foreach ($daily_sales as $day): ?>
                  '<?php echo date('d M', strtotime($day['day'])); ?>',
              <?php endforeach; ?>
          ];
          
          const salesData = [
              <?php foreach ($daily_sales as $day): ?>
                  <?php echo $day['daily_sales'] ?? 0; ?>,
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
                          text: 'Sales Report (<?php echo date('d M Y', strtotime($start_date)); ?> to <?php echo date('d M Y', strtotime($end_date)); ?>)',
                          font: { size: 16 }
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

    // Modal Functions
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
      document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }
    
    // Open order details modal with order data
    function openOrderDetailsModal(orderId, orderDate, customerName, customerId, customerEmail, customerPhone, deliveryAddress, totalItems, totalAmount, status) {
        document.getElementById('modalOrderId').textContent = orderId;
        document.getElementById('modalOrderIdInput').value = orderId;
        document.getElementById('modalOrderDate').value = orderDate;
        document.getElementById('modalCustomerName').value = customerName;
        document.getElementById('modalCustomerId').value = customerId === '0' ? 'N/A' : customerId;
        document.getElementById('modalCustomerEmail').value = customerEmail;
        document.getElementById('modalCustomerPhone').value = customerPhone;
        document.getElementById('modalDeliveryAddress').value = deliveryAddress;
        document.getElementById('modalTotalItems').value = totalItems;
        document.getElementById('modalTotalAmount').value = 'RM ' + totalAmount;
        document.getElementById('modalOrderStatus').value = status;
        
        if (customerId === '0' || customerId === 'N/A') {
            document.getElementById('modalOrderStatus').disabled = true;
        } else {
            document.getElementById('modalOrderStatus').disabled = false;
        }
        
        openModal('orderDetailsModal');
    }
    
    // Close all modals when clicking outside
    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
          modals[i].style.display = 'none';
        }
        document.body.style.overflow = 'auto';
      }
    }
  </script>
</body>
</html>