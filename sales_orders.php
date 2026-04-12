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

<?php
$page_title = 'Sales Orders - Inventory System';
$page_title_en = 'Sales Orders';
$page_title_ku = 'فرۆشتنەکان';
include 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="badge-warning" style="width: 100%; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-cart-plus"></i> 
            <span data-lang-en><?php echo $edit_order ? 'Edit Order' : 'Create New Sale'; ?></span>
            <span data-lang-ckb><?php echo $edit_order ? 'دەستکاری داواکاری' : 'فرۆشتنی نوێ'; ?></span>
        </h2>
    </div>
    <form method="POST" action="sales_orders.php">
        <?php if ($edit_order): ?>
            <input type="hidden" name="order_id" value="<?php echo $edit_order['OrderID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="customer_id">
                    <span data-lang-en>Customer</span>
                    <span data-lang-ckb>کڕیار</span>
                </label>
                <select id="customer_id" name="customer_id" required>
                    <option value=""><span data-lang-en>Select a Customer</span><span data-lang-ckb>کڕیارێک هەڵبژێرە</span></option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['CustomerID']); ?>" <?php echo ($edit_order && $edit_order['CustomerID'] == $customer['CustomerID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="product_id">
                    <span data-lang-en>Product</span>
                    <span data-lang-ckb>بەرهەم</span>
                </label>
                <select id="product_id" name="product_id" required>
                    <option value=""><span data-lang-en>Select a Product</span><span data-lang-ckb>بەرهەمێک هەڵبژێرە</span></option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['ProductID']); ?>" <?php echo ($edit_order && $edit_order['ProductID'] == $product['ProductID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['Name']); ?> (<span data-lang-en>Stock</span><span data-lang-ckb>بڕ</span>: <?php echo htmlspecialchars($product['QuantityInStock']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="quantity">
                    <span data-lang-en>Quantity</span>
                    <span data-lang-ckb>بڕ</span>
                </label>
                <input type="number" id="quantity" name="quantity" value="<?php echo $edit_order ? htmlspecialchars($edit_order['Quantity']) : ''; ?>" required min="1">
            </div>
            <div class="form-group">
                <label for="unit_price">
                    <span data-lang-en>Unit Price ($)</span>
                    <span data-lang-ckb>نرخی دانە ($)</span>
                </label>
                <input type="number" id="unit_price" name="unit_price" value="<?php echo $edit_order ? htmlspecialchars($edit_order['UnitPrice']) : ''; ?>" required step="0.01" min="0.01">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_order): ?>
                <button type="submit" name="update_order" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update Order</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="sales_orders.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="create_order" class="btn btn-primary">
                    <i class="fas fa-check-double"></i>
                    <span data-lang-en>Process Sale</span>
                    <span data-lang-ckb>تەواوکردنی فرۆشتن</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-history"></i> 
            <span data-lang-en>Recent Sales History</span>
            <span data-lang-ckb>مێژووی فرۆشتنەکان</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><span data-lang-en>Order ID</span><span data-lang-ckb>ژمارەی داواکاری</span></th>
                    <th><span data-lang-en>Date</span><span data-lang-ckb>بەروار</span></th>
                    <th><span data-lang-en>Customer</span><span data-lang-ckb>کڕیار</span></th>
                    <th><span data-lang-en>Total</span><span data-lang-ckb>کۆ</span></th>
                    <th><span data-lang-en>User</span><span data-lang-ckb>بەکارهێنەر</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple); font-family: monospace;">#SO-<?php echo htmlspecialchars($order['OrderID']); ?></span></td>
                    <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                    <td><strong style="color: var(--accent-purple);">$<?php echo htmlspecialchars(number_format($order['TotalAmount'], 2)); ?></strong></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($order['Username']); ?></span></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="sales_orders.php?edit_id=<?php echo $order['OrderID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="sales_orders.php" onsubmit="return confirm('Are you sure? Inventory will be restored.');" style="margin:0;">
                                <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                <button type="submit" name="delete_order" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-receipt" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No sales orders yet.</span>
                        <span data-lang-ckb>هیچ فرۆشتنێک تۆمار نەکراوە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>