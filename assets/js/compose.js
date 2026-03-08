/**
 * MailFlow – Compose page logic
 * Rich editor toolbar, file chips, preview modal, template loader
 */

// ── Rich editor toolbar commands ──
document.querySelectorAll('.toolbar-btn[data-cmd]').forEach(btn => {
  btn.addEventListener('click', () => {
    const cmd = btn.dataset.cmd;
    const val = btn.dataset.val || null;
    document.execCommand(cmd, false, val);
    document.getElementById('message-editor').focus();
  });
});

// Sync hidden textarea for form submit
const editor = document.getElementById('message-editor');
const msgHidden = document.getElementById('message-hidden');
if (editor && msgHidden) {
  editor.addEventListener('input', () => {
    const isHtml = document.getElementById('html-mode')?.checked;
    msgHidden.value = isHtml ? editor.innerHTML : editor.innerText;
  });
}

// ── File upload chips ──
const fileInput   = document.getElementById('file-input');
const fileList    = document.getElementById('file-list');
let selectedFiles = new DataTransfer();

fileInput?.addEventListener('change', () => {
  Array.from(fileInput.files).forEach(file => {
    // Validate size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert(`"${file.name}" exceeds 5MB limit.`); return;
    }
    selectedFiles.items.add(file);
  });
  fileInput.files = selectedFiles.files;
  renderChips();
});

function renderChips() {
  fileList.innerHTML = '';
  Array.from(selectedFiles.files).forEach((file, i) => {
    const chip = document.createElement('div');
    chip.className = 'attachment-chip';
    chip.innerHTML = `
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
      </svg>
      <span class="truncate" style="max-width:120px">${escHtml(file.name)}</span>
      <span class="text-xs text-muted">${(file.size/1024).toFixed(0)}KB</span>
      <button type="button" onclick="removeFile(${i})" title="Remove">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>`;
    fileList.appendChild(chip);
  });
}

window.removeFile = function(idx) {
  const dt = new DataTransfer();
  Array.from(selectedFiles.files).forEach((f, i) => { if (i !== idx) dt.items.add(f); });
  selectedFiles = dt;
  fileInput.files = dt.files;
  renderChips();
};

// ── Preview modal ──
window.openPreview = function() {
  const fields = {
    from:    document.getElementById('sender-email')?.value,
    name:    document.getElementById('sender-name')?.value,
    to:      document.getElementById('to')?.value,
    cc:      document.getElementById('cc')?.value,
    bcc:     document.getElementById('bcc')?.value,
    subject: document.getElementById('subject')?.value,
    body:    document.getElementById('message-hidden')?.value || editor?.innerText,
  };
  document.getElementById('prev-from').textContent    = `${fields.name} <${fields.from}>`;
  document.getElementById('prev-to').textContent      = fields.to;
  document.getElementById('prev-cc').textContent      = fields.cc || '—';
  document.getElementById('prev-subject').textContent = fields.subject;
  document.getElementById('prev-body').innerHTML      = fields.body.replace(/\n/g,'<br>');
  document.querySelector('.preview-modal').classList.add('open');
};
window.closePreview = function() {
  document.querySelector('.preview-modal').classList.remove('open');
};

// ── Template loader ──
window.loadTemplate = function(select) {
  const tmpl = window.TEMPLATES?.[select.value];
  if (!tmpl) return;
  document.getElementById('subject').value = tmpl.subject;
  if (editor) { editor.innerText = tmpl.body; msgHidden.value = tmpl.body; }
};

// ── CC/BCC toggle ──
document.getElementById('toggle-cc')?.addEventListener('click', () => {
  const row = document.getElementById('cc-row');
  row.classList.toggle('hidden');
  if (!row.classList.contains('hidden')) document.getElementById('cc').focus();
});
document.getElementById('toggle-bcc')?.addEventListener('click', () => {
  const row = document.getElementById('bcc-row');
  row.classList.toggle('hidden');
  if (!row.classList.contains('hidden')) document.getElementById('bcc').focus();
});

// ── HTML mode toggle ──
document.getElementById('html-mode')?.addEventListener('change', function() {
  if (this.checked) {
    editor.innerHTML = `<p>Write <strong>HTML</strong> here...</p>`;
  } else {
    editor.innerText = editor.innerText;
  }
  msgHidden.value = editor.innerHTML;
});

// ── Escape helper ──
function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
