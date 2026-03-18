<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f9; }
        header { background-color: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; background: #dc3545; padding: 8px 15px; border-radius: 4px; }
        header a:hover { background: #c82333; }
        .sidebar { width: 200px; background: #333; color: white; position: fixed; height: 100vh; padding-top: 20px; }
        .sidebar a { display: block; color: white; padding: 15px; text-decoration: none; border-bottom: 1px solid #444; }
        .sidebar a:hover { background: #555; }
        .main-content { margin-left: 200px; padding: 20px; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .dashboard-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; text-decoration: none; color: #333; transition: transform 0.2s; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .dashboard-card h3 { margin: 0 0 10px 0; color: #0056b3; }
        .dashboard-card p { margin: 0; color: #666; }
    </style>
</head>
<body>

<header>
    <div>
        <h2>Inventory System</h2>
    </div>
    <div>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="sidebar">
    <a href="dashboard.php" style="background: #555;">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="inventory.php">Inventory</a>
    <a href="sales_orders.php">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
    <a href="users.php">Users</a>
    <a href="reports.php">Reports & Backup</a>
</div>

<div class="main-content">
    <h1>Dashboard</h1>
    <p>Welcome to your central control panel. Select a module below or from the sidebar to manage your system.</p>

    <div class="dashboard-grid">
        <a href="sales_orders.php" class="dashboard-card">
            <h3>Process Sales</h3>
            <p>Create and manage customer orders.</p>
        </a>
        <a href="inventory.php" class="dashboard-card">
            <h3>Check Inventory</h3>
            <p>View stock levels and reorder alerts.</p>
        </a>
        <a href="products.php" class="dashboard-card">
            <h3>Manage Products</h3>
            <p>Add or edit items in your catalog.</p>
        </a>
        <a href="supplier_orders.php" class="dashboard-card">
            <h3>Restock Items</h3>
            <p>Order more inventory from suppliers.</p>
        </a>
        <a href="reports.php" class="dashboard-card">
            <h3>View Reports</h3>
            <p>See sales data and backup the database.</p>
        </a>
        <a href="users.php" class="dashboard-card">
            <h3>Manage Staff</h3>
            <p>Add or remove user accounts.</p>
        </a>
    </div>
</div>

</body>
</html>