<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Inventory System'; ?></title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if (!isset($hide_sidebar) || !$hide_sidebar): ?>
    <?php include 'includes/sidebar.php'; ?>
    <main class="main">
        <div class="top-bar">
            <div class="page-title">
                <h1>
                    <span data-lang-en><?php echo $page_title_en ?? 'Inventory'; ?></span>
                    <span data-lang-ckb><?php echo $page_title_ku ?? 'کۆگا'; ?></span>
                </h1>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: -5px; opacity: 0.8;">
                    <span data-lang-en>System Management & Control</span>
                    <span data-lang-ckb>بەڕێوەبردن و کۆنترۆڵکردنی سیستم</span>
                </div>
            </div>
            <div class="controls">
                <div class="control-pill" style="padding-inline-end: 18px; gap: 12px; border: 1px solid var(--accent-purple); background: rgba(159, 122, 234, 0.05);">
                    <div style="background: var(--accent-purple); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-primary); line-height: 1;">
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </div>
                        <div style="font-size: 0.65rem; color: var(--accent-purple); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-top: 2px;">
                            <?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>
                        </div>
                    </div>
                </div>
                <div class="control-pill">
                    <button id="btnEn">EN</button>
                    <button id="btnKu">کـوردی</button>
                </div>
                <div class="control-pill">
                    <button id="btnLight"><i class="fas fa-sun"></i></button>
                    <button id="btnDark"><i class="fas fa-moon"></i></button>
                </div>
            </div>
        </div>
<?php endif; ?>
