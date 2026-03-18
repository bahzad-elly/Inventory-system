<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$stmt_products = $pdo->query("SELECT COUNT(*) FROM Product");
$total_products = $stmt_products->fetchColumn();

$stmt_low_stock = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE QuantityInStock <= ReorderLevel");
$low_stock_alerts = $stmt_low_stock->fetchColumn();

$stmt_revenue = $pdo->query("SELECT SUM(TotalAmount) FROM SalesOrder");
$total_revenue = $stmt_revenue->fetchColumn();
if (!$total_revenue) {
    $total_revenue = 0;
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
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .card h3 { margin-top: 0; color: #555; }
        .card p { font-size: 24px; font-weight: bold; margin: 0; color: #0056b3; }
        .card.alert p { color: #dc3545; }
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
    <a href="dashboard.php" style="background: #555;">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="inventory.php">Inventory</a>
    <a href="sales_orders.php">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="customers.php">Customers</a>
</div>

<div class="main-content">
    <h1>Dashboard Overview</h1>
    
    <div class="cards">
        <div class="card">
            <h3>Total Products</h3>
            <p><?php echo htmlspecialchars($total_products); ?></p>
        </div>
        <div class="card <?php echo ($low_stock_alerts > 0) ? 'alert' : ''; ?>">
            <h3>Low Stock Alerts</h3>
            <p><?php echo htmlspecialchars($low_stock_alerts); ?></p>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <p>$<?php echo htmlspecialchars(number_format($total_revenue, 2)); ?></p>
        </div>
    </div>
</div>

</body>
</html>