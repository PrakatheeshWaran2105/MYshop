<?php
declare(strict_types=1);

/**
 * Chatbot Core Helper Functions for KGF Mens Wear
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Ensures the chatbot_logs database table exists.
 */
function ensureChatbotTablesExist(PDO $pdo): void
{
    try {
        $sql = "CREATE TABLE IF NOT EXISTS chatbot_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            user_message TEXT NOT NULL,
            bot_response TEXT NOT NULL,
            intent VARCHAR(80) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (session_id),
            INDEX (intent)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
    } catch (\Throwable $e) {
        error_log("Chatbot Table Init Warning: " . $e->getMessage());
    }
}

/**
 * Gets or creates unique session ID for the chatbot.
 */
function getChatSessionId(): string
{
    if (empty($_SESSION['chatbot_session_id'])) {
        $_SESSION['chatbot_session_id'] = 'chat_' . bin2hex(random_bytes(8));
    }
    return $_SESSION['chatbot_session_id'];
}

/**
 * Initializes chat history in PHP session.
 */
function initChatHistory(): array
{
    if (!isset($_SESSION['chatbot_history']) || !is_array($_SESSION['chatbot_history'])) {
        $_SESSION['chatbot_history'] = [
            [
                'role' => 'bot',
                'text' => "👋 Hi there! Welcome to **KGF Mens Wear**.<br>How can I help you today?",
                'timestamp' => date('h:i A'),
                'quick_replies' => [
                    "👕 Search Shirts & Jeans",
                    "📦 Track My Order",
                    "🚚 Delivery & Returns",
                    "🎁 Discounts & Offers",
                    "📐 Size Guide"
                ]
            ]
        ];
    }
    return $_SESSION['chatbot_history'];
}

/**
 * Appends a message to session chat history.
 */
function appendChatMessage(string $role, string $text, array $products = [], array $quickReplies = []): void
{
    if (!isset($_SESSION['chatbot_history']) || !is_array($_SESSION['chatbot_history'])) {
        initChatHistory();
    }
    
    $_SESSION['chatbot_history'][] = [
        'role' => $role,
        'text' => $text,
        'products' => $products,
        'quick_replies' => $quickReplies,
        'timestamp' => date('h:i A')
    ];
}

/**
 * Clears chat history for current session.
 */
function clearChatSessionHistory(): void
{
    unset($_SESSION['chatbot_history']);
    initChatHistory();
}

/**
 * Main AI / Smart Response Handler
 */
