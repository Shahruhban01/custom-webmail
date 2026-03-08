<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM webmail_emails WHERE id=? AND user_id=?");
$stmt->execute([$id, $userId]);
$email = $stmt->fetch();

if (!$email) {
    setFlash('error', 'Email not found.');
    redirect(APP_URL . '/dashboard/sent.php');
}

$attachments = $email['attachments'] ? json_decode($email['attachments'], true) : [];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('View Email');
?>
<div class="app-shell">
  <?php renderSidebar('sent'); ?>
  <div class="main-content">
    <header class="topbar">
      <button class="topbar-menu-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div class="topbar-actions" style="margin-left:0;flex:1">
        <a href="<?= APP_URL ?>/dashboard/sent.php" class="btn btn-ghost btn-sm">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to Sent
        </a>
      </div>
    </header>

    <main class="page-body">
      <div style="max-width:780px">
        <div class="card">
          <div class="email-view-header">
            <div class="email-view-subject"><?= clean($email['subject']) ?></div>
            <div class="email-view-meta">
              <span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                From: <strong><?= clean($email['sender_name']) ?></strong> &lt;<?= clean($email['sender_email']) ?>&gt;
              </span>
              <span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                To: <strong><?= clean($email['receiver_email']) ?></strong>
              </span>
              <?php if ($email['cc']): ?>
              <span>CC: <?= clean($email['cc']) ?></span>
              <?php endif; ?>
              <?php if ($email['bcc']): ?>
              <span>BCC: <?= clean($email['bcc']) ?></span>
              <?php endif; ?>
              <span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?= date('F j, Y \a\t g:i A', strtotime($email['created_at'])) ?>
              </span>
              <span>
                <?php
                $sc = ['sent'=>'badge-success','failed'=>'badge-danger','draft'=>'badge-warning','scheduled'=>'badge-primary'];
                ?>
                <span class="badge <?= $sc[$email['status']] ?? 'badge-muted' ?>"><?= ucfirst($email['status']) ?></span>
                <span class="badge badge-muted"><?= ucfirst($email['priority']) ?> Priority</span>
              </span>
            </div>
          </div>

          <div class="email-view-body">
            <?php if ($email['is_html']): ?>
              <?= $email['message'] ?>
            <?php else: ?>
              <?= nl2br(clean($email['message'])) ?>
            <?php endif; ?>
          </div>

          <?php if (!empty($attachments)): ?>
          <div style="padding:1rem 1.5rem;border-top:1px solid var(--border)">
            <div class="text-sm font-semibold" style="margin-bottom:.6rem;color:var(--muted)">
              ATTACHMENTS (<?= count($attachments) ?>)
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
              <?php foreach ($attachments as $path):
                $fname = basename($path);
                $size  = file_exists($path) ? round(filesize($path)/1024, 1) . ' KB' : 'N/A';
              ?>
              <div class="attachment-chip">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                </svg>
                <span><?= clean($fname) ?></span>
                <span class="text-xs text-muted"><?= $size ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div style="padding:.85rem 1.5rem;border-top:1px solid var(--border);display:flex;gap:.5rem;justify-content:flex-end">
            <a href="<?= APP_URL ?>/dashboard/compose.php" class="btn btn-secondary btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 00-4-4H4"/>
              </svg>
              Forward / Resend
            </a>
            <form method="POST" action="<?= APP_URL ?>/dashboard/sent.php" style="display:inline"
                  onsubmit="return confirm('Delete this email?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="delete_id" value="<?= $email['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14H6L5 6"/>
                </svg>
                Delete
              </button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php renderFooter(['sidebar.js']); ?>
