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
if (isset($_POST['add_member'])) {
    // Add new member
    $name = $conn->real_escape_string($_POST['member_name']);
    $email = $conn->real_escape_string($_POST['member_email']);
    $phone = $conn->real_escape_string($_POST['member_phone']);
    $address = $conn->real_escape_string($_POST['member_address']);
    $status = $conn->real_escape_string($_POST['member_status']);
    
    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $address, $status);
    
    if ($stmt->execute()) {
        $success_message = "Member added successfully!";
    } else {
        $error_message = "Error adding member: " . $conn->error;
    }
    $stmt->close();
} elseif (isset($_POST['update_member'])) {
    // Update existing member
    $id = $conn->real_escape_string($_POST['member_id']);
    $name = $conn->real_escape_string($_POST['member_name']);
    $email = $conn->real_escape_string($_POST['member_email']);
    $phone = $conn->real_escape_string($_POST['member_phone']);
    $address = $conn->real_escape_string($_POST['member_address']);
    $status = $conn->real_escape_string($_POST['member_status']);
    
    $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $status, $id);
    
    if ($stmt->execute()) {
        $success_message = "Member updated successfully!";
    } else {
        $error_message = "Error updating member: " . $conn->error;
    }
    $stmt->close();
}

// Get all members from database
$members = [];
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM customers";

