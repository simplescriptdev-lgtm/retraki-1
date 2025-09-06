// Клієнтська навігація і прості модалки/корзина
function loadPage(name) {
  fetch('/pages/' + name + '.php')
    .then(r => r.text())
    .then(html => {
      document.getElementById('content').innerHTML = html;
      document.querySelectorAll('.nav-link').forEach(x=>{
        x.classList.toggle('active', x.getAttribute('data-page') === name);
      });
      if (window.afterPageLoad) window.afterPageLoad(name);
    });
}

document.addEventListener('DOMContentLoaded', () => {
  // Кліки по меню
  document.querySelectorAll('.nav-link').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const page = a.getAttribute('data-page');
      if (page) loadPage(page);
    });
  });

  // Автовідкриття сторінки з параметра ?open=...
  const params = new URLSearchParams(location.search);
  const open = params.get('open');
  if (open) loadPage(open);
});
