<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    flash('error', 'Invalid order.');
    redirect('profile.php');
}

// Fetch order — must belong to logged-in user
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    redirect('profile.php');
}

// Fetch order items
$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// Fetch tracking logs (if table exists)
$trackingLogs = [];
try {
    $logStmt = $pdo->prepare('SELECT * FROM order_tracking_logs WHERE order_id = ? ORDER BY created_at DESC');
    $logStmt->execute([$orderId]);
    $trackingLogs = $logStmt->fetchAll();
} catch (\Exception $e) {
    // Table may not exist yet — silently ignore
}

// Determine active step (1–5)
$statusMap = [
    'pending'         => 1,
    'processing'      => 2,
    'shipped'         => 3,
    'out_for_delivery'=> 4,
    'delivered'       => 5,
    'cancelled'       => 0,
];
$currentStep = $statusMap[strtolower($order['status'])] ?? 1;
$isCancelled = strtolower($order['status']) === 'cancelled';

$steps = [
    ['icon' => '📋', 'label' => 'Order Placed',      'desc' => 'Payment confirmed & order received'],
    ['icon' => '⚙️',  'label' => 'Processing',         'desc' => 'Your items are being prepared'],
    ['icon' => '🚚', 'label' => 'Shipped',             'desc' => 'Package picked up by courier'],
    ['icon' => '📍', 'label' => 'Out for Delivery',   'desc' => 'On the way to your address'],
    ['icon' => '✅', 'label' => 'Delivered',           'desc' => 'Package delivered successfully'],
];

$pageTitle = 'Track Order #' . $orderId . ' | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>

<style>
/* ── Tracking Page Styles ─────────────────────────────── */
.track-wrapper {
    max-width: 860px;
    margin: 0 auto;
    padding: 0 16px 60px;
}

