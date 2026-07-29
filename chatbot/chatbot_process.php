<?php
declare(strict_types=1);

/**
 * Chatbot AJAX Processing Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

// Load bootstrap configuration & DB connection
$bootstrapPath = dirname(__DIR__) . '/config/bootstrap.php';
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

require_once __DIR__ . '/chatbot_functions.php';

// Accept JSON input or standard POST data
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}

$userMessage = trim((string)($inputData['message'] ?? $_POST['message'] ?? ''));

if ($userMessage === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Message cannot be empty.'
    ]);
    exit;
}

// Get logged-in user ID if available
$userId = null;
if (isset($pdo) && function_exists('currentUser')) {
    $user = currentUser($pdo);
    if ($user && isset($user['id'])) {
        $userId = (int)$user['id'];
    }
}

// Store user message in session history
appendChatMessage('user', $userMessage);

// Process query through smart chatbot functions
$result = processUserMessage($pdo, $userMessage, $userId);

// Store bot response in session history
appendChatMessage('bot', $result['reply'], $result['products'], $result['quick_replies']);

// Send JSON response
echo json_encode([
    'status' => 'success',
    'reply' => $result['reply'],
    'intent' => $result['intent'],
    'products' => $result['products'],
    'quick_replies' => $result['quick_replies'],
    'timestamp' => date('h:i A')
]);
exit;
