// ===== SPA з підтримкою query (?id=...) =====
function loadPage(name, query = "") {
  const url = "/pages/" + name + ".php" + (query ? ("?" + query) : "");
  fetch(url)
    .then(r => r.text())
    .then(html => {
      const content = document.getElementById("content");
      content.innerHTML = html;
      // підсвітка активного пункту меню (зліва)
      document.querySelectorAll(".nav-link[data-page]").forEach(x => {
        x.classList.toggle("active", x.getAttribute("data-page") === name && !x.closest("#content"));
      });
      if (window.afterPageLoad) window.afterPageLoad(name);
    });
}

// Автовідкриття вкладки з ?open=
document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(location.search);
  const open = params.get("open");
  if (open) {
    let q = "";
    if (open === "item") {
      const id = params.get("id");
      if (id) q = "id=" + encodeURIComponent(id);
    }
    loadPage(open, q);
  }
});

// ===== ДЕЛЕГУВАННЯ: працює і для меню, і для кнопок усередині контенту =====
document.addEventListener("click", (e) => {
  const el = e.target.closest("a.nav-link, button.nav-link");
  if (!el) return;
  const page = el.getAttribute("data-page");
  if (!page) return;
  e.preventDefault();
  const query = el.getAttribute("data-query") || "";
  loadPage(page, query);
});

/* ============================ */
/*  Глобальні обробники модалки */
/* ============================ */

// Безпечне екранування HTML
function escapeHtml(str) {
  return String(str)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

// Відкриття модалки з деталями видаленого товару (подієва делегація)
document.addEventListener("click", (e) => {
  const btn = e.target.closest(".open-deleted-details");
  if (!btn) return;

  const id = btn.getAttribute("data-audit-id");
  const holder = document.getElementById("audit-json-" + id);
  if (!holder) return;

  let data = {};
  try { data = JSON.parse(holder.textContent || "{}") || {}; } catch(_) {}

  const body = document.getElementById("deletedItemBody");
  const modal = document.getElementById("deletedItemModal");
  if (!body || !modal) return;

  const img = (data.photo && typeof data.photo === "string")
    ? '<img src="' + data.photo + '" alt="Фото товару" style="width:260px;max-height:220px;object-fit:cover;border:1px solid #1f2937;border-radius:8px;background:#0a0f1e">'
    : '<div class="badge">Немає фото</div>';

  body.innerHTML = `
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      <div>${img}</div>
      <div style="flex:1;min-width:240px">
        <p><b>Назва:</b> ${escapeHtml(data.name || "—")}</p>
        <p><b>Бренд:</b> ${escapeHtml(data.brand || "—")}</p>
        <p><b>Артикул:</b> ${escapeHtml(data.sku || "—")}</p>
        <p><b>Сектор:</b> ${escapeHtml(data.sector || "—")}</p>
        <p><b>Кількість на момент видалення:</b> ${Number.isFinite(+data.qty) ? +data.qty : "—"}</p>
        <p><b>Створено:</b> ${escapeHtml(data.created_at || "—")}</p>
        <p><b>Нотатки:</b><br>${escapeHtml(data.notes || "—").replace(/\n/g,"<br>")}</p>
      </div>
    </div>
  `;

  modal.style.display = "flex";
});

// Закриття модалки кнопкою
document.addEventListener("click", (e) => {
  if (e.target.id === "modalCloseBtn" || e.target.closest("#modalCloseBtn")) {
    const modal = document.getElementById("deletedItemModal");
    if (modal) modal.style.display = "none";
  }
});

// Закриття модалки кліком по фону
document.addEventListener("click", (e) => {
  if (e.target && e.target.id === "deletedItemModal") {
    e.target.style.display = "none";
  }
});
