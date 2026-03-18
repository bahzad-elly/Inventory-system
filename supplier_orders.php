<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_supplier_order'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $order_date = date('Y-m-d');
        $user_id = $_SESSION['user_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT SupplierID FROM Product WHERE ProductID = ?");
            $stmt->execute([$product_id]);
            $supplier_id = $stmt->fetchColumn();

            if (!$supplier_id) {
                throw new Exception("Invalid product selection.");
            }

            $stmt = $pdo->prepare("INSERT INTO SupplierOrder (SupplierID, ProductID, UserID, OrderDate, QuantityOrdered) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$supplier_id, $product_id, $user_id, $order_date, $quantity]);

            $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock + ? WHERE ProductID = ?");
            $stmt->execute([$quantity, $product_id]);

            $pdo->commit();
            header("Location: supplier_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_supplier_order'])) {
        $order_id = $_POST['order_id'];
        $new_product_id = $_POST['product_id'];
        $new_quantity = (int)$_POST['quantity'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT ProductID, QuantityOrdered FROM SupplierOrder WHERE SupplierOrderID = ?");
            $stmt->execute([$order_id]);
            $old_order = $stmt->fetch();

            if ($old_order) {
                $stmt = $pdo->prepare("SELECT QuantityInStock FROM Inventory WHERE ProductID = ? FOR UPDATE");
                $stmt->execute([$old_order['ProductID']]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock < $old_order['QuantityOrdered']) {
                    throw new Exception("Cannot update: the original items from this restock have already been sold.");
                }

                $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock - ? WHERE ProductID = ?");
                $stmt->execute([$old_order['QuantityOrdered'], $old_order['ProductID']]);
            }

            $stmt = $pdo->prepare("SELECT SupplierID FROM Product WHERE ProductID = ?");
            $stmt->execute([$new_product_id]);
            $new_supplier_id = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock + ? WHERE ProductID = ?");
            $stmt->execute([$new_quantity, $new_product_id]);

            $stmt = $pdo->prepare("UPDATE SupplierOrder SET SupplierID = ?, ProductID = ?, QuantityOrdered = ? WHERE SupplierOrderID = ?");
            $stmt->execute([$new_supplier_id, $new_product_id, $new_quantity, $order_id]);

            $pdo->commit();
            header("Location: supplier_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_supplier_order'])) {
        $order_id = $_POST['order_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT ProductID, QuantityOrdered FROM SupplierOrder WHERE SupplierOrderID = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if ($order) {
                $stmt = $pdo->prepare("SELECT QuantityInStock FROM Inventory WHERE ProductID = ? FOR UPDATE");
                $stmt->execute([$order['ProductID']]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock < $order['QuantityOrdered']) {
                    throw new Exception("Cannot delete this order because some of these items have already been sold.");
                }

                $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock - ? WHERE ProductID = ?");
                $stmt->execute([$order['QuantityOrdered'], $order['ProductID']]);
            }

            $stmt = $pdo->prepare("DELETE FROM SupplierOrder WHERE SupplierOrderID = ?");
            $stmt->execute([$order_id]);

            $pdo->commit();
            header("Location: supplier_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$edit_order = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT SupplierOrderID, ProductID, QuantityOrdered FROM SupplierOrder WHERE SupplierOrderID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_order = $stmt->fetch();
}

$products_stmt = $pdo->query("SELECT p.ProductID, p.Name, s.SupplierName FROM Product p JOIN Supplier s ON p.SupplierID = s.SupplierID ORDER BY p.Name ASC");
$products = $products_stmt->fetchAll();

$orders_stmt = $pdo->query("SELECT so.SupplierOrderID, so.OrderDate, so.QuantityOrdered, p.Name AS ProductName, s.SupplierName, u.Username FROM SupplierOrder so JOIN Product p ON so.ProductID = p.ProductID JOIN Supplier s ON so.SupplierID = s.SupplierID JOIN User u ON so.UserID = u.UserID ORDER BY so.SupplierOrderID DESC");
$orders = $orders_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Orders - Inventory System</title>
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
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .btn-edit { background-color: #17a2b8; padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; font-size: 14px; }
        .btn-edit:hover { background-color: #138496; }
        .btn-delete { background-color: #dc3545; padding: 5px 10px; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-cancel { background-color: #6c757d; padding: 10px 15px; text-decoration: none; color: white; border-radius: 4px; margin-left: 10px; }
        .btn-cancel:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; }
        .action-forms { display: flex; gap: 5px; align-items: center; }
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
    <a href="supplier_orders.php" style="background: #555;">Restock Orders</a>
    <a href="customers.php">Customers</a>
    <a href="users.php">Users</a>
</div>

<div class="main-content">
    <h1>Manage Restock Orders</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_order ? 'Edit Restock Order' : 'Place Restock Order'; ?></h3>
        <form method="POST" action="supplier_orders.php">
            <?php if ($edit_order): ?>
                <input type="hidden" name="order_id" value="<?php echo $edit_order['SupplierOrderID']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select a Product to Restock</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['ProductID']); ?>" <?php echo ($edit_order && $edit_order['ProductID'] == $product['ProductID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['Name']); ?> (Supplier: <?php echo htmlspecialchars($product['SupplierName']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity to Order</label>
                <input type="number" id="quantity" name="quantity" value="<?php echo $edit_order ? htmlspecialchars($edit_order['QuantityOrdered']) : ''; ?>" required min="1">
            </div>
            
            <?php if ($edit_order): ?>
                <button type="submit" name="update_supplier_order">Update Order</button>
                <a href="supplier_orders.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="create_supplier_order">Place Order</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Restock History</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Ordered By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['SupplierOrderID']); ?></td>
                    <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                    <td><?php echo htmlspecialchars($order['SupplierName']); ?></td>
                    <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                    <td><?php echo htmlspecialchars($order['QuantityOrdered']); ?></td>
                    <td><?php echo htmlspecialchars($order['Username']); ?></td>
                    <td class="action-forms">
                        <a href="supplier_orders.php?edit_id=<?php echo $order['SupplierOrderID']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" action="supplier_orders.php" onsubmit="return confirm('Are you sure you want to delete this order? This will reduce your current inventory.');" style="margin:0;">
                            <input type="hidden" name="order_id" value="<?php echo $order['SupplierOrderID']; ?>">
                            <button type="submit" name="delete_supplier_order" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No restock orders found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>