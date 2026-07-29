<?php
declare(strict_types=1);

/**
 * Chatbot Session History API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

$bootstrapPath = dirname(__DIR__) . '/config/bootstrap.php';
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

require_once __DIR__ . '/chatbot_functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

if ($action === 'clear') {
    clearChatSessionHistory();
    echo json_encode([
        'status' => 'success',
        'message' => 'Chat history cleared.',
        'history' => initChatHistory()
    ]);
    exit;
}

// Return current conversation history
$history = initChatHistory();

echo json_encode([
    'status' => 'success',
    'history' => $history
]);
exit;
