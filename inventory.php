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

<?php
$page_title = 'Inventory - Inventory System';
$page_title_en = 'Inventory';
$page_title_ku = 'کۆگا';
include 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="badge-warning" style="width: 100%; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($edit_item): ?>
<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-sliders-h"></i> 
            <span data-lang-en>Adjust Stock: <?php echo htmlspecialchars($edit_item['ProductName']); ?></span>
            <span data-lang-ckb>دەستکاری بڕ: <?php echo htmlspecialchars($edit_item['ProductName']); ?></span>
        </h2>
    </div>
    <form method="POST" action="inventory.php">
        <input type="hidden" name="product_id" value="<?php echo $edit_item['ProductID']; ?>">
        
        <div class="form-group">
            <label>
                <span data-lang-en>Product Name</span>
                <span data-lang-ckb>ناوی بەرهەم</span>
            </label>
            <input type="text" value="<?php echo htmlspecialchars($edit_item['ProductName']); ?>" readonly style="background: var(--hover-bg);">
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="quantity">
                    <span data-lang-en>Quantity in Stock</span>
                    <span data-lang-ckb>بڕی بەردەست</span>
                </label>
                <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($edit_item['QuantityInStock']); ?>" required min="0">
            </div>
            <div class="form-group">
                <label for="reorder_level">
                    <span data-lang-en>Reorder Level (Alert Threshold)</span>
                    <span data-lang-ckb>بڕی کەمترین (بۆ ئاگاداری)</span>
                </label>
                <input type="number" id="reorder_level" name="reorder_level" value="<?php echo htmlspecialchars($edit_item['ReorderLevel']); ?>" required min="0">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" name="update_inventory" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <span data-lang-en>Save Adjustments</span>
                <span data-lang-ckb>پاشەکەوتکردن</span>
            </button>
            <a href="inventory.php" class="btn btn-danger">
                <i class="fas fa-times"></i>
                <span data-lang-en>Cancel</span>
                <span data-lang-ckb>پاشگەزبوونەوە</span>
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-boxes-stacked"></i> 
            <span data-lang-en>Current Stock Status</span>
            <span data-lang-ckb>بارودۆخی ئێستای کۆگا</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><span data-lang-en>Product</span><span data-lang-ckb>بەرهەم</span></th>
                    <th><span data-lang-en>Supplier</span><span data-lang-ckb>دابینکەر</span></th>
                    <th><span data-lang-en>In Stock</span><span data-lang-ckb>بەردەست</span></th>
                    <th><span data-lang-en>Alert at</span><span data-lang-ckb>ئاگاداری</span></th>
                    <th><span data-lang-en>Status</span><span data-lang-ckb>بارودۆخ</span></th>
                    <th><span data-lang-en>Action</span><span data-lang-ckb>کردار</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_items as $item): ?>
                <?php $is_low = $item['QuantityInStock'] <= $item['ReorderLevel']; ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple);"><?php echo htmlspecialchars($item['ProductID']); ?></span></td>
                    <td>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($item['ProductName']); ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($item['Brand']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($item['SupplierName']); ?></td>
                    <td><strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($item['QuantityInStock']); ?></strong></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo htmlspecialchars($item['ReorderLevel']); ?></span></td>
                    <td>
                        <?php if ($is_low): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-arrow-trend-down"></i>
                                <span data-lang-en>Low Stock</span>
                                <span data-lang-ckb>کەمی بڕ</span>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i>
                                <span data-lang-en>Healthy</span>
                                <span data-lang-ckb>جێگیر</span>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="inventory.php?edit_id=<?php echo $item['ProductID']; ?>" class="btn" style="padding: 8px 15px; background: #f5f3ff; color: var(--accent-purple); font-size: 0.85rem;">
                            <i class="fas fa-pencil"></i>
                            <span data-lang-en>Adjust</span>
                            <span data-lang-ckb>دەستکاری</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($inventory_items)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No inventory records found.</span>
                        <span data-lang-ckb>هیچ زانیارییەکی کۆگا نییە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>