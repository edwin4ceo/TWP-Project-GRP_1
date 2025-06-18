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
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = $conn->real_escape_string($_POST['category_name']);
        $description = $conn->real_escape_string($_POST['category_description']);
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $success_message = "Category added successfully!";
        } else {
            $error_message = "Error adding category: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_category'])) {
        // Update existing category
        $id = $conn->real_escape_string($_POST['category_id']);
        $name = $conn->real_escape_string($_POST['category_name']);
        $description = $conn->real_escape_string($_POST['category_description']);
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        
        if ($stmt->execute()) {
            $success_message = "Category updated successfully!";
        } else {
            $error_message = "Error updating category: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_category'])) {
        // Delete category
        $id = $conn->real_escape_string($_POST['category_id']);
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Error deleting category: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get all categories from database
$categories = [];
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $stmt->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Manage Categories</title>
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

    /* Filter Dropdown and Add Category Button */
    .filter-dropdown {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      gap: 20px;
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

    .filter-dropdown button {
      font-size: 1rem;
      padding: 10px 15px;
      background-color: var(--primary);
      color: var(--white);
      border: none; 
      cursor: pointer;
      border-radius: 5px;
    }

    .filter-dropdown button:hover {
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
      padding: 20px;
      border: 1px solid #888;
      width: 50%;
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

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
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

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <ul class="sidebar-menu">
      <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span></a></li>
      <li><a href="admin-manage-staff.php"><i class="fas fa-user-tie"></i> <span class="menu-text">Manage Staff</span></a></li>
      <li><a href="admin-manage-member.php"><i class="fas fa-users"></i> <span class="menu-text">Manage Members</span></a></li>
      <li><a href="admin-manage-categories.php" class="active"><i class="fas fa-tags"></i> <span class="menu-text">Categories</span></a></li>
      <li><a href="admin-manage-product.php"><i class="fas fa-utensils"></i> <span class="menu-text">Products</span></a></li>
      <li><a href="admin-manage-orders.php"><i class="fas fa-shopping-basket"></i> <span class="menu-text">Orders</span></a></li>
      <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> <span class="menu-text">Sales Reports</span></a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="admin-content">
    <div class="management-header">
      <div class="page-title">
        <h2>Categories Management</h2>
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

    <!-- Category Filter Dropdown with Add Category Button -->
    <div class="filter-dropdown">
      <select id="category-filter">
        <option value="all">All Categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?php echo htmlspecialchars($category['id']); ?>">
            <?php echo htmlspecialchars($category['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
        <button class="btn-add-category" onclick="openAddCategoryModal()">
          <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <!-- Categories Table -->
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Category ID</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="4" style="text-align: center;">No categories found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $category): ?>
              <tr>
                <td>C<?php echo str_pad($category['id'], 3, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo htmlspecialchars($category['description']); ?></td>
                <td>
                  <button class="action-btn" onclick="openEditCategoryModal(
                    <?php echo $category['id']; ?>,
                    '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($category['description'] ?? '', ENT_QUOTES); ?>'
                  )">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="action-btn" onclick="confirmDeleteCategory(<?php echo $category['id']; ?>)">
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
    &copy; 2025 BakeEase Bakery. All Rights Reserved.
  </footer>

  <!-- Add Category Modal -->
  <div id="addCategoryModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Category</h3>
        <span class="close" onclick="closeAddCategoryModal()">&times;</span>
      </div>
      <form action="admin-manage-categories.php" method="POST">
        <div class="form-group">
          <label for="category-name">Category Name</label>
          <input type="text" id="category-name" name="category_name" required>
        </div>
        <div class="form-group">
          <label for="category-description">Description</label>
          <textarea id="category-description" name="category_description"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeAddCategoryModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="add_category">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Category Modal -->
  <div id="editCategoryModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Category</h3>
        <span class="close" onclick="closeEditCategoryModal()">&times;</span>
      </div>
      <form action="admin-manage-categories.php" method="POST">
        <input type="hidden" id="edit-category-id" name="category_id">
        <div class="form-group">
          <label for="edit-category-name">Category Name</label>
          <input type="text" id="edit-category-name" name="category_name" required>
        </div>
        <div class="form-group">
          <label for="edit-category-description">Description</label>
          <textarea id="edit-category-description" name="category_description"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_category">Update</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteCategoryModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Delete</h3>
        <span class="close" onclick="closeDeleteCategoryModal()">&times;</span>
      </div>
      <form action="admin-manage-categories.php" method="POST">
        <input type="hidden" id="delete-category-id" name="category_id">
        <p>Are you sure you want to delete this category? This action cannot be undone.</p>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeDeleteCategoryModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="delete_category">Delete</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    // Category Modal functions
    function openAddCategoryModal() {
      document.getElementById('addCategoryModal').style.display = 'block';
    }

    function closeAddCategoryModal() {
      document.getElementById('addCategoryModal').style.display = 'none';
    }

    function openEditCategoryModal(id, name, description) {
      document.getElementById('edit-category-id').value = id;
      document.getElementById('edit-category-name').value = name;
      document.getElementById('edit-category-description').value = description;
      document.getElementById('editCategoryModal').style.display = 'block';
    }

    function closeEditCategoryModal() {
      document.getElementById('editCategoryModal').style.display = 'none';
    }

    function confirmDeleteCategory(id) {
      document.getElementById('delete-category-id').value = id;
      document.getElementById('deleteCategoryModal').style.display = 'block';
    }

    function closeDeleteCategoryModal() {
      document.getElementById('deleteCategoryModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        document.getElementById('addCategoryModal').style.display = 'none';
        document.getElementById('editCategoryModal').style.display = 'none';
        document.getElementById('deleteCategoryModal').style.display = 'none';
      }
    }

    // Filter categories
    document.getElementById('category-filter').addEventListener('change', function() {
      const filterValue = this.value.toLowerCase();
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const categoryId = row.cells[0].textContent.toLowerCase();
        const categoryName = row.cells[1].textContent.toLowerCase();
        
        if (filterValue === 'all' || categoryId.includes(filterValue) || categoryName.includes(filterValue)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>