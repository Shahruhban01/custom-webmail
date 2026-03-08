<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];
$errors = [];

// Save new template
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $name    = clean($_POST['name'] ?? '');
        $subject = clean($_POST['subject'] ?? '');
        $body    = $_POST['body'] ?? '';
        if (!$name || !$subject || !$body) {
            $errors[] = 'All fields are required.';
        } else {
            $db->prepare("INSERT INTO webmail_email_templates (user_id,name,subject,body) VALUES (?,?,?,?)")
               ->execute([$userId, $name, $subject, $body]);
            setFlash('success', 'Template saved.');
            redirect(APP_URL . '/dashboard/templates.php');
        }
    } elseif ($action === 'delete') {
        $tid = (int)$_POST['template_id'];
        $db->prepare("DELETE FROM webmail_email_templates WHERE id=? AND user_id=?")->execute([$tid, $userId]);
        setFlash('success', 'Template deleted.');
        redirect(APP_URL . '/dashboard/templates.php');
    }
}

$stmt = $db->prepare("SELECT * FROM webmail_email_templates WHERE user_id IN (1,?) ORDER BY id");
$stmt->execute([$userId]);
$templates = $stmt->fetchAll();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('Templates');
?>
<div class="app-shell">
  <?php renderSidebar('templates'); ?>
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
        <h1 class="page-title">Email Templates</h1>
        <p class="page-subtitle">Reusable email templates for common scenarios</p>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:start">

        <!-- Template list -->
        <div>
          <div class="card">
            <div class="card-header">
              <h3 style="font-size:.9rem;font-weight:700">Saved Templates</h3>
              <span class="badge badge-primary"><?= count($templates) ?></span>
            </div>
            <?php if (empty($templates)): ?>
            <div class="card-body" style="text-align:center;color:var(--muted);padding:2rem">No templates yet.</div>
            <?php else: ?>
            <?php foreach ($templates as $t): ?>
            <div style="padding:.9rem 1.1rem;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
              <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:.875rem;margin-bottom:.15rem"><?= clean($t['name']) ?></div>
                <div class="text-xs text-muted truncate"><?= clean($t['subject']) ?></div>
              </div>
              <div style="display:flex;gap:.3rem;flex-shrink:0">
                <a href="<?= APP_URL ?>/dashboard/compose.php?template=<?= $t['id'] ?>"
                   class="btn btn-ghost btn-icon btn-sm" title="Use template">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </a>
                <form method="POST" onsubmit="return confirm('Delete template?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-icon btn-sm" style="color:var(--danger)" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14H6L5 6"/>
                    </svg>
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Create template -->
        <div class="card">
          <div class="card-header">
            <h3 style="font-size:.9rem;font-weight:700">Create New Template</h3>
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="save">
              <div class="form-group">
                <label class="form-label">Template Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Offer Letter" required>
              </div>
              <div class="form-group">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="Email subject" required>
              </div>
              <div class="form-group">
                <label class="form-label">Body</label>
                <textarea name="body" class="form-control" style="min-height:200px"
                          placeholder="Use [Name], [Company] etc. as placeholders" required></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-full">Save Template</button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php renderFooter(['sidebar.js']); ?>
