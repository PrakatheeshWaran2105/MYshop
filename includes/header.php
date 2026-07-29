<?php
$pageTitle = $pageTitle ?? 'KGF Mens Wear';
$currentUser = isset($pdo) ? currentUser($pdo) : null;
$currentPage = basename($_SERVER['PHP_SELF']);

$wishlistCount = 0;
if ($currentUser && isset($pdo)) {
    $wishlistCount = getWishlistCount($pdo, (int)$currentUser['id']);
}
file_put_contents(
    ROOT_PATH . '/debug_db.log',
    "DATE: " . date('Y-m-d H:i:s') . "\n" .
    "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n" .
    "USER_ID: " . ($currentUser['id'] ?? 'null') . "\n" .
    "WISHLIST_COUNT: " . $wishlistCount . "\n\n",
    FILE_APPEND
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Modern menswear, curated for confidence.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= url('assets/kgf-logo-shield.png') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=1.0.2">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
<!-- Vertical Floating Social/Utility Sidebar (Visible on Wide Desktop Screen Margins) -->
</div>


<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-left">
            <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram" class="topbar-social">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </a>
            <a href="https://wa.me/919876543210" target="_blank" rel="noopener" aria-label="WhatsApp Support" class="topbar-social">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            </a>
            <span class="topbar-info">Support: +91 98765 43210</span>
        </div>
        <div class="topbar-center">
            <span class="promo-text">Free shipping above ₹1,499 · Easy 7-day returns</span>
        </div>
        <div class="topbar-right">
            <div class="currency-selector" id="currencySelector">
                <button class="currency-trigger" id="currencyTrigger" aria-haspopup="true" aria-expanded="false">
                    <span>🌐 EN / ₹ INR</span>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="currency-dropdown" id="currencyDropdown">
                    <button data-currency="INR">₹ INR (India)</button>
                    <button data-currency="USD">$ USD (US)</button>
                    <button data-currency="EUR">€ EUR (Europe)</button>
                </div>
            </div>
        </div>
    </div>
</div>
 
<header class="site-header" id="siteHeader">
    <div class="container nav-wrap">
        <button class="drawer-toggle-btn" id="drawerToggleBtn" aria-label="Open side menu" title="Explore Categories">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            <span class="drawer-btn-text">Explore</span>
        </button>

        <a class="brand" href="<?= url('index.php') ?>" title="KGF Mens Wear Home">
            <img src="<?= url('assets/kgf-logo-shield.png') ?>" alt="KGF Logo" class="brand-logo-img">
            <span class="brand-title">KGF <strong>Mens Wear</strong></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Open menu">☰</button>

        <nav class="main-nav" id="mainNav">
            <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
            <a class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>" href="<?= url('shop.php') ?>">Shop</a>
            <a href="<?= url('shop.php?category=shirts') ?>">Shirts</a>
            <a href="<?= url('shop.php?category=jeans') ?>">Jeans</a>
            <a href="<?= url('contact.php') ?>">Contact</a>
        </nav>

        <div class="nav-actions">
            <button class="icon-btn" id="searchButton" aria-label="Search" title="Search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            
            <a class="icon-btn wishlist-btn" href="<?= url('wishlist.php') ?>" id="wishlistButton" title="Wishlist">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span class="wishlist-badge" id="wishlistBadge"><?= $wishlistCount ?></span>
            </a>

            <button class="icon-btn theme-toggle-btn" id="themeToggle" aria-label="Toggle Theme" title="Toggle Theme">
                <svg class="sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <svg class="moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
            
            <?php if ($currentUser): ?>
                <span class="user-greeting">Hi, <strong><?= e(explode(' ', $currentUser['name'])[0]) ?></strong></span>
                <a class="icon-btn profile-icon" href="<?= url('profile.php') ?>" title="Account: <?= e($currentUser['name']) ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            <?php else: ?>
                <span class="user-greeting">Welcome, <strong>Guest</strong></span>
                <a class="icon-btn login-icon" href="<?= url('login.php') ?>" title="Login / Account">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            <?php endif; ?>

            <a class="cart-btn" href="<?= url('cart.php') ?>" title="Shopping Bag">
                Bag <span><?= cartCount() ?></span>
            </a>
        </div>
    </div>

    <form class="search-panel" id="searchPanel" action="<?= url('shop.php') ?>" method="get">
        <div class="container search-inner">
            <div class="search-input-wrap">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" name="q" placeholder="Search shirts, jeans, jackets..." autocomplete="off">
            </div>
            <button type="submit" class="search-submit-btn">
                <span>Search</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </div>
    </form>
</header>

<!-- Slide-out Side Drawer Menu -->
<div class="sidebar-drawer" id="sidebarDrawer" aria-hidden="true">
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer-content">
        <div class="drawer-header">
            <div class="drawer-brand">
                <img src="<?= url('assets/kgf-logo-shield.png') ?>" alt="KGF Logo" class="drawer-logo-img">
                <span class="brand-title">KGF <strong>Mens Wear</strong></span>
            </div>
            <button class="drawer-close" id="drawerClose" aria-label="Close menu">&times;</button>
        </div>
        <div class="drawer-body">
            <div class="drawer-nav">
                <span class="drawer-section-title">Collections</span>
                <a href="<?= url('shop.php') ?>">All Products</a>
                <a href="<?= url('shop.php?category=shirts') ?>">Casual Shirts</a>
                <a href="<?= url('shop.php?category=jeans') ?>">Premium Jeans</a>
                <a href="<?= url('shop.php?category=tshirts') ?>">Minimal T-Shirts</a>
                
                <span class="drawer-section-title">Explore KGF</span>
                <a href="<?= url('contact.php') ?>">Contact Support</a>
                <a href="<?= url('profile.php') ?>">Your Profile / Orders</a>
            </div>
            <div class="drawer-footer">
                <p>Curated for confidence.</p>
                <div class="drawer-socials">
                    <a href="https://instagram.com" target="_blank" rel="noopener">Instagram</a>
                    <a href="https://wa.me/919876543210" target="_blank" rel="noopener">WhatsApp Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message = flash('success')): ?>
    <div class="flash success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="flash error"><?= e($message) ?></div>
<?php endif; ?>

<main>
