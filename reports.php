<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['download_backup'])) {
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql_dump = "-- Inventory System Database Backup\n";
    $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $row[1] . ";\n\n";

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $escaped_keys = array_map(function($key) { return "`$key`"; }, $keys);
                
                $sql_dump .= "INSERT INTO `$table` (" . implode(", ", $escaped_keys) . ") VALUES (";
                
                $vals = [];
                foreach ($row as $val) {
                    if (is_null($val)) {
                        $vals[] = "NULL";
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $sql_dump .= implode(", ", $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }

    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="inventory_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Content-Length: ' . strlen($sql_dump));
    
    echo $sql_dump;
    exit;
}

$total_sales_stmt = $pdo->query("SELECT SUM(TotalAmount) FROM SalesOrder");
$total_sales = $total_sales_stmt->fetchColumn() ?: 0;

$total_customers_stmt = $pdo->query("SELECT COUNT(*) FROM Customer");
$total_customers = $total_customers_stmt->fetchColumn();

$total_products_stmt = $pdo->query("SELECT COUNT(*) FROM Product");
$total_products = $total_products_stmt->fetchColumn();

$low_stock_stmt = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE QuantityInStock <= ReorderLevel");
$low_stock_items = $low_stock_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Backup - Inventory System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f9; }
        header { background-color: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; background: #dc3545; padding: 8px 15px; border-radius: 4px; }
        header a:hover { background: #c82333; }
        .sidebar { width: 200px; background: #333; color: white; position: fixed; height: 100vh; padding-top: 20px; }
        .sidebar a { display: block; color: white; padding: 15px; text-decoration: none; border-bottom: 1px solid #444; }
        .sidebar a:hover { background: #555; }
        .main-content { margin-left: 200px; padding: 20px; }
        .card-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .card h3 { margin-top: 0; color: #333; font-size: 18px; }
        .card .value { font-size: 32px; font-weight: bold; color: #0056b3; margin: 10px 0; }
        .card.warning .value { color: #dc3545; }
        .backup-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .backup-container h2 { margin-top: 0; }
        .backup-container p { color: #666; margin-bottom: 20px; }
        button { padding: 15px 30px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; font-weight: bold; }
        button:hover { background-color: #218838; }
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
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="inventory.php">Inventory</a>
    <a href="sales_orders.php">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
    <a href="users.php">Users</a>
    <a href="reports.php" style="background: #555;">Reports & Backup</a>
</div>

<div class="main-content">
    <h1>System Reports</h1>

    <div class="card-container">
        <div class="card">
            <h3>Total Sales Revenue</h3>
            <div class="value">$<?php echo number_format($total_sales, 2); ?></div>
        </div>
        <div class="card">
            <h3>Registered Customers</h3>
            <div class="value"><?php echo $total_customers; ?></div>
        </div>
        <div class="card">
            <h3>Products in Catalog</h3>
            <div class="value"><?php echo $total_products; ?></div>
        </div>
        <div class="card warning">
            <h3>Low Stock Alerts</h3>
            <div class="value"><?php echo $low_stock_items; ?></div>
        </div>
    </div>

    <div class="backup-container">
        <h2>Database Management</h2>
        <p>Keep your data safe. Click the button below to generate and download a complete SQL backup of your entire database system, including all tables, products, sales, and user accounts.</p>
        <form method="POST" action="reports.php">
            <button type="submit" name="download_backup">Download Full Database Backup</button>
        </form>
    </div>
</div>

</body>
</html>