<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_supplier'])) {
        $name = trim($_POST['supplier_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);

        $stmt = $pdo->prepare("INSERT INTO Supplier (SupplierName, Phone, Email, Address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $address]);
        
        header("Location: suppliers.php");
        exit;
    } elseif (isset($_POST['update_supplier'])) {
        $id = $_POST['supplier_id'];
        $name = trim($_POST['supplier_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);

        $stmt = $pdo->prepare("UPDATE Supplier SET SupplierName = ?, Phone = ?, Email = ?, Address = ? WHERE SupplierID = ?");
        $stmt->execute([$name, $phone, $email, $address, $id]);
        
        header("Location: suppliers.php");
        exit;
    } elseif (isset($_POST['delete_supplier'])) {
        $id = $_POST['supplier_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM Supplier WHERE SupplierID = ?");
            $stmt->execute([$id]);
            header("Location: suppliers.php");
            exit;
        } catch (PDOException $e) {
            $error = "Cannot delete this supplier because they are linked to existing products.";
        }
    }
}

$edit_supplier = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Supplier WHERE SupplierID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_supplier = $stmt->fetch();
}

$stmt = $pdo->query("SELECT * FROM Supplier ORDER BY SupplierID DESC");
$suppliers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Inventory System</title>
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
    <a href="suppliers.php" style="background: #555;">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php">Customers</a>
</div>

<div class="main-content">
    <h1>Manage Suppliers</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_supplier ? 'Edit Supplier' : 'Add New Supplier'; ?></h3>
        <form method="POST" action="suppliers.php">
            <?php if ($edit_supplier): ?>
                <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['SupplierID']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="supplier_name">Supplier Name</label>
                <input type="text" id="supplier_name" name="supplier_name" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['SupplierName']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Address']) : ''; ?>">
            </div>
            
            <?php if ($edit_supplier): ?>
                <button type="submit" name="update_supplier">Update Supplier</button>
                <a href="suppliers.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_supplier">Add Supplier</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Supplier List</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supplier['SupplierID']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['SupplierName']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['Email']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['Address']); ?></td>
                    <td class="action-forms">
                        <a href="suppliers.php?edit_id=<?php echo $supplier['SupplierID']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" action="suppliers.php" onsubmit="return confirm('Are you sure you want to delete this supplier?');" style="margin:0;">
                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['SupplierID']; ?>">
                            <button type="submit" name="delete_supplier" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No suppliers found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>