function processUserMessage(PDO $pdo, string $userMessage, ?int $userId = null): array
{
    $msgLower = strtolower(trim($userMessage));
    $intent = 'general';
    $responseText = '';
    $products = [];
    $quickReplies = [
        "👕 Search Shirts",
        "👖 Search Jeans",
        "📦 Track My Order",
        "🚚 Shipping Info",
        "💬 Contact Support"
    ];

    // Ensure logs table exists
    ensureChatbotTablesExist($pdo);

    // 1. GREETING INTENT
    if (preg_match('/\b(hi|hello|hey|hola|greetings|good morning|good afternoon|good evening|wassup|start)\b/i', $msgLower)) {
        $intent = 'greeting';
        $responseText = "Hello! 👋 Great to see you at **KGF Mens Wear**. I can assist you with finding clothing styles, checking stock, tracking your orders, or answering store policies. What are you looking for today?";
    }
    // 2. ORDER TRACKING INTENT
    elseif (preg_match('/(track|order|status|where is my order|shipment|delivery status|order #|\b#?\d+\b)/i', $msgLower)) {
        $intent = 'order_tracking';
        
        // Check if an order ID is present in the text (e.g., #101, order 5)
        if (preg_match('/#?(\d+)/', $msgLower, $matches)) {
            $orderId = (int)$matches[1];
            $order = getOrderDetailsById($pdo, $orderId, $userId);
            
            if ($order) {
                $statusBadge = strtoupper($order['status']);
                $dateFormatted = date('d M Y, h:i A', strtotime($order['created_at']));
                $amountFormatted = number_format((float)$order['total_amount'], 2);
                
                $responseText = "<strong>📦 Order #{$order['id']} Status:</strong> <span class='chat-badge status-{$order['status']}'>{$statusBadge}</span><br>";
                $responseText .= "• <strong>Placed On:</strong> {$dateFormatted}<br>";
                $responseText .= "• <strong>Total Amount:</strong> ₹{$amountFormatted}<br>";
                $responseText .= "• <strong>Payment Status:</strong> " . ucfirst($order['payment_status']) . "<br>";
                $responseText .= "• <strong>Shipping Address:</strong> " . htmlspecialchars($order['address']) . "<br>";
                
                if (!empty($order['items'])) {
                    $responseText .= "<br><strong>Items Included:</strong><ul class='chat-item-list'>";
                    foreach ($order['items'] as $item) {
                        $itemTotal = number_format($item['price'] * $item['quantity'], 2);
                        $responseText .= "<li>" . htmlspecialchars($item['product_name']) . " (x{$item['quantity']}) - ₹{$itemTotal}</li>";
                    }
                    $responseText .= "</ul>";
                }
            } else {
                $responseText = "I couldn't find Order <strong>#{$orderId}</strong> in our system. Please check your order ID or log in to view your recent orders under your <a href='profile.php' target='_blank'>Profile</a>.";
            }
        } elseif ($userId) {
            // Fetch recent orders for logged-in user
            $userOrders = getRecentUserOrders($pdo, $userId, 3);
            if (!empty($userOrders)) {
                $responseText = "Here are your recent orders:<br>";
                foreach ($userOrders as $ord) {
                    $st = strtoupper($ord['status']);
                    $dt = date('d M Y', strtotime($ord['created_at']));
                    $responseText .= "• <strong>Order #{$ord['id']}</strong> (₹{$ord['total_amount']}) - <span class='chat-badge status-{$ord['status']}'>{$st}</span> ({$dt})<br>";
                }
                $responseText .= "<br>Type <em>'Order #[ID]'</em> (e.g. <code>Order #{$userOrders[0]['id']}</code>) to see full item breakdown!";
            } else {
                $responseText = "You don't have any recent orders yet. Ready to upgrade your wardrobe? Check out our <a href='shop.php'>Shop Collection</a>!";
            }
        } else {
            $responseText = "To track an order, please enter your <strong>Order ID</strong> (e.g., <em>'Track Order #101'</em>) or <a href='login.php'>Login to your account</a> to view all your past purchases!";
        }
    }
    // 3. PRODUCT SEARCH INTENT
    elseif (preg_match('/(shirt|jean|denim|tshirt|tee|jacket|oxford|utility|sand|black|evening|wear|clothes|cloth|style|collection|buy|price|cost|product|recommend|show me|looking for)/i', $msgLower)) {
        $intent = 'product_search';
        $matchingProducts = searchDatabaseProducts($pdo, $userMessage);
        
        if (!empty($matchingProducts)) {
            $products = $matchingProducts;
            $count = count($products);
            $responseText = "We found <strong>{$count}</strong> great items matching your style preference:";
        } else {
            $responseText = "I couldn't find exact matches for that term. However, here are some of our best-selling essentials from our collection:";
            $products = getFeaturedProducts($pdo, 3);
        }
    }
    // 4. SHIPPING & DELIVERY INTENT
    elseif (preg_match('/(ship|delivery|shipping|courier|dispatch|how long|delivery time|charge)/i', $msgLower)) {
        $intent = 'shipping_info';
        $responseText = "🚚 <strong>Shipping & Delivery Policy:</strong><br>";
        $responseText .= "• <strong>Free Express Shipping:</strong> Available on all orders above ₹1,499!<br>";
        $responseText .= "• <strong>Standard Shipping:</strong> ₹99 flat fee on orders below ₹1,499.<br>";
        $responseText .= "• <strong>Estimated Delivery:</strong> 3 to 5 business days across India.<br>";
        $responseText .= "• You will receive real-time tracking links via SMS and email once your package dispatches!";
    }
    // 5. RETURNS & EXCHANGES INTENT
    elseif (preg_match('/(return|exchange|refund|cancel|damaged|policy|replace)/i', $msgLower)) {
        $intent = 'return_policy';
        $responseText = "🔄 <strong>7-Day Easy Return & Exchange Policy:</strong><br>";
        $responseText .= "• We offer a hassle-free 7-day return/exchange window from the date of delivery.<br>";
        $responseText .= "• Items must be unworn, unwashed, and in original packaging with tags intact.<br>";
        $responseText .= "• To initiate a return or exchange, visit your <a href='profile.php'>Profile Page</a> or contact support.";
    }
    // 6. SIZE GUIDE & FITTING INTENT
    elseif (preg_match('/(size|fit|fitting|measurement|small|medium|large|xl|xxl|chart)/i', $msgLower)) {
        $intent = 'size_guide';
        $responseText = "📐 <strong>KGF Mens Wear Size Guide:</strong><br><br>";
        $responseText .= "<strong>Shirts & Tees (Chest Size):</strong><br>";
        $responseText .= "• <strong>S:</strong> 38 inches<br>";
        $responseText .= "• <strong>M:</strong> 40 inches<br>";
        $responseText .= "• <strong>L:</strong> 42 inches<br>";
        $responseText .= "• <strong>XL:</strong> 44 inches<br>";
        $responseText .= "• <strong>XXL:</strong> 46 inches<br><br>";
        $responseText .= "<strong>Jeans & Trousers:</strong> Waist sizes range from 28\" to 36\" with flexible stretch denim.";
    }
    // 7. OFFERS & DISCOUNTS INTENT
    elseif (preg_match('/(offer|discount|coupon|promo|code|deal|sale|cheap|discount code)/i', $msgLower)) {
        $intent = 'discounts';
        $responseText = "🎁 <strong>Exclusive Offers for You:</strong><br>";
        $responseText .= "• Use code <strong style='color:#d97706;'>KGF10</strong> to get <strong>10% OFF</strong> on orders above ₹1,999!<br>";
        $responseText .= "• Auto-free express shipping on orders over ₹1,499.<br>";
        $responseText .= "• Check out our <a href='shop.php'>Shop Page</a> for ongoing seasonal price drops!";
    }
    // 8. PAYMENT METHODS INTENT
    elseif (preg_match('/(payment|pay|razorpay|cod|cash on delivery|upi|card|gpay|phonepe|netbanking)/i', $msgLower)) {
        $intent = 'payment_methods';
        $responseText = "💳 <strong>Accepted Payment Options:</strong><br>";
        $responseText .= "• <strong>Razorpay Secure Checkout:</strong> Credit/Debit Cards (Visa, Mastercard, RuPay)<br>";
        $responseText .= "• <strong>UPI:</strong> Google Pay, PhonePe, Paytm, BHIM<br>";
        $responseText .= "• <strong>Net Banking:</strong> All major Indian banks supported<br>";
        $responseText .= "• <strong>Cash on Delivery (COD):</strong> Available for select pincodes";
    }
    // 9. CONTACT & STORE INTENT
    elseif (preg_match('/(contact|support|phone|call|email|help|address|location|store)/i', $msgLower)) {
        $intent = 'contact_support';
        $responseText = "📞 <strong>Contact KGF Customer Support:</strong><br>";
        $responseText .= "• <strong>Email:</strong> support@kgfmenswear.com<br>";
        $responseText .= "• <strong>Phone / WhatsApp:</strong> +91 6374777777 (Mon - Sat, 9 AM - 8 PM)<br>";
        $responseText .= "• <strong>Location:</strong> KGF Mens Wear HQ, Fashion tricht,Samayapuram ,Tamilnadu, India.<br>";
        $responseText .= "• Or send us a message directly via our <a href='contact.php'>Contact Form</a>.";
    }
    // 10. DEFAULT / FALLBACK INTENT
    else {
        $intent = 'fallback';
        $responseText = "I'm not sure I quite understood that, but I'm here to help! You can try asking about our <strong>shirts & jeans collection</strong>, <strong>tracking an order</strong>, <strong>shipping times</strong>, or <strong>size fitting</strong>.";
    }

    // Save interaction log
    $sessionId = getChatSessionId();
    logChatMessage($pdo, $sessionId, $userId, $userMessage, $responseText, $intent);

    return [
        'reply' => $responseText,
        'intent' => $intent,
        'products' => $products,
        'quick_replies' => $quickReplies
    ];
}

