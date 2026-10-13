// KrishiDisha — Main JavaScript

document.addEventListener('DOMContentLoaded', () => {

  // ── Navbar scroll effect ──────────────────────────────
  const navbar = document.querySelector('.kd-navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    });
  }

  // ── Mobile sidebar toggle ─────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ── Animated counters ─────────────────────────────────
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length > 0) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    counters.forEach(c => observer.observe(c));
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.count);
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = Math.floor(current).toLocaleString();
      if (current >= target) clearInterval(timer);
    }, 16);
  }

  // ── Fade-up on scroll ─────────────────────────────────
  const fadeEls = document.querySelectorAll('.fade-up');
  if (fadeEls.length > 0) {
    const fadeObserver = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.15 });
    fadeEls.forEach(el => fadeObserver.observe(el));
  }

  // ── Auto-dismiss alerts ───────────────────────────────
  document.querySelectorAll('.alert-kd[data-autohide]').forEach(el => {
    setTimeout(() => el.remove(), parseInt(el.dataset.autohide) || 4000);
  });

  // ── Nutrition retention chart (simple bars) ───────────
  document.querySelectorAll('.retention-bar').forEach(bar => {
    const pct = parseFloat(bar.dataset.pct) || 0;
    bar.style.width = '0%';
    setTimeout(() => { bar.style.width = pct + '%'; bar.style.transition = 'width 1s ease'; }, 200);
  });

  // ── Confirm dialogs ───────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Form validation helpers ───────────────────────────
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
          field.style.borderColor = '#ef4444';
          valid = false;
        } else {
          field.style.borderColor = '';
        }
      });
      if (!valid) e.preventDefault();
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-filter-table]').forEach(input => input.addEventListener('input', () => {
    document.querySelectorAll('#' + input.dataset.filterTable + ' tbody tr').forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(input.value.toLowerCase()); });
  }));
  const modeInput = document.getElementById('modeInput');
  const setMode = mode => {
    if (!modeInput) return;
    modeInput.value = mode;
    document.getElementById('regionFields').style.display = mode === 'region' ? 'block' : 'none';
    document.getElementById('vitaminFields').style.display = mode === 'vitamin' ? 'block' : 'none';
    document.querySelectorAll('[data-mode]').forEach(button => { button.setAttribute('aria-pressed', String(button.dataset.mode === mode)); });
  };
  if (modeInput) setMode(modeInput.value);
  document.querySelectorAll('[data-mode]').forEach(button => button.addEventListener('click', () => setMode(button.dataset.mode)));
  document.querySelectorAll('[data-reject-id]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('rejectId').value = button.dataset.rejectId;
    document.getElementById('rejectModal').style.display = 'flex';
    document.querySelector('#rejectModal textarea').focus();
  }));
  document.querySelectorAll('[data-close-reject]').forEach(button => button.addEventListener('click', () => { document.getElementById('rejectModal').style.display = 'none'; }));
  const cook = document.getElementById('cook_sel');
  if (cook) cook.addEventListener('change', () => {
    const select = document.querySelector('[name="recipe_id"]'); select.value = '';
    select.querySelectorAll('option[data-cook]').forEach(option => { option.hidden = !!cook.value && option.dataset.cook !== cook.value; option.disabled = option.hidden; });
  });
  const chatBody = document.getElementById('chatBody');
  if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
  const chat = document.querySelector('.chat-input textarea');
  if (chat) chat.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); chat.form.requestSubmit(); } });
  document.querySelectorAll('[data-close-detail]').forEach(button => button.addEventListener('click', () => document.getElementById('cropDetailModal')?.remove()));
});
