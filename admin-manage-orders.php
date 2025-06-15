<?php
// Start session and check admin login
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $success_message = "Order status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating order: " . $e->getMessage();
        }
    }
}

// Get all orders from database
$orders = [];
try {
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $query = "SELECT * FROM orders";
    $params = [];
    
    if (!empty($search) || !empty($status_filter)) {
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(order_id LIKE ? OR customer_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status_filter)) {
            $conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY order_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Manage Orders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="sidebar.css">
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
    }

    /* Admin Header */
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

    .logo-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: inherit;
    }

    /* Admin Container */
    .admin-container {
      display: flex;
      min-height: calc(100vh - 80px);
      margin-top: 80px;
    }

    /* Main Content */
    .admin-content {
      flex: 1;
      padding: 30px;
      margin-left: 250px;
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

    /* Alerts */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
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

    /* Top Controls */
    .top-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-container {
      position: relative;
      width: 400px;
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--dark-gray);
    }

    .search-container input {
      width: 100%;
      padding: 10px 15px 10px 40px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    .btn-add-order {
      background-color: var(--primary);
      color: var(--white);
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      transition: background-color 0.3s;
    }

    .btn-add-order:hover {
      background-color: var(--primary-dark);
    }

    .btn-add-order i {
      margin-right: 8px;
    }

    /* Order Table */
    .order-table-container {
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
    }

    .order-table th {
      background-color: #f5f5f5;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      color: #666;
      font-size: 0.85rem;
    }

    .order-table td {
      padding: 15px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
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

    .status.processing {
      background-color: #e6f3ff;
      color: #3498db;
    }

    .status.completed {
      background-color: #e6f7ee;
      color: #2ecc71;
    }

    .status.cancelled {
      background-color: #ffebee;
      color: #e74c3c;
    }

    .order-action-btn {
      background-color: var(--primary);
      color: var(--white);
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      transition: background-color 0.3s;
      font-size: 0.85rem;
    }

    .order-action-btn:hover {
      background-color: var(--primary-dark);
    }

    .order-action-btn i {
      margin-right: 5px;
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 2000;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-container {
      background-color: var(--white);
      border-radius: 8px;
      width: 800px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      margin: 0;
      color: var(--brown);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #999;
    }

    .modal-body {
      padding: 20px;
    }

    .info-section {
      margin-bottom: 25px;
    }

    .info-section h4 {
      margin-bottom: 15px;
      color: var(--brown);
      font-size: 1.1rem;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    }

    .info-item label {
      display: block;
      font-size: 0.8rem;
      color: #666;
      margin-bottom: 5px;
    }

    .info-item p {
      margin: 0;
      padding: 8px 10px;
      background-color: var(--gray);
      border-radius: 4px;
    }

    .order-items {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .order-items th {
      background-color: #f5f5f5;
      padding: 10px;
      text-align: left;
      font-size: 0.8rem;
      color: #666;
    }

    .order-items td {
      padding: 10px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .order-summary table {
      width: 100%;
    }

    .order-summary td {
      padding: 8px 0;
    }

    .order-summary td:last-child {
      text-align: right;
      font-weight: 600;
    }

    .status-select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: var(--white);
    }

    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-primary {
      background-color: var(--primary);
      color: var(--white);
      border: none;
      padding: 8px 20px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
    }

    .btn-secondary {
      background-color: #666;
      color: var(--white);
      border: none;
      padding: 8px 20px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-secondary:hover {
      background-color: #555;
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
        <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin-manage-staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
        <li><a href="admin-manage-member.php"><i class="fas fa-users"></i> Manage Members</a></li>
        <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> Products</a></li>
        <li><a href="admin-manage-orders.php" class="active"><i class="fas fa-shopping-basket"></i> Orders</a></li>
        <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> Sales Reports</a></li>
      </ul>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-content">
      <div class="page-header">
        <div class="page-title">
          <h2>Order Management</h2>
          <p>View and manage all orders</p>
        </div>
      </div>

      <!-- Display success/error messages -->
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <div class="top-controls">
        <form method="GET" action="admin-manage-orders.php" class="search-container">
          <i class="fas fa-search search-icon"></i>
          <input type="search" name="search" placeholder="Search by Order ID or Customer Name..." 
                 value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </form>
        <button class="btn-add-order" onclick="window.location.href='add-order.php'">
          <i class="fas fa-plus"></i> Add Order
        </button>
      </div>

      <div class="order-table-container">
        <table class="order-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer Name</th>
              <th>Total Amount</th>
              <th>Order Date</th>
              <th>Payment Method</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr>
                <td colspan="7" style="text-align: center;">No orders found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                  <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                  <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                  <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                  <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                  <td>
                    <span class="status <?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                      <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                  </td>
                  <td>
                    <button class="order-action-btn" onclick="openModal(
                      '<?php echo $order['id']; ?>',
                      '<?php echo addslashes($order['customer_name']); ?>',
                      '<?php echo $order['total_amount']; ?>',
                      '<?php echo $order['order_date']; ?>',
                      '<?php echo addslashes($order['payment_method']); ?>',
                      '<?php echo $order['status']; ?>',
                      '<?php echo addslashes($order['customer_email'] ?? ''); ?>',
                      '<?php echo addslashes($order['customer_phone'] ?? ''); ?>',
                      '<?php echo addslashes($order['delivery_address'] ?? ''); ?>',
                      '<?php echo addslashes($order['customer_note'] ?? ''); ?>'
                    )">
                      <i class="fas fa-info-circle"></i> Details
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Order Details Modal -->
  <div class="modal-overlay" id="orderModal">
    <div class="modal-container">
      <div class="modal-header">
        <h3>Order Details - #<span id="modalOrderId"></span></h3>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Customer Information -->
        <div class="info-section">
          <h4>Customer Information</h4>
          <div class="info-grid">
            <div class="info-item">
              <label>Full Name</label>
              <p id="modalCustomerName"></p>
            </div>
            <div class="info-item">
              <label>Email</label>
              <p id="modalCustomerEmail"></p>
            </div>
            <div class="info-item">
              <label>Phone Number</label>
              <p id="modalCustomerPhone"></p>
            </div>
            <div class="info-item">
              <label>Order Date</label>
              <p id="modalOrderDate"></p>
            </div>
          </div>
        </div>

        <!-- Delivery Information -->
        <div class="info-section">
          <h4>Delivery Information</h4>
          <div class="info-grid">
            <div class="info-item">
              <label>Delivery Address</label>
              <p id="modalDeliveryAddress"></p>
            </div>
            <div class="info-item">
              <label>Status</label>
              <p id="modalOrderStatus"></p>
            </div>
            <div class="info-item">
              <label>Customer Note</label>
              <p id="modalCustomerNote"></p>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="info-section">
          <h4>Order Items</h4>
          <table class="order-items">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody id="orderItemsBody">
              <!-- Will be populated by JavaScript -->
            </tbody>
          </table>
        </div>

        <!-- Order Summary -->
        <div class="info-section">
          <h4>Order Summary</h4>
          <div class="order-summary">
            <table>
              <tr>
                <td>Subtotal:</td>
                <td id="modalSubtotal">RM0.00</td>
              </tr>
              <tr>
                <td>Shipping Fee:</td>
                <td id="modalShippingFee">RM0.00</td>
              </tr>
              <tr>
                <td>Total Amount:</td>
                <td id="modalTotalAmount">RM0.00</td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Order Status Update -->
        <div class="info-section">
          <h4>Update Order Status</h4>
          <form id="statusUpdateForm" method="POST" action="admin-manage-orders.php">
            <input type="hidden" id="modalOrderIdInput" name="order_id">
            <div class="info-grid">
              <div class="info-item">
                <label>Current Status</label>
                <p id="modalCurrentStatus"></p>
              </div>
              <div class="info-item">
                <label>Change Status</label>
                <select class="status-select" name="status" id="statusSelect">
                  <option value="Pending">Pending</option>
                  <option value="Processing">Processing</option>
                  <option value="Completed">Completed</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
            </div>
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeModal()">Close</button>
        <button type="submit" form="statusUpdateForm" name="update_status" class="btn-primary">Update Status</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <script>
    // Modal functions
    function openModal(id, name, total, date, payment, status, email, phone, address, note) {
      // Populate modal fields
      document.getElementById('modalOrderId').textContent = id;
      document.getElementById('modalOrderIdInput').value = id;
      document.getElementById('modalCustomerName').textContent = name;
      document.getElementById('modalCustomerEmail').textContent = email || 'N/A';
      document.getElementById('modalCustomerPhone').textContent = phone || 'N/A';
      document.getElementById('modalOrderDate').textContent = new Date(date).toLocaleString();
      document.getElementById('modalDeliveryAddress').textContent = address || 'N/A';
      document.getElementById('modalCustomerNote').textContent = note || 'N/A';
      
      // Set status
      const statusElement = document.getElementById('modalOrderStatus');
      statusElement.innerHTML = `<span class="status ${status.toLowerCase()}">${status}</span>`;
      document.getElementById('modalCurrentStatus').innerHTML = `<span class="status ${status.toLowerCase()}">${status}</span>`;
      
      // Set selected status in dropdown
      document.getElementById('statusSelect').value = status;
      
      // For demo purposes, we'll create some sample order items
      // In a real application, you would fetch these from the database
      const itemsBody = document.getElementById('orderItemsBody');
      itemsBody.innerHTML = `
        <tr>
          <td>Sample Product 1</td>
          <td>2</td>
          <td>RM10.00</td>
          <td>RM20.00</td>
        </tr>
        <tr>
          <td>Sample Product 2</td>
          <td>1</td>
          <td>RM15.00</td>
          <td>RM15.00</td>
        </tr>
      `;
      
      // Calculate totals (in real app, these would come from database)
      const subtotal = 35.00;
      const shipping = 5.00;
      const totalAmount = subtotal + shipping;
      
      document.getElementById('modalSubtotal').textContent = `RM${subtotal.toFixed(2)}`;
      document.getElementById('modalShippingFee').textContent = `RM${shipping.toFixed(2)}`;
      document.getElementById('modalTotalAmount').textContent = `RM${totalAmount.toFixed(2)}`;
      
      // Show modal
      document.getElementById('orderModal').classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      document.getElementById('orderModal').classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside of it
    document.getElementById('orderModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && document.getElementById('orderModal').classList.contains('active')) {
        closeModal();
      }
    });
  </script>
</body>
</html>