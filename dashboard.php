<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

// Fetch Summary Stats
$total_sales_stmt = $pdo->query("SELECT SUM(TotalAmount) FROM SalesOrder");
$total_sales = $total_sales_stmt->fetchColumn() ?: 0;

$total_customers_stmt = $pdo->query("SELECT COUNT(*) FROM Customer");
$total_customers = $total_customers_stmt->fetchColumn();

$total_products_stmt = $pdo->query("SELECT COUNT(*) FROM Product");
$total_products = $total_products_stmt->fetchColumn();

$low_stock_stmt = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE QuantityInStock <= ReorderLevel");
$low_stock_items = $low_stock_stmt->fetchColumn();
?>

<?php
$page_title = 'Dashboard - Inventory System';
$page_title_en = 'Dashboard';
$page_title_ku = 'داشبۆرد';
include 'includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple);">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <p data-lang-en>Total Revenue</p>
            <p data-lang-ckb>کۆی داهات</p>
            <h3>$<?php echo number_format($total_sales, 2); ?></h3>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <p data-lang-en>Customers</p>
            <p data-lang-ckb>کڕیاران</p>
            <h3><?php echo $total_customers; ?></h3>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <p data-lang-en>Products</p>
            <p data-lang-ckb>کاتالۆگی کاڵا</p>
            <h3><?php echo $total_products; ?></h3>
        </div>
    </div>
    <div class="stat-card" style="<?php echo $low_stock_items > 0 ? 'border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.05);' : ''; ?>">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <p data-lang-en>Low Stock</p>
            <p data-lang-ckb>کردارە بەپەلەکان</p>
            <h3 style="color: #ef4444;"><?php echo $low_stock_items; ?></h3>
        </div>
    </div>
</div>

<div class="card-header" style="margin-top: 2rem; margin-bottom: 1rem;">
    <h2>
        <i class="fas fa-layer-group"></i>
        <span data-lang-en>System Modules</span>
        <span data-lang-ckb>بەشەکانی سیستم</span>
    </h2>
</div>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
    <a href="sales_orders.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #e0f2fe; color: #0284c7; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-cart-shopping"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>Process Sales</span>
            <span data-lang-ckb>فرۆشتنی کەلوپەل</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>Create and manage customer orders efficiently.</span>
            <span data-lang-ckb>دروستکردن و بەڕێوەبردنی داواکارییەکانی کڕیار.</span>
        </p>
    </a>

    <a href="inventory.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #fef3c7; color: #d97706; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-warehouse"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>Check Inventory</span>
            <span data-lang-ckb>پشکنینی کۆگا</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>View stock levels and receive reorder alerts.</span>
            <span data-lang-ckb>بینینی بڕی کاڵاکان و ئاگادارکردنەوەی کەمی.</span>
        </p>
    </a>

    <a href="products.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-box-open"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>Manage Products</span>
            <span data-lang-ckb>بەڕێوەبردنی بەرهەمەکان</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>Add, edit, or remove items from your catalog.</span>
            <span data-lang-ckb>زیادکردن و دەستکاری کردنی لیستی کاڵاکان.</span>
        </p>
    </a>

    <a href="supplier_orders.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #fae8ff; color: #c026d3; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-truck-ramp-box"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>Restock Items</span>
            <span data-lang-ckb>کڕینەوەی کاڵا</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>Order more inventory from your trusted suppliers.</span>
            <span data-lang-ckb>داواکردنی کاڵای زیاتر لە دابینکەرەکانەوە.</span>
        </p>
    </a>

    <a href="reports.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #ffedd5; color: #ea580c; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-chart-line"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>View Reports</span>
            <span data-lang-ckb>بینینی ڕاپۆرتەکان</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>Analyze sales data and backup your database.</span>
            <span data-lang-ckb>شیکردنەوەی داتا و هێنانەوەی زانیارییەکان.</span>
        </p>
    </a>

    <a href="users.php" class="card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; transition: transform 0.2s;">
        <div class="stat-icon" style="background: #f3f4f6; color: #4b5563; width: 80px; height: 80px; font-size: 2.5rem;">
            <i class="fas fa-user-gear"></i>
        </div>
        <h3 style="font-size: 1.4rem;">
            <span data-lang-en>Manage Staff</span>
            <span data-lang-ckb>بەڕێوەبردنی ستاف</span>
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            <span data-lang-en>Control system access and user permissions.</span>
            <span data-lang-ckb>کۆنترۆڵکردنی دەسەڵاتەکانی بەکارهێنەران.</span>
        </p>
    </a>
</div>

<style>
    .dashboard-grid .card:hover {
        transform: translateY(-10px);
        border-color: var(--accent-purple);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
</style>

<?php include 'includes/footer.php'; ?>