<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_order'])) {
        $customer_id = $_POST['customer_id'];
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $unit_price = (float)$_POST['unit_price'];
        $order_date = date('Y-m-d');
        $user_id = $_SESSION['user_id'];
        $total_amount = $quantity * $unit_price;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT QuantityInStock FROM Inventory WHERE ProductID = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $stock = $stmt->fetchColumn();

            if ($stock < $quantity) {
                throw new Exception("Not enough stock available for this product.");
            }

            $stmt = $pdo->prepare("INSERT INTO SalesOrder (CustomerID, UserID, OrderDate, TotalAmount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$customer_id, $user_id, $order_date, $total_amount]);
            $order_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO OrderItem (OrderID, ProductID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $quantity, $unit_price]);

            $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock - ? WHERE ProductID = ?");
            $stmt->execute([$quantity, $product_id]);

            $pdo->commit();
            header("Location: sales_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_order'])) {
        $order_id = $_POST['order_id'];
        $customer_id = $_POST['customer_id'];
        $new_product_id = $_POST['product_id'];
        $new_quantity = (int)$_POST['quantity'];
        $new_unit_price = (float)$_POST['unit_price'];
        $new_total_amount = $new_quantity * $new_unit_price;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT ProductID, Quantity FROM OrderItem WHERE OrderID = ?");
            $stmt->execute([$order_id]);
            $old_item = $stmt->fetch();

            if ($old_item) {
                $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock + ? WHERE ProductID = ?");
                $stmt->execute([$old_item['Quantity'], $old_item['ProductID']]);
            }

            $stmt = $pdo->prepare("SELECT QuantityInStock FROM Inventory WHERE ProductID = ? FOR UPDATE");
            $stmt->execute([$new_product_id]);
            $current_stock = $stmt->fetchColumn();

            if ($current_stock < $new_quantity) {
                throw new Exception("Not enough stock available for the updated quantity/product.");
            }

            $stmt = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock - ? WHERE ProductID = ?");
            $stmt->execute([$new_quantity, $new_product_id]);

            $stmt = $pdo->prepare("UPDATE OrderItem SET ProductID = ?, Quantity = ?, UnitPrice = ? WHERE OrderID = ?");
            $stmt->execute([$new_product_id, $new_quantity, $new_unit_price, $order_id]);

            $stmt = $pdo->prepare("UPDATE SalesOrder SET CustomerID = ?, TotalAmount = ? WHERE OrderID = ?");
            $stmt->execute([$customer_id, $new_total_amount, $order_id]);

            $pdo->commit();
            header("Location: sales_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_order'])) {
        $order_id = $_POST['order_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT ProductID, Quantity FROM OrderItem WHERE OrderID = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();

            foreach ($items as $item) {
                $stmt_inv = $pdo->prepare("UPDATE Inventory SET QuantityInStock = QuantityInStock + ? WHERE ProductID = ?");
                $stmt_inv->execute([$item['Quantity'], $item['ProductID']]);
            }

            $stmt = $pdo->prepare("DELETE FROM OrderItem WHERE OrderID = ?");
            $stmt->execute([$order_id]);

            $stmt = $pdo->prepare("DELETE FROM SalesOrder WHERE OrderID = ?");
            $stmt->execute([$order_id]);

            $pdo->commit();
            header("Location: sales_orders.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred while deleting the order.";
        }
    }
}

$edit_order = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT so.OrderID, so.CustomerID, oi.ProductID, oi.Quantity, oi.UnitPrice FROM SalesOrder so JOIN OrderItem oi ON so.OrderID = oi.OrderID WHERE so.OrderID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_order = $stmt->fetch();
}

$customers_stmt = $pdo->query("SELECT CustomerID, Name FROM Customer ORDER BY Name ASC");
$customers = $customers_stmt->fetchAll();

$products_stmt = $pdo->query("SELECT p.ProductID, p.Name, i.QuantityInStock FROM Product p JOIN Inventory i ON p.ProductID = i.ProductID ORDER BY p.Name ASC");
$products = $products_stmt->fetchAll();

$orders_stmt = $pdo->query("SELECT so.OrderID, so.OrderDate, so.TotalAmount, c.Name AS CustomerName, u.Username FROM SalesOrder so JOIN Customer c ON so.CustomerID = c.CustomerID JOIN User u ON so.UserID = u.UserID ORDER BY so.OrderID DESC");
$orders = $orders_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Orders - Inventory System</title>
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
    <a href="sales_orders.php" style="background: #555;">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
    <a href="users.php">Users</a>
</div>

<div class="main-content">
    <h1>Manage Sales Orders</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_order ? 'Edit Order' : 'Create New Order'; ?></h3>
        <form method="POST" action="sales_orders.php">
            <?php if ($edit_order): ?>
                <input type="hidden" name="order_id" value="<?php echo $edit_order['OrderID']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Select a Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['CustomerID']); ?>" <?php echo ($edit_order && $edit_order['CustomerID'] == $customer['CustomerID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select a Product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['ProductID']); ?>" <?php echo ($edit_order && $edit_order['ProductID'] == $product['ProductID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['Name']); ?> (In Stock: <?php echo htmlspecialchars($product['QuantityInStock']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" value="<?php echo $edit_order ? htmlspecialchars($edit_order['Quantity']) : ''; ?>" required min="1">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="unit_price">Unit Price ($)</label>
                    <input type="number" id="unit_price" name="unit_price" value="<?php echo $edit_order ? htmlspecialchars($edit_order['UnitPrice']) : ''; ?>" required step="0.01" min="0.01">
                </div>
            </div>
            
            <?php if ($edit_order): ?>
                <button type="submit" name="update_order">Update Order</button>
                <a href="sales_orders.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="create_order">Process Sale</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Recent Sales Orders</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Processed By</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                    <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                    <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                    <td><?php echo htmlspecialchars($order['Username']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($order['TotalAmount'], 2)); ?></td>
                    <td class="action-forms">
                        <a href="sales_orders.php?edit_id=<?php echo $order['OrderID']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" action="sales_orders.php" onsubmit="return confirm('Are you sure you want to delete this order? Inventory will be restored.');" style="margin:0;">
                            <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                            <button type="submit" name="delete_order" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No sales orders found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>