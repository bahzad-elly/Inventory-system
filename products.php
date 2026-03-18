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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Inventory System</title>
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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
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
    <a href="products.php" style="background: #555;">Products</a>
    <a href="inventory.php">Inventory</a>
    <a href="sales_orders.php">Sales Orders</a>
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
</div>

<div class="main-content">
    <h1>Manage Products</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
        <form method="POST" action="products.php">
            <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['ProductID']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select a Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo htmlspecialchars($supplier['SupplierID']); ?>" <?php echo ($edit_product && $edit_product['SupplierID'] == $supplier['SupplierID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['SupplierName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Brand']) : ''; ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Category']) : ''; ?>">
                </div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="size">Size</label>
                    <input type="text" id="size" name="size" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Size']) : ''; ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="gender">Gender</label>
                    <input type="text" id="gender" name="gender" value="<?php echo $edit_product ? htmlspecialchars($edit_product['Gender']) : ''; ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['Description']) : ''; ?></textarea>
            </div>
            
            <?php if ($edit_product): ?>
                <button type="submit" name="update_product">Update Product</button>
                <a href="products.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_product">Add Product</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Product List</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['ProductID']); ?></td>
                    <td><?php echo htmlspecialchars($product['Name']); ?></td>
                    <td><?php echo htmlspecialchars($product['Brand']); ?></td>
                    <td><?php echo htmlspecialchars($product['Category']); ?></td>
                    <td><?php echo htmlspecialchars($product['Size']); ?></td>
                    <td><?php echo htmlspecialchars($product['SupplierName']); ?></td>
                    <td class="action-forms">
                        <a href="products.php?edit_id=<?php echo $product['ProductID']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" action="products.php" onsubmit="return confirm('Are you sure you want to delete this product?');" style="margin:0;">
                            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                            <button type="submit" name="delete_product" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No products found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>