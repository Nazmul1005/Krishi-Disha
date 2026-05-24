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

  // ── Profit Calculator ─────────────────────────────────
  const calcForm = document.getElementById('profitCalcForm');
  if (calcForm) {
    calcForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const area         = parseFloat(document.getElementById('land_area').value)    || 0;
      const investment   = parseFloat(document.getElementById('investment').value)   || 0;
      const yield_kg     = parseFloat(document.getElementById('yield_kg').value)     || 0;
      const market_price = parseFloat(document.getElementById('market_price').value) || 0;

      const gross    = yield_kg * market_price;
      const profit   = gross - investment;
      const roi      = investment > 0 ? ((profit / investment) * 100).toFixed(1) : 0;
      const per_acre = area > 0 ? (profit / area).toFixed(0) : 0;

      document.getElementById('result_gross').textContent  = '৳ ' + gross.toLocaleString('en-BD');
      document.getElementById('result_profit').textContent = '৳ ' + profit.toLocaleString('en-BD');
      document.getElementById('result_roi').textContent    = roi + '%';
      document.getElementById('result_acre').textContent   = '৳ ' + parseInt(per_acre).toLocaleString('en-BD');

      const resultBox = document.getElementById('calcResults');
      if (resultBox) { resultBox.style.display = 'block'; resultBox.classList.add('fade-up', 'visible'); }
      document.getElementById('result_profit').style.color = profit >= 0 ? '#10b981' : '#ef4444';
    });
  }

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
