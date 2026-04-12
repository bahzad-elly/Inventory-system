<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['download_backup'])) {
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql_dump = "-- Inventory System Database Backup\n";
    $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $row[1] . ";\n\n";

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $escaped_keys = array_map(function($key) { return "`$key`"; }, $keys);
                
                $sql_dump .= "INSERT INTO `$table` (" . implode(", ", $escaped_keys) . ") VALUES (";
                
                $vals = [];
                foreach ($row as $val) {
                    if (is_null($val)) {
                        $vals[] = "NULL";
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $sql_dump .= implode(", ", $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }

    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="inventory_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Content-Length: ' . strlen($sql_dump));
    
    echo $sql_dump;
    exit;
}

$total_sales_stmt = $pdo->query("SELECT SUM(TotalAmount) FROM SalesOrder");
$total_sales = $total_sales_stmt->fetchColumn() ?: 0;

$total_customers_stmt = $pdo->query("SELECT COUNT(*) FROM Customer");
$total_customers = $total_customers_stmt->fetchColumn();

$total_products_stmt = $pdo->query("SELECT COUNT(*) FROM Product");
$total_products = $total_products_stmt->fetchColumn();

$low_stock_stmt = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE QuantityInStock <= ReorderLevel");
$low_stock_items = $low_stock_stmt->fetchColumn();

// Fetch Recent Sales
$recent_sales_stmt = $pdo->query("SELECT so.OrderID, so.OrderDate, so.TotalAmount, c.Name AS CustomerName FROM SalesOrder so JOIN Customer c ON so.CustomerID = c.CustomerID ORDER BY so.OrderID DESC LIMIT 10");
$recent_sales = $recent_sales_stmt->fetchAll();

// Fetch Recent Restocks
$recent_restocks_stmt = $pdo->query("SELECT so.SupplierOrderID, so.OrderDate, so.QuantityOrdered, s.SupplierName, p.Name AS ProductName FROM SupplierOrder so JOIN Supplier s ON so.SupplierID = s.SupplierID JOIN Product p ON so.ProductID = p.ProductID ORDER BY so.SupplierOrderID DESC LIMIT 10");
$recent_restocks = $recent_restocks_stmt->fetchAll();
?>

<?php
$page_title = 'System Reports - Inventory System';
$page_title_en = 'Reports & Backup';
$page_title_ku = 'ڕاپۆرت و باکئەپ';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-chart-line"></i> 
            <span data-lang-en>Business Performance Summary</span>
            <span data-lang-ckb>کورتەی ئەدای کار</span>
        </h2>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label" data-lang-en>Total Revenue</span>
                <span class="stat-label" data-lang-ckb>کۆی داهات</span>
                <h3 class="stat-value">$<?php echo number_format($total_sales, 2); ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label" data-lang-en>Total Customers</span>
                <span class="stat-label" data-lang-ckb>کۆی کڕیاران</span>
                <h3 class="stat-value"><?php echo $total_customers; ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label" data-lang-en>Products in Catalog</span>
                <span class="stat-label" data-lang-ckb>کۆی کاڵاکان</span>
                <h3 class="stat-value"><?php echo $total_products; ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label" data-lang-en>Low Stock Items</span>
                <span class="stat-label" data-lang-ckb>کاڵا کەمبووەکان</span>
                <h3 class="stat-value" style="color: #ef4444;"><?php echo $low_stock_items; ?></h3>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem; margin-top: 2rem;">
    <div class="card">
        <div class="card-header">
            <h2>
                <i class="fas fa-receipt"></i> 
                <span data-lang-en>Recent Sales History</span>
                <span data-lang-ckb>مێژووی فرۆشتنەکان</span>
            </h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><span data-lang-en>Customer</span><span data-lang-ckb>کڕیار</span></th>
                        <th><span data-lang-en>Amount</span><span data-lang-ckb>بڕ</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sales as $sale): ?>
                    <tr>
                        <td>#SO-<?php echo $sale['OrderID']; ?></td>
                        <td><?php echo htmlspecialchars($sale['CustomerName']); ?></td>
                        <td><strong style="color: var(--accent-purple);">$<?php echo number_format($sale['TotalAmount'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>
                <i class="fas fa-truck-loading"></i> 
                <span data-lang-en>Recent Restock Logs</span>
                <span data-lang-ckb>تۆماری کڕینەوەکان</span>
            </h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><span data-lang-en>Product</span><span data-lang-ckb>کاڵا</span></th>
                        <th><span data-lang-en>Supplier</span><span data-lang-ckb>دابینکەر</span></th>
                        <th><span data-lang-en>Qty</span><span data-lang-ckb>ژمارە</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_restocks as $restock): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($restock['ProductName']); ?></td>
                        <td><?php echo htmlspecialchars($restock['SupplierName']); ?></td>
                        <td><span class="badge badge-success"><?php echo $restock['QuantityOrdered']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 2rem; background: linear-gradient(135deg, var(--card-bg) 0%, var(--hover-bg) 100%); border: 1px solid var(--accent-purple);">
    <div class="card-header" style="border-bottom-color: rgba(139, 92, 246, 0.1);">
        <h2>
            <i class="fas fa-database"></i> 
            <span data-lang-en>Database Security</span>
            <span data-lang-ckb>پاراستنی داتابەیس</span>
        </h2>
    </div>
    <div style="padding: 2rem; text-align: center;">
        <div style="max-width: 600px; margin: 0 auto;">
            <i class="fas fa-shield-heart" style="font-size: 3.5rem; color: var(--accent-purple); margin-bottom: 1.5rem; display: block;"></i>
            <h3 style="margin-bottom: 1rem;">
                <span data-lang-en>Download Full System Backup</span>
                <span data-lang-ckb>دابەزاندنی کۆپییەکی یەدەگ</span>
            </h3>
            <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 2rem;">
                <span data-lang-en>Secure your business data by downloading a complete snapshot of your database. This includes products, sales, customers, and user credentials in a single SQL file.</span>
                <span data-lang-ckb>داتاکانت بپارێزە بە دابەزاندنی تەواوی زانیارییەکانی سیستمەکە. ئەمە هەموو کاڵا، فرۆشتن، کڕیار و هەژمارەکان لەخۆ دەگرێت لە یەک فایلدا.</span>
            </p>
            <form method="POST" action="reports.php">
                <button type="submit" name="download_backup" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem; border-radius: 50px; box-shadow: var(--shadow-lg);">
                    <i class="fas fa-download"></i>
                    <span data-lang-en>Generate Backup File</span>
                    <span data-lang-ckb>دروستکردنی فایلی یەدەگ</span>
                </button>
            </form>
            <div style="margin-top: 1.5rem; font-size: 0.85rem; color: var(--text-secondary);">
                <i class="fas fa-clock-rotate-left"></i>
                <span data-lang-en>Last system health check: <?php echo date('Y-m-d H:i'); ?></span>
                <span data-lang-ckb>کۆتا پشکنینی سیستم: <?php echo date('Y-m-d H:i'); ?></span>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>