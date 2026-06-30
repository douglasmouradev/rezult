/* Toasts */
const TOAST_ICONS = {
  success: 'ph-check-circle',
  error: 'ph-warning-circle',
  info: 'ph-info',
};

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.setAttribute('role', type === 'error' ? 'alert' : 'status');
  el.innerHTML = `<i class="ph ${TOAST_ICONS[type] || TOAST_ICONS.info}" aria-hidden="true"></i><span>${escapeHtml(message)}</span>`;
  container.appendChild(el);
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(100%)';
    setTimeout(() => el.remove(), 300);
  }, 4500);
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

/* Sidebar mobile + recolher desktop */
function initSidebar() {
  const shell = document.querySelector('.app-shell');
  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.menu-toggle');
  const overlay = document.querySelector('.sidebar-overlay');
  if (!sidebar || !toggle || !shell) return;

  const mq = window.matchMedia('(max-width: 1024px)');

  const isMobile = () => mq.matches;

  const setMobileOpen = (open) => {
    sidebar.classList.toggle('open', open);
    overlay?.classList.toggle('visible', open);
    document.body.classList.toggle('sidebar-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
  };

  const setDesktopCollapsed = (collapsed) => {
    shell.classList.toggle('sidebar-collapsed', collapsed);
    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    toggle.setAttribute('aria-label', collapsed ? 'Abrir menu' : 'Fechar menu');
  };

  const close = () => {
    if (isMobile()) {
      setMobileOpen(false);
    } else {
      setDesktopCollapsed(true);
    }
  };

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isMobile()) {
      setMobileOpen(!sidebar.classList.contains('open'));
    } else {
      setDesktopCollapsed(!shell.classList.contains('sidebar-collapsed'));
    }
  });

  overlay?.addEventListener('click', close);

  sidebar.querySelector('.sidebar-close')?.addEventListener('click', (e) => {
    e.preventDefault();
    close();
  });

  sidebar.querySelectorAll('.nav-item').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobile()) setMobileOpen(false);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  const applyInitialState = () => {
    if (isMobile()) {
      setMobileOpen(false);
    } else if (window.innerWidth < 1280) {
      setDesktopCollapsed(true);
    } else {
      setDesktopCollapsed(false);
    }
  };

  mq.addEventListener('change', () => {
    setMobileOpen(false);
    shell.classList.remove('sidebar-collapsed');
    document.body.style.overflow = '';
    document.body.classList.remove('sidebar-open');
    applyInitialState();
  });

  applyInitialState();
}

/* Confirmação */
document.querySelectorAll('[data-confirm]').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});

/* Toggle status lançamento */
document.querySelectorAll('.toggle-status').forEach((btn) => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    btn.disabled = true;
    btn.style.opacity = '0.5';
    const form = btn.closest('form');
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const data = await res.json().catch(() => ({}));
        const novo = data.status || (btn.classList.contains('badge-pago') ? 'pendente' : 'pago');
        btn.className = `badge badge-${novo} badge-toggle toggle-status`;
        btn.textContent = novo.charAt(0).toUpperCase() + novo.slice(1);
        showToast('Status atualizado', 'success');
      }
    } catch {
      showToast('Erro ao atualizar', 'error');
    } finally {
      btn.disabled = false;
      btn.style.opacity = '';
    }
  });
});

/* Loading em formulários */
document.querySelectorAll('form:not(.toggle-status-form):not(#empresa-form)').forEach((form) => {
  form.addEventListener('submit', () => {
    const btn = form.querySelector('[type="submit"].btn-primary, button.btn-primary');
    if (btn && !btn.dataset.noLoading) {
      btn.disabled = true;
      btn.dataset.originalText = btn.innerHTML;
      btn.innerHTML = '<i class="ph ph-circle-notch" style="animation:spin 0.8s linear infinite"></i> Aguarde...';
    }
  });
});

/* Utilitários */
function formatBRL(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
}

function dismissCookieBanner(banner) {
  try {
    localStorage.setItem('rezult_cookies_ok', '1');
  } catch (_) {
    /* modo privado / storage bloqueado */
  }
  banner.hidden = true;
  banner.classList.add('is-dismissed');
  banner.setAttribute('aria-hidden', 'true');
}

function initCookieBanner() {
  const banner = document.getElementById('cookie-banner');
  const btn = document.getElementById('cookie-accept');
  if (!banner || !btn) return;

  let accepted = false;
  try {
    accepted = localStorage.getItem('rezult_cookies_ok') === '1';
  } catch (_) {}

  if (accepted) {
    banner.hidden = true;
    banner.classList.add('is-dismissed');
    return;
  }

  banner.hidden = false;
  banner.classList.remove('is-dismissed');
  banner.setAttribute('aria-hidden', 'false');

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();
    dismissCookieBanner(banner);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
      || document.querySelector('input[name="_csrf"]')?.value;
    if (csrf) {
      try {
        await fetch('/privacidade/cookies', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: '_csrf=' + encodeURIComponent(csrf),
        });
      } catch (_) {}
    }
  });
}

function initTheme() {
  const root = document.documentElement;
  const saved = localStorage.getItem('rezult_theme');
  const btn = document.querySelector('.theme-toggle');
  const icon = btn?.querySelector('.ph');
  const apply = (dark) => {
    root.classList.toggle('theme-dark', dark);
    if (icon) {
      icon.classList.toggle('ph-moon', !dark);
      icon.classList.toggle('ph-sun', dark);
    }
  };
  apply(saved === 'dark');
  btn?.addEventListener('click', () => {
    const dark = !root.classList.contains('theme-dark');
    apply(dark);
    localStorage.setItem('rezult_theme', dark ? 'dark' : 'light');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCookieBanner();
  initTheme();
});

/* Spin animation inline */
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
