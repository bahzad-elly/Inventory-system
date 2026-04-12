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

<?php
$page_title = 'Suppliers - Inventory System';
$page_title_en = 'Suppliers';
$page_title_ku = 'دابینکەران';
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
            <i class="fas fa-truck-field"></i> 
            <span data-lang-en><?php echo $edit_supplier ? 'Edit Supplier' : 'Add New Supplier'; ?></span>
            <span data-lang-ckb><?php echo $edit_supplier ? 'دەستکاری دابینکەر' : 'زیادکردنی دابینکەر'; ?></span>
        </h2>
    </div>
    <form method="POST" action="suppliers.php">
        <?php if ($edit_supplier): ?>
            <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['SupplierID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="supplier_name">
                    <span data-lang-en>Supplier Name</span>
                    <span data-lang-ckb>ناوی دابینکەر</span>
                </label>
                <input type="text" id="supplier_name" name="supplier_name" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['SupplierName']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">
                    <span data-lang-en>Phone Number</span>
                    <span data-lang-ckb>ژمارەی تەلەفۆن</span>
                </label>
                <input type="text" id="phone" name="phone" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Phone']) : ''; ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="email">
                    <span data-lang-en>Email Address</span>
                    <span data-lang-ckb>ئیمەیڵ</span>
                </label>
                <input type="email" id="email" name="email" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="address">
                    <span data-lang-en>Address</span>
                    <span data-lang-ckb>ناونیشان</span>
                </label>
                <input type="text" id="address" name="address" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['Address']) : ''; ?>">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_supplier): ?>
                <button type="submit" name="update_supplier" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update Supplier</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="suppliers.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="add_supplier" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-lang-en>Add Supplier</span>
                    <span data-lang-ckb>زیادکردنی دابینکەر</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-building-user"></i> 
            <span data-lang-en>Supplier List</span>
            <span data-lang-ckb>لیستی دابینکەران</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><span data-lang-en>Name</span><span data-lang-ckb>ناو</span></th>
                    <th><span data-lang-en>Contact</span><span data-lang-ckb>پەیوەندی</span></th>
                    <th><span data-lang-en>Address</span><span data-lang-ckb>ناونیشان</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple); font-family: monospace;">#S-<?php echo htmlspecialchars($supplier['SupplierID']); ?></span></td>
                    <td style="font-weight: 500; font-size: 1.05rem;"><?php echo htmlspecialchars($supplier['SupplierName']); ?></td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <a href="tel:<?php echo htmlspecialchars($supplier['Phone']); ?>" style="text-decoration: none; color: inherit; font-size: 0.9rem;">
                                <i class="fas fa-phone-alt" style="color: var(--accent-purple); width: 16px;"></i> <?php echo htmlspecialchars($supplier['Phone']); ?>
                            </a>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                <i class="fas fa-envelope" style="color: var(--accent-purple); width: 16px;"></i> <?php echo htmlspecialchars($supplier['Email']); ?>
                            </span>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($supplier['Address']); ?></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="suppliers.php?edit_id=<?php echo $supplier['SupplierID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="suppliers.php" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                                <input type="hidden" name="supplier_id" value="<?php echo $supplier['SupplierID']; ?>">
                                <button type="submit" name="delete_supplier" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-building-circle-exclamation" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No suppliers found.</span>
                        <span data-lang-ckb>هیچ دابینکەرێک نەدۆزرایەوە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>