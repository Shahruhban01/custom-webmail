/**
 * MailFlow – Global JS utilities
 */

// Auto-dismiss flash alerts
document.querySelectorAll('.alert[data-autodismiss]').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(-6px)';
    setTimeout(() => el.remove(), 400);
  }, 4000);
});

// Password visibility toggle
document.querySelectorAll('.password-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.input-icon-wrap').querySelector('input');
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.querySelector('.eye-icon').innerHTML = isPass
      ? `<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  });
});

// Password strength meter
const pwInput = document.getElementById('reg-password');
const strengthFill = document.getElementById('strength-fill');
const strengthLabel = document.getElementById('strength-label');

if (pwInput && strengthFill) {
  pwInput.addEventListener('input', () => {
    const pw = pwInput.value;
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const levels = [
      { pct: '0%',   cls: '',                label: '' },
      { pct: '25%',  cls: 'strength-weak',   label: 'Weak' },
      { pct: '50%',  cls: 'strength-fair',   label: 'Fair' },
      { pct: '75%',  cls: 'strength-good',   label: 'Good' },
      { pct: '100%', cls: 'strength-strong', label: 'Strong' },
    ];
    const lvl = levels[score];
    strengthFill.style.width     = lvl.pct;
    strengthFill.className       = 'strength-fill ' + lvl.cls;
    if (strengthLabel) strengthLabel.textContent = lvl.label;
  });
}
