<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SESSION['reset_admin_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$error = '';
$flashSuccess = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request security token.';
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $adminId = (int) $_SESSION['reset_admin_id'];

        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            $error = 'Please enter a valid 6-digit numeric OTP.';
        } elseif (strlen($password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, role, reset_otp, reset_otp_expires FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $userAcc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userAcc || empty($userAcc['reset_otp'])) {
                $error = 'OTP request was not found. Please request a new OTP.';
            } elseif (empty($userAcc['reset_otp_expires']) || strtotime($userAcc['reset_otp_expires']) < time()) {
                $error = 'The OTP has expired. Please request a new OTP.';
            } elseif (!password_verify($otp, $userAcc['reset_otp'])) {
                $error = 'Incorrect OTP code. Please check your email and try again.';
            } else {
                // OTP is valid! Update password and clear OTP fields
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE id = ?");
                $update->execute([$passwordHash, $adminId]);

                unset(
                    $_SESSION['reset_admin_id'],
                    $_SESSION['reset_admin_email'],
                    $_SESSION['admin_otp_verified']
                );

                if ($userAcc['role'] === 'admin') {
                    flash('success', 'Your admin password has been reset successfully! Please log in.');
                    header('Location: login.php');
                    exit;
                } else {
                    flash('success', 'Your password has been reset successfully! Please log in with your new password.');
                    header('Location: ../login.php');
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
    <title>Verify OTP & Reset Password | KGF Control Room</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login">
    <form class="form-card" method="post">
        <span class="eyebrow">KGF Control Room</span>
        <h1>Reset Password</h1>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
            Enter the 6-digit OTP code sent to <strong><?= e($_SESSION['reset_admin_email'] ?? '') ?></strong> and set your new password.
        </p>

        <?php if ($flashSuccess): ?>
            <div class="success-message"><?= e($flashSuccess) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="form-error"><?= $error ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <label>
            6-Digit OTP Code
            <input 
                type="text" 
                name="otp" 
                value="<?= e($_POST['otp'] ?? '') ?>"
                minlength="6" 
                maxlength="6" 
                pattern="[0-9]{6}" 
                inputmode="numeric" 
                placeholder="Enter 6-digit OTP" 
                required 
                autofocus 
                style="letter-spacing: 4px; font-size: 1.1rem; text-align: center;"
            >
        </label>

        <label>
            New Password
            <input type="password" name="password" placeholder="••••••••" required minlength="6">
        </label>

        <label>
            Confirm New Password
            <input type="password" name="confirm_password" placeholder="••••••••" required minlength="6">
        </label>

        <button type="submit" class="btn primary full" style="margin-top: 10px;">Reset Password</button>

        <p style="margin-top: 15px; text-align: center;">
            <a href="forgot_password.php">← Request a new OTP</a>
        </p>
    </form>
</body>
</html>