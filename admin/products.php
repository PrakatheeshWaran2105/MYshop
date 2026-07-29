<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch logged in admin user info
$adminStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$adminStmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $adminStmt->fetch();

if (!$currentAdmin) {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    header('Location: login.php');
    exit;
}

$error = '';
$success = flash('success');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_stock') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $newStock = max(0, (int)($_POST['stock'] ?? 0));

            if ($productId > 0) {
                $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $stmt->execute([$newStock, $productId]);
                flash('success', 'Stock quantity updated successfully!');
                header('Location: products.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId > 0) {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                flash('success', 'Product deleted successfully!');
                header('Location: products.php');
                exit;
            }
        } elseif ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if (empty($name) || empty($category) || $price <= 0) {
                $error = 'Please fill in product name, category, and valid price.';
            } else {
                // Generate slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                $slug = $slug ?: 'product-' . time();

                // Check slug uniqueness
                $checkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $checkSlug->execute([$slug]);
                if ($checkSlug->fetch()) {
                    $slug .= '-' . time();
                }

                // Handle file upload
                $imageFilename = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['image'];
                    $fileName = $file['name'];
                    $fileTmpName = $file['tmp_name'];
                    $fileSize = $file['size'];
                    $fileError = $file['error'];

                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($fileExt, $allowed)) {
                        if ($fileError === 0) {
                            if ($fileSize <= 5 * 1024 * 1024) { // 5MB limit
                                $imageFilename = $slug . '-' . time() . '.' . $fileExt;
                                $uploadDir = dirname(__DIR__) . '/uploads/products/';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0777, true);
                                }
                                $fileDestination = $uploadDir . $imageFilename;
                                if (!move_uploaded_file($fileTmpName, $fileDestination)) {
                                    $error = 'Failed to save the uploaded image.';
                                }
                            } else {
                                $error = 'The image file size exceeds the 5MB limit.';
                            }
                        } else {
                            $error = 'There was an error uploading the image.';
                        }
                    } else {
                        $error = 'Invalid image format. Only JPG, JPEG, PNG, and WEBP are allowed.';
                    }
                }

                if (empty($error)) {
                    $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, category, price, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$name, $slug, $description, $category, $price, $stock, $imageFilename]);

                    flash('success', 'New product "' . e($name) . '" added successfully!');
                    header('Location: products.php');
                    exit;
                }
            }
        }
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products | KGF Control Room</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0.3">
</head>
<body class="admin-page">
<script>
    (function() {
        var theme = localStorage.getItem('admin-theme') || 'dark';
        document.body.setAttribute('data-theme', theme);
    })();
</script>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <h2>KGF Admin</h2>
        </div>
        <div class="admin-sidebar-user"><?= e($currentAdmin['email']) ?></div>
        
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="products.php" class="active">📦 Products & Stock</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="../index.php" target="_blank">🛍️ View store ↗</a>
        
        <div class="admin-sidebar-bottom">
            <a href="logout.php" class="logout-link" title="Logout">
                <img src="../assets/images/logout.png" alt="Logout" class="logout-icon">
            </a>
            <button class="icon-btn theme-toggle-btn" id="adminThemeToggle" aria-label="Toggle Theme" title="Toggle Theme">
                <svg class="sun-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <svg class="moon-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-header-actions">
            <div>
                <span class="eyebrow">Inventory Management</span>
                <h1>Products & Stock</h1>
            </div>
            <button class="btn primary" id="openAddProductModal">+ Add New Product</button>
        </div>

        <?php if ($success): ?>
            <div class="success-message" style="margin-bottom: 25px;"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="form-error" style="margin-bottom: 25px;"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #888; padding: 30px;">No products found in inventory.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>#<?= (int)$p['id'] ?></td>
                                <td>
                                    <strong><?= e($p['name']) ?></strong>
                                    <div style="font-size: 0.78rem; color: #888; margin-top: 2px;"><?= e($p['slug']) ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e(ucfirst($p['category'])) ?></span>
                                </td>
                                <td><strong>₹<?= number_format((float)$p['price'], 2) ?></strong></td>
                                <td>
                                    <form method="post" class="stock-update-form">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        
                                        <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" required>
                                        <button type="submit" class="btn-action btn-action-secondary" title="Save Stock Quantity">Save</button>

                                        <?php if ((int)$p['stock'] <= 0): ?>
                                            <span class="badge badge-danger">Out of Stock</span>
                                        <?php elseif ((int)$p['stock'] <= 5): ?>
                                            <span class="badge badge-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($p['status']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn-action btn-action-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add New Product Modal -->
        <div id="addProductModal" class="modal-overlay" style="display:none;" aria-modal="true" role="dialog">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Add New Product</h3>
                    <button class="modal-close-btn" id="closeAddProductModal" aria-label="Close">&times;</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="form-grid-2">
                        <label>
                            Product Name
                            <input type="text" name="name" placeholder="e.g. Premium Cotton Shirt" required>
                        </label>

                        <label>
                            Category
                            <select name="category" required>
                                <option value="shirts">Shirts</option>
                                <option value="jeans">Jeans</option>
                                <option value="tshirts">T-Shirts</option>
                                <option value="trousers">Trousers</option>
                                <option value="jackets">Jackets</option>
                            </select>
                        </label>
                    </div>

                    <div class="form-grid-2">
                        <label>
                            Price (₹)
                            <input type="number" step="0.01" name="price" placeholder="1299.00" required min="1">
                        </label>

                        <label>
                            Stock Quantity
                            <input type="number" name="stock" placeholder="25" required min="0" value="10">
                        </label>
                    </div>

                    <div class="form-grid-2">
                        <label>
                            Description
                            <textarea name="description" rows="3" placeholder="Enter product details, fit, and fabric info..."></textarea>
                        </label>

                        <label>
                            Product Image
                            <input type="file" name="image" accept="image/*">
                            <span style="display: block; font-size: 0.75rem; color: #888; margin-top: 5px;">Allowed formats: JPG, JPEG, PNG, WEBP (Max 5MB)</span>
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn secondary" id="cancelAddProductModal">Cancel</button>
                        <button type="submit" class="btn primary">Add Product to Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/admin.js"></script>
<script>
(function () {
    var modal = document.getElementById('addProductModal');
    var openBtn = document.getElementById('openAddProductModal');
    var closeBtn = document.getElementById('closeAddProductModal');
    var cancelBtn = document.getElementById('cancelAddProductModal');

    function openModal() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Click outside to close
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // ESC key to close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
    });

    <?php if ($error): ?>
    // Re-open modal if there was a validation error
    openModal();
    <?php endif; ?>
})();
</script>
</body>
</html>
