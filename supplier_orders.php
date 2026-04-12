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

<?php
$page_title = 'Restock Orders - Inventory System';
$page_title_en = 'Restock Orders';
$page_title_ku = 'کڕینەوەی کاڵا';
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
            <i class="fas fa-truck-ramp-box"></i> 
            <span data-lang-en><?php echo $edit_order ? 'Edit Restock Order' : 'Place Restock Order'; ?></span>
            <span data-lang-ckb><?php echo $edit_order ? 'دەستکاری کڕینەوە' : 'داواکاری نوێی کڕینەوە'; ?></span>
        </h2>
    </div>
    <form method="POST" action="supplier_orders.php">
        <?php if ($edit_order): ?>
            <input type="hidden" name="order_id" value="<?php echo $edit_order['SupplierOrderID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="product_id">
                    <span data-lang-en>Product</span>
                    <span data-lang-ckb>بەرهەم</span>
                </label>
                <select id="product_id" name="product_id" required>
                    <option value=""><span data-lang-en>Select a Product to Restock</span><span data-lang-ckb>بەرهەمێک هەڵبژێرە</span></option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['ProductID']); ?>" <?php echo ($edit_order && $edit_order['ProductID'] == $product['ProductID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['Name']); ?> (<span data-lang-en>Supplier</span><span data-lang-ckb>دابینکەر</span>: <?php echo htmlspecialchars($product['SupplierName']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">
                    <span data-lang-en>Quantity to Order</span>
                    <span data-lang-ckb>بڕی داواکراو</span>
                </label>
                <input type="number" id="quantity" name="quantity" value="<?php echo $edit_order ? htmlspecialchars($edit_order['QuantityOrdered']) : ''; ?>" required min="1">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_order): ?>
                <button type="submit" name="update_supplier_order" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update Order</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="supplier_orders.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="create_supplier_order" class="btn btn-primary">
                    <i class="fas fa-truck-loading"></i>
                    <span data-lang-en>Place Order</span>
                    <span data-lang-ckb>ناردنی داواکاری</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-file-invoice"></i> 
            <span data-lang-en>Restock History</span>
            <span data-lang-ckb>مێژووی کڕینەوەکان</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><span data-lang-en>Order ID</span><span data-lang-ckb>ژمارە</span></th>
                    <th><span data-lang-en>Date</span><span data-lang-ckb>بەروار</span></th>
                    <th><span data-lang-en>Supplier</span><span data-lang-ckb>دابینکەر</span></th>
                    <th><span data-lang-en>Product</span><span data-lang-ckb>بەرهەم</span></th>
                    <th><span data-lang-en>Qty</span><span data-lang-ckb>بڕ</span></th>
                    <th><span data-lang-en>By</span><span data-lang-ckb>لە لایەن</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple);">#RO-<?php echo htmlspecialchars($order['SupplierOrderID']); ?></span></td>
                    <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($order['SupplierName']); ?></td>
                    <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                    <td><strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($order['QuantityOrdered']); ?></strong></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($order['Username']); ?></span></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="supplier_orders.php?edit_id=<?php echo $order['SupplierOrderID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="supplier_orders.php" onsubmit="return confirm('Are you sure? This will reduce your current inventory.');" style="margin:0;">
                                <input type="hidden" name="order_id" value="<?php echo $order['SupplierOrderID']; ?>">
                                <button type="submit" name="delete_supplier_order" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-truck-moving" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No restock orders found.</span>
                        <span data-lang-ckb>هیچ داواکارییەکی کڕینەوە نییە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>