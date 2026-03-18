<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_customer'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("INSERT INTO Customer (Name, Phone, Email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $email]);

        header("Location: customers.php");
        exit;
    } elseif (isset($_POST['update_customer'])) {
        $id = $_POST['customer_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("UPDATE Customer SET Name = ?, Phone = ?, Email = ? WHERE CustomerID = ?");
        $stmt->execute([$name, $phone, $email, $id]);

        header("Location: customers.php");
        exit;
    } elseif (isset($_POST['delete_customer'])) {
        $id = $_POST['customer_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM Customer WHERE CustomerID = ?");
            $stmt->execute([$id]);
            header("Location: customers.php");
            exit;
        } catch (PDOException $e) {
            $error = "Cannot delete this customer because they have existing sales orders.";
        }
    }
}

$edit_customer = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE CustomerID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_customer = $stmt->fetch();
}

$stmt = $pdo->query("SELECT * FROM Customer ORDER BY CustomerID DESC");
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Inventory System</title>
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
    <a href="suppliers.php">Suppliers</a>
    <a href="supplier_orders.php">Restock Orders</a>
    <a href="customers.php" style="background: #555;">Customers</a>
</div>

<div class="main-content">
    <h1>Manage Customers</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></h3>
        <form method="POST" action="customers.php">
            <?php if ($edit_customer): ?>
                <input type="hidden" name="customer_id" value="<?php echo $edit_customer['CustomerID']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Customer Name</label>
                <input type="text" id="name" name="name" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Email']) : ''; ?>">
            </div>
            
            <?php if ($edit_customer): ?>
                <button type="submit" name="update_customer">Update Customer</button>
                <a href="customers.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_customer">Add Customer</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <h3>Customer List</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['CustomerID']); ?></td>
                    <td><?php echo htmlspecialchars($customer['Name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                    <td class="action-forms">
                        <a href="customers.php?edit_id=<?php echo $customer['CustomerID']; ?>" class="btn-edit">Edit</a>
                        <form method="POST" action="customers.php" onsubmit="return confirm('Are you sure you want to delete this customer?');" style="margin:0;">
                            <input type="hidden" name="customer_id" value="<?php echo $customer['CustomerID']; ?>">
                            <button type="submit" name="delete_customer" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No customers found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>