/* Hero banner */
.track-hero {
    background: linear-gradient(135deg, #1a0a00 0%, #0f1117 60%, #0d1a2e 100%);
    border: 1px solid rgba(255, 106, 42, 0.18);
    border-radius: 28px;
    padding: 36px 40px;
    margin-bottom: 28px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.track-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(255,106,42,0.18) 0%, transparent 70%);
    pointer-events: none;
}
.track-hero-left .eyebrow {
    font-size: 0.74rem;
    letter-spacing: 0.18em;
    color: #ff6a2a;
    font-weight: 700;
    text-transform: uppercase;
    display: block;
    margin-bottom: 8px;
}
.track-hero-left h1 {
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    color: #fff;
    margin: 0 0 6px;
    font-weight: 800;
}
.track-hero-left .track-date {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.5);
}
.track-hero-right {
    text-align: right;
}
.track-status-badge {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.status-delivered   { background: rgba(52,211,153,0.18); color: #34d399; border: 1px solid rgba(52,211,153,0.35); }
.status-shipped     { background: rgba(59,130,246,0.18); color: #60a5fa; border: 1px solid rgba(59,130,246,0.35); }
.status-processing  { background: rgba(251,191,36,0.18); color: #fbbf24; border: 1px solid rgba(251,191,36,0.35); }
.status-pending     { background: rgba(156,163,175,0.18); color: #9ca3af; border: 1px solid rgba(156,163,175,0.35); }
.status-out         { background: rgba(255,106,42,0.18); color: #ff6a2a; border: 1px solid rgba(255,106,42,0.35); }
.status-cancelled   { background: rgba(239,68,68,0.18); color: #f87171; border: 1px solid rgba(239,68,68,0.35); }
.track-amount {
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    margin-top: 10px;
}
.track-amount span { color: #ff6a2a; }

/* Tracking number chip */
.tracking-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,106,42,0.1);
    border: 1px solid rgba(255,106,42,0.25);
    border-radius: 12px;
    padding: 8px 16px;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.8);
    margin-top: 12px;
}
.tracking-chip code {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    color: #ff6a2a;
    font-weight: 700;
    letter-spacing: 0.05em;
}

/* Estimated delivery */
.est-delivery-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    background: rgba(52,211,153,0.1);
    border: 1px solid rgba(52,211,153,0.25);
    border-radius: 12px;
    padding: 8px 16px;
    font-size: 0.85rem;
    color: #34d399;
    font-weight: 600;
}

/* Progress stepper card */
.stepper-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 36px 40px;
    margin-bottom: 24px;
}
.stepper-card h2 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 32px;
}

/* Stepper track */
.stepper-track {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}
.stepper-line-bg {
    position: absolute;
    top: 22px;
    left: 22px;
    right: 22px;
    height: 4px;
    background: var(--line);
    border-radius: 4px;
    z-index: 0;
}
.stepper-line-fill {
    position: absolute;
    top: 22px;
    left: 22px;
    height: 4px;
    border-radius: 4px;
    background: linear-gradient(90deg, #ff6a2a 0%, #ff9a5a 100%);
    transition: width 1s cubic-bezier(.4,0,.2,1);
    z-index: 1;
    box-shadow: 0 0 12px rgba(255,106,42,0.55);
}
.stepper-line-fill.delivered {
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
    box-shadow: 0 0 12px rgba(16,185,129,0.55);
}

/* Each step */
.stepper-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex: 1;
    position: relative;
    z-index: 2;
}
.step-node {
    width: 44px; height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    border: 3px solid var(--line);
    background: var(--bg);
    transition: all 0.35s ease;
    position: relative;
}
.step-node.done {
    background: linear-gradient(135deg, #10b981, #059669);
    border-color: #10b981;
    box-shadow: 0 0 18px rgba(16,185,129,0.45);
}
.step-node.active {
    background: linear-gradient(135deg, #ff6a2a, #ff9a5a);
    border-color: #ff6a2a;
    box-shadow: 0 0 20px rgba(255,106,42,0.6);
    animation: pulse-ring 2s infinite;
}
@keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 0 rgba(255,106,42,0.6); }
    70%  { box-shadow: 0 0 0 12px rgba(255,106,42,0); }
    100% { box-shadow: 0 0 0 0 rgba(255,106,42,0); }
}
.step-node.cancelled {
    background: rgba(239,68,68,0.15);
    border-color: #f87171;
}

.step-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--muted);
    max-width: 70px;
    line-height: 1.3;
}
.step-label.active { color: #ff6a2a; }
.step-label.done   { color: #34d399; }

.step-desc {
    font-size: 0.67rem;
    color: var(--muted);
    text-align: center;
    max-width: 80px;
    line-height: 1.3;
    opacity: 0.75;
}

/* Cancelled banner */
.cancelled-banner {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 16px;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #f87171;
    font-weight: 600;
    margin-bottom: 24px;
}

/* Timeline card */
.timeline-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 24px;
    overflow: hidden;
    margin-bottom: 24px;
}
.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 28px;
    background: rgba(255,106,42,0.05);
    border-bottom: 1px solid var(--line);
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}
.timeline-header:hover { background: rgba(255,106,42,0.1); }
.timeline-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 10px;
}
.timeline-chevron {
    transition: transform 0.3s ease;
    color: var(--muted);
}
.timeline-chevron.open { transform: rotate(180deg); }
.timeline-body {
    padding: 24px 28px;
    display: none;
}
.timeline-body.open { display: block; }

/* Individual log item */
.log-item {
    display: flex;
    gap: 18px;
    padding-bottom: 24px;
    position: relative;
}
.log-item:last-child { padding-bottom: 0; }
.log-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 17px; top: 36px;
    width: 2px;
    height: calc(100% - 12px);
    background: var(--line);
}
.log-dot {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    background: var(--bg);
    border: 2px solid var(--line);
}
.log-dot.active {
    background: rgba(255,106,42,0.15);
    border-color: #ff6a2a;
}
.log-content { flex: 1; }
.log-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 3px;
}
.log-location {
    font-size: 0.8rem;
    color: #ff6a2a;
    font-weight: 600;
    margin-bottom: 3px;
}
.log-note {
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 4px;
}
.log-time {
    font-size: 0.75rem;
    color: var(--muted);
    opacity: 0.7;
}

/* No logs placeholder */
.no-logs {
    text-align: center;
    padding: 30px 20px;
    color: var(--muted);
    font-size: 0.92rem;
}
.no-logs .icon { font-size: 2.4rem; margin-bottom: 10px; }

/* Items card */
.items-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 28px;
    margin-bottom: 24px;
}
.items-card h3 {
    margin: 0 0 20px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink);
}
.order-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--line);
    gap: 12px;
    flex-wrap: wrap;
}
.order-item-row:last-child { border-bottom: none; }
.order-item-name {
    font-weight: 600;
    color: var(--ink);
    font-size: 0.92rem;
}
.order-item-meta {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 2px;
}
.order-item-price {
    font-weight: 700;
    color: #ff6a2a;
    font-size: 0.95rem;
    white-space: nowrap;
}

