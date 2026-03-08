<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM webmail_users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name      = clean($_POST['name'] ?? '');
        $signature = $_POST['signature'] ?? '';
        if (!$name) { $errors[] = 'Name is required.'; }
        else {
            $db->prepare("UPDATE webmail_users SET name=?, signature=? WHERE id=?")
               ->execute([$name, $signature, $userId]);
            $_SESSION['user']['name'] = $name;
            setFlash('success', 'Profile updated.');
            redirect(APP_URL . '/dashboard/settings.php');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $conf    = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $conf) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE webmail_users SET password=? WHERE id=?")->execute([$hash, $userId]);
            setFlash('success', 'Password changed successfully.');
            redirect(APP_URL . '/dashboard/settings.php');
        }
    }
}

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('Settings');
?>
<div class="app-shell">
  <?php renderSidebar('settings'); ?>
  <div class="main-content">
    <header class="topbar">
      <button class="topbar-menu-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
    </header>

    <main class="page-body">
      <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" data-autodismiss><?= clean($flash['msg']) ?></div>
      <?php endif; ?>
      <?php if ($errors): ?>
      <div class="alert alert-error"><?= clean($errors[0]) ?></div>
      <?php endif; ?>

      <div class="page-header">
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Manage your account and preferences</p>
      </div>

      <div style="max-width:620px;display:flex;flex-direction:column;gap:1.5rem">

        <!-- Profile card -->
        <div class="card">
          <div class="card-header">
            <h3 style="font-size:.95rem;font-weight:700">Profile</h3>
          </div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
              <div class="avatar avatar-lg" style="background:linear-gradient(135deg,#6366f1,#06b6d4);font-size:1.5rem">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-weight:700;font-size:1rem"><?= clean($user['name']) ?></div>
                <div class="text-sm text-muted"><?= clean($user['email']) ?></div>
                <div class="text-xs text-muted mt-1">Member since <?= date('F Y', strtotime($user['created_at'])) ?></div>
              </div>
            </div>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="profile">
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control"
                       value="<?= clean($user['name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address <span class="text-muted text-xs">(read-only)</span></label>
                <input type="email" class="form-control" value="<?= clean($user['email']) ?>" disabled>
              </div>
              <div class="form-group">
                <label class="form-label">Email Signature</label>
                <textarea name="signature" class="form-control" style="min-height:100px"
                          placeholder="Your professional signature…"><?= clean($user['signature'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
          </div>
        </div>

        <!-- Change password -->
        <div class="card">
          <div class="card-header">
            <h3 style="font-size:.95rem;font-weight:700">Change Password</h3>
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="password">
              <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" id="reg-password" name="new_password" class="form-control"
                       placeholder="Min. 8 characters" required>
                <div class="strength-bar mt-1"><div class="strength-fill" id="strength-fill"></div></div>
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
          </div>
        </div>

        <!-- Danger zone -->
        <div class="card" style="border-color:#fecaca">
          <div class="card-header" style="background:#fff5f5">
            <h3 style="font-size:.95rem;font-weight:700;color:var(--danger)">Danger Zone</h3>
          </div>
          <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
              <div>
                <div style="font-weight:600;font-size:.875rem">Delete All Emails</div>
                <div class="text-sm text-muted">Permanently remove all email records from history.</div>
              </div>
              <form method="POST" onsubmit="return confirm('This cannot be undone. Delete ALL emails?')">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete_all">
                <button type="submit" class="btn btn-danger btn-sm">Delete All</button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<?php renderFooter(['sidebar.js']); ?>
