<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $role = trim($_POST['role']);

        try {
            $stmt = $pdo->prepare("INSERT INTO User (Username, PasswordHash, Email, Role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $role]);
            header("Location: users.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        
        try {
            if (!empty($_POST['password'])) {
                $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE User SET Username = ?, PasswordHash = ?, Email = ?, Role = ? WHERE UserID = ?");
                $stmt->execute([$username, $password, $email, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE User SET Username = ?, Email = ?, Role = ? WHERE UserID = ?");
                $stmt->execute([$username, $email, $role, $id]);
            }
            header("Location: users.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        
        if ($id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account while you are logged in.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM User WHERE UserID = ?");
                $stmt->execute([$id]);
                header("Location: users.php");
                exit;
            } catch (PDOException $e) {
                $error = "Cannot delete this user because they have processed sales or restock orders.";
            }
        }
    }
}

$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT UserID, Username, Email, Role FROM User WHERE UserID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}

$stmt = $pdo->query("SELECT UserID, Username, Email, Role FROM User ORDER BY UserID DESC");
$users = $stmt->fetchAll();
?>

<?php
$page_title = 'Users - Inventory System';
$page_title_en = 'Users';
$page_title_ku = 'بەکارهێنەران';
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
            <i class="fas fa-user-gear"></i> 
            <span data-lang-en><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></span>
            <span data-lang-ckb><?php echo $edit_user ? 'دەستکاری بەکارهێنەر' : 'زیادکردنی بەکارهێنەر'; ?></span>
        </h2>
    </div>
    <form method="POST" action="users.php">
        <?php if ($edit_user): ?>
            <input type="hidden" name="user_id" value="<?php echo $edit_user['UserID']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="username">
                    <span data-lang-en>Username</span>
                    <span data-lang-ckb>ناوی بەکارهێنەر</span>
                </label>
                <input type="text" id="username" name="username" value="<?php echo $edit_user ? htmlspecialchars($edit_user['Username']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="password">
                    <span data-lang-en>Password</span>
                    <span data-lang-ckb>وشەی نهێنی</span>
                    <?php if ($edit_user): ?>
                        <small style="display:block; color: var(--text-secondary); margin-top: 4px;">
                            <span data-lang-en>(Leave blank to keep current)</span>
                            <span data-lang-ckb>(بە تالێ بەجێی بهێڵە ئەگەر ناتەوێ بیگۆڕی)</span>
                        </small>
                    <?php endif; ?>
                </label>
                <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
            </div>
        </div>

            <div class="form-group">
                <label for="email">
                    <span data-lang-en>Email Address</span>
                    <span data-lang-ckb>ئیمەیڵ</span>
                </label>
                <input type="email" id="email" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['Email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="role">
                    <span data-lang-en>Role</span>
                    <span data-lang-ckb>پلە</span>
                </label>
                <select id="role" name="role" required>
                    <option value="Admin" <?php echo ($edit_user && $edit_user['Role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="Employee" <?php echo ($edit_user && $edit_user['Role'] == 'Employee') ? 'selected' : ''; ?>>Employee</option>
                </select>
            </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <?php if ($edit_user): ?>
                <button type="submit" name="update_user" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span data-lang-en>Update User</span>
                    <span data-lang-ckb>نوێکردنەوە</span>
                </button>
                <a href="users.php" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                    <span data-lang-en>Cancel</span>
                    <span data-lang-ckb>پاشگەزبوونەوە</span>
                </a>
            <?php else: ?>
                <button type="submit" name="add_user" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    <span data-lang-en>Add User</span>
                    <span data-lang-ckb>زیادکردنی بەکارهێنەر</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-users"></i> 
            <span data-lang-en>System Users</span>
            <span data-lang-ckb>بەکارهێنەرانی سیستم</span>
        </h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><span data-lang-en>Username</span><span data-lang-ckb>ناوی بەکارهێنەر</span></th>
                    <th><span data-lang-en>Email</span><span data-lang-ckb>ئیمەیڵ</span></th>
                    <th><span data-lang-en>Role</span><span data-lang-ckb>پلە</span></th>
                    <th><span data-lang-en>Actions</span><span data-lang-ckb>کردارەکان</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><span class="badge" style="background: var(--icon-bg); color: var(--accent-purple); font-family: monospace;">#U-<?php echo htmlspecialchars($user['UserID']); ?></span></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($user['Username']); ?></td>
                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                    <td>
                        <span class="badge <?php echo $user['Role'] == 'Admin' ? 'badge-primary' : 'badge-success'; ?>" style="opacity: 0.8;">
                            <i class="fas <?php echo $user['Role'] == 'Admin' ? 'fa-shield-halved' : 'fa-user'; ?>"></i>
                            <?php echo htmlspecialchars($user['Role']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="users.php?edit_id=<?php echo $user['UserID']; ?>" class="btn" style="padding: 8px 12px; background: #e0f2fe; color: #0284c7;">
                                <i class="fas fa-user-pen"></i>
                            </a>
                            <form method="POST" action="users.php" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                                <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                <button type="submit" name="delete_user" class="btn" style="padding: 8px 12px; background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-secondary);">
                        <i class="fas fa-users-slash" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <span data-lang-en>No users found.</span>
                        <span data-lang-ckb>هیچ بەکارهێنەرێک نەدۆزرایەوە.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>