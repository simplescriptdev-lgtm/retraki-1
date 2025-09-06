// Клієнтська навігація (SPA) + модалки для аудиту видалень

function loadPage(name) {
  fetch('/pages/' + name + '.php')
    .then(r => r.text())
    .then(html => {
      const content = document.getElementById('content');
      content.innerHTML = html;
      document.querySelectorAll('.nav-link').forEach(x => {
        x.classList.toggle('active', x.getAttribute('data-page') === name);
      });
      if (window.afterPageLoad) window.afterPageLoad(name);
    });
}

document.addEventListener('DOMContentLoaded', () => {
  // Меню
  document.querySelectorAll('.nav-link').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const page = a.getAttribute('data-page');
      if (page) loadPage(page);
    });
  });

  // Автовідкриття вкладки з ?open=
  const params = new URLSearchParams(location.search);
  const open = params.get('open');
  if (open) loadPage(open);
});

/* ============================ */
/*  Глобальні обробники модалки */
/* ============================ */

// Безпечне екранування HTML
function escapeHtml(str) {
  return String(str)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'", '&#039;');
}

// Відкриття модалки з деталями видаленого товару (подієва делегація)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.open-deleted-details');
  if (!btn) return;

  const id = btn.getAttribute('data-audit-id');
  const holder = document.getElementById('audit-json-' + id);
  if (!holder) return;

  let data = {};
  try { data = JSON.parse(holder.textContent || '{}') || {}; } catch(_) {}

  const body = document.getElementById('deletedItemBody');
  const modal = document.getElementById('deletedItemModal');
  if (!body || !modal) return;

  const img = (data.photo && typeof data.photo === 'string')
    ? '<img src="' + data.photo + '" alt="Фото товару" style="width:260px;max-height:220px;object-fit:cover;border:1px solid #1f2937;border-radius:8px;background:#0a0f1e">'
    : '<div class="badge">Немає фото</div>';

  body.innerHTML = `
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      <div>${img}</div>
      <div style="flex:1;min-width:240px">
        <p><b>Назва:</b> ${escapeHtml(data.name || '—')}</p>
        <p><b>Бренд:</b> ${escapeHtml(data.brand || '—')}</p>
        <p><b>Артикул:</b> ${escapeHtml(data.sku || '—')}</p>
        <p><b>Сектор:</b> ${escapeHtml(data.sector || '—')}</p>
        <p><b>Кількість на момент видалення:</b> ${Number.isFinite(+data.qty) ? +data.qty : '—'}</p>
        <p><b>Створено:</b> ${escapeHtml(data.created_at || '—')}</p>
        <p><b>Нотатки:</b><br>${escapeHtml(data.notes || '—').replace(/\n/g,'<br>')}</p>
      </div>
    </div>
  `;

  modal.style.display = 'flex';
});

// Закриття модалки (кнопка)
document.addEventListener('click', (e) => {
  if (e.target.id === 'modalCloseBtn' || e.target.closest('#modalCloseBtn')) {
    const modal = document.getElementById('deletedItemModal');
    if (modal) modal.style.display = 'none';
  }
});

// Закриття модалки (клік по фону)
document.addEventListener('click', (e) => {
  if (e.target && e.target.id === 'deletedItemModal') {
    e.target.style.display = 'none';
  }
});
