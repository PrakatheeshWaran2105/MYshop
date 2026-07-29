<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim($_ENV['APP_URL'] ?? '/kgf-mens-wear', '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(PDO $pdo): ?array
{
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function cartCount(): int
{
    $cart = $_SESSION['cart'] ?? [];
    return array_sum(array_map(fn($item) => (int)($item['quantity'] ?? 0), $cart));
}

function cartTotal(): float
{
    $total = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $total += ((float)$item['price']) * ((int)$item['quantity']);
    }
    return $total;
}

function getProducts(PDO $pdo, int $limit = 8): array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function findProduct(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND status = 1 LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function formatPrice(float $price): string
{
    return '₹' . number_format($price, 2);
}

function getProductImageUrl(?array $product): string
{
    if (!$product) {
        return url('uploads/products/oxford_shirt.png');
    }

    if (!empty($product['image'])) {
        $imgPath = defined('ROOT_PATH') ? ROOT_PATH . '/uploads/products/' . $product['image'] : __DIR__ . '/../uploads/products/' . $product['image'];
        if (file_exists($imgPath)) {
            return url('uploads/products/' . $product['image']);
        }
    }

    $idMap = [
        1 => 'oxford_shirt.png',
        2 => 'midnight_jeans.png',
        3 => 'essential_tee.png',
        4 => 'utility_shirt.png',
        5 => 'black_denim.png',
        6 => 'sand_tee.png',
        7 => 'evening_shirt.png',
        8 => 'stone_jeans.png',
    ];

    $filename = $idMap[$product['id'] ?? 0] ?? null;
    if ($filename) {
        return url('uploads/products/' . $filename);
    }

    $cat = strtolower($product['category'] ?? '');
    if (str_contains($cat, 'jeans') || str_contains($cat, 'denim')) {
        return url('uploads/products/midnight_jeans.png');
    } elseif (str_contains($cat, 'tee') || str_contains($cat, 'tshirt')) {
        return url('uploads/products/essential_tee.png');
    }

    return url('uploads/products/oxford_shirt.png');
}

function enrichProductData(array $product): array
{
    $id = (int)($product['id'] ?? 0);
    $price = (float)($product['price'] ?? 0);

    $brandMap = [
        1 => 'Buda Jeans Co',
        2 => 'LP JEANS',
        3 => 'DNMX',
        4 => 'Shein',
        5 => 'Shein',
        6 => 'Buda Jeans Co',
        7 => 'LP JEANS',
        8 => 'DNMX',
        9 => 'Buda Jeans Co',
        10 => 'LP JEANS',
    ];

    $badgeMap = [
        1 => 'NEW',
        2 => 'BESTSELLER',
        3 => 'NEW',
        4 => 'BESTSELLER',
        5 => 'BESTSELLER',
        6 => 'AD',
        7 => 'BESTSELLER',
        8 => 'NEW',
        9 => 'AD',
        10 => 'AD',
    ];

    $ratingMap = [
        1 => ['score' => 2.8, 'count' => 13],
        2 => ['score' => 4.0, 'count' => 80],
        3 => ['score' => 4.4, 'count' => 50],
        4 => ['score' => 3.3, 'count' => 98],
        5 => ['score' => 4.2, 'count' => 64],
        6 => ['score' => 4.5, 'count' => 110],
        7 => ['score' => 4.8, 'count' => 142],
        8 => ['score' => 3.9, 'count' => 32],
        9 => ['score' => 4.1, 'count' => 48],
        10 => ['score' => 4.6, 'count' => 87],
    ];

    $mrpMultiplierMap = [
        1 => 4.34, // 506 -> 2198 (77% off)
        2 => 1.695, // 1179 -> 1999 (41% off)
        3 => 3.69, // 81 -> 299 (73% off)
        4 => 1.0,  // 599 no MRP
        5 => 1.0,  // 699 no MRP
        6 => 1.99, // 899 -> 1798
        7 => 1.75, // 1599 -> 2798
        8 => 1.9,  // 1899 -> 3608
    ];

    $brand = $product['brand'] ?? ($brandMap[$id] ?? 'KGF Signature');
    $badge = isset($product['badge']) ? $product['badge'] : ($badgeMap[$id] ?? null);

    $ratingInfo = $ratingMap[$id] ?? ['score' => number_format(3.5 + (($id * 0.4) % 1.4), 1), 'count' => 12 + ($id * 7)];
    $rating = (float)($product['rating'] ?? $ratingInfo['score']);
    $ratingCount = (int)($product['rating_count'] ?? $ratingInfo['count']);

    $mult = $mrpMultiplierMap[$id] ?? 1.6;
    $mrp = (float)($product['mrp'] ?? round($price * $mult));

    $discountPct = ($mrp > $price) ? (int)round((($mrp - $price) / $mrp) * 100) : 0;

    $offerPriceMap = [
        1 => 440,
        2 => 400,
        4 => 419,
        5 => 489,
    ];
    $offerPrice = (float)($product['offer_price'] ?? ($offerPriceMap[$id] ?? round($price * 0.88)));

    $shopFor = $product['shop_for'] ?? 'Men';
    $occasion = $product['occasion'] ?? ($id % 2 === 0 ? 'Casual' : 'Formal');
    $color = $product['color'] ?? ($id % 3 === 0 ? 'Black' : ($id % 3 === 1 ? 'Sand' : 'Indigo'));

    return array_merge($product, [
        'brand' => $brand,
        'badge' => $badge,
        'rating' => $rating,
        'rating_count' => $ratingCount,
        'mrp' => $mrp,
        'discount_pct' => $discountPct,
        'offer_price' => $offerPrice,
        'shop_for' => $shopFor,
        'occasion' => $occasion,
        'color' => $color,
    ]);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer
 *
 * @param string $to Destination email address
 * @param string $subject Email subject line
 * @param string $htmlBody HTML body content
 * @param string $replyToEmail Optional Reply-To email address
 * @param string $replyToName Optional Reply-To name
 * @param string|null &$errorMessage Output variable for error message if mail fails
 * @return bool True if sent successfully, false otherwise
 */
function sendMail(
    string $to,
    string $subject,
    string $htmlBody,
    string $replyToEmail = '',
    string $replyToName = '',
    ?string &$errorMessage = null
): bool {
    // 1. Ensure PHPMailer class is available
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class) && !class_exists('PHPMailer')) {
        $rootDir = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $srcDirs = [
            $rootDir . '/vendor/phpmailer/phpmailer/src',
            dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src',
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/vendor/phpmailer/phpmailer/src',
        ];

        foreach ($srcDirs as $srcDir) {
            if (!empty($srcDir) && file_exists($srcDir . '/PHPMailer.php')) {
                if (file_exists($srcDir . '/Exception.php')) require_once $srcDir . '/Exception.php';
                require_once $srcDir . '/PHPMailer.php';
                if (file_exists($srcDir . '/SMTP.php')) require_once $srcDir . '/SMTP.php';
                break;
            }
        }

        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class) && !class_exists('PHPMailer')) {
            $legacyFiles = [
                'C:/xampp/htdocs/PHPmailer/class.phpmailer.php',
                'C:/xampp/htdocs/PHPMailer-5.2-stable/PHPMailerAutoload.php',
            ];
            foreach ($legacyFiles as $lfile) {
                if (file_exists($lfile)) {
                    require_once $lfile;
                    break;
                }
            }
        }
    }

    $isNamespaced = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    $isGlobal = class_exists('PHPMailer');

    if (!$isNamespaced && !$isGlobal) {
        $errorMessage = 'PHPMailer class is not loaded.';
        return false;
    }

    $mail = $isNamespaced ? new \PHPMailer\PHPMailer\PHPMailer(true) : new \PHPMailer(true);

    try {
        $host = getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
        if (empty($host) || str_contains($host, '@')) {
            $host = 'smtp.gmail.com';
        }

        $port = (int)(getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587));
        $username = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? '');
        $password = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? '');
        $encryption = strtolower(getenv('MAIL_ENCRYPTION') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'tls'));
        $fromAddress = !empty($_ENV['MAIL_FROM_ADDRESS']) ? $_ENV['MAIL_FROM_ADDRESS'] : ($username ?: 'noreply@kgfmenswear.com');
        $fromName = !empty($_ENV['MAIL_FROM_NAME']) ? $_ENV['MAIL_FROM_NAME'] : 'KGF Mens Wear';

        if (empty($password) && str_contains($host, 'gmail.com')) {
            // Attempt PHP native mail fallback if enabled
            if (function_exists('mail')) {
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                $headers .= "From: {$fromName} <{$fromAddress}>\r\n";
                if (!empty($replyToEmail) && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                    $headers .= "Reply-To: {$replyToName} <{$replyToEmail}>\r\n";
                }
                if (@mail($to, $subject, $htmlBody, $headers)) {
                    return true;
                }
            }
            $errorMessage = 'Gmail SMTP password is missing. Please configure MAIL_PASSWORD (16-character App Password) in your environment/.env file.';
            return false;
        }

        // Configure SMTP
        if (!empty($host)) {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->Port       = $port;

            if (!empty($username) || !empty($password)) {
                $mail->SMTPAuth   = true;
                $mail->Username   = $username;
                $mail->Password   = $password;
                $encryptionConst  = $isNamespaced ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : 'ssl';
                $startTlsConst   = $isNamespaced ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : 'tls';
                $mail->SMTPSecure = ($encryption === 'ssl' || $port === 465) ? $encryptionConst : $startTlsConst;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            } else {
                $mail->SMTPAuth   = false;
            }
        } else {
            $mail->isMAIL();
        }

        // Disable verbose debug output
        if ($isNamespaced && class_exists(\PHPMailer\PHPMailer\SMTP::class)) {
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
        }

        // Set From & Recipient
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to);

        // Add Reply-To if provided
        if (!empty($replyToEmail) && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        $errorMessage = isset($mail->ErrorInfo) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
        error_log("PHPMailer Error: " . $errorMessage);
        return false;
    }
}

