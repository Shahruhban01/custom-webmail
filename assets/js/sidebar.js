/**
 * MailFlow – Sidebar toggle for mobile
 */
const sidebar        = document.querySelector('.sidebar');
const overlay        = document.querySelector('.sidebar-overlay');
const menuBtn        = document.querySelector('.topbar-menu-btn');
const closeBtns      = document.querySelectorAll('.sidebar-close-btn');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

menuBtn?.addEventListener('click', openSidebar);
overlay?.addEventListener('click', closeSidebar);
closeBtns.forEach(b => b.addEventListener('click', closeSidebar));

// Mark active nav link
const path = window.location.pathname;
document.querySelectorAll('.sidebar-nav a').forEach(link => {
  if (path.includes(link.getAttribute('href')?.split('/').pop())) {
    link.classList.add('active');
  }
});
