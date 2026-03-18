<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_inventory'])) {
    $inventory_id = $_POST['inventory_id'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];

    $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = ?, ReorderLevel = ? WHERE InventoryID = ?");
    $stmt->execute([$quantity, $reorder_level, $inventory_id]);

    header("Location: inventory.php");
    exit;
}

$stmt = $pdo->query("SELECT i.InventoryID, i.QuantityInStock, i.ReorderLevel, p.Name, p.Brand FROM Inventory i JOIN Product p ON i.ProductID = p.ProductID ORDER BY p.Name ASC");
$inventory_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Inventory System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f9; }
        header { background-color: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; background: #dc3545; padding: 8px 15px; border-radius: 4px; }
        header a:hover { background: #c82333; }
        .sidebar { width: 200px; background: #333; color: white; position: fixed; height: 100vh; padding-top: 20px; }
        .sidebar a { display: block; color: white; padding: 15px; text-decoration: none; border-bottom: 1px solid #444; }
        .sidebar a:hover { background: #555; }
        .main-content { margin-left: 200px; padding: 20px; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: middle; }
        th { background-color: #f8f9fa; }
        input[type="number"] { width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 6px 12px; background-color: #ffc107; color: black; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #e0a800; }
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
    <a href="inventory.php" style="background: #555;">Inventory</a>
    <a href="#">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="customers.php">Customers</a>
</div>

<div class="main-content">
    <h1>Manage Inventory</h1>

    <div class="table-container">
        <h3>Current Stock Levels</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Quantity In Stock</th>
                    <th>Reorder Level</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_items as $item): ?>
                <tr>
                    <form method="POST" action="">
                        <td><?php echo htmlspecialchars($item['Name']); ?></td>
                        <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                        <td>
                            <input type="hidden" name="inventory_id" value="<?php echo $item['InventoryID']; ?>">
                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['QuantityInStock']); ?>" required min="0">
                        </td>
                        <td>
                            <input type="number" name="reorder_level" value="<?php echo htmlspecialchars($item['ReorderLevel']); ?>" required min="0">
                        </td>
                        <td>
                            <button type="submit" name="update_inventory">Update</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($inventory_items)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No inventory records found. Add products first.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>