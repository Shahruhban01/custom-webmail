<?php
require_once __DIR__ . '/../config.php';

$message = ''; $type = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $message = 'Please enter a valid email address.'; $type = 'error';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM webmail_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $db->prepare("DELETE FROM webmail_password_resets WHERE email = ?")->execute([$email]);
            $db->prepare("INSERT INTO webmail_password_resets (email, token, expires_at) VALUES (?,?,?)")
               ->execute([$email, $token, $expires]);

            $resetLink = APP_URL . "/auth/reset_password.php?token={$token}&email=" . urlencode($email);
            $subject   = 'Reset your MailFlow password';
            $body      = "Hi,\n\nYou requested a password reset.\nClick the link below (valid for 1 hour):\n\n{$resetLink}\n\nIf you didn't request this, ignore this email.\n\nMailFlow Team";
            // $headers   = "From: MailFlow <no-reply@mailflow.local>\r\nContent-Type: text/plain;charset=UTF-8\r\n";
            $headers = "From: MailFlow <" . DEFAULT_SENDER_EMAIL . ">\r\nContent-Type: text/plain;charset=UTF-8\r\n";
            mail($email, $subject, $body, $headers);
        }
        // Always show success (security: don't reveal if email exists)
        $message = 'If that email is registered, you\'ll receive a reset link shortly.';
        $type = 'success';
    }
}

require_once __DIR__ . '/../components/header.php';
renderHead('Forgot Password', 'auth');
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <span class="auth-logo-name">MailFlow</span>
    </div>

    <h1 class="auth-title">Reset password</h1>
    <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>

    <?php if ($message): ?>
    <div class="alert alert-<?= $type ?>" data-autodismiss><?= clean($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
        </div>
      </div>
      <button type="submit" class="btn-auth">Send Reset Link</button>
    </form>
    <div class="auth-footer"><a href="<?= APP_URL ?>/auth/login.php">← Back to login</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; renderFooter(); ?>
