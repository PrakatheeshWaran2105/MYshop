<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!isLoggedIn()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'redirect' => url('login.php')
        ]);
        exit;
    }
    flash('error', 'Please log in to manage your wishlist.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token.'
            ]);
            exit;
        }
        flash('error', 'Invalid request.');
        redirect('index.php');
    }

    $productId = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? 'toggle';
    $userId = $_SESSION['user_id'];

    if ($productId <= 0) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid product.'
            ]);
            exit;
        }
        flash('error', 'Invalid product.');
        redirect('index.php');
    }

    $actionTaken = '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        $actionTaken = 'added';
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $actionTaken = 'removed';
    } elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $actionTaken = 'removed';
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            $actionTaken = 'added';
        }
    }

    // Get the updated wishlist count
    $wishlistCount = getWishlistCount($pdo, (int)$userId);

    if ($actionTaken === 'added') {
        $message = 'Product added to wishlist! (Total: ' . $wishlistCount . ')';
    } else {
        $message = 'Product removed from wishlist! (Total: ' . $wishlistCount . ')';
    }
    flash('success', $message);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'wishlist_count' => $wishlistCount,
            'action' => $actionTaken
        ]);
        exit;
    }

    // Redirect back to the referrer or a default page
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'shop.php';
    header("Location: $referrer");
    exit;
} else {
    redirect('shop.php');
}
