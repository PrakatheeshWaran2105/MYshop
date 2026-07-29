<?php
require_once __DIR__ . '/config/bootstrap.php';

if (empty($_SESSION['reset_user_id'])) {
    redirect('forgot_password.php');
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
        $userId = (int) $_SESSION['reset_user_id'];

        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            $error = 'Please enter a valid 6-digit numeric OTP.';
        } elseif (strlen($password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, role, reset_otp, reset_otp_expires FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $userAcc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userAcc || empty($userAcc['reset_otp'])) {
                $error = 'OTP request was not found. Please request a new OTP.';
            } elseif (empty($userAcc['reset_otp_expires']) || strtotime($userAcc['reset_otp_expires']) < time()) {
                $error = 'The OTP has expired. Please request a new OTP.';
            } elseif (!password_verify($otp, $userAcc['reset_otp'])) {
                $error = 'Incorrect OTP code. Please check and try again.';
            } else {
                // Update password to new password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE id = ?");
                $update->execute([$passwordHash, $userId]);

                unset(
                    $_SESSION['reset_user_id'],
                    $_SESSION['reset_user_email'],
                    $_SESSION['reset_admin_id'],
                    $_SESSION['reset_admin_email']
                );

                flash('success', 'Your password has been updated successfully! Please log in with your new password.');
                redirect('login.php');
            }
        }
    }
}

$pageTitle = 'Reset Password | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-grid">
        <div class="auth-copy">
            <span class="eyebrow">Set New Password</span>
            <h1>Enter OTP & Reset Password</h1>
            <p>Enter the 6-digit OTP sent to <strong><?= e($_SESSION['reset_user_email'] ?? '') ?></strong> and choose your new password.</p>
        </div>
        <form method="post" class="form-card">
            <h2>Reset Password</h2>
            
            <?php if ($flashSuccess): ?>
                <div class="flash success"><?= $flashSuccess ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="form-error"><?= e($error) ?></div>
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
                <div class="password-input-container">
                    <input type="password" name="password" placeholder="••••••••" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </label>

            <label>
                Confirm New Password
                <div class="password-input-container">
                    <input type="password" name="confirm_password" placeholder="••••••••" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </label>

            <button type="submit" class="btn primary full" style="margin-top: 10px;">Change Password</button>

            <p style="margin-top: 15px; text-align: center;">
                <a href="<?= url('forgot_password.php') ?>">← Request a new OTP</a>
            </p>
        </form>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
