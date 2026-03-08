<?php
/**
 * Dashboard sidebar component
 */
function renderSidebar(string $active = ''): void {
    $user = $_SESSION['user'] ?? ['name' => 'User', 'email' => ''];
    $initial = strtoupper(substr($user['name'], 0, 1));
    $base = APP_URL;

    // Count drafts
    $drafts = 0;
    try {
        $db = getDB();
        $s = $db->prepare("SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='draft'");
        $s->execute([$_SESSION['user_id']]);
        $drafts = (int) $s->fetchColumn();
    } catch (Exception $e) {}
?>
<div class="sidebar-overlay"></div>
<aside class="sidebar">
  <a href="<?= $base ?>/dashboard/index.php" class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
    </div>
    <span class="sidebar-logo-text">MailFlow</span>
  </a>

  <div class="sidebar-compose">
    <button class="btn-compose" onclick="location.href='<?= $base ?>/dashboard/compose.php'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Compose Email
    </button>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Mailbox</div>
    <ul class="sidebar-nav">
      <li>
        <a href="<?= $base ?>/dashboard/compose.php" class="<?= $active==='compose'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Compose
        </a>
      </li>
      <li>
        <a href="<?= $base ?>/dashboard/sent.php" class="<?= $active==='sent'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
          Sent Mail
        </a>
      </li>
      <li>
        <a href="<?= $base ?>/dashboard/drafts.php" class="<?= $active==='drafts'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
          Drafts
          <?php if ($drafts > 0): ?>
          <span class="nav-badge"><?= $drafts ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= $base ?>/dashboard/sent.php?filter=attachments" class="<?= $active==='attachments'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
          </svg>
          Attachments
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Manage</div>
    <ul class="sidebar-nav">
      <li>
        <a href="<?= $base ?>/dashboard/templates.php" class="<?= $active==='templates'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
          </svg>
          Templates
        </a>
      </li>
      <li>
        <a href="<?= $base ?>/dashboard/settings.php" class="<?= $active==='settings'?'active':'' ?>">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
          </svg>
          Settings
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-user">
    <div class="avatar avatar-sm" style="background: linear-gradient(135deg,#6366f1,#06b6d4)">
      <?= $initial ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name truncate"><?= clean($user['name']) ?></div>
      <div class="sidebar-user-email truncate"><?= clean($user['email']) ?></div>
    </div>
    <a href="<?= $base ?>/auth/logout.php" title="Logout" style="color:rgba(255,255,255,.4); display:flex;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </a>
  </div>
</aside>
<?php
}
