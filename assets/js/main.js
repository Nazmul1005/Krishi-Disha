/* Local progressive enhancement: server-rendered pages remain useful without JavaScript. */
document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const sidebar = document.getElementById('sidebar');
  const menus = [...document.querySelectorAll('#sidebarToggle,[data-open-menu]')];
  const setMenu = open => {
    sidebar?.classList.toggle('open', open);
    menus.forEach(button => button.setAttribute('aria-expanded', String(open)));
    if (open) sidebar?.querySelector('a')?.focus();
  };
  menus.forEach(button => button.addEventListener('click', () => setMenu(!sidebar?.classList.contains('open'))));
  document.addEventListener('click', event => {
    if (sidebar?.classList.contains('open') && !sidebar.contains(event.target) && !menus.some(button => button.contains(event.target))) setMenu(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar?.classList.contains('open')) { setMenu(false); menus[0]?.focus(); }
  });
  document.querySelectorAll('[data-dismiss-alert]').forEach(button => button.addEventListener('click', () => button.closest('.alert-kd').remove()));
  document.querySelectorAll('button,a').forEach(control => {
    if (control.textContent.trim() || control.getAttribute('aria-label')) return;
    const symbol = control.querySelector('i');
    const labels = { 'fa-pen': 'Edit record', 'fa-trash': 'Withdraw or delete record', 'fa-comments': 'Open consultation messages', 'fa-check': 'Approve record', 'fa-ban': 'Suspend account', 'fa-times': 'Reject record', 'fa-xmark': 'Reject record' };
    const entry = Object.entries(labels).find(([name]) => symbol?.classList.contains(name));
    if (entry) control.setAttribute('aria-label', entry[1]);
  });

  // Keep labels useful in existing forms as well as the new components.
  document.querySelectorAll('form').forEach((form, formIndex) => {
    form.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach((field, fieldIndex) => {
      field.id ||= 'field-' + formIndex + '-' + fieldIndex;
      if (field.labels?.length) return;
      let label = field.previousElementSibling;
      if (label?.tagName !== 'LABEL') label = field.parentElement?.querySelector('label');
      if (label && !label.htmlFor) label.htmlFor = field.id;
      else if (!field.getAttribute('aria-label')) {
        const labels = { quantity_kg: 'Quantity (kg)', q: 'Search', category: 'Category', season: 'Season', quantity: 'Quantity', message: 'Message', status: 'Status', role: 'Role' };
        field.setAttribute('aria-label', labels[field.name] || field.placeholder || field.name.replaceAll('_', ' ') || 'Form field');
      }
    });
  });

  // A table keeps its semantic headings; narrow screens display labeled rows.
  document.querySelectorAll('.table-kd').forEach(table => {
    const headings = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
    table.dataset.responsive = 'true';
    table.querySelectorAll('tbody tr').forEach(row => [...row.children].forEach((cell, index) => { cell.dataset.label = headings[index] || ''; }));
    table.parentElement?.classList.add('table-scroll');
  });

  const dialog = document.getElementById('confirmDialog');
  let pendingAction = null;
  const approved = new WeakSet();
  if (dialog) dialog.addEventListener('close', () => {
    const pending = pendingAction; pendingAction = null;
    if (!pending) return;
    pending.trigger.focus();
    if (dialog.returnValue === 'confirm') {
      approved.add(pending.trigger);
      if (pending.form) pending.form.requestSubmit(pending.trigger);
      else pending.trigger.click();
    }
  });
  const askConfirmation = (event, trigger, form) => {
    if (approved.has(trigger)) { approved.delete(trigger); return; }
    event.preventDefault();
    if (!dialog?.showModal) { if (confirm(trigger.dataset.confirm)) { approved.add(trigger); form ? form.requestSubmit(trigger) : trigger.click(); } return; }
    pendingAction = { trigger, form };
    document.getElementById('confirmMessage').textContent = trigger.dataset.confirm;
    dialog.returnValue = 'cancel'; dialog.showModal();
  };
  document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
    if (event.submitter?.dataset.confirm) askConfirmation(event, event.submitter, form);
  }));
  document.querySelectorAll('a[data-confirm]').forEach(link => link.addEventListener('click', event => askConfirmation(event, link, null)));
  document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
    if (event.defaultPrevented || form.method === 'dialog' || form.id === 'farmEstimate') return;
    form.setAttribute('aria-busy', 'true');
    if (event.submitter) event.submitter.setAttribute('aria-busy', 'true');
    document.getElementById('pageLoading').hidden = false;
  }));
  window.addEventListener('pageshow', () => {
    document.getElementById('pageLoading').hidden = true;
    document.querySelectorAll('form[aria-busy],button[aria-busy]').forEach(control => control.removeAttribute('aria-busy'));
  });

  // Inline messages supplement native validity and are available to screen readers.
  const reportField = field => {
    const valid = field.validity.valid;
    field.setAttribute('aria-invalid', String(!valid));
    let error = document.getElementById(field.id + '-error');
    if (!valid) {
      if (!error) { error = document.createElement('p'); error.id = field.id + '-error'; error.className = 'field-error'; error.setAttribute('role', 'alert'); field.after(error); }
      error.textContent = field.validationMessage;
      const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean)); descriptions.add(error.id); field.setAttribute('aria-describedby', [...descriptions].join(' '));
    } else if (error) { error.remove(); const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(id => id !== error.id); field.setAttribute('aria-describedby', descriptions.join(' ')); }
  };
  document.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach(field => {
    field.addEventListener('invalid', () => reportField(field));
    field.addEventListener('blur', () => { if (field.value || field.getAttribute('aria-invalid') === 'true') reportField(field); });
    field.addEventListener('input', () => { if (field.getAttribute('aria-invalid') === 'true') reportField(field); });
  });
  document.querySelectorAll('[data-filter-table]').forEach(input => input.addEventListener('input', () => {
    document.getElementById(input.dataset.filterTable)?.querySelectorAll('tbody tr').forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(input.value.toLowerCase()); });
  }));
  document.querySelectorAll('[data-count]').forEach(el => { el.textContent = Number(el.dataset.count).toLocaleString(document.documentElement.lang === 'bn' ? 'bn-BD' : 'en-BD'); });
  document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
  document.querySelectorAll('.retention-bar').forEach(bar => { bar.style.width = Math.max(0, Math.min(100, Number(bar.dataset.pct) || 0)) + '%'; if (!reducedMotion) bar.style.transition = 'width .3s ease'; });

  const modeInput = document.getElementById('modeInput');
  const setMode = mode => {
    if (!modeInput) return;
    modeInput.value = mode;
    document.getElementById('regionFields').style.display = mode === 'region' ? 'block' : 'none';
    document.getElementById('vitaminFields').style.display = mode === 'vitamin' ? 'block' : 'none';
    document.querySelectorAll('[data-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.mode === mode)));
  };
  if (modeInput) setMode(modeInput.value);
  document.querySelectorAll('[data-mode]').forEach(button => button.addEventListener('click', () => setMode(button.dataset.mode)));
  const reject = document.getElementById('rejectModal');
  document.querySelectorAll('[data-reject-id]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('rejectId').value = button.dataset.rejectId;
    reject.returnValue = ''; reject.showModal(); reject.querySelector('textarea').focus();
  }));
  document.querySelectorAll('[data-close-reject]').forEach(button => button.addEventListener('click', () => reject.close()));
  const cropDetail = document.getElementById('cropDetailModal');
  if (cropDetail?.showModal) { if (cropDetail.open) cropDetail.close(); cropDetail.showModal(); }
  document.querySelectorAll('[data-close-detail]').forEach(button => button.addEventListener('click', () => cropDetail?.close()));
  const cook = document.getElementById('cook_sel');
  if (cook) cook.addEventListener('change', () => {
    const select = document.querySelector('[name="recipe_id"]'); select.value = '';
    select.querySelectorAll('option[data-cook]').forEach(option => { option.hidden = !!cook.value && option.dataset.cook !== cook.value; option.disabled = option.hidden; });
  });
  const chatBody = document.getElementById('chatBody'); if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
  const chat = document.querySelector('.chat-input textarea');
  if (chat) chat.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); chat.form.requestSubmit(); } });

  document.querySelectorAll('[role="tablist"]').forEach(list => {
    const tabs = [...list.querySelectorAll('[role="tab"]')];
    const activate = tab => tabs.forEach(item => { const active = item === tab; item.setAttribute('aria-selected', String(active)); item.tabIndex = active ? 0 : -1; const panel = document.getElementById(item.getAttribute('aria-controls')); if (panel) panel.hidden = !active; });
    tabs.forEach((tab, index) => { tab.addEventListener('click', () => activate(tab)); tab.addEventListener('keydown', event => { let next; if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length]; if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length]; if (event.key === 'Home') next = tabs[0]; if (event.key === 'End') next = tabs.at(-1); if (next) { event.preventDefault(); activate(next); next.focus(); } }); });
  });
});
