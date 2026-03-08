<?php
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
$error = ''; $success = '';

// Validate token
$valid = false;
if ($token && $email) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM webmail_password_resets WHERE email=? AND token=? AND expires_at > NOW()");
    $stmt->execute([$email, $token]);
    $valid = (bool) $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $pass = $_POST['password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if (!$valid) { $error = 'Invalid or expired reset link.'; }
    elseif (strlen($pass) < 8) { $error = 'Password must be at least 8 characters.'; }
    elseif ($pass !== $conf) { $error = 'Passwords do not match.'; }
    else {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE webmail_users SET password=? WHERE email=?")->execute([$hash, $email]);
        $db->prepare("DELETE FROM webmail_password_resets WHERE email=?")->execute([$email]);
        setFlash('success', 'Password updated successfully. Please sign in.');
        redirect(APP_URL . '/auth/login.php');
    }
}

require_once __DIR__ . '/../components/header.php';
renderHead('Reset Password', 'auth');
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
      </div>
      <span class="auth-logo-name">MailFlow</span>
    </div>

    <h1 class="auth-title">New password</h1>
    <p class="auth-subtitle">Choose a strong password for your account</p>

    <?php if (!$valid && !$_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="alert alert-error">This reset link is invalid or has expired.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= clean($error) ?></div>
    <?php endif; ?>

    <?php if ($valid || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="token" value="<?= clean($token) ?>">
      <input type="hidden" name="email" value="<?= clean($email) ?>">

      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input type="password" id="reg-password" name="password" class="form-control" placeholder="Min. 8 characters" required>
          <button type="button" class="password-toggle" tabindex="-1">
            <svg class="eye-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="strength-bar mt-1"><div class="strength-fill" id="strength-fill"></div></div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
        </div>
      </div>

      <button type="submit" class="btn-auth">Update Password</button>
    </form>
    <?php endif; ?>

    <div class="auth-footer"><a href="<?= APP_URL ?>/auth/login.php">← Back to login</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; renderFooter(); ?>
