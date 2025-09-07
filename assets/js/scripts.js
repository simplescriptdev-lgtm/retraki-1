// SPA + модалки + мобільне меню — стабільна версія
(() => {
  const BASE = window.BASE || "";
  const sidebar  = document.getElementById("sidebar");
  const backdrop = document.getElementById("backdrop");
  const burger   = document.getElementById("menuToggle");
  const MQ = 992;

  function openMenu(){
    if(!sidebar||!backdrop||!burger) return;
    sidebar.classList.add("open");
    backdrop.classList.add("show");
    burger.classList.add("is-open");
    burger.setAttribute("aria-expanded","true");
    document.body.style.overflow="hidden";
  }
  function closeMenu(){
    if(!sidebar||!backdrop||!burger) return;
    sidebar.classList.remove("open");
    backdrop.classList.remove("show");
    burger.classList.remove("is-open");
    burger.setAttribute("aria-expanded","false");
    document.body.style.overflow="";
  }
  function loadPage(name, query=""){
    const url = `${BASE}/pages/${name}.php${query?("?"+query):""}`;
    fetch(url)
      .then(r => { if(!r.ok) throw new Error(`HTTP ${r.status} ${url}`); return r.text(); })
      .then(html => {
        const content = document.getElementById("content");
        if (!content) return;
        content.innerHTML = html;

        document.querySelectorAll(".nav-link[data-page]").forEach(x=>{
          x.classList.toggle("active", x.getAttribute("data-page")===name && !x.closest("#content"));
        });

        if (window.afterPageLoad) window.afterPageLoad(name);
      })
      .catch(err => console.error("loadPage error:", err));
  }

  // Делегування кліків по SPA-посиланнях
  document.addEventListener("click", e => {
    const el = e.target.closest("a.nav-link,button.nav-link");
    if (!el) return;

    const page = el.getAttribute("data-page");
    if (!page) return; // звичайні посилання/кнопки не чіпаємо

    e.preventDefault();
    const query = el.getAttribute("data-query") || "";
    loadPage(page, query);
    if (window.innerWidth < MQ) closeMenu();
  });

  // Мобільне меню
  burger?.addEventListener("click", e => { e.preventDefault(); sidebar?.classList.contains("open") ? closeMenu() : openMenu(); });
  backdrop?.addEventListener("click", closeMenu);
  document.addEventListener("keydown", e => { if (e.key === "Escape") closeMenu(); });
  window.addEventListener("resize", () => { if (window.innerWidth >= MQ) closeMenu(); });

  // Автовідкриття з ?open=
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

  /* ====== Модалка деталей видаленого товару (з аудиту) ====== */
  function escapeHtml(str){
    return String(str)
      .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
      .replaceAll('"',"&quot;").replaceAll("'","&#039;");
  }
  document.addEventListener("click", e => {
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
      ? `<img src="${data.photo}" alt="Фото товару" style="width:260px;max-height:220px;object-fit:cover;border:1px solid #1f2937;border-radius:8px;background:#0a0f1e">`
      : '<div class="badge">Немає фото</div>';

    body.innerHTML = `
      <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
        <div>${img}</div>
        <div style="flex:1;min-width:240px">
          <p><b>Назва:</b> ${escapeHtml(data.name || "—")}</p>
          <p><b>Бренд:</b> ${escapeHtml(data.brand || "—")}</p>
          <p><b>Артикул:</b> ${escapeHtml(data.sku || "—")}</p>
          <p><b>Сектор:</b> ${escapeHtml(data.sector || "—")}</p>
          <p><b>Кількість:</b> ${Number.isFinite(+data.qty) ? +data.qty : "—"}</p>
          <p><b>Створено:</b> ${escapeHtml(data.created_at || "—")}</p>
          <p><b>Нотатки:</b><br>${escapeHtml(data.notes || "—").replace(/\n/g, "<br>")}</p>
        </div>
      </div>`;
    modal.style.display = "flex";
  });
  document.addEventListener("click", e => {
    if (e.target.id === "modalCloseBtn" || e.target.closest("#modalCloseBtn")) {
      const modal = document.getElementById("deletedItemModal");
      if (modal) modal.style.display = "none";
    }
    if (e.target && e.target.id === "deletedItemModal") e.target.style.display = "none";
  });
})();
