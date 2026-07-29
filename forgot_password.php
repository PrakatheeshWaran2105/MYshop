<?php
require_once __DIR__ . '/config/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request security token.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
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

                $_SESSION['reset_user_id'] = $userAcc['id'];
                $_SESSION['reset_user_email'] = $userAcc['email'];

                if ($mailSent) {
                    flash('success', 'A 6-digit OTP code has been sent to your email (' . e($userAcc['email']) . ').');
                } else {
                    flash('success', 'OTP code generated successfully! (Your OTP: <strong>' . $otp . '</strong>)');
                }
                redirect('reset_password.php');
            }
        }
    }
}

$pageTitle = 'Forgot Password | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-grid">
        <div class="auth-copy">
            <span class="eyebrow">Account Recovery</span>
            <h1>Forgot Your Password?</h1>
            <p>Enter your registered email address and we'll send you a 6-digit OTP to reset your password.</p>
        </div>
        <form method="post" class="form-card">
            <h2>Forgot Password</h2>
            <?php if (!empty($error)): ?>
                <div class="form-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <label>
                Registered Email Address
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@example.com" required autofocus>
            </label>
            
            <button class="btn primary full" type="submit" style="margin-top: 10px;">Send OTP Code</button>
            <p style="margin-top: 15px; text-align: center;">
                <a href="<?= url('login.php') ?>">← Back to Login</a>
            </p>
        </form>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
