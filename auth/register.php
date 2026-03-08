<?php
require_once __DIR__ . '/../config.php';
if (!empty($_SESSION['user_id'])) redirect(APP_URL . '/dashboard/index.php');

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $name  = clean($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass || !$conf) {
        $error = 'All fields are required.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $conf) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM webmail_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $ins  = $db->prepare("INSERT INTO webmail_users (name, email, password) VALUES (?, ?, ?)");
            $ins->execute([$name, $email, $hash]);
            setFlash('success', 'Account created! Please sign in.');
            redirect(APP_URL . '/auth/login.php');
        }
    }
}

require_once __DIR__ . '/../components/header.php';
renderHead('Register', 'auth');
?>
<div class="auth-page">
  <div class="auth-card" style="max-width:480px">
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <span class="auth-logo-name">MailFlow</span>
    </div>

    <h1 class="auth-title">Create account</h1>
    <p class="auth-subtitle">Start sending emails in seconds</p>

    <?php if ($error): ?>
    <div class="alert alert-error" data-autodismiss><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input type="text" name="name" class="form-control" placeholder="John Doe"
                 value="<?= clean($_POST['name'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= clean($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input type="password" id="reg-password" name="password" class="form-control"
                 placeholder="Min. 8 characters" required>
          <button type="button" class="password-toggle" tabindex="-1">
            <svg class="eye-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="strength-bar mt-1"><div class="strength-fill" id="strength-fill"></div></div>
        <div class="text-xs text-muted mt-1" id="strength-label"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input type="password" name="confirm_password" class="form-control"
                 placeholder="Re-enter password" required>
        </div>
      </div>

      <button type="submit" class="btn-auth">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="<?= APP_URL ?>/auth/login.php">Sign in</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../components/footer.php'; renderFooter(); ?>
