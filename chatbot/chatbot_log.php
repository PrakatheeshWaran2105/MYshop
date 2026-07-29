<?php
declare(strict_types=1);

/**
 * Chatbot Analytics & Logs Management Dashboard
 */

$bootstrapPath = dirname(__DIR__) . '/config/bootstrap.php';
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

require_once __DIR__ . '/chatbot_functions.php';

// Ensure table exists
ensureChatbotTablesExist($pdo);

// Page Title
$pageTitle = 'Chatbot Activity Logs';

// Fetch Summary Metrics
$totalLogs = 0;
$intentCounts = [];
$recentLogs = [];

try {
    // Total count
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM chatbot_logs");
    $totalLogs = (int)$stmtCount->fetchColumn();

    // Intent counts
    $stmtIntents = $pdo->query("SELECT intent, COUNT(*) as cnt FROM chatbot_logs GROUP BY intent ORDER BY cnt DESC");
    $intentCounts = $stmtIntents->fetchAll(PDO::FETCH_ASSOC);

    // Latest 50 logs
    $stmtLogs = $pdo->query("SELECT l.*, u.name as user_name, u.email as user_email FROM chatbot_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 50");
    $recentLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (\Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - KGF Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --accent-gold: #d97706;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-back {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: var(--accent-gold);
            color: #ffffff;
        }
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
        }
        .metric-card h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent-gold);
        }
        /* Table */
        .card-table {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }
        th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 600;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .intent-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(217, 119, 6, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .msg-text {
            color: #ffffff;
            font-weight: 500;
        }
        .resp-text {
            color: #cbd5e1;
            font-size: 12.5px;
            max-height: 80px;
            overflow-y: auto;
        }
        .user-tag {
            color: #38bdf8;
            font-weight: 500;
        }
        .guest-tag {
            color: #94a3b8;
            font-style: italic;
        }
        .time-tag {
            color: var(--text-muted);
            font-size: 11px;
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💬 Chatbot Activity & Query Logs</h1>
        <a href="../admin/dashboard.php" class="btn-back">← Admin Dashboard</a>
    </div>

    <!-- Top Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Total Conversations Logged</h3>
            <div class="number"><?= number_format($totalLogs) ?></div>
        </div>
        <?php foreach (array_slice($intentCounts, 0, 3) as $intentItem): ?>
            <div class="metric-card">
                <h3>Intent: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $intentItem['intent']))) ?></h3>
                <div class="number"><?= number_format((int)$intentItem['cnt']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Logs Table -->
    <div class="card-table">
        <div class="card-header">Latest 50 User Interactions</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 140px;">User</th>
                    <th style="width: 120px;">Intent</th>
                    <th>User Message</th>
                    <th>Bot Response</th>
                    <th style="width: 130px;">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">
                            No chatbot activity logged yet. Open your website to start chatting!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td>#<?= (int)$log['id'] ?></td>
                            <td>
                                <?php if (!empty($log['user_name'])): ?>
                                    <span class="user-tag"><?= htmlspecialchars($log['user_name']) ?></span>
                                <?php else: ?>
                                    <span class="guest-tag">Guest</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="intent-badge"><?= htmlspecialchars($log['intent']) ?></span>
                            </td>
                            <td class="msg-text"><?= htmlspecialchars($log['user_message']) ?></td>
                            <td><div class="resp-text"><?= $log['bot_response'] ?></div></td>
                            <td class="time-tag"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
