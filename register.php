<?php
require_once __DIR__ . '/config/bootstrap.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!$email) $errors[] = 'Enter a valid email.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
            flash('success', 'Account created. Please login.');
            redirect('login.php');
        } catch (PDOException $e) {
            $errors[] = 'This email is already registered.';
        }
    }
}

$pageTitle = 'Create Account | KGF Mens Wear';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-grid">
        <div class="auth-copy"><span class="eyebrow">Join the club</span><h1>Your next look starts here.</h1><p>Save favourites, checkout faster and track every order.</p></div>
        <form method="post" class="form-card">
            <h2>Create account</h2>
            <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <label>Name<input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required></label>
            <label>Email<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
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
            <button class="btn primary full" type="submit">Create account</button>
            <p>Already registered? <a href="<?= url('login.php') ?>">Login</a></p>
        </form>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
