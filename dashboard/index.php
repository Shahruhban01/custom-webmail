<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];

// Stats
$total  = $db->prepare("SELECT COUNT(*) FROM webmail_emails WHERE user_id=?");
$total->execute([$userId]); $totalCount = (int)$total->fetchColumn();

$sent   = $db->prepare("SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='sent'");
$sent->execute([$userId]); $sentCount = (int)$sent->fetchColumn();

$failed = $db->prepare("SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='failed'");
$failed->execute([$userId]); $failedCount = (int)$failed->fetchColumn();

$drafts = $db->prepare("SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='draft'");
$drafts->execute([$userId]); $draftCount = (int)$drafts->fetchColumn();

// Recent emails
$recent = $db->prepare("SELECT * FROM webmail_emails WHERE user_id=? ORDER BY created_at DESC LIMIT 8");
$recent->execute([$userId]); $recentEmails = $recent->fetchAll();

$flash = getFlash();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('Dashboard');
?>
<div class="app-shell">
  <?php renderSidebar('dashboard'); ?>
  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-menu-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div class="topbar-search">
        <span class="search-icon">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input type="text" placeholder="Search emails…" id="search-input">
      </div>
      <div class="topbar-actions">
        <button class="topbar-icon-btn" onclick="location.href='<?= APP_URL ?>/dashboard/compose.php'" title="Compose">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
        <div class="topbar-user">
          <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#06b6d4)">
            <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
          </div>
          <span class="topbar-user-name"><?= clean($_SESSION['user']['name']) ?></span>
        </div>
      </div>
    </header>

    <main class="page-body">
      <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" data-autodismiss><?= clean($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of your email activity</p>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background:#ede9fe">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalCount ?></div>
            <div class="stat-label">Total Emails</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1fae5">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"/>
              <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $sentCount ?></div>
            <div class="stat-label">Sent</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fef3c7">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $draftCount ?></div>
            <div class="stat-label">Drafts</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fee2e2">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $failedCount ?></div>
            <div class="stat-label">Failed</div>
          </div>
        </div>
      </div>

      <!-- Recent Emails -->
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:1rem;font-weight:700">Recent Emails</h2>
          <a href="<?= APP_URL ?>/dashboard/sent.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <?php if (empty($recentEmails)): ?>
        <div class="card-body" style="text-align:center;padding:3rem 1rem">
          <div style="font-size:2.5rem;margin-bottom:.75rem">✉️</div>
          <p style="color:var(--muted);font-size:.9rem">No emails sent yet.</p>
          <a href="<?= APP_URL ?>/dashboard/compose.php" class="btn btn-primary btn-sm" style="margin-top:.75rem">Compose your first email</a>
        </div>
        <?php else: ?>
        <?php foreach ($recentEmails as $email): ?>
        <a href="<?= APP_URL ?>/dashboard/view_email.php?id=<?= $email['id'] ?>" class="email-list-item">
          <div class="email-item-avatar">
            <div class="avatar avatar-sm" style="background:<?= avatarColor($email['receiver_email']) ?>">
              <?= strtoupper(substr($email['receiver_email'], 0, 1)) ?>
            </div>
          </div>
          <div class="email-item-body">
            <div class="email-item-header">
              <span class="email-item-to"><?= clean($email['receiver_email']) ?></span>
              <span class="email-item-time"><?= timeAgo($email['created_at']) ?></span>
            </div>
            <div class="email-item-subject"><?= clean($email['subject']) ?></div>
            <div class="email-item-preview"><?= clean(substr(strip_tags($email['message']), 0, 90)) ?>…</div>
          </div>
          <div style="margin-left:.5rem">
            <?php
              $badges = [
                'sent'      => ['success', 'Sent'],
                'failed'    => ['danger',  'Failed'],
                'draft'     => ['warning', 'Draft'],
                'scheduled' => ['info',    'Scheduled'],
              ];
              [$cls, $lbl] = $badges[$email['status']] ?? ['muted','Unknown'];
            ?>
            <span class="badge badge-<?= $cls ?>"><?= $lbl ?></span>
          </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php
/**
 * Generate a deterministic avatar color from email
 */
function avatarColor(string $email): string {
    $colors = ['#6366f1','#8b5cf6','#ec4899','#06b6d4','#10b981','#f59e0b','#ef4444','#3b82f6'];
    return $colors[abs(crc32($email)) % count($colors)];
}

/**
 * Human-readable time ago
 */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}

renderFooter(['sidebar.js']);
?>
