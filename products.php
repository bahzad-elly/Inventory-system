<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $category = trim($_POST['category']);
        $size = trim($_POST['size']);
        $gender = trim($_POST['gender']);
        $description = trim($_POST['description']);
        $supplier_id = $_POST['supplier_id'];

        $stmt = $pdo->prepare("INSERT INTO Product (Name, Brand, Category, Size, Gender, Description, SupplierID) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $brand, $category, $size, $gender, $description, $supplier_id]);

        $product_id = $pdo->lastInsertId();
        $stmt2 = $pdo->prepare("INSERT INTO Inventory (ProductID, QuantityInStock, ReorderLevel) VALUES (?, 0, 10)");
        $stmt2->execute([$product_id]);

        header("Location: products.php");
        exit;
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $category = trim($_POST['category']);
        $size = trim($_POST['size']);
        $gender = trim($_POST['gender']);
        $description = trim($_POST['description']);
        $supplier_id = $_POST['supplier_id'];

        $stmt = $pdo->prepare("UPDATE Product SET Name = ?, Brand = ?, Category = ?, Size = ?, Gender = ?, Description = ?, SupplierID = ? WHERE ProductID = ?");
        $stmt->execute([$name, $brand, $category, $size, $gender, $description, $supplier_id, $id]);

        header("Location: products.php");
        exit;
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['product_id'];
        
        try {
            $pdo->beginTransaction();
            
            $stmt1 = $pdo->prepare("DELETE FROM Inventory WHERE ProductID = ?");
            $stmt1->execute([$id]);
            
            $stmt2 = $pdo->prepare("DELETE FROM Product WHERE ProductID = ?");
            $stmt2->execute([$id]);
            
            $pdo->commit();
            header("Location: products.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Cannot delete this product because it is linked to existing sales or restock orders.";
        }
    }
}

$edit_product = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Product WHERE ProductID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_product = $stmt->fetch();
}

$supplier_stmt = $pdo->query("SELECT SupplierID, SupplierName FROM Supplier ORDER BY SupplierName ASC");
$suppliers = $supplier_stmt->fetchAll();

$product_stmt = $pdo->query("SELECT p.*, s.SupplierName FROM Product p JOIN Supplier s ON p.SupplierID = s.SupplierID ORDER BY p.ProductID DESC");
$products = $product_stmt->fetchAll();
?>

<?php
$page_title = 'Products - Inventory System';
$page_title_en = 'Products';
$page_title_ku = 'بەرهەمەکان';
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
            <i class="fas fa-plus-circle"></i> 
            <span data-lang-en><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></span>
            <span data-lang-ckb><?php echo $edit_product ? 'دەستکاری بەرهەم' : 'زیادکردنی بەرهەم'; ?></span>
        </h2>
    </div>
    <form method="POST" action="products.php">
        <?php if ($edit_product): ?>
            <input type="hidden" name="product_id" value="<?php echo $edit_product['ProductID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="name">
                    <span data-lang-en>Product Name</span>
                    <span data-lang-ckb>ناوی بەرهەم</span>
                </label>
                <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="supplier_id">
                    <span data-lang-en>Supplier</span>
                    <span data-lang-ckb>دابینکەر</span>
                </label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value=""><span data-lang-en>Select a Supplier</span><span data-lang-ckb>دابینکەرێک هەڵبژێرە</span></option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo htmlspecialchars($supplier['SupplierID']); ?>" <?php echo ($edit_product && $edit_product['SupplierID'] == $supplier['SupplierID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['SupplierName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="brand">
                    <span data-lang-en>Brand</span>
                    <span data-lang-ckb>مارکە</span>
                </label>
                <input type="text" id="brand" name="brand" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Brand']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="category">
                    <span data-lang-en>Category</span>
                    <span data-lang-ckb>پۆلێن</span>
                </label>
                <input type="text" id="category" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Category']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="size">
                    <span data-lang-en>Size</span>
                    <span data-lang-ckb>قەبارە</span>
                </label>
                <input type="text" id="size" name="size" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Size']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="gender">
                    <span data-lang-en>Gender</span>
                    <span data-lang-ckb>ڕەگەز</span>
                </label>
                <select id="gender" name="gender">
                    <option value="Unisex" <?php echo ($edit_product && $edit_product['Gender'] == 'Unisex') ? 'selected' : ''; ?>>Unisex</option>
                    <option value="Male" <?php echo ($edit_product && $edit_product['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($edit_product && $edit_product['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">
                <span data-lang-en>Description</span>
                <span data-lang-ckb>وەسف</span>
            </label>
            <textarea id="description" name="description" rows="2"><?php echo $edit_product ? htmlspecialchars($edit_product['Description']) : ''; ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_product): ?>
                <button type="submit" name="update_product" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update Product</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="products.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="add_product" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-lang-en>Add Product</span>
                    <span data-lang-ckb>زیادکردن</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-list"></i> 
            <span data-lang-en>Product List</span>
            <span data-lang-ckb>لیستی بەرهەمەکان</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><span data-lang-en>Name</span><span data-lang-ckb>ناو</span></th>
                    <th><span data-lang-en>Brand</span><span data-lang-ckb>مارکە</span></th>
                    <th><span data-lang-en>Category</span><span data-lang-ckb>پۆلێن</span></th>
                    <th><span data-lang-en>Supplier</span><span data-lang-ckb>دابینکەر</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple);"><?php echo htmlspecialchars($product['ProductID']); ?></span></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($product['Name']); ?></td>
                    <td><?php echo htmlspecialchars($product['Brand']); ?></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo htmlspecialchars($product['Category']); ?></span></td>
                    <td><?php echo htmlspecialchars($product['SupplierName']); ?></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="products.php?edit_id=<?php echo $product['ProductID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="products.php" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                <button type="submit" name="delete_product" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No products found.</span>
                        <span data-lang-ckb>هیچ بەرهەمێک نەدۆزرایەوە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>