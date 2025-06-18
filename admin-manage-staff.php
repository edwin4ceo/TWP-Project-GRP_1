<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff'])) {
        // Add new staff
        $name = $conn->real_escape_string($_POST['staffName']);
        $email = $conn->real_escape_string($_POST['staffEmail']);
        $phone = $conn->real_escape_string($_POST['staffPhone']);
        $role = $conn->real_escape_string($_POST['staffRole']);
        $password = password_hash($_POST['staffPassword'], PASSWORD_DEFAULT);
        
        // Generate staff ID
        $prefix = ($role === 'admin') ? 'AD' : 'ST';
        $id = $prefix . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO admins (id, name, email, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $id, $name, $email, $phone, $role, $password);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff added successfully!";
        } else {
            $_SESSION['error'] = "Error adding staff: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_id'])) {
        // Delete staff
        $id = $conn->real_escape_string($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param('s', $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting staff: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['edit_staff'])) {
        // Edit staff
        $id = $conn->real_escape_string($_POST['editStaffId']);
        $name = $conn->real_escape_string($_POST['editStaffName']);
        $email = $conn->real_escape_string($_POST['editStaffEmail']);
        $phone = $conn->real_escape_string($_POST['editStaffPhone']);
        $role = $conn->real_escape_string($_POST['editStaffRole']);
        $status = $conn->real_escape_string($_POST['editStaffStatus']);
        
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param('ssssss', $name, $email, $phone, $role, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating staff: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search and filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';

$query = "SELECT * FROM admins WHERE 1=1";
$types = '';
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR id LIKE ?)";
    $search_term = "%$search%";
    $types .= 'sss';
    array_push($params, $search_term, $search_term, $search_term);
}

if (!empty($role_filter) && $role_filter !== 'All Roles') {
    $query .= " AND role = ?";
    $types .= 's';
    $params[] = $role_filter;
}

$query .= " ORDER BY FIELD(role, 'Admin', 'Manager', 'Head Baker', 'Baker', 'Cashier', 'Delivery'), name";

// Prepare the statement
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$staff_members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Manage Staff</title>
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

    .admin-nav .profile {
      display: flex;
      align-items: center;
      cursor: pointer;
      position: relative;
    }

    .admin-nav .profile img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .admin-nav .profile-name {
      font-weight: 600;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      background-color: var(--white);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
      padding: 10px 0;
      min-width: 180px;
      z-index: 100;
    }

    .dropdown-menu a {
      display: block;
      padding: 8px 20px;
      color: #333;
      text-decoration: none;
      transition: all 0.3s;
    }

    .dropdown-menu a:hover {
      background-color: #f5f5f5;
      color: var(--primary);
    }

    .dropdown-menu.show {
      display: block;
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

    .management-header button {
      font-size: 1rem;
      padding: 10px 15px;
      background-color: var(--primary);
      color: var(--white);
      border: none; 
      cursor: pointer;
      border-radius: 5px;
    }

    .management-header button:hover {
      background-color: var(--primary-dark);
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

    .member-action-btn:hover {
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

    .action-buttons{
      margin-left: auto;
    }
    .search-filter .action-buttons button{
      font-size: 1rem;
      padding: 10px 15px;
      background-color: var(--primary);
      color: var(--white);
      border: none;
      cursor: pointer;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    .search-filter .action-buttons button:hover {
      background-color: var(--primary-dark);
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

    #addStaffModal .modal-content {
      margin-top: 0%; 
      margin-bottom: auto; 
    }

    #editStaffModal .modal-content {
      margin-top: 5%; 
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
    
    .password-wrapper {
      position: relative;
    }
    
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--dark-gray);
      cursor: pointer;
      font-size: 1rem;
    }
    
    .password-hint {
      display: block;
      margin-top: 5px;
      color: var(--dark-gray);
      font-size: 0.8rem;
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
      <li><a href="admin-manage-staff.php" class="active"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
      <li><a href="admin-manage-member.php"><i class="fas fa-users"></i> Manage Members</a></li>
      <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> Categories</a></li>
      <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> Products</a></li>
      <li><a href="admin-manage-orders.php"><i class="fas fa-shopping-basket"></i> Orders</a></li>
      <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> Sales Reports</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="admin-content">
    <div class="management-header">
      <div class="page-title">
        <h2>Staff Management</h2>
      </div>
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

    <!-- Search & Filter -->
    <div class="search-filter">
      <form method="GET" action="admin-manage-staff.php" class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search staff..." value="<?= htmlspecialchars($search) ?>">
      </form>
      <form method="GET" action="admin-manage-staff.php" class="filter-dropdown">
        <select name="role" onchange="this.form.submit()">
          <option value="All Roles" <?= (empty($role_filter) || $role_filter === 'All Roles') ? 'selected' : '' ?>>All Roles</option>
          <option value="Admin" <?= ($role_filter === 'Admin') ? 'selected' : '' ?>>Admin</option>
          <option value="Manager" <?= ($role_filter === 'Manager') ? 'selected' : '' ?>>Manager</option>
          <option value="Head Baker" <?= ($role_filter === 'Head Baker') ? 'selected' : '' ?>>Head Baker</option>
          <option value="Baker" <?= ($role_filter === 'Baker') ? 'selected' : '' ?>>Baker</option>
          <option value="Cashier" <?= ($role_filter === 'Cashier') ? 'selected' : '' ?>>Cashier</option>
          <option value="Delivery" <?= ($role_filter === 'Delivery') ? 'selected' : '' ?>>Delivery</option>
        </select>
      </form>
      <div class="action-buttons">
        <button onclick="openModal('addStaffModal')">
          <i class="fas fa-plus"></i> Add Staff
        </button>
      </div>
    </div>

    <!-- Staff Table -->
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($staff_members) > 0): ?>
            <?php foreach ($staff_members as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td>
                  <span class="badge <?= $row['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td>
                  <button class="action-btn" onclick="openEditModal(
                    '<?= $row['id'] ?>',
                    '<?= addslashes($row['name']) ?>',
                    '<?= $row['email'] ?>',
                    '<?= $row['phone'] ?>',
                    '<?= $row['role'] ?>',
                    '<?= $row['status'] ?>'
                  )">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button type="button" class="action-btn" onclick="openDeleteModal('<?= $row['id'] ?>')">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center;">No staff members found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Footer -->
  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <!-- Add Staff Modal -->
  <div id="addStaffModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Staff</h3>
        <span class="close" onclick="closeModal('addStaffModal')">&times;</span>
      </div>
      <div class="modal-body">
        <form id="addStaffForm" method="POST" action="admin-manage-staff.php">
          <div class="form-group">
            <label for="staffName">Full Name</label>
            <input type="text" id="staffName" name="staffName" placeholder="Enter full name" required>
          </div>
          
          <div class="form-group">
            <label for="staffEmail">Email</label>
            <input type="email" id="staffEmail" name="staffEmail" placeholder="Enter email address" required>
          </div>
          
          <div class="form-group">
            <label for="staffPhone">Phone Number</label>
            <input type="tel" id="staffPhone" name="staffPhone" placeholder="Enter phone number" required>
          </div>
          
          <div class="form-group">
            <label for="staffRole">Role</label>
            <select id="staffRole" name="staffRole" required>
              <option value="" disabled selected>Select role</option>
              <option value="Admin">Admin</option>
              <option value="Manager">Manager</option>
              <option value="Head Baker">Head Baker</option>
              <option value="Baker">Baker</option>
              <option value="Cashier">Cashier</option>
              <option value="Delivery">Delivery</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="staffPassword">Temporary Password</label>
            <div class="password-wrapper">
              <input type="password" id="staffPassword" name="staffPassword" placeholder="Create temporary password" required>
              <button type="button" class="toggle-password" onclick="togglePasswordVisibility('staffPassword')">
                <i class="fas fa-eye-slash"></i> 
              </button>
            </div>
            <small class="password-hint">Minimum 8 characters with at least 1 number</small>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('addStaffModal')">Cancel</button>
            <button type="submit" class="btn-submit" name="add_staff">Add Staff</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Staff Modal -->
  <div id="editStaffModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Staff</h3>
        <span class="close" onclick="closeModal('editStaffModal')">&times;</span>
      </div>
      <div class="modal-body">
        <form id="editStaffForm" method="POST" action="admin-manage-staff.php">
          <input type="hidden" id="editStaffId" name="editStaffId">
          
          <div class="form-group">
            <label for="editStaffName">Full Name</label>
            <input type="text" id="editStaffName" name="editStaffName" placeholder="Enter full name" required>
          </div>
          
          <div class="form-group">
            <label for="editStaffEmail">Email</label>
            <input type="email" id="editStaffEmail" name="editStaffEmail" placeholder="Enter email address" required>
          </div>
          
          <div class="form-group">
            <label for="editStaffPhone">Phone Number</label>
            <input type="tel" id="editStaffPhone" name="editStaffPhone" placeholder="Enter phone number" required>
          </div>
          
          <div class="form-group">
            <label for="editStaffRole">Role</label>
            <select id="editStaffRole" name="editStaffRole" required>
              <option value="" disabled>Select role</option>
              <option value="Admin">Admin</option>
              <option value="Manager">Manager</option>
              <option value="Head Baker">Head Baker</option>
              <option value="Baker">Baker</option>
              <option value="Cashier">Cashier</option>
              <option value="Delivery">Delivery</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="editStaffStatus">Status</label>
            <select id="editStaffStatus" name="editStaffStatus" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('editStaffModal')">Cancel</button>
            <button type="submit" class="btn-submit" name="edit_staff">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Delete</h3>
        <span class="close" onclick="closeModal('deleteModal')">&times;</span>
      </div>
      <form method="POST" action="admin-manage-staff.php">
        <input type="hidden" id="deleteStaffId" name="delete_id">
        <p>Are you sure you want to delete this staff member? This action cannot be undone.</p>
        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
          <button type="submit" class="btn-submit">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal Functions
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
      document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }
    
    // Open edit modal with staff data
    function openEditModal(id, name, email, phone, role, status) {
      document.getElementById('editStaffId').value = id;
      document.getElementById('editStaffName').value = name;
      document.getElementById('editStaffEmail').value = email;
      document.getElementById('editStaffPhone').value = phone;
      document.getElementById('editStaffRole').value = role;
      document.getElementById('editStaffStatus').value = status;
      openModal('editStaffModal');
    }
    
    // Password Toggle
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      const icon = document.querySelector(`#${inputId} + button i`);
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye'); 
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash'); 
      }
    }
    
    // Form Submission
    document.getElementById('addStaffForm').addEventListener('submit', function(e) {
      const password = document.getElementById('staffPassword').value;
      if (password.length < 8 || !/\d/.test(password)) {
        e.preventDefault();
        alert('Password must be at least 8 characters long and contain at least one number');
      }
    });

    // Open delete confirmation modal
    function openDeleteModal(id) {
      document.getElementById('deleteStaffId').value = id;
      openModal('deleteModal');
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