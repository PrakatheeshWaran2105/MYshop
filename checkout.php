<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!isLoggedIn()) {
    flash('error', 'Please login before checkout.');
    redirect('login.php');
}

$currentUser = currentUser($pdo);
$order = null;

// Handle Step 1: Create Order & prepare Razorpay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid security token.');
        redirect('checkout.php');
    }

    if (empty($_SESSION['cart'])) {
        flash('error', 'Your shopping bag is empty.');
        redirect('shop.php');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressText = trim($_POST['address'] ?? '');
    $fullAddress = "Name: {$name}\nPhone: {$phone}\nAddress: {$addressText}";

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_amount, status, payment_status, address) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'],
            cartTotal(),
            'pending',
            'pending',
            $fullAddress
        ]);

        $orderId = (int)$pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)');

        foreach ($_SESSION['cart'] as $item) {
            $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price']]);
        }

        $pdo->commit();

        // Create Razorpay Order
        $rzpResult = createRazorpayOrder(cartTotal(), "order_{$orderId}", [
            'order_id' => $orderId,
            'user_email' => $currentUser['email'] ?? ''
        ]);

        if ($rzpResult['success']) {
            $rzpOrderId = $rzpResult['order_id'];
            $pdo->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?')->execute([$rzpOrderId, $orderId]);
            $_SESSION['active_checkout_order_id'] = $orderId;
            $_SESSION['active_razorpay_order_id'] = $rzpOrderId;
            $_SESSION['checkout_customer_name'] = $name;
            $_SESSION['checkout_customer_phone'] = $phone;
            redirect('checkout.php?pay=1');
        } else {
            flash('error', 'Razorpay initialization error: ' . ($rzpResult['error'] ?? 'Unknown error'));
            redirect('checkout.php');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not create order: ' . $e->getMessage());
        redirect('checkout.php');
    }
}

// Payment Step Verification
$isPaymentStep = isset($_GET['pay']) && !empty($_SESSION['active_checkout_order_id']);
if ($isPaymentStep) {
    $orderId = (int)$_SESSION['active_checkout_order_id'];
    $order = getOrderDetails($pdo, $orderId);
    if (!$order || $order['payment_status'] === 'paid') {
        unset($_SESSION['active_checkout_order_id'], $_SESSION['active_razorpay_order_id']);
        redirect('shop.php');
    }
} else {
    if (empty($_SESSION['cart'])) {
        redirect('shop.php');
    }
}

$razorpayKey = trim($_ENV['RAZORPAY_KEY_ID'] ?? '');
$razorpaySecret = trim($_ENV['RAZORPAY_KEY_SECRET'] ?? '');

$hasRazorpayKey =
    $razorpayKey !== '' &&
    $razorpaySecret !== '' &&
    (
        str_starts_with($razorpayKey, 'rzp_test_') ||
        str_starts_with($razorpayKey, 'rzp_live_')
    );

$isTestKey = !$hasRazorpayKey;

require ROOT_PATH . '/includes/header.php';
?>

