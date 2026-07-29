<?php
require_once __DIR__ . '/config/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = $id ? findProduct($pdo, $id) : null;

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

$product = enrichProductData($product);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid request. Please try again.');
        redirect('product.php?id=' . $id);
    }

    $quantity = max(1, min(10, (int)($_POST['quantity'] ?? 1)));
    $_SESSION['cart'][$id] = [
        'id' => $id,
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'quantity' => $quantity,
        'image' => $product['image']
    ];
    flash('success', 'Product added to your bag.');
    redirect('cart.php');
}

$pageTitle = $product['name'] . ' | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section product-page">
    <div class="container product-detail">
        <div class="product-gallery">
            <img src="<?= getProductImageUrl($product) ?>" alt="<?= e($product['name']) ?>" class="detail-product-img">
        </div>
        <div class="product-content">
            <span class="eyebrow"><?= e($product['brand']) ?> · <?= e($product['category']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <div class="fashion-rating-pill rating-green" style="margin: 10px 0;">
                <span class="rating-val"><?= number_format((float)$product['rating'], 1) ?> ★</span>
                <span class="rating-pipe">|</span>
                <span class="rating-num"><?= (int)$product['rating_count'] ?> Verified Ratings</span>
            </div>
            <div class="fashion-price-block" style="font-size: 1.4rem; margin: 15px 0;">
                <span class="price-current" style="font-size: 1.6rem;"><?= formatPrice((float)$product['price']) ?></span>
                <?php if (!empty($product['mrp']) && $product['mrp'] > $product['price']): ?>
                    <span class="price-mrp" style="font-size: 1.1rem;"><?= formatPrice((float)$product['mrp']) ?></span>
                    <span class="price-discount" style="font-size: 1rem;">(<?= (int)$product['discount_pct'] ?>% off)</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($product['offer_price']) && $product['offer_price'] < $product['price']): ?>
                <div class="fashion-offer-tag" style="font-size: 0.95rem; margin-bottom: 15px;">
                    <span class="offer-percent-icon" style="width: 18px; height: 18px; font-size: 0.75rem;">%</span>
                    <span class="offer-label">Special Offer Price: <?= formatPrice((float)$product['offer_price']) ?></span>
                </div>
            <?php endif; ?>
            <p><?= e($product['description']) ?></p>


            <form method="post" class="product-form">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <label>Size</label>
                <div class="size-options">
                    <label><input type="radio" name="size" value="S"><span>S</span></label>
                    <label><input type="radio" name="size" value="M" checked><span>M</span></label>
                    <label><input type="radio" name="size" value="L"><span>L</span></label>
                    <label><input type="radio" name="size" value="XL"><span>XL</span></label>
                </div>
                <label>Quantity</label>
                <input class="qty" type="number" name="quantity" value="1" min="1" max="10">
                <button class="btn primary full" type="submit">Add to bag</button>
            </form>

            <form method="post" action="wishlist_action.php" style="margin-top: 15px;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <?php 
                $isWl = false;
                if ($currentUser) {
                    $isWl = isInWishlist($pdo, $currentUser['id'], $product['id']);
                }
                ?>
                <button class="btn ghost full" type="submit" style="display: flex; gap: 8px; justify-content: center; align-items: center; border: 1.5px solid var(--accent); color: var(--accent); background: transparent; transition: all 0.3s ease;">
                    <svg class="heart-icon" width="20" height="20" viewBox="0 0 24 24" fill="<?= $isWl ? 'var(--accent)' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: fill 0.3s ease;">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span><?= $isWl ? 'Remove from Wishlist' : 'Add to Wishlist' ?></span>
                </button>
            </form>
        </div>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
