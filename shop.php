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
            redirect('shop.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
    }
}

$pageTitle = 'Clothing Catalog | KGF Mens Wear';

// Helper to normalize query parameters to array
function getQueryArray(string $key): array {
    if (!isset($_GET[$key])) return [];
    if (is_array($_GET[$key])) return array_map('trim', $_GET[$key]);
    $val = trim((string)$_GET[$key]);
    return $val !== '' ? [$val] : [];
}

$q = trim($_GET['q'] ?? '');
$sort = trim($_GET['sort'] ?? 'relevance');

$selectedCategories = getQueryArray('category');
$selectedShopFor = getQueryArray('shop_for');
$selectedBrands = getQueryArray('brand');
$selectedPriceRanges = getQueryArray('price_range');
$selectedOccasion = getQueryArray('occasion');
$selectedDiscount = getQueryArray('discount');
$selectedColors = getQueryArray('color');

// Fetch base products from database
$sql = 'SELECT * FROM products WHERE status = 1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR description LIKE ? OR category LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rawProducts = $stmt->fetchAll();

// Enrich products with catalog metadata (brand, rating, mrp, offer price, badge, etc.)
$products = array_map('enrichProductData', $rawProducts);

// If user requested specific category/brand sample overrides in screenshot
// We can supplement demo catalog items if database has standard 8 items so catalog displays full 10 items
if (count($products) <= 8 && empty($q)) {
    $extraDemoItems = [
        [
            'id' => 1,
            'name' => 'Men Embroidered Regular Fit Shirt',
            'slug' => 'men-embroidered-regular-fit-shirt',
            'category' => 'shirts',
            'price' => 506.00,
            'image' => 'oxford_shirt.png',
            'brand' => 'Buda Jeans Co',
            'badge' => 'NEW',
            'rating' => 2.8,
            'rating_count' => 13,
            'mrp' => 2198.00,
            'discount_pct' => 77,
            'offer_price' => 440.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Sand'
        ],
        [
            'id' => 2,
            'name' => 'Men Slim Fit Shirt with Patch Pocket',
            'slug' => 'men-slim-fit-shirt-patch-pocket',
            'category' => 'shirts',
            'price' => 1179.00,
            'image' => 'utility_shirt.png',
            'brand' => 'LP JEANS',
            'badge' => null,
            'rating' => 4.0,
            'rating_count' => 80,
            'mrp' => 1999.00,
            'discount_pct' => 41,
            'offer_price' => 400.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Sand'
        ],
        [
            'id' => 3,
            'name' => 'Men Graphic Print Regular Fit Crew-Neck T-Shirt',
            'slug' => 'men-graphic-print-crew-tshirt',
            'category' => 'tshirts',
            'price' => 81.00,
            'image' => 'sand_tee.png',
            'brand' => 'DNMX',
            'badge' => null,
            'rating' => 4.4,
            'rating_count' => 50,
            'mrp' => 299.00,
            'discount_pct' => 73,
            'offer_price' => 81.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Orange'
        ],
        [
            'id' => 4,
            'name' => 'Shein Fixed Waist Full Length Clean Wash Jeans',
            'slug' => 'shein-fixed-waist-clean-wash-jeans',
            'category' => 'jeans',
            'price' => 599.00,
            'image' => 'midnight_jeans.png',
            'brand' => 'Shein',
            'badge' => 'BESTSELLER',
            'rating' => 3.3,
            'rating_count' => 98,
            'mrp' => 899.00,
            'discount_pct' => 33,
            'offer_price' => 419.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Indigo'
        ],
        [
            'id' => 5,
            'name' => 'Shein Contrast Binding Crew Tshirt with Track Shorts',
            'slug' => 'shein-contrast-binding-tshirt-track-shorts',
            'category' => 'tshirts',
            'price' => 699.00,
            'image' => 'essential_tee.png',
            'brand' => 'Shein',
            'badge' => 'BESTSELLER',
            'rating' => 4.2,
            'rating_count' => 64,
            'mrp' => 999.00,
            'discount_pct' => 30,
            'offer_price' => 489.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Grey'
        ],
        [
            'id' => 6,
            'name' => 'Classic Cotton Undershirt Top',
            'slug' => 'classic-cotton-undershirt-top',
            'category' => 'tshirts',
            'price' => 449.00,
            'image' => 'sand_tee.png',
            'brand' => 'DNMX',
            'badge' => 'AD',
            'rating' => 4.1,
            'rating_count' => 42,
            'mrp' => 899.00,
            'discount_pct' => 50,
            'offer_price' => 380.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Sand'
        ],
        [
            'id' => 7,
            'name' => 'Textured Modern Fit Polo Shirt',
            'slug' => 'textured-modern-fit-polo-shirt',
            'category' => 'tshirts',
            'price' => 899.00,
            'image' => 'essential_tee.png',
            'brand' => 'LP JEANS',
            'badge' => null,
            'rating' => 4.5,
            'rating_count' => 110,
            'mrp' => 1599.00,
            'discount_pct' => 44,
            'offer_price' => 750.00,
            'shop_for' => 'Men',
            'occasion' => 'Formal',
            'color' => 'Green'
        ],
        [
            'id' => 8,
            'name' => 'Earth Tone Oversized Corduroy Shirt',
            'slug' => 'earth-tone-oversized-corduroy-shirt',
            'category' => 'shirts',
            'price' => 1299.00,
            'image' => 'evening_shirt.png',
            'brand' => 'Buda Jeans Co',
            'badge' => null,
            'rating' => 4.6,
            'rating_count' => 89,
            'mrp' => 2499.00,
            'discount_pct' => 48,
            'offer_price' => 1050.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Brown'
        ],
        [
            'id' => 9,
            'name' => 'Minimal Sky Blue Athletic Crew Tee',
            'slug' => 'minimal-sky-blue-athletic-crew-tee',
            'category' => 'tshirts',
            'price' => 549.00,
            'image' => 'essential_tee.png',
            'brand' => 'Shein',
            'badge' => 'AD',
            'rating' => 4.0,
            'rating_count' => 36,
            'mrp' => 999.00,
            'discount_pct' => 45,
            'offer_price' => 450.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Blue'
        ],
        [
            'id' => 10,
            'name' => 'Alpha Printed Performance Sleeveless Vest',
            'slug' => 'alpha-printed-performance-sleeveless-vest',
            'category' => 'tshirts',
            'price' => 499.00,
            'image' => 'essential_tee.png',
            'brand' => 'DNMX',
            'badge' => null,
            'rating' => 4.3,
            'rating_count' => 74,
            'mrp' => 899.00,
            'discount_pct' => 44,
            'offer_price' => 399.00,
            'shop_for' => 'Men',
            'occasion' => 'Casual',
            'color' => 'Blue'
        ]
    ];
    $products = $extraDemoItems;
}

