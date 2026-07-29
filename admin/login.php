<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Auto-seed default admin user if not present
try {
    $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkAdmin->execute(['admin@kgf.com']);
    $existing = $checkAdmin->fetch();

    if (!$existing) {
        $defaultHash = password_hash('admin@123', PASSWORD_DEFAULT);
        $seedStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@kgf.com', ?, 'admin')");
        $seedStmt->execute([$defaultHash]);
    }
} catch (\Throwable $t) {
    // Suppress error if table structure is still initializing
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request security token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $userAcc = $stmt->fetch();

            if ($userAcc && password_verify($password, $userAcc['password'])) {
                if ($userAcc['role'] === 'admin') {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $userAcc['id'];
                    $_SESSION['admin_name'] = $userAcc['name'];
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'This account is a customer account. Please <a href="../login.php" style="color: #ff6a2a; text-decoration: underline;">log in here</a>.';
                }
            } else {
                $error = 'Invalid admin email or password.';
            }
        }
    }
}

$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | KGF Control Room</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0.2">
</head>
<body class="admin-login">
    <form class="form-card" method="post">
        <span class="eyebrow">KGF Control Room</span>
        <h1>Admin Login</h1>
        
        <?php if ($flashSuccess): ?>
            <div class="success-message"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        
        <?php if ($flashError): ?>
            <div class="form-error"><?= e($flashError) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="form-error"><?= $error ?></div>
        <?php endif; ?>


        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        
        <label>
            Email
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@kgf.com" required autofocus>
        </label>

        
        <label>
            Password
            <input type="password" name="password" placeholder="••••••••" required>
        </label>
        
        <button class="btn primary full" type="submit">Enter Dashboard</button>
        <a href="forgot_password.php">Forgot password?</a>
    </form>
</body>
</html>

