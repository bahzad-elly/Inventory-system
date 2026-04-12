<aside class="sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fas fa-boxes-stacked"></i></div>
        <span class="logo-text">Inventory Pro</span>
    </div>
    <div class="nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> 
            <span data-lang-en>Dashboard</span>
            <span data-lang-ckb>داشبۆرد</span>
        </a>
        <a href="products.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> 
            <span data-lang-en>Products</span>
            <span data-lang-ckb>بەرهەمەکان</span>
        </a>
        <a href="inventory.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
            <i class="fas fa-list-check"></i> 
            <span data-lang-en>Inventory</span>
            <span data-lang-ckb>کۆگا</span>
        </a>
        <a href="sales_orders.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sales_orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-cart-shopping"></i> 
            <span data-lang-en>Sales</span>
            <span data-lang-ckb>فرۆشتن</span>
        </a>
        <a href="suppliers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck-field"></i> 
            <span data-lang-en>Suppliers</span>
            <span data-lang-ckb>دابینکەران</span>
        </a>
        <a href="supplier_orders.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'supplier_orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice"></i> 
            <span data-lang-en>Restock</span>
            <span data-lang-ckb>کڕینەوە</span>
        </a>
        <a href="customers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> 
            <span data-lang-en>Customers</span>
            <span data-lang-ckb>کڕیاران</span>
        </a>
        <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> 
            <span data-lang-en>Users</span>
            <span data-lang-ckb>بەکارهێنەران</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> 
            <span data-lang-en>Reports</span>
            <span data-lang-ckb>ڕاپۆرتەکان</span>
        </a>
        <div style="margin-top: 1rem; border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <a href="logout.php" class="nav-item" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt" style="color: #ef4444;"></i> 
                <span data-lang-en>Logout</span>
                <span data-lang-ckb>چوونە دەرەوە</span>
            </a>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="nav-item">
            <i class="fas fa-user-circle"></i> 
            <span><?php echo $_SESSION['username'] ?? 'User'; ?></span>
        </div>
    </div>
</aside>