/**
 * Searches database for products based on user query terms.
 */
function searchDatabaseProducts(PDO $pdo, string $query, int $limit = 4): array
{
    try {
        $cleanQuery = trim($query);
        // Extract keywords
        preg_match_all('/\b\w{3,}\b/', strtolower($cleanQuery), $matches);
        $keywords = $matches[0] ?? [];
        
        if (empty($keywords)) {
            $keywords = ['shirt', 'jeans', 'tee'];
        }

        $whereClauses = [];
        $params = [];
        
        foreach ($keywords as $idx => $kw) {
            // Ignore common query stopwords
            if (in_array($kw, ['show', 'find', 'want', 'looking', 'some', 'good', 'best', 'with', 'have', 'need', 'please', 'tell', 'about', 'like'])) {
                continue;
            }
            $paramName = ":kw_{$idx}";
            $whereClauses[] = "(name LIKE {$paramName} OR category LIKE {$paramName} OR description LIKE {$paramName})";
            $params[$paramName] = "%{$kw}%";
        }

        if (empty($whereClauses)) {
            return getFeaturedProducts($pdo, $limit);
        }

        $sql = "SELECT id, name, slug, description, category, price, image FROM products WHERE status = 1 AND (" . implode(' OR ', $whereClauses) . ") ORDER BY id DESC LIMIT {$limit}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return getFeaturedProducts($pdo, $limit);
        }

        return formatProductArray($rows);
    } catch (\Throwable $e) {
        error_log("Chatbot Product Search Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets fallback / featured products from DB.
 */
function getFeaturedProducts(PDO $pdo, int $limit = 3): array
{
    try {
        $stmt = $pdo->prepare("SELECT id, name, slug, description, category, price, image FROM products WHERE status = 1 ORDER BY id ASC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return formatProductArray($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Formats database rows into clean product arrays for response cards.
 */
function formatProductArray(array $rows): array
{
    $formatted = [];
    foreach ($rows as $row) {
        $img = !empty($row['image']) ? $row['image'] : 'oxford_shirt.png';
        // Base URL helper matching project assets structure
        $imgUrl = 'uploads/' . $img;
        if (!file_exists(dirname(__DIR__) . '/' . $imgUrl)) {
            $imgUrl = 'assets/kgf-logo-shield.png';
        }

        $formatted[] = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['name']),
            'slug' => htmlspecialchars($row['slug']),
            'category' => ucfirst($row['category']),
            'price' => number_format((float)$row['price'], 2),
            'image_url' => $imgUrl,
            'url' => 'product.php?slug=' . urlencode($row['slug'])
        ];
    }
    return $formatted;
}

/**
 * Helper to fetch order details by Order ID.
 */
function getOrderDetailsById(PDO $pdo, int $orderId, ?int $userId = null): ?array
{
    try {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid LIMIT 1");
            $stmt->execute([':id' => $orderId, ':uid' => $userId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
        }
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) return null;

        // Fetch items
        $itemStmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = :oid");
        $itemStmt->execute([':oid' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Helper to fetch recent orders for a user.
 */
function getRecentUserOrders(PDO $pdo, int $userId, int $limit = 3): array
{
    try {
        $stmt = $pdo->prepare("SELECT id, total_amount, status, payment_status, created_at FROM orders WHERE user_id = :uid ORDER BY id DESC LIMIT :lim");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Logs chat message into chatbot_logs table.
 */
function logChatMessage(PDO $pdo, string $sessionId, ?int $userId, string $userMessage, string $botResponse, string $intent): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO chatbot_logs (session_id, user_id, user_message, bot_response, intent) VALUES (:sid, :uid, :umsg, :bresp, :intent)");
        $stmt->execute([
            ':sid' => $sessionId,
            ':uid' => $userId,
            ':umsg' => $userMessage,
            ':bresp' => $botResponse,
            ':intent' => $intent
        ]);
    } catch (\Throwable $e) {
        error_log("Chatbot Log Save Error: " . $e->getMessage());
    }
}
