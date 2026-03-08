<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    validateCsrf();
    $did  = (int)$_POST['delete_id'];
    $stmt = $db->prepare("SELECT attachments FROM webmail_emails WHERE id=? AND user_id=?");
    $stmt->execute([$did, $userId]);
    $row = $stmt->fetch();
    if ($row) {
        // Delete attachment files
        if ($row['attachments']) {
            foreach (json_decode($row['attachments'], true) as $path) {
                if (file_exists($path)) unlink($path);
            }
        }
        $db->prepare("DELETE FROM webmail_emails WHERE id=? AND user_id=?")->execute([$did, $userId]);
        setFlash('success', 'Email deleted.');
    }
    redirect(APP_URL . '/dashboard/sent.php');
}

// Filters
$filter  = $_GET['filter'] ?? 'all';
$search  = clean($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = "WHERE e.user_id = :uid";
$params = [':uid' => $userId];

if ($filter === 'attachments') { $where .= " AND e.attachments IS NOT NULL"; }
elseif (in_array($filter, ['sent','failed','draft','scheduled'])) {
    $where .= " AND e.status = :status"; $params[':status'] = $filter;
}

if ($search) {
    $where .= " AND (e.subject LIKE :q OR e.receiver_email LIKE :q OR e.message LIKE :q)";
    $params[':q'] = "%{$search}%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM webmail_emails e $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = (int)ceil($total / $perPage);

$stmt = $db->prepare("SELECT * FROM webmail_emails e $where ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$emails = $stmt->fetchAll();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('Sent Mail');
?>
<div class="app-shell">
  <?php renderSidebar($filter === 'attachments' ? 'attachments' : 'sent'); ?>
  <div class="main-content">
    <header class="topbar">
      <button class="topbar-menu-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <form class="topbar-search" method="GET">
        <span class="search-icon">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Search emails…">
        <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= clean($filter) ?>"><?php endif; ?>
      </form>
      <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="location.href='<?= APP_URL ?>/dashboard/compose.php'">
          + Compose
        </button>
      </div>
    </header>

    <main class="page-body">
      <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" data-autodismiss><?= clean($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
        <div>
          <h1 class="page-title">
            <?= $filter === 'attachments' ? 'Attachments' : ($filter === 'all' ? 'All Emails' : ucfirst($filter)) ?>
          </h1>
          <p class="page-subtitle"><?= $total ?> email<?= $total !== 1 ? 's' : '' ?> found</p>
        </div>
        <!-- Filter tabs -->
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          <?php
          $tabs = [
            ['all',       'All'],
            ['sent',      'Sent'],
            ['draft',     'Drafts'],
            ['failed',    'Failed'],
            ['scheduled', 'Scheduled'],
          ];
          foreach ($tabs as [$val, $lbl]):
          ?>
          <a href="?filter=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
             class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $lbl ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <?php if (empty($emails)): ?>
        <div style="text-align:center;padding:3.5rem 1rem">
          <div style="font-size:2.5rem;margin-bottom:.75rem">📭</div>
          <p style="color:var(--muted)">No emails found<?= $search ? " for \"$search\"" : '' ?>.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>To</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th style="width:100px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $priorityBadge = ['high'=>'danger','normal'=>'muted','low'=>'success'];
              $statusBadge   = ['sent'=>'success','failed'=>'danger','draft'=>'warning','scheduled'=>'info'];
              foreach ($emails as $e):
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <div class="avatar avatar-sm" style="background:<?= avatarColor($e['receiver_email']) ?>;font-size:.7rem">
                      <?= strtoupper(substr($e['receiver_email'],0,1)) ?>
                    </div>
                    <span class="truncate" style="max-width:180px"><?= clean($e['receiver_email']) ?></span>
                  </div>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:.4rem">
                    <?php if ($e['attachments']): ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2">
                      <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                    </svg>
                    <?php endif; ?>
                    <span class="truncate" style="max-width:220px"><?= clean($e['subject']) ?></span>
                  </div>
                </td>
                <td><span class="badge badge-<?= $priorityBadge[$e['priority']] ?? 'muted' ?>"><?= ucfirst($e['priority']) ?></span></td>
                <td><span class="badge badge-<?= $statusBadge[$e['status']] ?? 'muted' ?>"><?= ucfirst($e['status']) ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, Y g:i A', strtotime($e['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:.3rem">
                    <a href="<?= APP_URL ?>/dashboard/view_email.php?id=<?= $e['id'] ?>"
                       class="btn btn-ghost btn-icon btn-sm" title="View">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </a>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete this email?')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                      <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="Delete"
                              style="color:var(--danger)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"/>
                          <path d="M19 6l-1 14H6L5 6"/>
                          <path d="M10 11v6"/><path d="M14 11v6"/>
                          <path d="M9 6V4h6v2"/>
                        </svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;border-top:1px solid var(--border)">
          <span class="text-sm text-muted">Page <?= $page ?> of <?= $pages ?></span>
          <div style="display:flex;gap:.3rem">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="?page=<?= $p ?>&filter=<?= $filter ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="btn btn-sm <?= $p===$page ? 'btn-primary' : 'btn-secondary' ?>"><?= $p ?></a>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php
function avatarColor(string $email): string {
    $colors = ['#6366f1','#8b5cf6','#ec4899','#06b6d4','#10b981','#f59e0b','#ef4444','#3b82f6'];
    return $colors[abs(crc32($email)) % count($colors)];
}
renderFooter(['sidebar.js']);
?>
