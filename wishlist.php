<?php
require_once __DIR__ . '/config/bootstrap.php';

$pageTitle = 'My Wishlist | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';

$wishlistItems = [];
if (isLoggedIn()) {
    $wishlistItems = getUserWishlist($pdo, $_SESSION['user_id']);
}
?>
<section class="page-hero compact">
    <div class="container">
        <span class="eyebrow">Your Favorites</span>
        <h1>My Wishlist</h1>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <div class="empty-state" style="text-align: center; padding: 60px 0;">
                <h2>Login to see your favorites</h2>
                <p>Keep track of the styles you love in one place.</p>
                <a class="btn primary" href="<?= url('login.php') ?>" style="margin-top: 20px;">Log In / Register</a>
            </div>
        <?php elseif (empty($wishlistItems)): ?>
            <div class="empty-state" style="text-align: center; padding: 60px 0;">
                <h2>Your wishlist is empty</h2>
                <p>Explore our catalog and add items you love to your wishlist.</p>
                <a class="btn primary" href="<?= url('shop.php') ?>" style="margin-top: 20px;">Explore Shop</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
                <?php foreach ($wishlistItems as $item): 
                    $item = enrichProductData($item); ?>
                    <article class="product-card" style="border: 1px solid var(--line); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; background: var(--surface); transition: all 0.3s ease;">
                        <div class="product-thumb" style="position: relative; overflow: hidden; background: #f0ede9;">
                            <a href="<?= url('product.php?id=' . $item['id']) ?>">
                                <img src="<?= getProductImageUrl($item) ?>" alt="<?= e($item['name']) ?>" style="width:100%; height: 350px; object-fit: cover;">
                            </a>
                            <form method="post" action="wishlist_action.php" style="position: absolute; top: 15px; right: 15px; z-index: 10;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="remove-btn" title="Remove from Wishlist" style="background: white; border: none; width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.15); color: #ff5252;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <div class="product-card-body" style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 15px;">
                            <div>
                                <h3 style="font-size: 1.1rem; margin: 0 0 8px; font-weight: 600;">
                                    <a href="<?= url('product.php?id=' . $item['id']) ?>" style="color: var(--ink);"><?= e($item['name']) ?></a>
                                </h3>
                                <p style="font-size: 0.9rem; color: var(--muted); margin: 0;"><?= formatPrice((float)$item['price']) ?></p>
                            </div>
                            
                            <form method="post" action="product.php?id=<?= $item['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn primary full" style="min-height: 44px; font-size: 0.9rem;">Add to Bag</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
