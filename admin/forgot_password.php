<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// Redirect if already logged in as admin
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request security token.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $userAcc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userAcc) {
                $error = 'No account found with this email address.';
            } else {
                $otp = (string) random_int(100000, 999999);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

                $update = $pdo->prepare("UPDATE users SET reset_otp = ?, reset_otp_expires = ? WHERE id = ?");
                $update->execute([$otpHash, $expiresAt, $userAcc['id']]);

                $mailError = null;
                $mailSent = sendAdminOtpEmail($userAcc['email'], $userAcc['name'], $otp, $mailError);

                if ($mailSent) {
                    $_SESSION['reset_admin_id'] = $userAcc['id'];
                    $_SESSION['reset_admin_email'] = $userAcc['email'];
                    flash('success', 'A 6-digit OTP code has been sent to your email (' . e($userAcc['email']) . ').');
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $_SESSION['reset_admin_id'] = $userAcc['id'];
                    $_SESSION['reset_admin_email'] = $userAcc['email'];
                    flash('success', 'OTP Code generated successfully! (Your OTP: <strong>' . $otp . '</strong>)');
                    header('Location: verify_otp.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | KGF Control Room</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login">
    <form class="form-card" method="post">
        <span class="eyebrow">KGF Control Room</span>
        <h1>Forgot Password</h1>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
            Enter your registered email address to receive a 6-digit OTP code.
        </p>

        <?php if (!empty($error)): ?>
            <div class="form-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?= e($success) ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <label>
            Registered Email
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="e.g. name@example.com" required autofocus>
        </label>

        <button type="submit" class="btn primary full" style="margin-top: 10px;">Send OTP Code</button>

        
        <p style="margin-top: 15px; text-align: center;">
            <a href="login.php">← Back to admin login</a>
        </p>
    </form>
</body>
</html>