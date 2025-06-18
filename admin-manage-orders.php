<?php
// Start session and check admin login
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $order_id);
        $stmt->execute();
        $success_message = "Order status updated successfully!";
    } catch (Exception $e) {
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Handle new order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : NULL;
    $customer_name = trim($_POST['customer_name']); 
    $total_amount = floatval($_POST['total_amount']);
    $status = $_POST['status'];
    $delivery_address = trim($_POST['delivery_address']);
    
    try {
        if ($customer_id !== NULL) {
            $check_stmt = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE id = ?");
            $check_stmt->bind_param('i', $customer_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                echo "<script>alert('Customer ID does not exist');</script>";
                echo "<script>window.location.href = 'admin-manage-orders.php';</script>";
                exit();
            } else {
                $customer_data = $check_result->fetch_assoc();
                $customer_name = $customer_data['name'];
                $customer_email = $customer_data['email'];
                $customer_phone = $customer_data['phone'];
            }
        }
        
        // Set default name if empty
        if (empty($customer_name)) {
            $customer_name = 'Guest';
        }
        
        $stmt = $conn->prepare("INSERT INTO orders 
            (customer_id, customer_name, customer_email, customer_phone, 
            total_amount, status, delivery_address, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('isssdss', $customer_id, $customer_name, $customer_email, 
                        $customer_phone, $total_amount, $status, $delivery_address);
        $stmt->execute();
        
        $success_message = "Order created successfully!";
        header("Location: admin-manage-orders.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Error creating order: " . $e->getMessage();
    }
}

// Get all orders with search and filter functionality
$orders = [];
try {
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';

    $query = "SELECT o.id, o.customer_id, 
             COALESCE(c.name, o.customer_name, 'Guest') AS customer_name,
             o.total_amount, o.status, o.order_date, o.delivery_address,
             COALESCE(o.customer_email, c.email, 'Not provided') AS customer_email, 
             COALESCE(o.customer_phone, c.phone, 'Not provided') AS customer_phone
      FROM orders o 
      LEFT JOIN customers c ON o.customer_id = c.id
      WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (o.id LIKE ? OR c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        $types .= 'ssss';
    }
    
    if (!empty($status_filter)) {
        $query .= " AND o.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $query .= " ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = intval($_POST['order_id']);
    
    try {
        // First delete order items (if they exist in a separate table)
        // $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
        
        // Then delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $success_message = "Order deleted successfully!";
        } else {
            $error_message = "Order not found or already deleted";
        }
        
        // Refresh the page to show updated list
        header("Location: admin-manage-orders.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Error deleting order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeEase - Order Management</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding-top: 70px;
        }

        .admin-container {
            display: flex;
            flex: 1;
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

        /* Main Content */
        .admin-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
            padding-top: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 21px;
        }

        .page-title h2 {
            color: var(--brown);
            margin: 0;
            font-size: 1.8rem;
        }

        /* Order Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
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

        .order-table th, .order-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .order-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .order-table td {
            font-size: 0.9rem;
        }

        .order-action-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
            transition: background-color 0.3s;
            font-size: 0.85rem;
        }

        .order-action-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Search and Filter Controls */
        .top-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
            display: inline-block;
        }

        .search-container .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1rem;
        }

        input[type="search"], select {
            padding: 10px 15px 10px 36px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            width: 250px;
            max-width: 100%;
            transition: border-color 0.3s;
        }

        input[type="search"]:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 5px var(--primary);
        }

        /* Status Badges */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
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

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px; 
        } 

        .alert-success {  
            background-color: #e6f7ee;  
            color: #2ecc71; 
            border: 1px solid #2ecc71;
        }

        .alert-error {
            background-color: #ffebee;
            color: #e74c3c;
            border: 1px solid #e74c3c;
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
            width: calc(100% - 250px);
        }

        @media (max-width: 992px) {
          .admin-footer {
            margin-left: 80px;
            width: calc(100% - 80px);
          }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            backdrop-filter: blur(3px);
            animation: fadeIn 0.3s ease-out;
        }

        #deleteModal .modal-content {
            max-width: 500px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-height: 80vh;
            overflow-y: auto;
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
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Order Details Styles */
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


        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .order-items-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-size: 0.8rem;
            color: #666;
        }

        .order-items-table td {
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

        /* Responsive Styles */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }

        .filter-dropdown select {
            padding: 10px 15px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 150px;
            min-width: 135px; 
        }

        .filter-dropdown select:hover,
        .filter-dropdown select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 5px var(--primary);
        }

        /* Add Order Modal specific styles */
        .info-item input[type="text"],
        .info-item input[type="number"],
        .info-item textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .info-item textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }


        .search-filter {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            margin-top: -6px;
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
        }

        .search-box input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .filter-dropdown select {
            padding: 10px 15px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .action-buttons {
            flex: 0 0 auto;
            margin-left: auto;
        }

        .action-buttons button {
            font-size: 1rem;
            padding: 10px 15px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons button:hover {
            background-color: var(--primary-dark);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white; 
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #777;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="logo-container">
            <img src="images/logo.png" alt="BakeEase Logo">
            <div class="logo-text">
                <h1>BakeEase</h1>
                <span>Admin</span>
            </div>
        </div>
        
        <nav class="admin-nav">
            <button class="btn-logout" onclick="window.location.href='admin-logout.php'">
              <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </nav>
    </header>

    <!-- Admin Container -->
    <div class="admin-container" style="display: flex; flex-direction: column; min-height: 100vh;">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <ul class="sidebar-menu">
        <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span></a></li>
        <li><a href="admin-manage-staff.php"><i class="fas fa-user-tie"></i> <span class="menu-text">Manage Staff</span></a></li>
        <li><a href="admin-manage-member.php"><i class="fas fa-users"></i> <span class="menu-text">Manage Members</span></a></li>
        <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> <span class="menu-text">Categories</span></a></li>
        <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> <span class="menu-text">Products</span></a></li>
        <li><a href="admin-manage-orders.php" class="active"><i class="fas fa-shopping-basket"></i> <span class="menu-text">Orders</span></a></li>
        <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> <span class="menu-text">Sales Reports</span></a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="page-header">
            <div class="page-title">
                <h2>Order Management</h2>
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

        <div class="search-filter">
          <form method="GET" action="admin-manage-orders.php" class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
          </form>
          
          <form method="GET" action="admin-manage-orders.php" class="filter-dropdown">
            <select name="status" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="processing" <?php echo ($status_filter === 'processing') ? 'selected' : ''; ?>>Processing</option>
              <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
              <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <?php if (isset($_GET['search'])): ?>
              <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>
          </form>
          
          <div class="action-buttons">
            <button onclick="openAddOrderModal()"><i class="fas fa-plus"></i> Add Order</button>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="table-responsive  ">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No orders found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td>
                                    <?php 
                                    $displayName = !empty($order['customer_name']) ? $order['customer_name'] : 'Guest';
                                    echo htmlspecialchars($displayName); 
                                    ?>
                                </td>
                                <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="order-action-btn" onclick="openOrderDetailsModal(
                                        '<?php echo $order['id']; ?>',
                                        '<?php echo htmlspecialchars($order['customer_name'] ?? 'Guest', ENT_QUOTES); ?>',
                                        '<?php echo $order['customer_id'] ?? ''; ?>',
                                        '<?php echo $order['total_amount']; ?>',
                                        '<?php echo $order['order_date']; ?>',
                                        '<?php echo htmlspecialchars($order['status'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($order['customer_email'] ?? '', ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($order['customer_phone'] ?? '', ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($order['delivery_address'] ?? '', ENT_QUOTES); ?>'
                                    )">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="order-action-btn" onclick="confirmDelete(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p>&copy; <?php echo date('Y'); ?> BakeEase Bakery. All rights reserved.</p>
    </footer>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Order Details - #<span id="modalOrderId"></span></h3>
                <span class="close" onclick="closeOrderDetailsModal()">&times;</span>
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
                    </div>
                </div>

                <!-- Order Items -->
                <div class="info-section">
                    <h4>Order Items</h4>
                    <table class="order-items-table">
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
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeOrderDetailsModal()">Close</button>
                            <button type="submit" class="btn btn-primary" name="update_status">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Order Modal -->
    <div id="addOrderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Order</h3>
                <span class="close" onclick="closeAddOrderModal()">&times;</span>
            </div>
            <form action="admin-manage-orders.php" method="POST">
                <div class="info-section">
                    <h4>Customer Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label for="customer_id">Customer ID (optional)</label>
                            <input type="number" id="customer_id" name="customer_id">
                        </div>
                        <div class="info-item">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" id="customer_name" name="customer_name" required>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h4>Order Details</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label for="total_amount">Total Amount (RM)</label>
                            <input type="number" step="0.01" id="total_amount" name="total_amount" required>
                        </div>
                        <div class="info-item">
                            <label for="status">Status</label>
                            <select class="status-select" name="status" id="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <label for="delivery_address">Delivery Address</label>
                            <textarea id="delivery_address" name="delivery_address" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddOrderModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="add_order">Save Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form action="  admin-manage-orders.php" method="POST">
                <input type="hidden" id="delete-order-id" name="order_id">
                <p>Are you sure you want to delete this order? This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="delete_order">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Open order details modal with data
    function openOrderDetailsModal(id, name, customerId, total, date, status, email, phone, address) {
        // Populate modal fields
        document.getElementById('modalOrderId').textContent = id;
        document.getElementById('modalOrderIdInput').value = id;
        
        const customerNameElement = document.getElementById('modalCustomerName');
        if (customerId) {
            customerNameElement.innerHTML = `<a href="admin-manage-member.php?customer_id=${customerId}">${name}</a>`;
        } else {
            customerNameElement.textContent = name;
        }
        
        document.getElementById('modalCustomerEmail').textContent = email || 'Not provided';
        document.getElementById('modalCustomerPhone').textContent = phone || 'Not provided';
        document.getElementById('modalOrderDate').textContent = new Date(date).toLocaleString();
        document.getElementById('modalDeliveryAddress').textContent = address || 'Not provided';

        // Set status
        document.getElementById('modalCurrentStatus').innerHTML = `<span class="status ${status.toLowerCase()}">${status}</span>`;
        
        // Set selected status in dropdown
        document.getElementById('statusSelect').value = status;
        
        // For demo purposes, we'll create some sample order items
        const itemsBody = document.getElementById('orderItemsBody');
        itemsBody.innerHTML = `
            <tr>
                <td>Chocolate Cake</td>
                <td>1</td>
                <td>RM45.00</td>
                <td>RM45.00</td>
            </tr>
            <tr>
                <td>Vanilla Cupcakes (6-pack)</td>
                <td>2</td>
                <td>RM18.00</td>
                <td>RM36.00</td>
            </tr>
        `;
        
        // Calculate totals
        const subtotal = 81.00;
        const shipping = 8.00;
        const totalAmount = subtotal + shipping;
        
        document.getElementById('modalSubtotal').textContent = `RM${subtotal.toFixed(2)}`;
        document.getElementById('modalShippingFee').textContent = `RM${shipping.toFixed(2)}`;
        document.getElementById('modalTotalAmount').textContent = `RM${totalAmount.toFixed(2)}`;
        
        // Show modal
        document.getElementById('orderDetailsModal').style.display = 'block';
    }

    function closeOrderDetailsModal() {
        document.getElementById('orderDetailsModal').style.display = 'none';
    }

    function openAddOrderModal() {
        document.getElementById('addOrderModal').style.display = 'block';
    }

    function closeAddOrderModal() {
        document.getElementById('addOrderModal').style.display = 'none';
    }

    function confirmDelete(orderId) {
        document.getElementById('delete-order-id').value = orderId;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            closeOrderDetailsModal();
            closeAddOrderModal();
            closeDeleteModal();
        }
    }
</script>
</body>
</html>