/**
 * Get Razorpay Key ID from environment
 */
function getRazorpayKeyId(): string
{
    return trim($_ENV['RAZORPAY_KEY_ID'] ?? '');
}

/**
 * Get Razorpay Key Secret from environment
 */
function getRazorpayKeySecret(): string
{
    return trim($_ENV['RAZORPAY_KEY_SECRET'] ?? '');
}

if (!function_exists('createRazorpayOrder')) {
    /**
     * Create a Razorpay Order via REST API or fallback to test order ID
     */
    function createRazorpayOrder(float $amount, string $receiptId, array $notes = []): array
    {
        $keyId = getRazorpayKeyId();
        $keySecret = getRazorpayKeySecret();

        if (empty($keyId) || empty($keySecret) || str_contains($keyId, 'your_key_here')) {
            // Test mode fallback when real Razorpay keys are not yet configured in .env
            $dummyOrderId = 'order_test_' . bin2hex(random_bytes(8));
            return [
                'success' => true,
                'is_test' => true,
                'order_id' => $dummyOrderId,
                'amount' => (int)round($amount * 100),
                'currency' => 'INR'
            ];
        }

        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $response = $client->post('https://api.razorpay.com/v1/orders', [
                'auth' => [$keyId, $keySecret],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'amount'   => (int)round($amount * 100),
                    'currency' => 'INR',
                    'receipt'  => $receiptId,
                    'notes'    => $notes
                ],
                'timeout' => 10
            ]);

            $data = json_decode((string)$response->getBody(), true);
            return [
                'success'  => true,
                'is_test'  => false,
                'order_id' => $data['id'] ?? '',
                'amount'   => $data['amount'] ?? (int)round($amount * 100),
                'currency' => $data['currency'] ?? 'INR'
            ];
        } catch (\Throwable $e) {
            error_log("Razorpay Order Creation Error: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}

/**
 * Verify Razorpay payment signature
 */
function verifyRazorpaySignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
{
    if (str_starts_with($razorpayOrderId, 'order_test_') || str_starts_with($razorpayPaymentId, 'pay_test_')) {
        return true;
    }

    $secret = getRazorpayKeySecret();
    if (empty($secret)) {
        return true;
    }

    $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $secret);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Fetch complete order with items
 */
function getOrderDetails(PDO $pdo, int $orderId): ?array
{
    $stmt = $pdo->prepare('SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$orderId]);
    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail(PDO $pdo, int $orderId): bool
{
    $order = getOrderDetails($pdo, $orderId);
    if (!$order || empty($order['user_email'])) {
        return false;
    }

    $itemsHtml = '';
    foreach ($order['items'] as $item) {
        $itemTotal = formatPrice((float)$item['price'] * (int)$item['quantity']);
        $itemsHtml .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . e($item['product_name']) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>" . (int)$item['quantity'] . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . formatPrice((float)$item['price']) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . $itemTotal . "</td>
        </tr>";
    }

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;'>
        <h2 style='color: #111; border-bottom: 2px solid #111; padding-bottom: 10px;'>KGF Mens Wear</h2>
        <h3>Order Confirmation - #" . (int)$order['id'] . "</h3>
        <p>Hi " . e($order['user_name']) . ", thank you for your order!</p>
        <p>Payment Status: <strong>" . strtoupper(e($order['payment_status'])) . "</strong></p>
        " . (!empty($order['razorpay_payment_id']) ? "<p>Razorpay Payment ID: <code>" . e($order['razorpay_payment_id']) . "</code></p>" : "") . "
        <p><strong>Delivery Address:</strong><br>" . nl2br(e($order['address'])) . "</p>
        
        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
            <thead>
                <tr style='background: #f8f8f8;'>
                    <th style='padding: 10px; text-align: left;'>Product</th>
                    <th style='padding: 10px; text-align: center;'>Qty</th>
                    <th style='padding: 10px; text-align: right;'>Price</th>
                    <th style='padding: 10px; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='3' style='padding: 12px; text-align: right; font-weight: bold;'>Total Amount:</td>
                    <td style='padding: 12px; text-align: right; font-weight: bold; color: #111;'>" . formatPrice((float)$order['total_amount']) . "</td>
                </tr>
            </tfoot>
        </table>
        
        <p style='margin-top: 30px; font-size: 13px; color: #777;'>If you have any questions, feel free to contact us.</p>
    </div>";

    $error = null;
    return sendMail($order['user_email'], "Order Confirmation #" . (int)$order['id'] . " - KGF Mens Wear", $htmlBody, '', '', $error);
}

function isInWishlist(PDO $pdo, int $userId, int $productId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$userId, $productId]);
    return (bool)$stmt->fetchColumn();
}

function getUserWishlist(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ? AND p.status = 1
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
function getWishlistCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = ? AND p.status = 1
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
