<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_input = trim($_POST['username']);
    $password_input = $_POST['password'];

    $stmt = $pdo->prepare("SELECT UserID, Username, PasswordHash, Role FROM user WHERE Username = ?");
    $stmt->execute([$username_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($password_input, $user['PasswordHash'])) {
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];
        
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<?php
$page_title = 'Login - Inventory System';
$hide_sidebar = true;
include 'includes/header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh; width: 100%;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 3rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="logo-icon" style="margin: 0 auto 1.5rem; width: 60px; height: 60px; font-size: 2rem;">
                <i class="fas fa-lock"></i>
            </div>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">
                <span data-lang-en>Welcome Back</span>
                <span data-lang-ckb>بەخێربێیتەوە</span>
            </h2>
            <p style="color: var(--text-secondary);">
                <span data-lang-en>Please login to your account</span>
                <span data-lang-ckb>تکایە بچۆ ناو هەژمارەکەت</span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="badge-warning" style="width: 100%; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="username">
                    <span data-lang-en>Username</span>
                    <span data-lang-ckb>ناوی بەکارهێنەر</span>
                </label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 15px; top: 15px; color: var(--accent-purple);"></i>
                    <input type="text" id="username" name="username" required autocomplete="off" style="padding-left: 45px;">
                </div>
            </div>
            <div class="form-group">
                <label for="password">
                    <span data-lang-en>Password</span>
                    <span data-lang-ckb>وشەی نهێنی</span>
                </label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 15px; top: 15px; color: var(--accent-purple);"></i>
                    <input type="password" id="password" name="password" required style="padding-left: 45px;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 15px; font-size: 1.1rem; margin-top: 1rem;">
                <span data-lang-en>Sign In</span>
                <span data-lang-ckb>چوونە ژوورەوە</span>
            </button>
        </form>

        <div style="margin-top: 2rem; text-align: center; font-size: 0.85rem; color: var(--text-secondary);">
            &copy; <?php echo date('Y'); ?> <span class="logo-text">Inventory Pro</span>
        </div>
    </div>
</div>

<style>
    /* Custom overrides for login page to handle icons since text-align inherit might be tricky */
    body.ku .form-group div i { left: auto !important; right: 15px !important; }
    body.ku .form-group input { padding-left: 18px !important; padding-right: 45px !important; }
    body.ku .top-bar { position: absolute; top: 20px; left: 20px; right: 20px; }
    body:not(.ku) .top-bar { position: absolute; top: 20px; left: 20px; right: 20px; }
</style>

<?php include 'includes/footer.php'; ?>