<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';
requireAuth();

$db     = getDB();
$userId = $_SESSION['user_id'];
$user   = $_SESSION['user'];
$errors = []; $success = '';

// Load templates for JS
$tmplStmt = $db->prepare("SELECT * FROM webmail_email_templates WHERE user_id IN (1, ?) ORDER BY id");
$tmplStmt->execute([$userId]); $templates = $tmplStmt->fetchAll();

// Load user signature
$sigStmt = $db->prepare("SELECT signature FROM webmail_users WHERE id=?");
$sigStmt->execute([$userId]); $sigRow = $sigStmt->fetch();
$signature = $sigRow['signature'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $action       = $_POST['action'] ?? 'send'; // 'send' or 'draft'
    $senderName   = clean($_POST['sender_name'] ?? $user['name']);
    $senderEmail  = filter_var($_POST['sender_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $to           = filter_var($_POST['to'] ?? '', FILTER_VALIDATE_EMAIL);
    $cc           = $_POST['cc'] ?? '';
    $bcc          = $_POST['bcc'] ?? '';
    $subject      = clean($_POST['subject'] ?? '');
    $message      = $_POST['message'] ?? '';
    $isHtml       = !empty($_POST['is_html']);
    $priority     = $_POST['priority'] ?? 'normal';
    $scheduleDate = $_POST['scheduled_at'] ?? '';

    // Validate
    if (!$senderEmail) $errors[] = 'Invalid sender email.';
    if (!$to)          $errors[] = 'Invalid recipient email.';
    if (!$subject)     $errors[] = 'Subject is required.';
    if (!$message)     $errors[] = 'Message body cannot be empty.';

    // File upload handling
    $savedFiles = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $allowedExt = ALLOWED_EXTENSIONS;
        foreach ($_FILES['attachments']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['attachments']['size'][$i] > MAX_FILE_SIZE) {
                $errors[] = "File '{$_FILES['attachments']['name'][$i]}' exceeds 5MB."; continue;
            }
            $origName = basename($_FILES['attachments']['name'][$i]);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "File type '.{$ext}' is not allowed."; continue;
            }
            // Safe filename
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $dest     = UPLOAD_DIR . $safeName;
            if (move_uploaded_file($tmpName, $dest)) {
                $savedFiles[] = $dest;
            }
        }
    }

    if (empty($errors)) {
        $params = compact('senderName','senderEmail','to','cc','bcc','subject','message','isHtml','priority');
        $params['attachments'] = $savedFiles;

        $status = 'draft';
        if ($action === 'send') {
            if ($scheduleDate && strtotime($scheduleDate) > time()) {
                $status = 'scheduled';
            } else {
                $result = sendMail($params);
                $status = $result['success'] ? 'sent' : 'failed';
            }
        }

        $record = $params;
        $record['user_id']      = $userId;
        $record['saved_files']  = $savedFiles;
        $record['status']       = $status;
        $record['scheduled_at'] = ($status === 'scheduled') ? $scheduleDate : null;
        saveEmailRecord($record);

        if ($action === 'draft') {
            setFlash('success', 'Draft saved.');
        } elseif ($status === 'sent') {
            setFlash('success', 'Email sent successfully!');
        } elseif ($status === 'scheduled') {
            setFlash('info', 'Email scheduled for ' . date('M j, Y g:i A', strtotime($scheduleDate)));
        } else {
            setFlash('error', 'Email failed to send. Check server mail settings.');
        }
        redirect(APP_URL . '/dashboard/compose.php');
    }
}

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/footer.php';
renderHead('Compose');
?>
<div class="app-shell">
  <?php renderSidebar('compose'); ?>
  <div class="main-content">
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
        <input type="text" placeholder="Search emails…">
      </div>
      <div class="topbar-actions">
        <div class="topbar-user">
          <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#06b6d4)">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <span class="topbar-user-name"><?= clean($user['name']) ?></span>
        </div>
      </div>
    </header>

    <main class="page-body">
      <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" data-autodismiss><?= clean($flash['msg']) ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
        <div>
          <h1 class="page-title">Compose Email</h1>
          <p class="page-subtitle">Create and send professional emails</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <?php if (!empty($templates)): ?>
          <select class="form-control btn-sm" onchange="loadTemplate(this)" style="width:auto;min-width:160px">
            <option value="">Load Template…</option>
            <?php foreach ($templates as $t): ?>
            <option value="<?= $t['id'] ?>"><?= clean($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openPreview()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            Preview
          </button>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" class="compose-wrap" id="compose-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="message" id="message-hidden">
        <input type="hidden" name="action" id="form-action" value="send">

        <div class="card">
          <!-- From fields -->
          <div class="card-body" style="border-bottom:1px solid var(--border);padding-bottom:1rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
              <div class="form-group" style="margin:0">
                <label class="form-label">Sender Name</label>
                <input type="text" name="sender_name" id="sender-name" class="form-control"
                       value="<?= clean($_POST['sender_name'] ?? $user['name']) ?>" placeholder="Your Name" required>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Sender Email</label>
                <input type="email" name="sender_email" id="sender-email" class="form-control"
                       value="<?= clean($_POST['sender_email'] ?? DEFAULT_SENDER_EMAIL) ?>" placeholder="sender@example.com" required>
              </div>
            </div>
          </div>

          <!-- To / CC / BCC -->
          <div style="border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;padding:.7rem 1rem;gap:.75rem">
              <label style="font-size:.8rem;font-weight:600;color:var(--muted);min-width:28px">To</label>
                            <input type="email" name="to" id="to" class="form-control"
                     style="border:none;box-shadow:none;padding-left:0;flex:1"
                     value="<?= clean($_POST['to'] ?? '') ?>"
                     placeholder="recipient@example.com" required>
              <div style="display:flex;gap:.4rem;margin-left:auto">
                <button type="button" id="toggle-cc"  class="btn btn-ghost btn-sm">CC</button>
                <button type="button" id="toggle-bcc" class="btn btn-ghost btn-sm">BCC</button>
              </div>
            </div>

            <div id="cc-row" class="hidden" style="display:flex;align-items:center;padding:.55rem 1rem;gap:.75rem;border-top:1px solid var(--border)">
              <label style="font-size:.8rem;font-weight:600;color:var(--muted);min-width:28px">CC</label>
              <input type="text" name="cc" id="cc" class="form-control"
                     style="border:none;box-shadow:none;padding-left:0;flex:1"
                     placeholder="cc@example.com, another@example.com"
                     value="<?= clean($_POST['cc'] ?? '') ?>">
            </div>

            <div id="bcc-row" class="hidden" style="display:flex;align-items:center;padding:.55rem 1rem;gap:.75rem;border-top:1px solid var(--border)">
              <label style="font-size:.8rem;font-weight:600;color:var(--muted);min-width:28px">BCC</label>
              <input type="text" name="bcc" id="bcc" class="form-control"
                     style="border:none;box-shadow:none;padding-left:0;flex:1"
                     placeholder="bcc@example.com"
                     value="<?= clean($_POST['bcc'] ?? '') ?>">
            </div>

            <div style="display:flex;align-items:center;padding:.55rem 1rem;gap:.75rem;border-top:1px solid var(--border)">
              <label style="font-size:.8rem;font-weight:600;color:var(--muted);min-width:28px">Sub</label>
              <input type="text" name="subject" id="subject" class="form-control"
                     style="border:none;box-shadow:none;padding-left:0;flex:1"
                     placeholder="Email subject…"
                     value="<?= clean($_POST['subject'] ?? '') ?>" required>
            </div>
          </div>

          <!-- Toolbar + editor -->
          <div style="border-bottom:1px solid var(--border)">
            <div class="compose-toolbar">
              <button type="button" class="toolbar-btn" data-cmd="bold"           title="Bold"><b>B</b></button>
              <button type="button" class="toolbar-btn" data-cmd="italic"         title="Italic"><i>I</i></button>
              <button type="button" class="toolbar-btn" data-cmd="underline"      title="Underline"><u>U</u></button>
              <div class="toolbar-divider"></div>
              <button type="button" class="toolbar-btn" data-cmd="justifyLeft"    title="Align Left">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/>
                </svg>
              </button>
              <button type="button" class="toolbar-btn" data-cmd="justifyCenter" title="Center">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
              </button>
              <div class="toolbar-divider"></div>
              <button type="button" class="toolbar-btn" data-cmd="insertUnorderedList" title="Bullet List">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/>
                  <circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/>
                </svg>
              </button>
              <button type="button" class="toolbar-btn" data-cmd="insertOrderedList" title="Numbered List">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/>
                  <path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>
                </svg>
              </button>
              <div class="toolbar-divider"></div>
              <button type="button" class="toolbar-btn" data-cmd="foreColor" data-val="#6366f1" title="Purple text"
                      style="color:#6366f1;font-weight:800;font-size:1rem">A</button>
              <button type="button" class="toolbar-btn" data-cmd="foreColor" data-val="#ef4444" title="Red text"
                      style="color:#ef4444;font-weight:800;font-size:1rem">A</button>
              <div class="toolbar-divider"></div>
              <button type="button" class="toolbar-btn" data-cmd="createLink"
                      title="Insert Link" onclick="insertLink(event)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                  <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                </svg>
              </button>
              <div class="toolbar-divider"></div>
              <!-- HTML mode toggle -->
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.75rem;color:var(--muted);cursor:pointer;margin-left:.25rem">
                <input type="checkbox" id="html-mode" name="is_html" style="width:auto"
                       <?= !empty($_POST['is_html']) ? 'checked' : '' ?>>
                HTML
              </label>
            </div>

            <div id="message-editor" contenteditable="true"
                 data-placeholder="Write your message here…"><?= !empty($_POST['message']) ? clean($_POST['message']) : ($signature ? "\n\n\n-- \n" . clean($signature) : '') ?></div>
          </div>

          <!-- Bottom bar: attachments, options, actions -->
          <div class="card-body" style="padding:1rem 1.1rem">
            <div style="display:flex;align-items:flex-start;flex-wrap:wrap;gap:1rem">

              <!-- Attachments -->
              <div style="flex:1;min-width:200px">
                <label class="btn btn-ghost btn-sm" for="file-input" style="cursor:pointer">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                  </svg>
                  Attach Files
                </label>
                <input type="file" id="file-input" name="attachments[]" multiple class="hidden"
                       accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.gif,.zip,.xlsx,.csv">
                <div id="file-list" class="attachments-list"></div>
              </div>

              <!-- Priority & Schedule -->
              <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
                <div class="form-group" style="margin:0;min-width:120px">
                  <label class="form-label">Priority</label>
                  <select name="priority" class="form-control" style="padding:.5rem .75rem">
                    <option value="normal" <?= ($_POST['priority']??'normal')==='normal'?'selected':'' ?>>Normal</option>
                    <option value="high"   <?= ($_POST['priority']??'')==='high'  ?'selected':'' ?>>🔴 High</option>
                    <option value="low"    <?= ($_POST['priority']??'')==='low'   ?'selected':'' ?>>🟢 Low</option>
                  </select>
                </div>

                <div class="form-group" style="margin:0;min-width:200px">
                  <label class="form-label">Schedule Send (optional)</label>
                  <input type="datetime-local" name="scheduled_at" class="form-control"
                         style="padding:.5rem .75rem"
                         min="<?= date('Y-m-d\TH:i') ?>"
                         value="<?= clean($_POST['scheduled_at'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.1rem;flex-wrap:wrap">
              <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Draft
              </button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="openPreview()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                Preview
              </button>
              <button type="submit" class="btn btn-primary" id="send-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="22" y1="2" x2="11" y2="13"/>
                  <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Send Email
              </button>
            </div>
          </div>
        </div><!-- /.card -->
      </form>
    </main>
  </div>
</div>

<!-- Preview Modal -->
<div class="preview-modal" id="preview-modal">
  <div class="preview-modal-inner">
    <div class="preview-modal-header">
      <h3 style="font-size:1rem;font-weight:700">Email Preview</h3>
      <button class="btn btn-ghost btn-icon" onclick="closePreview()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="preview-modal-body">
      <div class="preview-meta">
        <span><strong>From:</strong> <span id="prev-from"></span></span>
        <span><strong>To:</strong> <span id="prev-to"></span></span>
        <span><strong>CC:</strong> <span id="prev-cc"></span></span>
        <span><strong>Subject:</strong> <span id="prev-subject"></span></span>
      </div>
      <hr class="preview-divider">
      <div class="preview-content" id="prev-body"></div>
    </div>
    <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.5rem">
      <button class="btn btn-secondary" onclick="closePreview()">Close</button>
      <button class="btn btn-primary" onclick="closePreview();document.getElementById('send-btn').click()">Send Now</button>
    </div>
  </div>
</div>

<!-- Template data for JS -->
<script>
window.TEMPLATES = {
  <?php foreach ($templates as $t): ?>
  "<?= $t['id'] ?>": {
    subject: <?= json_encode($t['subject']) ?>,
    body:    <?= json_encode($t['body']) ?>
  },
  <?php endforeach; ?>
};

function saveDraft() {
  document.getElementById('form-action').value = 'draft';
  // Sync editor content first
  const editor = document.getElementById('message-editor');
  document.getElementById('message-hidden').value = editor.innerText;
  document.getElementById('compose-form').submit();
}

function insertLink(e) {
  e.preventDefault();
  const url = prompt('Enter URL:');
  if (url) document.execCommand('createLink', false, url);
}

// Pre-fill message-hidden before submit
document.getElementById('compose-form').addEventListener('submit', function() {
  const editor  = document.getElementById('message-editor');
  const isHtml  = document.getElementById('html-mode').checked;
  document.getElementById('message-hidden').value = isHtml ? editor.innerHTML : editor.innerText;
});
</script>

<?php renderFooter(['sidebar.js','compose.js']); ?>
