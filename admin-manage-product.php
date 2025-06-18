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
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = $_POST['product_name'];
        $description = $_POST['product_description'];
        $price = $_POST['product_price'];
        $category = $_POST['product_category'];
        $stock = $_POST['product_stock'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category, $stock]);
            $success_message = "Product added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding product: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_product'])) {
        // Update existing product
        $id = $_POST['product_id'];
        $name = $_POST['product_name'];
        $description = $_POST['product_description'];
        $price = $_POST['product_price'];
        $category = $_POST['product_category'];
        $stock = $_POST['product_stock'];
        
        try {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $category, $stock, $id]);
            $success_message = "Product updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating product: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $id = $_POST['product_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Product deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Get all products from database
$products = [];
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT * FROM products";
$conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $conditions[] = "name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($category_filter)) {
    $conditions[] = "category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY name";

try {
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}

// Get distinct categories for filter dropdown
$categories = [];
try {
    $result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL");
    $categories = array_column($result->fetch_all(MYSQLI_ASSOC), 'category');
    $result->close();
} catch (Exception $e) {
    // If error, categories dropdown will just show "All Categories"
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="images/png" href="images/logo.png" />
  <title>BakeEase - Manage Products</title>
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
      margin-bottom: 20px;
    }

    .management-header h2 {
      font-size: 1.8rem;
      color: var(--brown);
    }

    .management-header button {
      font-size: 1rem;
      padding: 10px 15px;
      background-color: var(--primary);
      color: var(--white);
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .management-header button:hover {
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
      margin-left: auto;
    }

    .action-buttons button{
      font-size: 1rem;
        padding: 10px 15px;
        background-color: var(--primary);
        color: var(--white);
        border: none;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s ease;
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

    .badge-danger {
      background-color: #ffebee;
      color: #e74c3c;
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
      outline: 0;
      background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(3px);
      animation: fadeIn 0.3s ease-out;
    }

    .modal-content {
      position: relative;
      width: 500px;
      margin: auto;
      background-color: #fff;
      border: 1px solid rgba(0,0,0,.2);
      border-radius: 0.3rem;
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      padding: 20px;
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

    @media (max-width: 576px) {
      .modal-content {
        width: 95%;
        margin: 10px auto;
      }
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

    .filter-dropdown {
      flex: 0 0 auto;
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
      <li><a href="admin-manage-categories.php"><i class="fas fa-tags"></i> <span class="menu-text">Categories</span></a></li>
      <li><a href="admin-manage-product.php" class="active"><i class="fas fa-utensils"></i> <span class="menu-text">Products</span></a></li>
      <li><a href="admin-manage-orders.php"><i class="fas fa-shopping-basket"></i> <span class="menu-text">Orders</span></a></li>
      <li><a href="admin-sales-reports.php"><i class="fas fa-chart-line"></i> <span class="menu-text">Sales Reports</span></a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="admin-content">
    <div class="management-header">
      <div class="page-title">
        <h2>Products Management</h2>
      </div>
    </div>

    <div class="search-filter">
      <form method="GET" action="admin-manage-product.php" class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
      </form>
      <form method="GET" action="admin-manage-product.php" class="filter-dropdown">
        <select name="category" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $category) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($category); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($_GET['search'])): ?>
          <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
        <?php endif; ?>
      </form>
      <div class="action-buttons">
        <button onclick="openAddModal()"><i class="fas fa-plus"></i> Add Product</button>
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

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="7" style="text-align: center;">No products found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $product): ?>
              <tr>
                <td>PR<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></td>
                <td>RM <?php echo number_format($product['price'], 2); ?></td>
                <td><?php echo $product['stock']; ?></td>
                <td>
                  <span class="badge <?php echo ($product['stock'] > 0) ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo ($product['stock'] > 0) ? 'Available' : 'Out of Stock'; ?>
                  </span>
                </td>
                <td>
                  <button class="action-btn" onclick="openEditModal(
                    <?php echo $product['id']; ?>,
                    '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?>',
                    '<?php echo $product['price']; ?>',
                    '<?php echo htmlspecialchars($product['category'] ?? '', ENT_QUOTES); ?>',
                    '<?php echo $product['stock']; ?>'
                  )">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="action-btn" onclick="confirmDelete(<?php echo $product['id']; ?>)">
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
    <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
  </footer>

  <!-- Add Product Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Product</h3>
        <span class="close" onclick="closeAddModal()">&times;</span>
      </div>
      <form action="admin-manage-product.php" method="POST">
        <div class="form-group">
          <label for="add-product-name">Product Name</label>
          <input type="text" id="add-product-name" name="product_name" required>
        </div>
        <div class="form-group">
          <label for="add-product-description">Description</label>
          <textarea id="add-product-description" name="product_description"></textarea>
        </div>
        <div class="form-group">
          <label for="add-product-price">Price (RM)</label>
          <input type="number" id="add-product-price" name="product_price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label for="add-product-category">Category</label>
          <input type="text" id="add-product-category" name="product_category" list="categories-list">
          <datalist id="categories-list">
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo htmlspecialchars($category); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label for="add-product-stock">Stock</label>
          <input type="number" id="add-product-stock" name="product_stock" min="0" required>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="add_product">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Product</h3>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <form action="admin-manage-product.php" method="POST">
        <input type="hidden" id="edit-product-id" name="product_id">
        <div class="form-group">
          <label for="edit-product-name">Product Name</label>
          <input type="text" id="edit-product-name" name="product_name" required>
        </div>
        <div class="form-group">
          <label for="edit-product-description">Description</label>
          <textarea id="edit-product-description" name="product_description"></textarea>
        </div>
        <div class="form-group">
          <label for="edit-product-price">Price (RM)</label>
          <input type="number" id="edit-product-price" name="product_price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label for="edit-product-category">Category</label>
          <input type="text" id="edit-product-category" name="product_category" list="categories-list">
          <datalist id="categories-list">
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo htmlspecialchars($category); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label for="edit-product-stock">Stock</label>
          <input type="number" id="edit-product-stock" name="product_stock" min="0" required>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_product">Update</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content" style="width: 50%;">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Delete</h3>
        <span class="close" onclick="closeDeleteModal()">&times;</span>
      </div>
      <form action="admin-manage-product.php" method="POST">
        <input type="hidden" id="delete-product-id" name="product_id">
        <p>Are you sure you want to delete this product? This action cannot be undone.</p>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" name="delete_product">Delete</button>
        </div>
      </form>
    </div>
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

    function openEditModal(id, name, description, price, category, stock) {
      document.getElementById('edit-product-id').value = id;
      document.getElementById('edit-product-name').value = name;
      document.getElementById('edit-product-description').value = description;
      document.getElementById('edit-product-price').value = price;
      document.getElementById('edit-product-category').value = category;
      document.getElementById('edit-product-stock').value = stock;
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(id) {
      document.getElementById('delete-product-id').value = id;
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
  </script>
</body>
</html>