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

$error = '';
$success = flash('success');

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } else {
        $orderId         = (int)($_POST['order_id'] ?? 0);
        $status          = trim($_POST['status'] ?? '');
        $paymentStatus   = trim($_POST['payment_status'] ?? '');
        $trackingNumber  = trim($_POST['tracking_number'] ?? '');
        $estDelivery     = trim($_POST['estimated_delivery'] ?? '');

        if ($orderId > 0) {
            $estDeliveryVal = !empty($estDelivery) ? $estDelivery : null;
            $trackingVal    = !empty($trackingNumber) ? $trackingNumber : null;

            // Check if columns exist (graceful fallback)
            try {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = ?, payment_status = ?, tracking_number = ?, estimated_delivery = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $paymentStatus, $trackingVal, $estDeliveryVal, $orderId]);
            } catch (\Exception $e) {
                // Fallback if tracking columns don't exist yet
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
                $stmt->execute([$status, $paymentStatus, $orderId]);
            }

            // Log status change into tracking logs (if table exists)
            try {
                $logNote = match($status) {
                    'processing'       => 'Admin updated: items being prepared.',
                    'shipped'          => 'Admin updated: package shipped.',
                    'out_for_delivery' => 'Admin updated: out for delivery.',
                    'delivered'        => 'Admin updated: package delivered.',
                    'cancelled'        => 'Admin updated: order cancelled.',
                    default            => 'Status updated by admin.',
                };
                $logStmt = $pdo->prepare("INSERT INTO order_tracking_logs (order_id, status, note) VALUES (?, ?, ?)");
                $logStmt->execute([$orderId, $status, $logNote]);
            } catch (\Exception $e) { /* table may not exist yet */ }

            flash('success', 'Order #' . $orderId . ' updated successfully!');
            header('Location: orders.php');
            exit;
        }
    }
}

// Fetch all orders with customer details
$ordersStmt = $pdo->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
");
$orders = $ordersStmt->fetchAll();

// Fetch order items for all orders
$itemsStmt = $pdo->query("SELECT * FROM order_items");
$allItems = $itemsStmt->fetchAll();

$orderItemsMap = [];
foreach ($allItems as $item) {
    $orderItemsMap[$item['order_id']][] = $item;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Orders | KGF Control Room</title>
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
        
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="products.php">📦 Products & Stock</a>
        <a href="orders.php" class="active">🛒 Orders</a>
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
        <div class="admin-header-actions">
            <div>
                <span class="eyebrow">Customer Purchases</span>
                <h1>Orders Management</h1>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="success-message" style="margin-bottom: 25px;"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="form-error" style="margin-bottom: 25px;"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Purchased Items</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Tracking</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #888; padding: 40px;">No customer orders placed yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <?php $items = $orderItemsMap[$o['id']] ?? []; ?>
                            <tr>
                                <td>
                                    <strong>#<?= (int)$o['id'] ?></strong>
                                    <div style="font-size: 0.78rem; color: #888; margin-top: 4px;">
                                        <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                    </div>
                                    <?php if (!empty($o['razorpay_payment_id'])): ?>
                                        <div style="font-size: 0.75rem; color: #ff6a2a; margin-top: 2px;">
                                            <code><?= e($o['razorpay_payment_id']) ?></code>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-top: 6px;">
                                        <a href="../track_order.php?id=<?= (int)$o['id'] ?>" target="_blank"
                                           style="font-size: 0.75rem; color: #60a5fa; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            🔍 Track
                                        </a>
                                    </div>
                                </td>

                                <td>
                                    <strong><?= e($o['customer_name']) ?></strong>
                                    <div style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 2px;"><?= e($o['customer_email']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 4px; max-width: 200px;">
                                        <?= e($o['address']) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php foreach ($items as $item): ?>
                                        <div style="font-size: 0.85rem; margin-bottom: 4px;">
                                            • <strong><?= e($item['product_name']) ?></strong> 
                                            <span style="color: var(--admin-text-muted);">(x<?= (int)$item['quantity'] ?>)</span> 
                                            - <span style="color: #ff6a2a;">₹<?= number_format((float)$item['price'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </td>

                                <td>
                                    <strong style="font-size: 1.1rem; color: var(--admin-text-title);">₹<?= number_format((float)$o['total_amount'], 2) ?></strong>
                                </td>

                                <td>
                                    <?php if ($o['payment_status'] === 'paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($o['payment_status'] === 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= e(ucfirst($o['payment_status'])) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($o['status'] === 'delivered'): ?>
                                        <span class="badge badge-success">Delivered</span>
                                    <?php elseif ($o['status'] === 'shipped'): ?>
                                        <span class="badge badge-info">Shipped</span>
                                    <?php elseif ($o['status'] === 'cancelled'): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= e(ucfirst($o['status'])) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Tracking Info column -->
                                <td>
                                    <?php if (!empty($o['tracking_number'])): ?>
                                        <div style="font-size: 0.75rem; font-weight: 700; color: #ff6a2a; font-family: monospace; margin-bottom: 4px;"><?= e($o['tracking_number']) ?></div>
                                    <?php else: ?>
                                        <div style="font-size: 0.74rem; color: #888; margin-bottom: 4px;">No tracking</div>
                                    <?php endif; ?>
                                    <?php if (!empty($o['estimated_delivery'])): ?>
                                        <div style="font-size: 0.74rem; color: #34d399;">Est: <?= date('d M Y', strtotime($o['estimated_delivery'])) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form method="post" style="display: flex; flex-direction: column; gap: 6px;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

                                        <select name="status" style="padding: 4px 8px; font-size: 0.8rem; background: var(--admin-input-bg); border: 1px solid var(--admin-input-border); color: var(--admin-input-text); border-radius: 6px;">
                                            <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="shipped" <?= $o['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="out_for_delivery" <?= $o['status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                            <option value="delivered" <?= $o['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>

                                        <select name="payment_status" style="padding: 4px 8px; font-size: 0.8rem; background: var(--admin-input-bg); border: 1px solid var(--admin-input-border); color: var(--admin-input-text); border-radius: 6px;">
                                            <option value="pending" <?= $o['payment_status'] === 'pending' ? 'selected' : '' ?>>Payment: Pending</option>
                                            <option value="paid" <?= $o['payment_status'] === 'paid' ? 'selected' : '' ?>>Payment: Paid</option>
                                            <option value="failed" <?= $o['payment_status'] === 'failed' ? 'selected' : '' ?>>Payment: Failed</option>
                                        </select>

                                        <input type="text" name="tracking_number"
                                               value="<?= e($o['tracking_number'] ?? '') ?>"
                                               placeholder="Tracking No."
                                               style="padding: 4px 8px; font-size: 0.78rem; background: var(--admin-input-bg); border: 1px solid var(--admin-input-border); color: var(--admin-input-text); border-radius: 6px; font-family: monospace;">

                                        <input type="date" name="estimated_delivery"
                                               value="<?= e($o['estimated_delivery'] ?? '') ?>"
                                               style="padding: 4px 8px; font-size: 0.78rem; background: var(--admin-input-bg); border: 1px solid var(--admin-input-border); color: var(--admin-input-text); border-radius: 6px;">

                                        <button type="submit" class="btn-action btn-action-primary" style="justify-content: center;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
