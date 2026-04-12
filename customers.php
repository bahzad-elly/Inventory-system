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

<?php
$page_title = 'Customers - Inventory System';
$page_title_en = 'Customers';
$page_title_ku = 'کڕیاران';
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
            <i class="fas fa-user-plus"></i> 
            <span data-lang-en><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></span>
            <span data-lang-ckb><?php echo $edit_customer ? 'دەستکاری کڕیار' : 'زیادکردنی کڕیار'; ?></span>
        </h2>
    </div>
    <form method="POST" action="customers.php">
        <?php if ($edit_customer): ?>
            <input type="hidden" name="customer_id" value="<?php echo $edit_customer['CustomerID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="name">
                    <span data-lang-en>Customer Name</span>
                    <span data-lang-ckb>ناوی کڕیار</span>
                </label>
                <input type="text" id="name" name="name" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">
                    <span data-lang-en>Phone Number</span>
                    <span data-lang-ckb>ژمارەی تەلەفۆن</span>
                </label>
                <input type="text" id="phone" name="phone" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Phone']) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">
                <span data-lang-en>Email Address</span>
                <span data-lang-ckb>ناونیشانی ئیمەیڵ</span>
            </label>
            <input type="email" id="email" name="email" value="<?php echo $edit_customer ? htmlspecialchars($edit_customer['Email']) : ''; ?>">
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_customer): ?>
                <button type="submit" name="update_customer" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update Customer</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="customers.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="add_customer" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span data-lang-en>Add Customer</span>
                    <span data-lang-ckb>زیادکردنی کڕیار</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-users-viewfinder"></i> 
            <span data-lang-en>Customer Directory</span>
            <span data-lang-ckb>لیستی کڕیاران</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><span data-lang-en>Name</span><span data-lang-ckb>ناو</span></th>
                    <th><span data-lang-en>Phone</span><span data-lang-ckb>تەلەفۆن</span></th>
                    <th><span data-lang-en>Email</span><span data-lang-ckb>ئیمەیڵ</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple); font-family: monospace;">#C-<?php echo htmlspecialchars($customer['CustomerID']); ?></span></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($customer['Name']); ?></td>
                    <td><a href="tel:<?php echo htmlspecialchars($customer['Phone']); ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-phone-alt" style="font-size: 0.8rem; color: var(--accent-purple);"></i>
                        <?php echo htmlspecialchars($customer['Phone']); ?>
                    </a></td>
                    <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="customers.php?edit_id=<?php echo $customer['CustomerID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="customers.php" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                                <input type="hidden" name="customer_id" value="<?php echo $customer['CustomerID']; ?>">
                                <button type="submit" name="delete_customer" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-user-slash" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No customers found.</span>
                        <span data-lang-ckb>هیچ کڕیارێک نەدۆزرایەوە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>