/* Address card */
.address-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 24px 28px;
    margin-bottom: 24px;
}
.address-card h3 {
    font-size: 0.9rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
    margin: 0 0 12px;
}
.address-card p {
    color: var(--ink);
    line-height: 1.7;
    margin: 0;
    font-size: 0.95rem;
}

/* Action buttons */
.track-actions {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ── Inline Live Tracking Map (inside stepper card) ───── */
.inline-map-wrap {
    margin-top: 32px;
    border-top: 1px solid var(--line);
    border-radius: 18px;
    overflow: hidden;
    background: rgba(0,0,0,0.25);
    border: 1px solid rgba(255,106,42,0.18);
}
.inline-map-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    padding: 18px 22px 16px;
    background: rgba(255,106,42,0.06);
    border-bottom: 1px solid rgba(255,106,42,0.15);
}
.inline-map-title {
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: 0.02em;
}
.inline-map-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.imap-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.74rem;
    font-weight: 700;
    border: 1px solid;
}
.imap-pill.store {
    background: rgba(255,106,42,0.12);
    border-color: rgba(255,106,42,0.35);
    color: #ff6a2a;
}
.imap-pill.dest {
    background: rgba(52,211,153,0.12);
    border-color: rgba(52,211,153,0.35);
    color: #34d399;
}
.imap-pill.user {
    background: rgba(59,130,246,0.12);
    border-color: rgba(59,130,246,0.35);
    color: #60a5fa;
}
.imap-pill.dist {
    background: rgba(168,85,247,0.12);
    border-color: rgba(168,85,247,0.35);
    color: #c084fc;
}
.inline-map-status {
    font-size: 0.78rem;
    color: var(--muted);
    padding: 8px 22px 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 28px;
}
#deliveryMap {
    width: 100%;
    height: 380px;
    display: block;
}
.inline-map-footer {
    padding: 12px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: rgba(0,0,0,0.2);
    border-top: 1px solid rgba(255,106,42,0.15);
}
.imap-legend {
    font-size: 0.78rem;
    color: var(--muted);
}
.imap-gmaps-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #ff6a2a, #ff9a5a);
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 7px 16px;
    border-radius: 50px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 3px 12px rgba(255,106,42,0.4);
    border: none;
    cursor: pointer;
}
.imap-gmaps-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 18px rgba(255,106,42,0.55);
    color: #fff;
    text-decoration: none;
}
.loc-spinner {
    width: 13px; height: 13px;
    border: 2px solid rgba(255,106,42,0.25);
    border-top-color: #ff6a2a;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
    flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Leaflet popup override */
.leaflet-popup-content-wrapper {
    background: #1a1a2e !important;
    color: #fff !important;
    border-radius: 12px !important;
    border: 1px solid rgba(255,106,42,0.35) !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5) !important;
}
.leaflet-popup-tip { background: #1a1a2e !important; }
.leaflet-popup-content { color: #fff !important; font-size: 0.88rem; }
.popup-store-name { font-weight: 800; color: #ff6a2a; font-size: 1rem; margin-bottom: 4px; }
.popup-addr { color: rgba(255,255,255,0.7); font-size: 0.8rem; }
.popup-user-name { font-weight: 800; color: #60a5fa; font-size: 1rem; margin-bottom: 4px; }

/* ── Map Modal ──────────────────────────────────────── */
.map-modal {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); z-index: 9999;
    align-items: center; justify-content: center; backdrop-filter: blur(8px);
}
.map-modal.open { display: flex; }
.map-modal-content {
    background: #1a1a2e; width: 95%; max-width: 900px;
    border-radius: 20px; border: 1px solid rgba(255,106,42,0.3);
    overflow: hidden; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.7);
}
.map-modal-close {
    position: absolute; top: 12px; right: 16px;
    background: rgba(255,255,255,0.1); border: none; color: #fff;
    font-size: 1.4rem; width: 34px; height: 34px; border-radius: 50%;
    cursor: pointer; z-index: 1000; display:flex; align-items:center; justify-content:center;
    transition: 0.2s;
}
.map-modal-close:hover { background: #ff6a2a; }
.live-track-action-box {
    background: rgba(255,106,42,0.05); border: 1px dashed rgba(255,106,42,0.3);
    padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 24px;
}
.live-track-action-box h4 { margin: 0 0 10px; color: #ff6a2a; font-size: 1.1rem; font-weight: 800; }
.live-track-action-box p { font-size: 0.85rem; color: var(--muted); margin: 0 0 16px; }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 640px) {
    .stepper-card { padding: 24px 16px; }
    .track-hero   { padding: 24px 22px; }
    .step-label   { font-size: 0.63rem; max-width: 55px; }
    .step-desc    { display: none; }
    .step-node    { width: 38px; height: 38px; font-size: 1rem; }
    .stepper-line-bg, .stepper-line-fill { top: 19px; left: 19px; right: 19px; }
    #deliveryMap         { height: 350px; }
    .inline-map-header   { padding: 16px 16px 10px; flex-direction: column; gap: 10px; }
    .inline-map-footer   { padding: 12px 16px; flex-direction: column; text-align: center; }
}
</style>

<!-- Leaflet.js CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<section class="section">
    <div class="track-wrapper">

        <!-- Hero banner -->
        <div class="track-hero">
            <div class="track-hero-left">
                <span class="eyebrow">🚚 Shipment Tracking</span>
                <h1>Order #<?= (int)$order['id'] ?></h1>
                <div class="track-date">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>

                <?php if (!empty($order['tracking_number'])): ?>
                    <div class="tracking-chip">
                        📦 Tracking No: <code><?= e($order['tracking_number']) ?></code>
                    </div>
                <?php endif; ?>

                <?php if (!empty($order['estimated_delivery'])): ?>
                    <div class="est-delivery-badge">
                        📅 Est. Delivery: <strong><?= date('d M Y', strtotime($order['estimated_delivery'])) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="track-hero-right">
                <?php
                $statusLabel = ucwords(str_replace('_', ' ', $order['status']));
                $badgeClass  = match(strtolower($order['status'])) {
                    'delivered'       => 'status-delivered',
                    'shipped'         => 'status-shipped',
                    'processing'      => 'status-processing',
                    'out_for_delivery'=> 'status-out',
                    'cancelled'       => 'status-cancelled',
                    default           => 'status-pending',
                };
                ?>
                <span class="track-status-badge <?= $badgeClass ?>">
                    <?= e($statusLabel) ?>
                </span>
                <div class="track-amount">
                    <span><?= formatPrice((float)$order['total_amount']) ?></span>
                </div>
                <?php if ($order['payment_status'] === 'paid'): ?>
                    <div style="margin-top:8px; font-size:0.8rem; color: #34d399;">✓ Payment Confirmed</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancelled banner -->
        <?php if ($isCancelled): ?>
        <div class="cancelled-banner">
            <span style="font-size:1.8rem;">❌</span>
            <div>
                <div style="font-size:1rem;">Order Cancelled</div>
                <div style="font-size:0.82rem; opacity:0.8; font-weight:400; margin-top:3px;">This order has been cancelled. Contact support if you need assistance.</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stepper Card -->
        <?php if (!$isCancelled): ?>
        <div class="stepper-card">
            <h2>📍 Live Tracking Status</h2>
            <div class="stepper-track">
                <!-- Background line -->
                <div class="stepper-line-bg"></div>

                <!-- Filled progress line -->
                <?php
                $pct = ($currentStep - 1) / (count($steps) - 1) * 100;
                $pct = max(0, min(100, $pct));
                $isDelivered = ($currentStep === 5);
                ?>
                <div class="stepper-line-fill <?= $isDelivered ? 'delivered' : '' ?>"
                     style="width: calc(<?= $pct ?>% - 0px);"></div>

                <?php foreach ($steps as $i => $step):
                    $stepNum = $i + 1;
                    $isDone   = ($stepNum < $currentStep);
                    $isActive = ($stepNum === $currentStep);
                    $nodeClass = $isDone ? 'done' : ($isActive ? 'active' : '');
                    $labelClass = $isDone ? 'done' : ($isActive ? 'active' : '');
                ?>
                <div class="stepper-step">
                    <div class="step-node <?= $nodeClass ?>">
                        <?php if ($isDone): ?>✓<?php else: ?><?= $step['icon'] ?><?php endif; ?>
                    </div>
                    <div class="step-label <?= $labelClass ?>"><?= e($step['label']) ?></div>
                    <div class="step-desc"><?= e($step['desc']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>
        <?php endif; ?>



        <!-- Tracking History Card (collapsible) -->
        <div class="timeline-card">
            <div class="timeline-header" id="timelineToggle" onclick="toggleTimeline()">
                <h3>🗓️ Tracking Details</h3>
                <svg class="timeline-chevron" id="timelineChevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="timeline-body open" id="timelineBody">
                <!-- 🗺️ LIVE TRACKING BUTTON IN TRACKING DETAILS -->
                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                <div class="live-track-action-box">
                    <h4>🗺️ Live Tracking Map</h4>
                    <p>Track the delivery man in real-time, see the distance to your location, and view the route.</p>
                    <button class="btn primary" onclick="openMapModal()" style="border-radius:50px; padding: 10px 24px; font-weight:800; font-size:1rem;">
                        📍 Open Live Map
                    </button>
                </div>
                <?php endif; ?>

                <?php if (empty($trackingLogs)): ?>
                    <!-- Auto-generate based on current status if no manual logs exist -->
                    <?php
                    $autoLogs = [];
                    $baseTime = strtotime($order['created_at']);

                    if ($currentStep >= 1) {
                        $autoLogs[] = ['status' => 'Order Placed', 'note' => 'Payment confirmed & order received successfully.', 'location' => 'KGF Store System', 'time' => $baseTime];
                    }
                    if ($currentStep >= 2) {
                        $autoLogs[] = ['status' => 'Processing', 'note' => 'Items are being picked and packed.', 'location' => 'KGF Warehouse', 'time' => $baseTime + 3600];
                    }
                    if ($currentStep >= 3) {
                        $autoLogs[] = ['status' => 'Shipped', 'note' => 'Package handed over to courier partner.', 'location' => 'Courier Facility, India', 'time' => $baseTime + 86400];
                    }
                    if ($currentStep >= 4) {
                        $autoLogs[] = ['status' => 'Out for Delivery', 'note' => 'Package is out for delivery with delivery agent.', 'location' => 'Your City, India', 'time' => $baseTime + 172800];
                    }
                    if ($currentStep >= 5) {
                        $autoLogs[] = ['status' => 'Delivered', 'note' => 'Package delivered successfully.', 'location' => 'Your Address', 'time' => $baseTime + 259200];
                    }
                    $autoLogs = array_reverse($autoLogs);

                    $stepIcons = [
                        'Order Placed'    => '📋',
                        'Processing'      => '⚙️',
                        'Shipped'         => '🚚',
                        'Out for Delivery'=> '📍',
                        'Delivered'       => '✅',
                    ];
                    ?>
                    <?php foreach ($autoLogs as $idx => $log): ?>
                    <div class="log-item">
                        <div class="log-dot <?= $idx === 0 ? 'active' : '' ?>">
                            <?= $stepIcons[$log['status']] ?? '📌' ?>
                        </div>
                        <div class="log-content">
                            <div class="log-title"><?= e($log['status']) ?></div>
                            <?php if (!empty($log['location'])): ?>
                                <div class="log-location">📍 <?= e($log['location']) ?></div>
                            <?php endif; ?>
                            <div class="log-note"><?= e($log['note']) ?></div>
                            <div class="log-time"><?= date('d M Y, h:i A', $log['time']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($autoLogs)): ?>
                    <div class="no-logs">
                        <div class="icon">📭</div>
                        <div>No tracking events yet. Updates will appear here once your order ships.</div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Real logs from DB -->
                    <?php foreach ($trackingLogs as $idx => $log): ?>
                    <div class="log-item">
                        <div class="log-dot <?= $idx === 0 ? 'active' : '' ?>">📌</div>
                        <div class="log-content">
                            <div class="log-title"><?= e(ucwords(str_replace('_', ' ', $log['status']))) ?></div>
                            <?php if (!empty($log['location'])): ?>
                                <div class="log-location">📍 <?= e($log['location']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($log['note'])): ?>
                                <div class="log-note"><?= e($log['note']) ?></div>
                            <?php endif; ?>
                            <div class="log-time"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ordered Items Card -->
        <div class="items-card">
            <h3>🛍️ Items in This Order</h3>
            <?php foreach ($items as $item): ?>
            <div class="order-item-row">
                <div>
                    <div class="order-item-name"><?= e($item['product_name']) ?></div>
                    <div class="order-item-meta">Qty: <?= (int)$item['quantity'] ?> × <?= formatPrice((float)$item['price']) ?></div>
                </div>
                <div class="order-item-price"><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></div>
            </div>
            <?php endforeach; ?>
            <div style="display:flex; justify-content:flex-end; padding-top:16px; border-top: 1px solid var(--line); margin-top: 6px;">
                <span style="font-size:1.1rem; font-weight:800; color: var(--ink);">
                    Grand Total: <span style="color:#ff6a2a;"><?= formatPrice((float)$order['total_amount']) ?></span>
                </span>
            </div>
        </div>

        <!-- Shipping Address Card -->
        <div class="address-card">
            <h3>📦 Delivery Address</h3>
            <p><?= nl2br(e($order['address'])) ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="track-actions">
            <a href="<?= url('profile.php') ?>" class="btn ghost" style="padding: 0 28px;">← My Orders</a>
            <a href="<?= url('shop.php') ?>"    class="btn primary" style="padding: 0 28px;">Continue Shopping</a>
            <?php if ($order['payment_status'] === 'paid'): ?>
            <a href="<?= url('order_success.php?id=' . (int)$order['id']) ?>" class="btn ghost" style="padding: 0 28px;">View Receipt</a>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ── LIVE TRACKING MAP MODAL ───────────────────────── -->
<div class="map-modal" id="mapModal">
    <div class="map-modal-content">
        <button class="map-modal-close" onclick="closeMapModal()">×</button>
        <div class="inline-map-wrap" style="margin:0; border:none; border-radius:0;">
            <div class="inline-map-header" style="padding-right: 50px;">
                <span class="inline-map-title">🗺️ Live Delivery Tracking</span>
                <div class="inline-map-pills">
                    <span class="imap-pill store">🚚 Delivery Man (KGF Store)</span>
                    <span class="imap-pill dest" id="destPill">📦 Analysing delivery address…</span>
                    <span class="imap-pill user" id="userPill">📍 Getting your location…</span>
                    <span class="imap-pill dist" id="iDistPill">📏 Calculating distance…</span>
                </div>
            </div>
            <div class="inline-map-status" id="iMapStatus">
                <span class="loc-spinner" id="iLocSpinner"></span>
                <span id="iStatusText">Opening map, getting your location &amp; analysing delivery address…</span>
            </div>
            <div id="deliveryMap" style="height: 450px;"></div>
            <div class="inline-map-footer">
                <span class="imap-legend">
                    🟠 Delivery Man (Store) &nbsp;|&nbsp;
                    🔵 Your Location &nbsp;|&nbsp;
                    🟢 Delivery Address
                </span>
                <a id="openGmapsBtn"
                   href="https://www.google.com/maps/place/10.928755,78.743269"
                   target="_blank" rel="noopener noreferrer"
                   class="imap-gmaps-btn">
                    🗺️ Open in Google Maps
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet.js JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs=" crossorigin=""></script>

<script>
function toggleTimeline() {
    var body    = document.getElementById('timelineBody');
    var chevron = document.getElementById('timelineChevron');
    body.classList.toggle('open');
    chevron.classList.toggle('open');
}

function openMapModal() {
    document.getElementById('mapModal').classList.add('open');
    if (window.deliveryMapObj) {
        setTimeout(function() {
            window.deliveryMapObj.invalidateSize();
            if (window.deliveryMapFit) window.deliveryMapFit();
        }, 150);
    }
}
function closeMapModal() {
    document.getElementById('mapModal').classList.remove('open');
}

/* ══════════════════════════════════════════════════════════
   LIVE DELIVERY MAP
   - 🚚 KGF Men's Wear (fixed to requested coordinates) = Delivery Man
   - 📍 User's live GPS location (browser geolocation)
   - 🏠 Delivery address from the order (geocoded via Nominatim)
   - Distance shown = user's GPS ↔ KGF Men's Wear
   ══════════════════════════════════════════════════════════ */
(function initDeliveryMap() {

    // ── KGF Men's Wear CONFIRMED FIXED LOCATION ──
    // Latitude: 10.928755, Longitude: 78.743269
    var STORE_LAT  = 10.928755;
    var STORE_LNG  = 78.743269;
    var STORE_NAME = "KGF Men's Wear (Delivery Man)";
    var STORE_ADDR = "Lat: 10.928755, Lng: 78.743269";

    // Order delivery address (injected by PHP)
    var DELIVERY_ADDRESS = <?= json_encode($order['address'] ?? '') ?>;

    // ── Haversine distance formula (km) ──
    function haversine(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) +
                Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*
                Math.sin(dLon/2)*Math.sin(dLon/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function fmtDist(d) {
        return d < 1 ? (d * 1000).toFixed(0) + ' m' : d.toFixed(1) + ' km';
    }

    function setStatus(msg, done) {
        var el = document.getElementById('iStatusText');
        var sp = document.getElementById('iLocSpinner');
        if (el) el.textContent = msg;
        if (sp && done) sp.style.display = 'none';
    }

    // ── Icons ──
    // Orange pulsing truck = KGF Men's Wear / Delivery Man
    var storeIcon = L.divIcon({
        className: '',
        html: '<div style="width:44px;height:44px;background:linear-gradient(135deg,#ff6a2a,#ff9a5a);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;border:3px solid #fff;box-shadow:0 4px 18px rgba(255,106,42,0.8);animation:pulse-ring 2s infinite;">🚚</div>',
        iconSize: [44, 44], iconAnchor: [22, 22], popupAnchor: [0, -28]
    });

    // Blue = user's live GPS
    var userIcon = L.divIcon({
        className: '',
        html: '<div style="width:42px;height:42px;background:linear-gradient(135deg,#2563eb,#60a5fa);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;border:3px solid #fff;box-shadow:0 4px 16px rgba(59,130,246,0.8);">📍</div>',
        iconSize: [42, 42], iconAnchor: [21, 21], popupAnchor: [0, -26]
    });

    // Green = delivery address pin
    var addrIcon = L.divIcon({
        className: '',
        html: '<div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#34d399);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 4px 14px rgba(16,185,129,0.7);">🏠</div>',
        iconSize: [38, 38], iconAnchor: [19, 19], popupAnchor: [0, -22]
    });

    // ── Init map (centres on KGF store first) ──
    var map = L.map('deliveryMap', {
        center: [STORE_LAT, STORE_LNG],
        zoom: 12,
        zoomControl: true,
        attributionControl: true
    });

    // CartoDB Dark Matter tiles (free, no API key)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // ── Place KGF Men's Wear marker (Delivery Man, pulsing orange) ──
    var storeMarker = L.marker([STORE_LAT, STORE_LNG], {icon: storeIcon}).addTo(map);
    storeMarker.bindPopup(
        '<div class="popup-store-name">🚚 ' + STORE_NAME + '</div>' +
        '<div class="popup-addr">📍 ' + STORE_ADDR + '</div>' +
        '<div class="popup-addr" style="color:#ff6a2a;margin-top:4px;font-weight:700;">📦 Delivery Origin</div>'
    ).openPopup();

    var gmBtn    = document.getElementById('openGmapsBtn');
    var userLat  = null, userLng  = null;
    var addrLat  = null, addrLng  = null;
    var gpsReady = false, addrReady = false;

    // Export map to window so modal can invalidateSize
    window.deliveryMapObj = map;

    // Fit map bounds to show all available points
    function fitAll() {
        var pts = [[STORE_LAT, STORE_LNG]];
        if (gpsReady  && userLat !== null) pts.push([userLat, userLng]);
        if (addrReady && addrLat !== null) pts.push([addrLat, addrLng]);
        if (pts.length > 1) {
            map.flyToBounds(L.latLngBounds(pts), { padding: [60, 60], duration: 1.5 });
        }
    }
    window.deliveryMapFit = fitAll;

    // Update the status bar message
    function updateStatus() {
        if (!gpsReady || !addrReady) return;
        if (userLat !== null) {
            var d = haversine(userLat, userLng, STORE_LAT, STORE_LNG);
            setStatus(
                '✅ Live tracking active — You are ' + fmtDist(d) +
                ' from KGF Men\'s Wear. Delivery address is also pinned on the map.',
                true
            );
        } else {
            setStatus('✅ Map ready — delivery address and KGF Men\'s Wear are shown. Enable location for live distance.', true);
        }
    }

    /* ──────────────────────────────────────────────────────
       STEP 1: USER'S LIVE GPS LOCATION
    ────────────────────────────────────────────────────── */
    if (!navigator.geolocation) {
        var up = document.getElementById('userPill');
        if (up) up.textContent = '📍 GPS not supported';
        gpsReady = true;
        updateStatus();
    } else {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                userLat  = pos.coords.latitude;
                userLng  = pos.coords.longitude;
                gpsReady = true;

                // Blue user live-location marker
                var userMarker = L.marker([userLat, userLng], {icon: userIcon}).addTo(map);
                userMarker.bindPopup(
                    '<div class="popup-user-name">📍 Your Live Location</div>' +
                    '<div class="popup-addr">Accuracy: ±' + Math.round(pos.coords.accuracy) + ' m</div>'
                );

                // Orange dashed line: Your location → KGF Men's Wear
                L.polyline([[userLat, userLng], [STORE_LAT, STORE_LNG]], {
                    color: '#ff6a2a', weight: 3.5, opacity: 0.9, dashArray: '12, 8'
                }).addTo(map);

                // Distance: user ↔ delivery man
                var dist = haversine(userLat, userLng, STORE_LAT, STORE_LNG);
                var distText = fmtDist(dist);

                // Update pills
                var up = document.getElementById('userPill');
                if (up) up.textContent = '📍 Your Location';
                var ip = document.getElementById('iDistPill');
                if (ip) ip.textContent = '📏 ' + distText + ' from Delivery Man';

                // Update Google Maps button → directions to KGF
                if (gmBtn) {
                    gmBtn.href = 'https://www.google.com/maps/dir/' +
                        userLat + ',' + userLng + '/' + STORE_LAT + ',' + STORE_LNG;
                    gmBtn.textContent = '🗺️ Get Directions to Delivery Man';
                }

                fitAll();
                updateStatus();
            },
            function(err) {
                var msg = 'Location denied';
                if (err.code === 2) msg = 'Location unavailable';
                if (err.code === 3) msg = 'Location timed out';
                var up = document.getElementById('userPill');
                if (up) up.textContent = '📍 ' + msg;
                gpsReady = true;
                updateStatus();
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    /* ──────────────────────────────────────────────────────
       STEP 2: DELIVERY ADDRESS ANALYSIS (Nominatim)
    ────────────────────────────────────────────────────── */
    if (!DELIVERY_ADDRESS || DELIVERY_ADDRESS.trim() === '') {
        var dp = document.getElementById('destPill');
        if (dp) dp.textContent = '📦 No delivery address';
        addrReady = true;
        updateStatus();
    } else {
        fetch(
            'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' +
            encodeURIComponent(DELIVERY_ADDRESS),
            { headers: { 'Accept-Language': 'en' } }
        )
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || data.length === 0) {
                var dp = document.getElementById('destPill');
                if (dp) dp.textContent = '📦 Address not found';
                addrReady = true;
                updateStatus();
                return;
            }

            addrLat   = parseFloat(data[0].lat);
            addrLng   = parseFloat(data[0].lon);
            addrReady = true;

            // Green delivery-address marker
            var addrMarker = L.marker([addrLat, addrLng], {icon: addrIcon}).addTo(map);
            addrMarker.bindPopup(
                '<div class="popup-addr-name">🏠 Delivery Address</div>' +
                '<div class="popup-addr" style="max-width:210px;word-break:break-word;">' +
                    (data[0].display_name || DELIVERY_ADDRESS).substring(0, 110) +
                '</div>'
            );

            // Green dashed line: KGF store → delivery address
            L.polyline([[STORE_LAT, STORE_LNG], [addrLat, addrLng]], {
                color: '#34d399', weight: 2.5, opacity: 0.75, dashArray: '8, 6'
            }).addTo(map);

            // Distance: store → delivery address
            var aDist = haversine(STORE_LAT, STORE_LNG, addrLat, addrLng);
            var dp = document.getElementById('destPill');
            if (dp) dp.textContent = '📦 Delivery Address — ' + fmtDist(aDist) + ' from store';

            fitAll();
            updateStatus();
        })
        .catch(function() {
            var dp = document.getElementById('destPill');
            if (dp) dp.textContent = '📦 Geocode failed';
            addrReady = true;
            updateStatus();
        });
    }
})();
</script>

<?php require ROOT_PATH . '/includes/footer.php'; ?>
