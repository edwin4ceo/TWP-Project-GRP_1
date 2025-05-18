<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - BakeEase</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f9;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 220px;
      background-color: #2c3e50;
      padding-top: 20px;
      color: white;
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .sidebar a {
      display: block;
      color: white;
      padding: 12px 20px;
      text-decoration: none;
      transition: background 0.3s;
    }
    .sidebar a:hover {
      background-color: #34495e;
    }
    .main-content {
      margin-left: 220px;
      padding: 20px;
    }
    .dashboard-title {
      margin-bottom: 20px;
    }
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 20px;
    }
    .card {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card i {
      font-size: 24px;
      margin-bottom: 10px;
      color: #2980b9;
    }
    .card h3 {
      margin: 10px 0 5px;
    }
    .card p {
      color: #555;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#dashboard"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="#staff"><i class="fas fa-users-cog"></i> Manage Staff</a>
    <a href="#members"><i class="fas fa-user-friends"></i> Manage Members</a>
    <a href="#categories"><i class="fas fa-tags"></i> Categories</a>
    <a href="#products"><i class="fas fa-box"></i> Products/Services</a>
    <a href="#orders"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="#sales"><i class="fas fa-file-invoice-dollar"></i> Sales Report</a>
  </div>

  <div class="main-content">
    <h1 class="dashboard-title">Dashboard</h1>
    <div class="cards">
      <div class="card">
        <i class="fas fa-file-alt"></i>
        <h3>4</h3>
        <p>Total Static Pages</p>
      </div>
      <div class="card">
        <i class="fas fa-folder"></i>
        <h3>15</h3>
        <p>Total Categories</p>
      </div>
      <div class="card">
        <i class="fas fa-box"></i>
        <h3>70</h3>
        <p>Total Items</p>
      </div>
      <div class="card">
        <i class="fas fa-shopping-bag"></i>
        <h3>20</h3>
        <p>Total Orders</p>
      </div>
      <div class="card">
        <i class="fas fa-user-friends"></i>
        <h3>30</h3>
        <p>Total Members</p>
      </div>
      <div class="card">
        <i class="fas fa-users-cog"></i>
        <h3>50</h3>
        <p>Total Staff</p>
      </div>
      <div class="card">
        <i class="fas fa-dollar-sign"></i>
        <h3>65</h3>
        <p>Total Revenue</p>
      </div>
    </div>
  </div>
</body>
</html>