if (!empty($search)) {
    $query .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $stmt = $conn->prepare($query);
    $search_term = "%$search%";
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Manage Members</title>
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
      margin-top: 20px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
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
      margin-bottom: 8px;
    }

    .page-title h2 {
      color: var(--brown);
      margin: 0;
      font-size: 1.8rem;
    }

    .page-title p {
      color: #666;
      margin: 2px 0 0;
    }

    /* Member Management Styles */
    .member-table-container {
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px;
    }

    .member-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .member-table th, .member-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .member-table th {
      background-color: var(--primary);
      color: white;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .member-table td {
      font-size: 0.9rem;
    }

    .member-action-btn {
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

    .top-controls {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 16px; 
      margin-bottom: 25px;
    }

    input[type="search"] {
      padding: 10px 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      width: 250px;
      max-width: 100%;
      transition: border-color 0.3s;
    }

    input[type="search"]:focus {
      outline: none;
      border-color: #e67e22;
      box-shadow: 0 0 5px #e67e22;
    }

    .btn-add-member {
      background-color: #e67e22;
      border: none;
      color: white;
      padding: 10px 18px;
      font-size: 1rem;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      transition: background-color 0.3s;
    }

    .btn-add-member:hover {
      background-color: var(--primary-dark);
    }

    .btn-add-member i {
      margin-right: 8px;
    }

    /* Logout Button */
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

    .status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .status.active {
      background-color: #e6f7ee;
      color: #2ecc71;
    }

    .status.inactive {
      background-color: #ffebee;
      color: #e74c3c;
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

    .search-container input[type="search"] {
      padding-left: 36px; 
    }

    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: flex-end;
      }
      
      .page-actions {
        margin-top: 15px;
      }
    }

    @media (max-width: 576px) {
      .admin-header .logo-container h1 {
        font-size: 1.5rem;
      }

      .admin-nav {
        width: 100%;
        justify-content: space-between;
      }
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

    @media (max-width: 992px) {
      .admin-footer {
        margin-left: 80px;
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

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 50%;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    #addModal .modal-content {
      margin-top: 5%; 
      margin-bottom: auto; 
    }

    #editModal .modal-content {
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

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
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

    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      min-height: 100px;
      resize: vertical;
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
      <!-- Logout Button -->
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
        <li><a href="admin-manage-member.php" class="active"><i class="fas fa-users"></i> <span class="menu-text">Manage Members</span></a></li>
        <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> <span class="menu-text">Categories</span></a></li>
        <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> <span class="menu-text">Products</span></a></li>
        <li><a href="admin-manage-orders.php"><i class="fas fa-shopping-basket"></i> <span class="menu-text">Orders</span></a></li>
        <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> <span class="menu-text">Sales Reports</span></a></li>
      </ul>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-content">
      <div class="page-header">
        <div class="page-title">
          <h2>Members Management</h2>
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
        <form method="GET" action="admin-manage-member.php" class="search-container">
          <i class="fas fa-search search-icon"></i>
          <input type="search" id="memberSearch" name="search" placeholder="Search members..." 
                 value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" />
        </form>
        <button class="btn-add-member" onclick="openAddModal()">
          <i class="fas fa-plus"></i> Add Member
        </button>
      </div>

      <div class="member-table-container">
        <table class="member-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th> 
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr>
                <td colspan="7" style="text-align: center;">No members found</td> 
              </tr>
            <?php else: ?>
              <?php foreach ($members as $member): ?>
                <tr>
                  <td>M<?php echo str_pad($member['id'], 4, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars($member['name']); ?></td>
                  <td><?php echo htmlspecialchars($member['email']); ?></td>
                  <td><?php echo htmlspecialchars($member['phone']); ?></td>
                  <td><?php echo htmlspecialchars($member['address'] ?? 'N/A'); ?></td> 
                  <td>
                    <span class="status <?php echo $member['status'] === 'active' ? 'active' : 'inactive'; ?>">
                      <?php echo ucfirst($member['status']); ?>
                    </span>
                  </td>
                  <td>
                    <button class="member-action-btn" onclick="openEditModal(
                      <?php echo $member['id']; ?>,
                      '<?php echo htmlspecialchars($member['name'], ENT_QUOTES); ?>',
                      '<?php echo htmlspecialchars($member['email'], ENT_QUOTES); ?>',
                      '<?php echo htmlspecialchars($member['phone'], ENT_QUOTES); ?>',
                      '<?php echo htmlspecialchars($member['address'] ?? '', ENT_QUOTES); ?>', 
                      '<?php echo htmlspecialchars($member['status'], ENT_QUOTES); ?>'
                    )">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="member-action-btn" onclick="confirmDelete(<?php echo $member['id']; ?>)">
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
  </div>
  
  <!-- Footer -->
  <footer class="admin-footer">
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <!-- Add Member Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Member</h3>
        <span class="close" onclick="closeAddModal()">&times;</span>
      </div>
      <form action="admin-manage-member.php" method="POST">
        <div class="form-group">
          <label for="add-member-name">Name</label>
          <input type="text" id="add-member-name" name="member_name" required>
        </div>
        <div class="form-group">
          <label for="add-member-email">Email</label>
          <input type="email" id="add-member-email" name="member_email" required>
        </div>
        <div class="form-group">
          <label for="add-member-phone">Phone</label>
          <input type="text" id="add-member-phone" name="member_phone" required>
        </div>
        <div class="form-group">
          <label for="add-member-address">Address</label>
          <textarea id="add-member-address" name="member_address" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="add-member-status">Status</label>
          <select id="add-member-status" name="member_status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="add_member">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Member Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Member</h3>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <form action="admin-manage-member.php" method="POST">
        <input type="hidden" id="edit-member-id" name="member_id">
        <div class="form-group">
          <label for="edit-member-name">Name</label>
          <input type="text" id="edit-member-name" name="member_name" required>
        </div>
        <div class="form-group">
          <label for="edit-member-email">Email</label>
          <input type="email" id="edit-member-email" name="member_email" required>
        </div>
        <div class="form-group">
          <label for="edit-member-phone">Phone</label>
          <input type="text" id="edit-member-phone" name="member_phone" required>
        </div>
        <div class="form-group">
          <label for="edit-member-address">Address</label>
          <textarea id="edit-member-address" name="member_address" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="edit-member-status">Status</label>
          <select id="edit-member-status" name="member_status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_member">Update</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Delete</h3>
        <span class="close" onclick="closeDeleteModal()">&times;</span>
      </div>
      <form action="admin-manage-member.php" method="POST">
        <input type="hidden" id="delete-member-id" name="member_id">
        <p>Are you sure you want to delete this member? This action cannot be undone.</p>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="delete_member">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal functions
    function openAddModal() {
      document.getElementById('addModal').style.display = 'block';
    }

    function closeAddModal() {
      document.getElementById('addModal').style.display = 'none';
    }

    function openEditModal(id, name, email, phone, address, status) {
      document.getElementById('edit-member-id').value = id;
      document.getElementById('edit-member-name').value = name;
      document.getElementById('edit-member-email').value = email;
      document.getElementById('edit-member-phone').value = phone;
      document.getElementById('edit-member-address').value = address;
      document.getElementById('edit-member-status').value = status;
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(id) {
      document.getElementById('delete-member-id').value = id;
      document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        document.getElementById('addModal').style.display = 'none';
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('deleteModal').style.display = 'none';
      }
    }

    // Live search functionality
    document.getElementById('memberSearch').addEventListener('input', function() {
      const searchValue = this.value.toLowerCase();
      const rows = document.querySelectorAll('.member-table tbody tr');
      
      rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        const phone = row.cells[3].textContent.toLowerCase();
        
        if (name.includes(searchValue) || email.includes(searchValue) || phone.includes(searchValue)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>