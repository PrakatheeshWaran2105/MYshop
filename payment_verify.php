<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!isLoggedIn()) {
    flash('error', 'Please login to complete payment verification.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    flash('error', 'Invalid security token.');
    redirect('checkout.php');
}

$orderId      = (int)($_POST['order_id'] ?? 0);
$rzpPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$rzpOrderId   = trim($_POST['razorpay_order_id'] ?? '');
$signature    = trim($_POST['razorpay_signature'] ?? '');

if ($orderId <= 0 || empty($rzpPaymentId)) {
    flash('error', 'Payment verification details missing.');
    redirect('checkout.php');
}

$order = getOrderDetails($pdo, $orderId);

if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
    flash('error', 'Order not found or unauthorized.');
    redirect('shop.php');
}

$isValidSignature = verifyRazorpaySignature($rzpOrderId ?: ($order['razorpay_order_id'] ?? ''), $rzpPaymentId, $signature);

if ($isValidSignature) {
    // Update order status to paid
    $stmt = $pdo->prepare('UPDATE orders SET payment_status = ?, status = ?, razorpay_payment_id = ?, razorpay_order_id = ? WHERE id = ?');
    $stmt->execute([
        'paid',
        'processing',
        $rzpPaymentId,
        $rzpOrderId ?: ($order['razorpay_order_id'] ?? null),
        $orderId
    ]);

    // Clear active session cart and checkout state
    unset($_SESSION['cart']);
    unset($_SESSION['active_checkout_order_id']);
    unset($_SESSION['active_razorpay_order_id']);

    // Send order confirmation email
    sendOrderConfirmationEmail($pdo, $orderId);

    flash('success', 'Payment successful! Order #' . $orderId . ' has been placed.');
    redirect('order_success.php?id=' . $orderId);
} else {
    // Mark order as failed
    $stmt = $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');
    $stmt->execute(['failed', $orderId]);

    flash('error', 'Payment verification failed. Please try again.');
    redirect('checkout.php?pay=1');
}