<section class="section auth-section">
    <div class="container checkout-grid">
        <?php if ($isPaymentStep && $order): ?>
            <!-- STEP 2: RAZORPAY PAYMENT INITIALIZATION -->
            <div class="form-card payment-box">
                <span class="eyebrow">Step 2 of 2 · Payment Processing</span>
                <h1>Order #<?= (int)$order['id'] ?></h1>
                <p>Complete your payment securely using Razorpay to confirm your order.</p>

                <?php if ($isTestKey): ?>
                    <div style="background: rgba(255, 170, 0, 0.1); border: 1px solid rgba(255, 170, 0, 0.3); padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-size: 14px; color: var(--accent);">
                        <strong>⚡ Test Mode Notice:</strong> Real Razorpay API key is not yet set in <code>.env</code>. You can test real checkout by adding <code>RAZORPAY_KEY_ID</code> or click below to simulate successful payment instantly.
                    </div>
                <?php endif; ?>

                <div style="margin: 24px 0;">
                    <button id="rzp-button" class="btn primary full" style="font-size: 16px; padding: 14px 24px;">
                        💳 Pay Now with Razorpay · <?= formatPrice((float)$order['total_amount']) ?>
                    </button>

                    <?php if ($isTestKey): ?>
                        <button type="button" onclick="simulateTestPayment()" class="btn ghost full btn-simulate">
                            ✔ Simulate Successful Payment (Test Mode)
                        </button>
                    <?php endif; ?>
                </div>

                <p style="font-size: 13px; color: var(--muted); margin-top: 16px; text-align: center;">
                    🔒 256-bit SSL encrypted. Safe & secure payment.
                </p>

                <!-- Hidden submission form to process Razorpay payment response -->
                <form id="payment-verify-form" method="post" action="<?= url('payment_verify.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id" value="">
                    <input type="hidden" id="razorpay_order_id" name="razorpay_order_id" value="">
                    <input type="hidden" id="razorpay_signature" name="razorpay_signature" value="">
                </form>
            </div>

            <aside class="summary-card">
                <h3>Order summary</h3>
                <?php foreach ($order['items'] as $item): ?>
                    <div>
                        <span><?= e($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span>
                        <strong><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Total</span>
                    <strong style="color: var(--accent);"><?= formatPrice((float)$order['total_amount']) ?></strong>
                </div>
                <div style="margin-top: 16px; font-size: 13px; color: var(--muted); background: var(--surface); padding: 14px; border-radius: 12px; border: 1px solid var(--line);">
                    <strong>Delivery address:</strong><br>
                    <?= nl2br(e($order['address'])) ?>
                </div>
            </aside>

            <!-- Razorpay Standard JS SDK -->
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
            function startRazorpayPayment() {
                <?php if ($hasRazorpayKey): ?>
                    var options = {
                        "key": "<?= e($razorpayKey) ?>",
                        "amount": "<?= (int)round((float)$order['total_amount'] * 100) ?>",
                        "currency": "INR",
                        "name": "<?= e($_ENV['COMPANY_NAME'] ?? 'KGF Mens Wear') ?>",
                        "description": "Order #<?= (int)$order['id'] ?>",
                        "order_id": "<?= e($_SESSION['active_razorpay_order_id'] ?? $order['razorpay_order_id'] ?? '') ?>",
                        "handler": function (response) {
                            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                            document.getElementById('razorpay_order_id').value = response.razorpay_order_id || "<?= e($_SESSION['active_razorpay_order_id'] ?? $order['razorpay_order_id'] ?? '') ?>";
                            document.getElementById('razorpay_signature').value = response.razorpay_signature || "";
                            document.getElementById('payment-verify-form').submit();
                        },
                        "prefill": {
                            "name": "<?= e($_SESSION['checkout_customer_name'] ?? $currentUser['name'] ?? '') ?>",
                            "email": "<?= e($currentUser['email'] ?? '') ?>",
                            "contact": "<?= e($_SESSION['checkout_customer_phone'] ?? '') ?>"
                        },
                        "theme": {
                            "color": "#ff6a2a"
                        }
                    };
                    var rzp = new Razorpay(options);
                    rzp.on('payment.failed', function (response) {
                        alert('Payment Failed: ' + (response.error.description || 'Transaction unsuccessful'));
                    });
                    rzp.open();
                <?php else: ?>
                    simulateTestPayment();
                <?php endif; ?>
            }

            function simulateTestPayment() {
                var dummyPaymentId = 'pay_test_' + Math.random().toString(36).substring(2, 12) + Date.now().toString(36);
                document.getElementById('razorpay_payment_id').value = dummyPaymentId;
                document.getElementById('razorpay_order_id').value = "<?= e($_SESSION['active_razorpay_order_id'] ?? $order['razorpay_order_id'] ?? ('order_test_' . $order['id'])) ?>";
                document.getElementById('razorpay_signature').value = "test_signature_valid";
                document.getElementById('payment-verify-form').submit();
            }

            document.getElementById('rzp-button')?.addEventListener('click', function(e) {
                e.preventDefault();
                startRazorpayPayment();
            });
            </script>


        <?php else: ?>
            <!-- STEP 1: DELIVERY DETAILS FORM -->
            <form method="post" class="form-card">
                <span class="eyebrow">Step 1 of 2 · Delivery Details</span>
                <h1>Shipping information</h1>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="place_order">

                <label>Full name
                    <input type="text" name="name" value="<?= e($currentUser['name'] ?? '') ?>" required placeholder="Enter full name">
                </label>
                <label>Phone number
                    <input type="tel" name="phone" required placeholder="Enter 10-digit mobile number">
                </label>
                <label>Full delivery address
                    <textarea name="address" rows="3" required placeholder="House/Flat No., Street, City, State, Pincode"></textarea>
                </label>


                <button class="btn primary full" type="submit">Proceed to Payment · <?= formatPrice(cartTotal()) ?></button>
            </form>

            <aside class="summary-card">
                <h3>Your selection</h3>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div>
                        <span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span>
                        <strong><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Total</span>
                    <strong><?= formatPrice(cartTotal()) ?></strong>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</section>

<?php require ROOT_PATH . '/includes/footer.php'; ?>
