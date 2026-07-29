<?php
require_once __DIR__ . '/config/bootstrap.php';

// Quick Add to Bag Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_add') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid security token.');
    } else {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $prod = findProduct($pdo, $prodId);
        if ($prod) {
            $currentQty = $_SESSION['cart'][$prodId]['quantity'] ?? 0;
            $_SESSION['cart'][$prodId] = [
                'id' => $prodId,
                'name' => $prod['name'],
                'price' => (float)$prod['price'],
                'quantity' => min(10, $currentQty + 1),
                'image' => $prod['image']
            ];
            flash('success', '"' . e($prod['name']) . '" added to your bag!');
            redirect('index.php#featured');
        }
    }
}

$pageTitle = 'KGF Mens Wear | Modern Menswear';
$products = getProducts($pdo, 8);
require ROOT_PATH . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy reveal">
            <span class="eyebrow">New Season · 2026</span>
            <h1>Wear confidence.<br><em>Own the room.</em></h1>
            <p>Premium everyday menswear with sharp silhouettes, comfortable fabrics and a clean modern attitude.</p>
            <div class="hero-buttons">
                <a class="btn primary" href="<?= url('shop.php') ?>">Shop collection</a>
                <a class="btn ghost" href="#featured">Explore style</a>
            </div>
            <div class="hero-stats">
                <div><strong>120+</strong><span>Modern styles</span></div>
                <div><strong>4.8/5</strong><span>Customer rating</span></div>
                <div><strong>7 Days</strong><span>Easy returns</span></div>
            </div>
        </div>
        <div class="hero-visual reveal">
            <div class="hero-card main-look">
                <img src="<?= url('assets/images/lookbook_hero.png') ?>" alt="KGF Lookbook" class="hero-lookbook-img">
            </div>
            <div class="floating-tag">Premium Fit<br><strong>From ₹799</strong></div>
        </div>
    </div>
</section>

<section class="section categories-bento-section">
    <div class="container">
        <div class="bento-head">
            <h2 class="bento-title">Made for every move</h2>
        </div>
        <div class="bento-grid">
            <a class="bento-card card-tall reveal reveal-left" href="<?= url('shop.php?category=shirts') ?>">
                <img src="<?= url('assets/images/cat_shirts.png') ?>" alt="Shirts" class="bento-img">
                <span class="bento-num">01</span>
                <div class="bento-content">
                    <h3>Shirts</h3>
                    <p>Smart casual essentials</p>
                </div>
            </a>
            <a class="bento-card reveal reveal-right" href="<?= url('shop.php?category=jeans') ?>">
                <img src="<?= url('assets/images/cat_jeans.png') ?>" alt="Jeans" class="bento-img">
                <span class="bento-num">02</span>
                <div class="bento-content">
                    <h3>Jeans</h3>
                    <p>Built for all-day comfort</p>
                </div>
            </a>
            <a class="bento-card reveal reveal-right" href="<?= url('shop.php?category=tshirts') ?>">
                <img src="<?= url('assets/images/cat_tshirts.png') ?>" alt="T-Shirts" class="bento-img">
                <span class="bento-num">03</span>
                <div class="bento-content">
                    <h3>T-Shirts</h3>
                    <p>Minimal and versatile</p>
                </div>
                <div class="bento-sparkle">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0L14.59 9.41L24 12L14.59 14.59L12 24L9.41 14.59L0 12L9.41 9.41L12 0Z"/>
                    </svg>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Fresh drops</span><h2>Featured products</h2></div>
            <a href="<?= url('shop.php') ?>">View all →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card reveal" onclick="window.location.href='<?= url('product.php?id=' . $product['id']) ?>'">
                    <div class="product-media" style="position: relative;">
                        <img src="<?= getProductImageUrl($product) ?>" alt="<?= e($product['name']) ?>" loading="lazy" class="product-img">
                        <span class="product-badge">New</span>
                        <div class="product-hover-overlay">
                            <span class="hover-btn">View Product →</span>
                        </div>
                        
                        <!-- Floating Wishlist Button -->
                        <form method="post" action="wishlist_action.php" class="card-wishlist-form" onclick="event.stopPropagation();" style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="action" value="toggle">
                            <?php 
                            $isWl = false;
                            if ($currentUser) {
                                $isWl = isInWishlist($pdo, $currentUser['id'], $product['id']);
                            }
                            ?>
                            <button type="submit" class="card-wishlist-btn" title="<?= $isWl ? 'Remove from Wishlist' : 'Add to Wishlist' ?>" style="background: rgba(255, 255, 255, 0.9); border: none; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.15); color: <?= $isWl ? '#ff5252' : '#888' ?>; transition: all 0.3s ease;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $isWl ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    <div class="product-info">
                        <div>
                            <p><?= e($product['category']) ?></p>
                            <h3><a href="<?= url('product.php?id=' . $product['id']) ?>" onclick="event.stopPropagation();"><?= e($product['name']) ?></a></h3>
                        </div>
                        <strong class="price-tag"><?= formatPrice((float)$product['price']) ?></strong>
                    </div>

                    <form method="post" class="quick-add-form" onclick="event.stopPropagation();">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="quick_add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <button type="submit" class="btn primary full quick-add-btn">+ Add to Bag</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section promo">
    <div class="container promo-card">
        <div>
            <span class="eyebrow">Member benefit</span>
            <h2>Get 10% off your first order</h2>
            <p>Create an account and use code <strong>KGF10</strong> during checkout.</p>
        </div>
        <a class="btn light" href="<?= url('register.php') ?>">Join KGF Club</a>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
