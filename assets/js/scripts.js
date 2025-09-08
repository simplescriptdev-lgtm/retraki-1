(function () {
  const content = document.getElementById('content');
  const BASE = window.BASE || '';

  function pageUrl(page, query) {
    return `${BASE}/pages/${page}.php${query ? `?${query}` : ''}`;
  }
  function dashUrl(page, query) {
    const qs = `open=${encodeURIComponent(page)}${query ? `&${query}` : ''}`;
    return `${BASE}/dashboard.php?${qs}`;
  }

  // Головний SPA-лоадер
  window.loadPage = function (page, query = '', opts = {}) {
    const push = opts.push !== false; // за замовчуванням true

    fetch(pageUrl(page, query), { credentials: 'same-origin' })
      .then(r => r.text())
      .then(html => {
        content.innerHTML = html;

        // активний пункт меню
        document.querySelectorAll('.sidebar .nav-link').forEach(a => {
          a.classList.toggle('active', a.dataset.page === page);
        });

        if (push) {
          history.pushState({ page, query }, '', dashUrl(page, query));
        }

        // скрол нагору
        window.scrollTo({ top: 0, behavior: 'instant' });
      })
      .catch(() => {
        // запасний варіант — повний перехід
        window.location.href = dashUrl(page, query);
      });
  };

  // Делегація кліків по SPA-посиланнях
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.nav-link');
    if (!a) return;
    const page = a.dataset.page;
    if (!page) return;

    e.preventDefault();
    const query = a.dataset.query || '';
    loadPage(page, query);
  });

  // Back/Forward
  window.addEventListener('popstate', (e) => {
    const st = e.state;
    if (st && st.page) {
      loadPage(st.page, st.query || '', { push: false });
    }
  });

  // Бургер/бекдроп для мобайл
  const btn = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('backdrop');
  function setMenu(open) {
    sidebar?.classList.toggle('open', open);
    backdrop?.classList.toggle('show', open);
    btn?.classList.toggle('is-open', open);
  }
  btn?.addEventListener('click', () => setMenu(!sidebar.classList.contains('open')));
  backdrop?.addEventListener('click', () => setMenu(false));

  // Зафіксувати початковий state (щоб «Назад» працював коректно)
  const params = new URLSearchParams(location.search);
  const open = params.get('open') || 'inventory';
  const q = params.get('q') || '';
  const cat = params.get('cat') || '';
  const initialQuery =
    (q ? `q=${encodeURIComponent(q)}` : '') +
    (cat ? `${q ? '&' : ''}cat=${encodeURIComponent(cat)}` : '');
  history.replaceState({ page: open, query: initialQuery }, '', location.href);

  // Глобальний пошук у топбарі (якщо є)
  const gForm = document.getElementById('global-search');
  if (gForm) {
    gForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const qVal = gForm.querySelector('input[name="q"]').value.trim();
      const query = qVal ? ('q=' + encodeURIComponent(qVal)) : '';
      loadPage('inventory', query);
    });
  }
})();
