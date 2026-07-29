<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch logged in admin user info
$adminStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$adminStmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $adminStmt->fetch();

if (!$currentAdmin) {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    header('Location: login.php');
    exit;
}

// Quick Stock Update Action from Dashboard
$error = '';
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_stock') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);
        $newStock = max(0, (int)($_POST['stock'] ?? 0));
        if ($productId > 0) {
            $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->execute([$newStock, $productId]);
            flash('success', 'Stock quantity updated successfully!');
            header('Location: dashboard.php');
            exit;
        }
    }
}

// Metrics
$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue = (float)($pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0);

// Fetch products for stock checking
$lowStockStmt = $pdo->query("SELECT * FROM products ORDER BY stock ASC LIMIT 6");
$lowStockProducts = $lowStockStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KGF Control Room | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0.3">
</head>
<body class="admin-page">
<script>
    (function() {
        var theme = localStorage.getItem('admin-theme') || 'dark';
        document.body.setAttribute('data-theme', theme);
    })();
</script>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <h2>KGF Admin</h2>
        </div>
        <div class="admin-sidebar-user"><?= e($currentAdmin['email']) ?></div>
        
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="products.php">📦 Products & Stock</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="../index.php" target="_blank">🛍️ View store ↗</a>
        
        <div class="admin-sidebar-bottom">
            <a href="logout.php" class="logout-link" title="Logout">
                <img src="../assets/images/logout.png" alt="Logout" class="logout-icon">
            </a>
            <button class="icon-btn theme-toggle-btn" id="adminThemeToggle" aria-label="Toggle Theme" title="Toggle Theme">
                <svg class="sun-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <svg class="moon-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
        </div>
    </aside>

    <main class="admin-main">
        <span class="eyebrow">Control Room</span>
        <h1>Dashboard</h1>
        <p class="admin-welcome">Welcome back, <strong><?= e($currentAdmin['name']) ?></strong></p>
 
        <?php if ($success): ?>
            <div class="success-message" style="margin-bottom: 25px;"><?= e($success) ?></div>
        <?php endif; ?>
 
        <?php if ($error): ?>
            <div class="form-error" style="margin-bottom: 25px;"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="admin-cards" style="margin-bottom: 40px;">
            <div class="admin-card">
                <span>Products</span>
                <strong><?= $productCount ?></strong>
            </div>
            <div class="admin-card">
                <span>Customers</span>
                <strong><?= $userCount ?></strong>
            </div>
            <div class="admin-card">
                <span>Total Orders</span>
                <strong><?= $orderCount ?></strong>
            </div>
            <div class="admin-card">
                <span>Revenue Paid</span>
                <strong>₹<?= number_format($totalRevenue, 2) ?></strong>
            </div>
        </div>
 
        <!-- Stock Quantity Monitor -->
        <div class="admin-header-actions">
            <div>
                <h3 style="margin: 0; color: var(--admin-text-title);">Inventory Stock Monitor</h3>
                <p style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.88rem;">Check and instantly add stock quantity to products</p>
            </div>
            <a href="products.php" class="btn primary">+ Manage All Products</a>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock Quantity</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockProducts as $p): ?>
                        <tr>
                            <td><strong><?= e($p['name']) ?></strong></td>
                            <td><span class="badge badge-info"><?= e(ucfirst($p['category'])) ?></span></td>
                            <td>₹<?= number_format((float)$p['price'], 2) ?></td>
                            <td>
                                <?php if ((int)$p['stock'] <= 0): ?>
                                    <span class="badge badge-danger">Out of Stock (0)</span>
                                <?php elseif ((int)$p['stock'] <= 5): ?>
                                    <span class="badge badge-warning">Low Stock (<?= (int)$p['stock'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock (<?= (int)$p['stock'] ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" class="stock-update-form">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="quick_stock">
                                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                    
                                    <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" required>
                                    <button type="submit" class="btn-action btn-action-primary">Update Quantity</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
