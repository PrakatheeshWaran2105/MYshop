<?php

require_once __DIR__ . '/config/bootstrap.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = "Invalid security token. Please try submitting again.";

    } else {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $message = trim($_POST["message"] ?? "");

        if ($name === "" || $email === "" || $message === "") {

            $error = "Please fill in all fields.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            $recipient = $_ENV['MAIL_TO_ADDRESS'] ?? $_ENV['MAIL_USERNAME'] ?? 'prakatheesh2105@gmail.com';
            $subject = "New Contact Inquiry from " . $name;

            $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                <h2 style='color: #111; margin-top: 0;'>New Contact Inquiry</h2>
                <p><strong>Name:</strong> " . e($name) . "</p>
                <p><strong>Email:</strong> <a href='mailto:" . e($email) . "'>" . e($email) . "</a></p>
                <p><strong>Message:</strong></p>
                <div style='background: #f9f9f9; padding: 15px; border-radius: 6px; white-space: pre-wrap; color: #333;'>" . e($message) . "</div>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #777;'>Submitted via KGF Mens Wear Contact Form on " . date('F j, Y, g:i a') . "</p>
            </div>
            ";

            $mailError = '';
            $sent = sendMail($recipient, $subject, $emailBody, $email, $name, $mailError);

            if ($sent) {

                $success = "Thank you for contacting KGF Mens Wear! Your message has been sent successfully to " . $recipient . ".";

                // Clear the form fields after successful send
                $name = "";
                $email = "";
                $message = "";

            } else {

                $error = "Failed to send email. " . ($mailError ? "Error details: " . $mailError : "Please check your mail configuration.");

            }
        }
    }
}

$pageTitle = "Contact | KGF Mens Wear";

require ROOT_PATH . '/includes/header.php';

?>

<section class="section auth-section">

    <div class="container auth-grid">

        <div class="auth-copy">

            <span class="eyebrow">
                Talk to us
            </span>

            <h1>
                Need help with your order?
            </h1>

            <p>
                Send your question and our support team will respond.
            </p>

        </div>

        <form method="POST" class="form-card">

            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <?php if (!empty($success)): ?>

                <div class="success-message">
                    <?= e($success) ?>
                </div>

            <?php endif; ?>

            <?php if (!empty($error)): ?>

                <div class="form-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <label>

                Name

                <input
                    type="text"
                    name="name"
                    value="<?= e($name ?? '') ?>"
                    placeholder="Enter your name"
                    required
                >

            </label>

            <label>

                Email

                <input
                    type="email"
                    name="email"
                    value="<?= e($email ?? '') ?>"
                    placeholder="Enter your email"
                    required
                >

            </label>

            <label>

                Message

                <textarea
                    name="message"
                    rows="5"
                    placeholder="Write your message"
                    required
                ><?= e($message ?? '') ?></textarea>

            </label>

            <button
                type="submit"
                class="btn primary full"
            >
                Send Message
            </button>

        </form>

    </div>

</section>

<?php

require ROOT_PATH . '/includes/footer.php';

?>