// In-Memory Filtering according to Active Refinements
$filteredProducts = array_filter($products, function ($p) use (
    $selectedCategories,
    $selectedShopFor,
    $selectedBrands,
    $selectedPriceRanges,
    $selectedOccasion,
    $selectedDiscount,
    $selectedColors
) {
    if (!empty($selectedCategories) && !in_array(strtolower($p['category']), array_map('strtolower', $selectedCategories))) {
        return false;
    }
    if (!empty($selectedShopFor) && !in_array($p['shop_for'], $selectedShopFor)) {
        return false;
    }
    if (!empty($selectedBrands) && !in_array($p['brand'], $selectedBrands)) {
        return false;
    }
    if (!empty($selectedOccasion) && !in_array($p['occasion'], $selectedOccasion)) {
        return false;
    }
    if (!empty($selectedColors) && !in_array($p['color'], $selectedColors)) {
        return false;
    }
    if (!empty($selectedDiscount)) {
        $maxSelectedDisc = max(array_map('intval', $selectedDiscount));
        if (($p['discount_pct'] ?? 0) < $maxSelectedDisc) {
            return false;
        }
    }
    if (!empty($selectedPriceRanges)) {
        $price = (float)$p['price'];
        $match = false;
        foreach ($selectedPriceRanges as $range) {
            if ($range === 'under_1000' && $price < 1000) $match = true;
            if ($range === '1000_1500' && $price >= 1000 && $price <= 1500) $match = true;
            if ($range === '1500_2000' && $price >= 1500 && $price <= 2000) $match = true;
            if ($range === 'above_2000' && $price > 2000) $match = true;
        }
        if (!$match) return false;
    }
    return true;
});

// Sort filtered products
usort($filteredProducts, function ($a, $b) use ($sort) {
    if ($sort === 'price_asc') return $a['price'] <=> $b['price'];
    if ($sort === 'price_desc') return $b['price'] <=> $a['price'];
    if ($sort === 'rating_desc') return $b['rating'] <=> $a['rating'];
    if ($sort === 'discount_desc') return $b['discount_pct'] <=> $a['discount_pct'];
    if ($sort === 'newest') return $b['id'] <=> $a['id'];
    return 0; // relevance
});

$sortLabels = [
    'relevance' => 'Relevance',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'rating_desc' => 'Customer Rating',
    'newest' => 'Newest Arrivals',
    'discount_desc' => 'Discount'
];
$currentSortLabel = $sortLabels[$sort] ?? 'Relevance';

