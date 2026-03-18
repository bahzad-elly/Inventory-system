<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_inventory'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $reorder_level = (int)$_POST['reorder_level'];

        try {
            $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = ?, ReorderLevel = ? WHERE ProductID = ?");
            $stmt->execute([$quantity, $reorder_level, $product_id]);
            
            header("Location: inventory.php");
            exit;
        } catch (PDOException $e) {
            $error = "An error occurred while updating the inventory.";
        }
    }
}

$edit_item = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT i.ProductID, i.QuantityInStock, i.ReorderLevel, p.Name AS ProductName FROM Inventory i JOIN Product p ON i.ProductID = p.ProductID WHERE i.ProductID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_item = $stmt->fetch();
}

$stmt = $pdo->query("SELECT i.ProductID, i.QuantityInStock, i.ReorderLevel, p.Name AS ProductName, p.Brand, s.SupplierName FROM Inventory i JOIN Product p ON i.ProductID = p.ProductID JOIN Supplier s ON p.SupplierID = s.SupplierID ORDER BY p.Name ASC");
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
        .form-container, .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .btn-edit { background-color: #17a2b8; padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; font-size: 14px; }
        .btn-edit:hover { background-color: #138496; }
        .btn-cancel { background-color: #6c757d; padding: 10px 15px; text-decoration: none; color: white; border-radius: 4px; margin-left: 10px; }
        .btn-cancel:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; }
        .status-ok { color: green; font-weight: bold; }
        .status-low { color: red; font-weight: bold; }
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
    <a href="sales_orders.php">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
    <a href="users.php">Users</a>
</div>

<div class="main-content">
    <h1>Manage Inventory</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($edit_item): ?>
    <div class="form-container">
        <h3>Adjust Stock Levels: <?php echo htmlspecialchars($edit_item['ProductName']); ?></h3>
        <form method="POST" action="inventory.php">
            <input type="hidden" name="product_id" value="<?php echo $edit_item['ProductID']; ?>">
            
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" value="<?php echo htmlspecialchars($edit_item['ProductName']); ?>" readonly>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="quantity">Quantity in Stock</label>
                    <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($edit_item['QuantityInStock']); ?>" required min="0">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="reorder_level">Reorder Level (Alert Threshold)</label>
                    <input type="number" id="reorder_level" name="reorder_level" value="<?php echo htmlspecialchars($edit_item['ReorderLevel']); ?>" required min="0">
                </div>
            </div>
            
            <button type="submit" name="update_inventory">Save Adjustments</button>
            <a href="inventory.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <h3>Current Stock</h3>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Supplier</th>
                    <th>In Stock</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_items as $item): ?>
                <?php $is_low = $item['QuantityInStock'] <= $item['ReorderLevel']; ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ProductID']); ?></td>
                    <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                    <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                    <td><?php echo htmlspecialchars($item['SupplierName']); ?></td>
                    <td><strong><?php echo htmlspecialchars($item['QuantityInStock']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['ReorderLevel']); ?></td>
                    <td>
                        <?php if ($is_low): ?>
                            <span class="status-low">Low Stock</span>
                        <?php else: ?>
                            <span class="status-ok">In Stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="inventory.php?edit_id=<?php echo $item['ProductID']; ?>" class="btn-edit">Adjust Stock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($inventory_items)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No inventory records found. Add a product first.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>