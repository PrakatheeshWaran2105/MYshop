<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isLoggedIn()) redirect('login.php');

$user = currentUser($pdo);

// Fetch user orders with first product image
$stmt = $pdo->prepare(
    'SELECT o.*, p.image AS product_image
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
         AND oi.id = (SELECT MIN(id) FROM order_items WHERE order_id = o.id)
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Account | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <div class="container" style="max-width: 920px; margin: 0 auto;">
        
        <!-- User Profile Account Card -->
        <div class="form-card" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; border-radius: 24px; padding: 30px 36px; flex-wrap: wrap; gap: 20px;">
            <div>
                <span class="eyebrow" style="color: #ff6a2a; letter-spacing: 2px; font-weight: 700; font-size: 0.78rem;">Account Details</span>
                <h1 style="margin: 6px 0 6px; color: var(--ink); font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px;"><?= e($user['name']) ?></h1>
                <p style="margin: 0; color: var(--muted); font-size: 1rem;"><?= e($user['email']) ?></p>
            </div>
            <div>
                <a class="btn ghost" href="<?= url('logout.php') ?>" style="padding: 10px 22px; border-radius: 12px;">Logout</a>
            </div>
        </div>

        <!-- Orders Section Card -->
        <div class="form-card" style="border-radius: 24px; padding: 36px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid var(--line); padding-bottom: 16px;">
                <h2 style="margin: 0; color: var(--ink); font-size: 1.6rem; font-weight: 700;">My Orders</h2>
                <span style="color: var(--muted); font-size: 0.9rem;"><?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?> placed</span>
            </div>

            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 50px 20px; color: var(--muted);">
                    <p style="font-size: 1.1rem; margin-bottom: 20px;">You haven't placed any orders yet.</p>
                    <a href="<?= url('shop.php') ?>" class="btn primary" style="padding: 12px 30px;">Explore Shop</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $statusBg = 'rgba(255, 255, 255, 0.08)';
                            $statusBorder = 'rgba(255, 255, 255, 0.15)';
                            $statusColor = '#ffffff';

                            if ($o['payment_status'] === 'paid') {
                                $statusBg = 'rgba(46, 204, 113, 0.15)';
                                $statusBorder = 'rgba(46, 204, 113, 0.35)';
                                $statusColor = '#2ecc71';
                            } elseif ($o['payment_status'] === 'failed') {
                                $statusBg = 'rgba(231, 76, 60, 0.15)';
                                $statusBorder = 'rgba(231, 76, 60, 0.35)';
                                $statusColor = '#e74c3c';
                            } elseif ($o['payment_status'] === 'pending') {
                                $statusBg = 'rgba(241, 196, 15, 0.15)';
                                $statusBorder = 'rgba(241, 196, 15, 0.35)';
                                $statusColor = '#f1c40f';
                            }
                        ?>
                        <div style="background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 20px 24px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; transition: border-color 0.2s;">
                            <!-- Left: product image + order info -->
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <!-- Product thumbnail -->
                                <div style="width: 64px; height: 64px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: rgba(255,255,255,0.06); border: 1px solid var(--line);">
                                    <?php if (!empty($o['product_image'])): ?>
                                        <img src="<?= url('uploads/products/' . e($o['product_image'])) ?>"
                                             alt="Product"
                                             style="width: 64px; height: 64px; object-fit: cover; display: block;">
                                    <?php else: ?>
                                        <div style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">🛍️</div>
                                    <?php endif; ?>
                                </div>
                                <!-- Order info -->
                                <div>
                                    <strong style="font-size: 1.15rem; color: var(--ink); display: block;">Order #<?= (int)$o['id'] ?></strong>
                                    <div style="font-size: 0.85rem; color: var(--muted); margin-top: 5px;">
                                        Placed on <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                    </div>
                                    <?php if (!empty($o['razorpay_payment_id'])): ?>
                                        <div style="font-size: 0.8rem; color: #ff6a2a; margin-top: 4px;">
                                            Payment ID: <code><?= e($o['razorpay_payment_id']) ?></code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                                <div style="text-align: right;">
                                    <strong style="font-size: 1.25rem; color: var(--ink); display: block;"><?= formatPrice((float)$o['total_amount']) ?></strong>
                                    <div style="margin-top: 4px;">
                                        <span style="display: inline-block; background: <?= $statusBg ?>; border: 1px solid <?= $statusBorder ?>; color: <?= $statusColor ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <?= e($o['payment_status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                    <?php if ($o['payment_status'] === 'paid'): ?>
                                        <!-- Track Order button -->
                                        <a href="<?= url('track_order.php?id=' . (int)$o['id']) ?>"
                                           class="btn primary"
                                           style="padding: 10px 18px; font-size: 0.85rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                                            🚚 Track Order
                                        </a>
                                        <a href="<?= url('order_success.php?id=' . (int)$o['id']) ?>" class="btn ghost" style="padding: 10px 18px; font-size: 0.85rem; border-radius: 10px;">View Receipt</a>
                                    <?php elseif ($o['payment_status'] === 'pending'): ?>
                                        <a href="<?= url('checkout.php?pay=1') ?>" class="btn primary" style="padding: 10px 18px; font-size: 0.85rem; border-radius: 10px;">Complete Payment</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