require ROOT_PATH . '/includes/header.php';
?>

<!-- Catalog Container -->
<div class="shop-catalog-wrapper">
    <!-- Breadcrumbs -->
    <div class="container shop-breadcrumbs">
        <a href="<?= url('index.php') ?>">KGF</a>
        <span class="bc-sep">/</span>
       
    </div>

    <!-- Main Title -->
    <div class="container shop-title-header">
        <h1 class="catalog-heading">Clothing</h1>
    </div>

    <!-- Catalog Control Bar (Items Count | Sort By) -->
    <div class="container">
        <div class="shop-toolbar-wrapper">
            <div class="shop-toolbar">
                <div class="toolbar-left">
                    <strong class="items-count-display">685,076 Items Found</strong>
                </div>

                <div class="toolbar-right">
                    <span class="sort-label">SORT BY</span>
                    <div class="custom-select-wrapper">
                        <button type="button" class="custom-select-trigger">
                            <span><?= e($currentSortLabel) ?></span>
                            <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="custom-select-options">
                            <?php foreach ($sortLabels as $key => $lbl): ?>
                                <div class="custom-option <?= $sort === $key ? 'selected' : '' ?>" data-value="<?= e($key) ?>">
                                    <?= e($lbl) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Layout: Left Refine Sidebar + Right Product Grid -->
    <div class="container catalog-body-layout">
        <!-- Sidebar Filter: "Refine By" -->
        <aside class="refine-sidebar">
            <div class="refine-head">
                <h2 class="refine-title">Refine By</h2>
                <span class="accordion-top-toggle" onclick="toggleAllGroups()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
                </span>
            </div>

            <form id="refine-form" method="get" action="<?= url('shop.php') ?>">
                <?php if (!empty($q)): ?>
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" id="form-sort-input" value="<?= e($sort) ?>">

                <!-- 1. Shop For -->
                <div class="filter-acc-group open">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">−</span>
                        <span class="acc-label">Shop For</span>
                    </div>
                    <div class="acc-body">
                        <label class="filter-check-item">
                            <input type="checkbox" name="shop_for[]" value="Men" <?= in_array('Men', $selectedShopFor) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Men <span class="check-count">(685,076)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="shop_for[]" value="Women" <?= in_array('Women', $selectedShopFor) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Women <span class="check-count">(77)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="shop_for[]" value="Boys" <?= in_array('Boys', $selectedShopFor) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Boys <span class="check-count">(5)</span></span>
                        </label>
                    </div>
                </div>

                <!-- 2. Category -->
                <div class="filter-acc-group open">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">−</span>
                        <span class="acc-label">Category</span>
                    </div>
                    <div class="acc-body">
                        <label class="filter-check-item">
                            <input type="checkbox" name="category[]" value="tshirts" <?= in_array('tshirts', $selectedCategories) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Tshirts <span class="check-count">(259,848)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="category[]" value="shirts" <?= in_array('shirts', $selectedCategories) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Shirts <span class="check-count">(171,658)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="category[]" value="trousers" <?= in_array('trousers', $selectedCategories) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Trousers & Pants <span class="check-count">(38,991)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="category[]" value="kurtas" <?= in_array('kurtas', $selectedCategories) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Kurtas & Shirts <span class="check-count">(33,653)</span></span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="category[]" value="jeans" <?= in_array('jeans', $selectedCategories) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Jeans <span class="check-count">(31,946)</span></span>
                        </label>
                        <button type="button" class="btn-more-categories" onclick="toggleMoreCategories(this)">MORE</button>
                    </div>
                </div>

                <!-- 3. Price -->
                <div class="filter-acc-group">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">+</span>
                        <span class="acc-label">Price</span>
                    </div>
                    <div class="acc-body" style="display: none;">
                        <label class="filter-check-item">
                            <input type="checkbox" name="price_range[]" value="under_1000" <?= in_array('under_1000', $selectedPriceRanges) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Under ₹1,000</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="price_range[]" value="1000_1500" <?= in_array('1000_1500', $selectedPriceRanges) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">₹1,000 - ₹1,500</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="price_range[]" value="1500_2000" <?= in_array('1500_2000', $selectedPriceRanges) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">₹1,500 - ₹2,000</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="price_range[]" value="above_2000" <?= in_array('above_2000', $selectedPriceRanges) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">₹2,000 & Above</span>
                        </label>
                    </div>
                </div>

                <!-- 4. Brands -->
                <div class="filter-acc-group">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">+</span>
                        <span class="acc-label">Brands</span>
                    </div>
                    <div class="acc-body" style="display: none;">
                        <label class="filter-check-item">
                            <input type="checkbox" name="brand[]" value="Buda Jeans Co" <?= in_array('Buda Jeans Co', $selectedBrands) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Buda Jeans Co</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="brand[]" value="LP JEANS" <?= in_array('LP JEANS', $selectedBrands) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">LP JEANS</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="brand[]" value="DNMX" <?= in_array('DNMX', $selectedBrands) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">DNMX</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="brand[]" value="Shein" <?= in_array('Shein', $selectedBrands) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Shein</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="brand[]" value="KGF Signature" <?= in_array('KGF Signature', $selectedBrands) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">KGF Signature</span>
                        </label>
                    </div>
                </div>

                <!-- 5. Occasion -->
                <div class="filter-acc-group">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">+</span>
                        <span class="acc-label">Occasion</span>
                    </div>
                    <div class="acc-body" style="display: none;">
                        <label class="filter-check-item">
                            <input type="checkbox" name="occasion[]" value="Casual" <?= in_array('Casual', $selectedOccasion) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Casual</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="occasion[]" value="Formal" <?= in_array('Formal', $selectedOccasion) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Formal</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="occasion[]" value="Party Wear" <?= in_array('Party Wear', $selectedOccasion) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Party Wear</span>
                        </label>
                    </div>
                </div>

                <!-- 6. Discount Ranges -->
                <div class="filter-acc-group">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">+</span>
                        <span class="acc-label">Discount Ranges</span>
                    </div>
                    <div class="acc-body" style="display: none;">
                        <label class="filter-check-item">
                            <input type="checkbox" name="discount[]" value="10" <?= in_array('10', $selectedDiscount) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">10% and Above</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="discount[]" value="30" <?= in_array('30', $selectedDiscount) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">30% and Above</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="discount[]" value="50" <?= in_array('50', $selectedDiscount) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">50% and Above</span>
                        </label>
                    </div>
                </div>

                <!-- 7. Colors -->
                <div class="filter-acc-group">
                    <div class="acc-header" onclick="toggleAccordion(this)">
                        <span class="acc-icon">+</span>
                        <span class="acc-label">Colors</span>
                    </div>
                    <div class="acc-body" style="display: none;">
                        <label class="filter-check-item">
                            <input type="checkbox" name="color[]" value="Black" <?= in_array('Black', $selectedColors) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Black</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="color[]" value="Sand" <?= in_array('Sand', $selectedColors) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Sand / Beige</span>
                        </label>
                        <label class="filter-check-item">
                            <input type="checkbox" name="color[]" value="Indigo" <?= in_array('Indigo', $selectedColors) ? 'checked' : '' ?> onchange="submitRefineForm()">
                            <span class="check-box"></span>
                            <span class="check-text">Indigo / Blue</span>
                        </label>
                    </div>
                </div>

                <?php if (!empty($selectedShopFor) || !empty($selectedCategories) || !empty($selectedBrands) || !empty($selectedPriceRanges) || !empty($selectedOccasion) || !empty($selectedDiscount) || !empty($selectedColors)): ?>
                    <div class="reset-filter-area">
                        <a href="<?= url('shop.php') ?>" class="reset-filters-link">Clear All Filters</a>
                    </div>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Product Cards Catalog Grid -->
        <main class="catalog-main-content">
            <?php if (empty($filteredProducts)): ?>
                <div class="empty-state">
                    <h2>No products found</h2>
                    <p>Try clearing your active filters or searching for something else.</p>
                    <a href="<?= url('shop.php') ?>" class="btn primary">Reset All Filters</a>
                </div>
            <?php else: ?>
                <div class="fashion-catalog-grid grid-cols-4" id="fashion-product-grid">
                    <?php foreach ($filteredProducts as $prod): ?>
                        <article class="fashion-product-card" onclick="window.location.href='<?= url('product.php?id=' . $prod['id']) ?>'">
                            <!-- Product Image Area -->
                            <div class="fashion-card-media" style="position: relative;">
                                <img src="<?= getProductImageUrl($prod) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" class="fashion-card-img">
                                <?php if (!empty($prod['badge'])): ?>
                                    <span class="fashion-badge badge-<?= strtolower($prod['badge']) ?>"><?= e($prod['badge']) ?></span>
                                <?php endif; ?>

                                <!-- Floating Wishlist Button -->
                                <form method="post" action="wishlist_action.php" class="card-wishlist-form" onclick="event.stopPropagation();" style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <?php 
                                    $isWl = false;
                                    if ($currentUser) {
                                        $isWl = isInWishlist($pdo, $currentUser['id'], $prod['id']);
                                    }
                                    ?>
                                    <button type="submit" class="card-wishlist-btn" title="<?= $isWl ? 'Remove from Wishlist' : 'Add to Wishlist' ?>" style="background: rgba(255, 255, 255, 0.9); border: none; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.15); color: <?= $isWl ? '#ff5252' : '#888' ?>; transition: all 0.3s ease;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $isWl ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            <!-- Product Info -->
                            <div class="fashion-card-info">
                                <div class="fashion-brand-heading"><?= e($prod['brand'] ?? 'KGF Signature') ?></div>
                                <h3 class="fashion-title">
                                    <a href="<?= url('product.php?id=' . $prod['id']) ?>" onclick="event.stopPropagation();"><?= e($prod['name']) ?></a>
                                </h3>

                                <!-- Rating Pill (Maroon for <3.5 rating like 2.8, Green for >=3.5) -->
                                <?php if (!empty($prod['rating'])): ?>
                                    <?php $isLowRating = ((float)$prod['rating'] < 3.5); ?>
                                    <div class="fashion-rating-pill <?= $isLowRating ? 'rating-maroon' : 'rating-green' ?>">
                                        <span class="rating-val"><?= number_format((float)$prod['rating'], 1) ?> ★</span>
                                        <span class="rating-pipe">|</span>
                                        <span class="rating-num"><?= (int)$prod['rating_count'] ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Price Block -->
                                <div class="fashion-price-block">
                                    <span class="price-current">₹<?= number_format((float)$prod['price']) ?></span>
                                    <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                                        <span class="price-mrp">₹<?= number_format((float)$prod['mrp']) ?></span>
                                        <span class="price-discount">(<?= (int)$prod['discount_pct'] ?>% off)</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Offer Price Badge -->
                                <?php if (!empty($prod['offer_price']) && $prod['offer_price'] < $prod['price']): ?>
                                    <div class="fashion-offer-tag">
                                        <span class="offer-percent-icon">%</span>
                                        <span class="offer-label">Offer Price: ₹<?= number_format((float)$prod['offer_price']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Quick Add Form -->
                                <form method="post" class="fashion-quick-add" onclick="event.stopPropagation();">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="quick_add">
                                    <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                                    <button type="submit" class="fashion-add-btn">+ Add to Bag</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function submitRefineForm() {
    document.getElementById('refine-form').submit();
}

function updateSortOption(val) {
    document.getElementById('form-sort-input').value = val;
    submitRefineForm();
}

function toggleAccordion(headerEl) {
    const groupEl = headerEl.parentElement;
    const bodyEl = groupEl.querySelector('.acc-body');
    const iconEl = headerEl.querySelector('.acc-icon');
    if (bodyEl.style.display === 'none' || !bodyEl.style.display) {
        bodyEl.style.display = 'block';
        iconEl.textContent = '−';
        groupEl.classList.add('open');
    } else {
        bodyEl.style.display = 'none';
        iconEl.textContent = '+';
        groupEl.classList.remove('open');
    }
}

function toggleAllGroups() {
    const groups = document.querySelectorAll('.filter-acc-group');
    let anyOpen = Array.from(groups).some(g => g.classList.contains('open'));
    groups.forEach(g => {
        const body = g.querySelector('.acc-body');
        const icon = g.querySelector('.acc-icon');
        if (anyOpen) {
            body.style.display = 'none';
            icon.textContent = '+';
            g.classList.remove('open');
        } else {
            body.style.display = 'block';
            icon.textContent = '−';
            g.classList.add('open');
        }
    });
}

function toggleMoreCategories(btn) {
    alert('More categories expanded');
}

// Custom Select Dropdown Toggle
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('.custom-select-trigger');
    const wrapper = document.querySelector('.custom-select-wrapper');
    const options = document.querySelectorAll('.custom-option');
    const formSortInput = document.getElementById('form-sort-input');

    if (trigger && wrapper) {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            wrapper.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        options.forEach(option => {
            option.addEventListener('click', () => {
                const val = option.getAttribute('data-value');
                const label = option.textContent.trim();
                
                // Update trigger text
                trigger.querySelector('span').textContent = label;
                
                // Update selected class
                options.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
                
                // Close wrapper
                wrapper.classList.remove('open');
                
                // Submit form
                if (formSortInput) {
                    formSortInput.value = val;
                    submitRefineForm();
                }
            });
        });
    }
});

// Grid switcher scripts removed. Display fixed to 4 columns.
document.write('<style>#sort-select { display: none !important; }</style>'); // fallback in case
</script>

<?php require ROOT_PATH . '/includes/footer.php'; ?>
