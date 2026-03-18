<?php
// dashboard.php
session_start();

// Check if the user is logged in. If not, redirect to login page.
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
        .cards { display: flex; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; }
    </style>
</head>
<body>

<header>
    <div>
        <h2>Inventory System</h2>
    </div>
    <div>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="sidebar">
    <a href="dashboard.php">Dashboard</a>
    <a href="#">Products</a>
    <a href="#">Inventory</a>
    <a href="#">Sales Orders</a>
    <a href="#">Suppliers</a>
    <a href="#">Customers</a>
</div>

<div class="main-content">
    <h1>Dashboard Overview</h1>
    <p>Select an option from the sidebar to manage your system.</p>
    
    <div class="cards">
        <div class="card">
            <h3>Total Products</h3>
            <p>0</p> </div>
        <div class="card">
            <h3>Low Stock Alerts</h3>
            <p>0</p>
        </div>
        <div class="card">
            <h3>Recent Sales</h3>
            <p>$0.00</p>
        </div>
    </div>
</div>

</body>
</html>