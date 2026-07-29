<?php
require_once __DIR__ . '/config/bootstrap.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        $errors[] = 'Enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('index.php');
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Login | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-grid">
        <div class="auth-copy"><span class="eyebrow">Welcome back</span><h1>Style looks better when it remembers you.</h1><p>Access your bag, orders and saved products.</p></div>
        <form method="post" class="form-card">
            <h2>Login</h2>
            <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <label>Email<input type="email" name="email" required></label>
            <label>Password
                <div class="password-input-container">
                    <input type="password" name="password" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </label>
            <button class="btn primary full" type="submit" style="margin-top: 10px;">Login</button>
            <p style="margin-top: 15px;"><a href="<?= url('forgot_password.php') ?>" style="color: #666; font-size: 0.9rem; text-decoration: none;">Forgot password?</a></p>
            <p>New customer? <a href="<?= url('register.php') ?>">Create account</a></p>


        </form>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
