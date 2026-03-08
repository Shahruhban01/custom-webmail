<?php
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['user_id'])) redirect(APP_URL . '/dashboard/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $pass  = $_POST['password'] ?? '';

    if (!$email || empty($pass)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM webmail_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = ['name' => $user['name'], 'email' => $user['email']];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(APP_URL . '/dashboard/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/../components/header.php';
renderHead('Login', 'auth');
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

    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
    <div class="alert alert-error" data-autodismiss>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= clean($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="you@example.com" value="<?= clean($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
          <label class="form-label" for="password" style="margin:0">Password</label>
          <a href="<?= APP_URL ?>/auth/forgot_password.php" class="text-sm" style="color:var(--primary)">Forgot password?</a>
        </div>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Enter your password" required>
          <button type="button" class="password-toggle" tabindex="-1">
            <svg class="eye-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-auth">Sign In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Create one</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; renderFooter(); ?>
