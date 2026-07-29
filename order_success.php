<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    redirect('shop.php');
}

$order = getOrderDetails($pdo, $orderId);

if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
    flash('error', 'Order not found.');
    redirect('shop.php');
}

$pageTitle = 'Order Confirmed #' . (int)$order['id'] . ' | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 840px; margin: 0 auto;">
        <!-- Header confirmation badge -->
        <div style="text-align: center; margin-bottom: 36px;">
            <div style="width: 76px; height: 76px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 38px; margin: 0 auto 20px; box-shadow: 0 12px 35px rgba(16, 185, 129, 0.45); font-weight: bold;">
                ✓
            </div>
            <span class="eyebrow" style="color: #34d399; letter-spacing: 0.18em; font-weight: 700;">Payment Confirmed</span>
            <h1 style="color: var(--ink); font-size: clamp(2.2rem, 5vw, 3.2rem); margin: 12px 0 8px;">Thank you for your order!</h1>
            <p style="color: var(--muted); font-size: 1.08rem;">We have successfully received your payment and are preparing your order for dispatch.</p>
        </div>

        <!-- Glassmorphic Order Summary Card -->
        <div class="form-card" style="margin-bottom: 30px; border-radius: 28px; padding: 36px;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 20px; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="margin: 0; font-size: 1.6rem; color: var(--ink);">Order #<?= (int)$order['id'] ?></h2>
                    <span style="color: var(--muted); font-size: 13px; margin-top: 4px; display: inline-block;">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div>
                    <span style="background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.35); padding: 8px 18px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">
                        <?= e($order['payment_status']) ?>
                    </span>
                </div>
            </div>

            <!-- Details Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 20px;">
                <div>
                    <span style="color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 4px;">Payment Method</span>
                    <strong style="color: var(--ink); font-size: 15px;">Razorpay Online Payment</strong>
                </div>
                <?php if (!empty($order['razorpay_payment_id'])): ?>
                <div>
                    <span style="color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 4px;">Razorpay Payment ID</span>
                    <code style="background: rgba(255, 106, 42, 0.14); color: #ff6a2a; border: 1px solid rgba(255, 106, 42, 0.3); padding: 4px 10px; border-radius: 8px; font-family: monospace; font-size: 13px; font-weight: 600;"><?= e($order['razorpay_payment_id']) ?></code>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ordered Items Table -->
            <h3 style="margin-bottom: 16px; color: var(--ink); font-size: 1.25rem;">Items Ordered</h3>
            <div style="border: 1px solid var(--line); border-radius: 16px; overflow: hidden; margin-bottom: 28px; background: var(--surface);">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: var(--surface); border-bottom: 1px solid var(--line); color: var(--ink);">
                            <th style="padding: 14px 18px; font-weight: 600;">Item Details</th>
                            <th style="padding: 14px 18px; text-align: center; font-weight: 600;">Qty</th>
                            <th style="padding: 14px 18px; text-align: right; font-weight: 600;">Price</th>
                            <th style="padding: 14px 18px; text-align: right; font-weight: 600;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr style="border-bottom: 1px solid var(--line); color: var(--ink);">
                                <td style="padding: 14px 18px; font-weight: 500; color: var(--ink);"><?= e($item['product_name']) ?></td>
                                <td style="padding: 14px 18px; text-align: center; color: var(--muted);"><?= (int)$item['quantity'] ?></td>
                                <td style="padding: 14px 18px; text-align: right; color: var(--muted);"><?= formatPrice((float)$item['price']) ?></td>
                                <td style="padding: 14px 18px; text-align: right; font-weight: 600; color: var(--ink);"><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--surface);">
                            <td colspan="3" style="padding: 16px 18px; text-align: right; font-weight: 700; color: var(--ink); font-size: 15px;">Grand Total:</td>
                            <td style="padding: 16px 18px; text-align: right; font-weight: 800; font-size: 1.2rem; color: var(--accent);"><?= formatPrice((float)$order['total_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Shipping Address Card -->
            <div style="background: var(--surface); border: 1px solid var(--line); padding: 20px; border-radius: 16px; font-size: 14px; margin-bottom: 32px; color: var(--ink);">
                <strong style="color: var(--ink); display: block; margin-bottom: 6px; font-size: 15px;">Shipping Address:</strong>
                <div style="line-height: 1.6; color: var(--muted);"><?= nl2br(e($order['address'])) ?></div>
            </div>

            <!-- Action buttons -->
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= url('track_order.php?id=' . (int)$order['id']) ?>"
                   class="btn primary"
                   style="padding: 0 32px; display: inline-flex; align-items: center; gap: 8px;">
                    🚚 Track Your Order
                </a>
                <a href="<?= url('shop.php') ?>" class="btn ghost" style="padding: 0 32px;">Continue Shopping</a>
                <a href="<?= url('profile.php') ?>" class="btn ghost" style="padding: 0 32px;">View All Orders</a>
            </div>
        </div>
    </div>
</section>

<?php require ROOT_PATH . '/includes/footer.php'; ?>
