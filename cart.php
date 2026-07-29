<?php
require_once __DIR__ . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid request.');
        redirect('cart.php');
    }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
        flash('success', 'Item removed from bag.');
    }

    if ($action === 'update' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = max(1, min(10, (int)($_POST['quantity'] ?? 1)));
        flash('success', 'Bag updated.');
    }

    redirect('cart.php');
}

$pageTitle = 'Your Bag | KGF Mens Wear';
$cart = $_SESSION['cart'] ?? [];
require ROOT_PATH . '/includes/header.php';
?>
<section class="page-hero compact">
    <div class="container"><span class="eyebrow">Your selection</span><h1>Shopping bag</h1></div>
</section>

<section class="section">
    <div class="container cart-layout">
        <div class="cart-items">
            <?php if (!$cart): ?>
                <div class="empty-state"><h2>Your bag is empty</h2><p>Add a few styles and come back.</p><a class="btn primary" href="<?= url('shop.php') ?>">Start shopping</a></div>
            <?php endif; ?>

            <?php foreach ($cart as $item): ?>
                <article class="cart-item">
                    <div class="cart-thumb">
                        <img src="<?= getProductImageUrl($item) ?>" alt="<?= e($item['name']) ?>" class="cart-thumb-img">
                    </div>
                    <div class="cart-meta">
                        <h3><?= e($item['name']) ?></h3>
                        <p><?= formatPrice((float)$item['price']) ?></p>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="action" value="update">
                            <input class="qty" type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="1" max="10">
                            <button type="submit">Update</button>
                        </form>
                    </div>
                    <div>
                        <strong><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></strong>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="action" value="remove">
                            <button class="link-danger" type="submit">Remove</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($cart): ?>
            <aside class="summary-card">
                <h3>Order summary</h3>
                <div><span>Subtotal</span><strong><?= formatPrice(cartTotal()) ?></strong></div>
                <div><span>Shipping</span><strong>Free</strong></div>
                <div class="summary-total"><span>Total</span><strong><?= formatPrice(cartTotal()) ?></strong></div>
                <a class="btn primary full" href="<?= url('checkout.php') ?>">Proceed to checkout</a>
            </aside>
        <?php endif; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
