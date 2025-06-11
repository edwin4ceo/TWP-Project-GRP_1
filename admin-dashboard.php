<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get admin name for welcome message
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Fetch stats from database
$today_orders = 0;
$yesterday_orders = 0;
$total_revenue = 0;
$last_week_revenue = 0;
$new_customers = 0;
$last_month_customers = 0;
$total_products = 0;
$low_stock_products = 0;

try {
    // Today's orders
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()");
    $today_orders = $stmt->fetchColumn();
    
    // Yesterday's orders for comparison
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $yesterday_orders = $stmt->fetchColumn();
    
    // Today's revenue
    $stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = CURDATE()");
    $total_revenue = $stmt->fetchColumn() ?? 0;
    
    // Last week's revenue (same day last week)
    $stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $last_week_revenue = $stmt->fetchColumn() ?? 0;
    
    // New customers today
    $stmt = $conn->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()");
    $new_customers = $stmt->fetchColumn();
    
    // New customers same day last month
    $stmt = $conn->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");
    $last_month_customers = $stmt->fetchColumn();
    
    // Total products
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();
    
    // Low stock products (assuming you have a 'stock' column)
    $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 5"); // Adjust threshold as needed
    $low_stock_products = $stmt->fetchColumn();
    
    // Fetch recent orders
    $recent_orders = [];
    $stmt = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recent_orders[] = $row;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Calculate percentage changes
$order_percentage_change = $yesterday_orders > 0 ? 
    round((($today_orders - $yesterday_orders) / $yesterday_orders) * 100, 1) : 0;

$revenue_percentage_change = $last_week_revenue > 0 ? 
    round((($total_revenue - $last_week_revenue) / $last_week_revenue) * 100, 1) : 0;

$customer_percentage_change = $last_month_customers > 0 ? 
    round((($new_customers - $last_month_customers) / $last_month_customers) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    /* Sidebar */
    .admin-sidebar {
      width: 250px;
      background-color: var(--brown);
      color: var(--white);
      padding: 20px 0;
      position: fixed;  
      height: calc(100vh - 80px);
      top: 80px;
      transition: all 0.3s;
      z-index: 1;
    }

    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar-menu li {
      margin-bottom: 5px;
    }

    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: var(--white);
      text-decoration: none;
      transition: all 0.3s;
      font-size: 1rem;
    }

    .sidebar-menu a:hover, .sidebar-menu a.active {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .sidebar-menu a i {
      margin-right: 10px;
      font-size: 1.1rem;
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
    
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    
    .page-title h2 {
      color: var(--brown);
      margin: 0;
      font-size: 1.8rem;
    }
    
    .page-title p {
      color: #666;
      margin: 5px 0 0;
    }

    /* Stats Cards */
    .stats-cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 20px;
    }

    .card {
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px;
      transition: transform 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .card-title {
      font-size: 0.9rem;
      color: #666;
      margin: 0;
    }

    .card-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--white);
    }

    .card-icon.orders {
      background-color: #3498db;
    }

    .card-icon.revenue {
      background-color: #2ecc71;
    }

    .card-icon.customers {
      background-color: #9b59b6;
    }

    .card-icon.products {
      background-color: #e74c3c;
    }

    .card-body h3 {
      font-size: 1.8rem;
      margin: 0;
      color: var(--brown);
    }

    .card-footer {
      margin-top: 10px;
      font-size: 0.8rem;
      color: #666;
    }

    .card-footer .text-success {
      color: #2ecc71;
    }

    .card-footer .text-danger {
      color: #e74c3c;
    }

    /* Recent Orders Table */
    .recent-orders {
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px;
      overflow-x: auto;
      grid-column: 1 / -1; 
      margin-top: 0; 
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .section-title {
      font-size: 1.2rem;
      color: var(--brown);
      margin: 0;
    }

    .view-all {
      color: var(--primary);
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s;
      display: inline-block;
      padding: 5px 10px;
      border-radius: 4px;
    }

    .view-all:hover {
      color: var(--primary-dark);
      background-color: rgba(230, 126, 34, 0.1);
      text-decoration: none;
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    table th {
      text-align: left;
      padding: 12px 15px;
      background-color: #f5f5f5;
      color: #666;
      font-weight: 600;
      font-size: 0.85rem;
      border-bottom: 2px solid #eee;
    }

    table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
      vertical-align: middle;
    }

    .status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      display: inline-block;
      min-width: 80px;
      text-align: center;
    }

    .status.pending {
      background-color: #fff4e5;
      color: #e67e22;
    }

    .status.completed {
      background-color: #e6f7ee;
      color: #2ecc71;
    }

    .status.cancelled {
      background-color: #ffebee;
      color: #e74c3c;
    }

    .text-primary {
      color: var(--primary);
      text-decoration: none;
      transition: color 0.3s;
      font-weight: 500;
    }

    .text-primary:hover {
      color: var(--primary-dark);
      text-decoration: underline;
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

    /* Responsive Design */
    @media (max-width: 992px) {
      .admin-sidebar {
        width: 80px;
      }

      .admin-content, .admin-footer {
        margin-left: 100px;
      }

      .sidebar-menu .menu-text {
        display: none;
      }

      .sidebar-menu a {
        justify-content: center;
      }

      .sidebar-menu i {
        margin-right: 0;
        font-size: 1.3rem;
      }
    }

    @media (max-width: 1200px) {
      .stats-cards {
        grid-template-columns: repeat(2, 1fr); /* 2 columns on medium screens */
      }
    }

    @media (max-width: 768px) {
      .stats-cards {
        grid-template-columns: 1fr; /* 1 column on small screens */
      }
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
          <span>Admin </span>
        </div>
      </a>
    </div>
    <nav class="admin-nav">
      <!-- Logout Button -->
      <button class="btn-logout" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </nav>
  </header>

  <!-- Admin Container -->
  <div class="admin-container">
      <!-- Sidebar -->
  <aside class="admin-sidebar">
    <ul class="sidebar-menu">
      <li><a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="manage-staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
      <li><a href="manage-member.php"><i class="fas fa-users"></i> Manage Members</a></li>
      <li><a href="manage-categories.php"><i class="fas fa-tags"></i> Categories</a></li>
      <li><a href="manage-product.php"><i class="fas fa-utensils"></i> Products</a></li>
      <li><a href="manage-orders.php"><i class="fas fa-shopping-basket"></i> Orders</a></li>
      <li><a href="sales-reports.php"><i class="fas fa-chart-line"></i> Sales Reports</a></li>
    </ul>
  </aside>
    
    <!-- Main Content -->
    <main class="admin-content">
      <div class="page-header">
        <div class="page-title">
          <h2>Dashboard Overview</h2>
          <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's what's happening with your store today.</p>
        </div>
      </div>
      
      <!-- Stats Cards -->
      <div class="stats-cards">
        <!-- Today's Orders Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Today's Orders</h3>
            <div class="card-icon orders">
              <i class="fas fa-shopping-basket"></i>
            </div>
          </div>
          <div class="card-body">
            <h3><?php echo $today_orders; ?></h3>
          </div>
          <div class="card-footer">
            <?php if ($order_percentage_change >= 0): ?>
              <span class="text-success"><i class="fas fa-arrow-up"></i> <?php echo abs($order_percentage_change); ?>% from yesterday</span>
            <?php else: ?>
              <span class="text-danger"><i class="fas fa-arrow-down"></i> <?php echo abs($order_percentage_change); ?>% from yesterday</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Total Revenue Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Total Revenue</h3>
            <div class="card-icon revenue">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
          <div class="card-body">
            <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
          </div>
          <div class="card-footer">
            <?php if ($revenue_percentage_change >= 0): ?>
              <span class="text-success"><i class="fas fa-arrow-up"></i> <?php echo abs($revenue_percentage_change); ?>% from last week</span>
            <?php else: ?>
              <span class="text-danger"><i class="fas fa-arrow-down"></i> <?php echo abs($revenue_percentage_change); ?>% from last week</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- New Customers Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">New Customers</h3>
            <div class="card-icon customers">
              <i class="fas fa-users"></i>
            </div>
          </div>
          <div class="card-body">
            <h3><?php echo $new_customers; ?></h3>
          </div>
          <div class="card-footer">
            <?php if ($customer_percentage_change >= 0): ?>
              <span class="text-success"><i class="fas fa-arrow-up"></i> <?php echo abs($customer_percentage_change); ?>% from last month</span>
            <?php else: ?>
              <span class="text-danger"><i class="fas fa-arrow-down"></i> <?php echo abs($customer_percentage_change); ?>% from last month</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Products Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Products</h3>
            <div class="card-icon products">
              <i class="fas fa-utensils"></i>
            </div>
          </div>
          <div class="card-body">
            <h3><?php echo $total_products; ?></h3>
          </div>
          <div class="card-footer">
            <?php if ($low_stock_products > 0): ?>
              <span class="text-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $low_stock_products; ?> low in stock</span>
            <?php else: ?>
              <span class="text-success"><i class="fas fa-check-circle"></i> All products in stock</span>
            <?php endif; ?>
          </div>
        </div>
      
      <!-- Recent Orders -->
      <div class="recent-orders">
        <div class="section-header">
          <h3 class="section-title">Recent Orders</h3>
          <a href="manage-orders.php" class="view-all">
            <i class="fas fa-list"></i> View All
          </a>
        </div>
        
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recent_orders)): ?>
                <?php foreach ($recent_orders as $order): ?>
                  <tr>
                    <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                    <td><?php echo $order['item_count'] ?? 0; ?></td>
                    <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td>
                      <span class="status <?php echo strtolower($order['status'] ?? 'pending'); ?>">
                        <?php echo htmlspecialchars($order['status'] ?? 'Pending'); ?>
                      </span>
                    </td>
                    <td><a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="text-primary">View</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>  
                  <td colspan="7" style="text-align: center;">No recent orders found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  
  <!-- Footer -->
  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>
  
</body>
</html>