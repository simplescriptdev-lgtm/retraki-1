<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
$user = $_SESSION['user'];

// Базовий префікс (якщо сайт не у корені)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Яку сторінку відкрити спочатку (підтримка ?open=...)
$open = $_GET['open'] ?? 'inventory';
$allowed = [
  'inventory'         => '/pages/inventory.php',
  'cart'              => '/pages/cart.php',
  'history'           => '/pages/history.php',
  'add_item'          => '/pages/add_item.php',
  'manage_users'      => '/pages/manage_users.php',
  'manage_locations'  => '/pages/manage_locations.php',
  'audit_items'       => '/pages/audit_items.php',
  'manage_categories' => '/pages/manage_categories.php',
  'import_export'     => '/pages/import_export.php',
  'item'              => '/pages/item.php',
  'edit_item'         => '/pages/edit_item.php',
];
$initial_page = $allowed[$open] ?? $allowed['inventory'];
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ретраки — Дашборд</title>

  <!-- Базові стилі -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=2">
  <!-- Тема/адаптив/гриди -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/theme.css?v=102">

  <script>window.BASE = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;</script>
  <script defer src="<?= $BASE ?>/assets/js/scripts.js?v=102"></script>
</head>
<body>

  <!-- Topbar (видимий на всіх екранах) -->
  <header class="topbar">
    <button id="menuToggle" class="hamburger" aria-label="Меню" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <div class="brand">Ретраки — інвентаризація</div>

    <!-- Глобальний пошук: відкриває inventory з параметром q -->
    <form id="global-search" class="searchbar" action="<?= $BASE ?>/dashboard.php" method="get" style="margin-left:auto;">
      <input type="hidden" name="open" value="inventory">
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             placeholder="Пошук по назві / артикулу / бренду / сектору / категорії…">
      <button class="btn secondary" type="submit">Пошук</button>
    </form>

    <div class="topbar-right" style="display:flex;align-items:center;gap:12px;">
      <span class="user">👤 <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a class="btn" href="<?= $BASE ?>/auth/logout.php">Вийти</a>
    </div>
  </header>

  <!-- Затемнення під виїжджаючим сайдбаром -->
  <div class="backdrop" id="backdrop"></div>

  <div class="app">
    <!-- Ліве меню -->
    <aside class="sidebar" id="sidebar">
      <div class="brand" style="margin:6px 6px 14px;font-weight:800;">Ретраки</div>
      <nav>
        <a href="#" class="nav-link <?= $open==='inventory'?'active':'' ?>" data-page="inventory">Залишки</a>
        <a href="#" class="nav-link <?= $open==='cart'?'active':'' ?>" data-page="cart">Корзина</a>
        <a href="#" class="nav-link <?= $open==='history'?'active':'' ?>" data-page="history">Історія</a>

        <?php if (($user['role'] ?? '') === 'manager'): ?>
          <div class="divider"></div>
          <a href="#" class="nav-link <?= $open==='add_item'?'active':'' ?>" data-page="add_item">Додати товар</a>
          <a href="#" class="nav-link <?= $open==='manage_users'?'active':'' ?>" data-page="manage_users">Користувачі</a>
          <a href="#" class="nav-link <?= $open==='manage_locations'?'active':'' ?>" data-page="manage_locations">Локації</a>
          <a href="#" class="nav-link <?= $open==='audit_items'?'active':'' ?>" data-page="audit_items">Історія змін та видалення товару</a>
          <a href="#" class="nav-link <?= $open==='manage_categories'?'active':'' ?>" data-page="manage_categories">Категорії</a>
          <a href="#" class="nav-link <?= $open==='import_export'?'active':'' ?>" data-page="import_export">Імпорт / Експорт</a>
        <?php endif; ?>
      </nav>
    </aside>

    <!-- Контент -->
    <main class="content">
      <div class="container" id="content">
        <?php
          // Початковий рендер потрібної сторінки (щоб було SEO/перезапуск без SPA)
          // inventory.php вже читає $_GET['q'], $_GET['cat'] тощо.
          include __DIR__ . $initial_page;
        ?>
      </div>
    </main>
  </div>

  <script>
  // Підключення SPA для глобального пошуку (без повного перезавантаження)
  document.addEventListener('DOMContentLoaded', () => {
    const gForm = document.getElementById('global-search');
    if (gForm) {
      gForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const q = gForm.querySelector('input[name="q"]').value.trim();
        const query = q ? ('q=' + encodeURIComponent(q)) : '';
        if (typeof window.loadPage === 'function') {
          // SPA-навігація у «Залишки»
          window.loadPage('inventory', query);
        } else {
          // запасний варіант
          window.location.href = '<?= $BASE ?>/dashboard.php?open=inventory' + (query ? '&' + query : '');
        }
      });
    }
  });
  </script>
</body>
</html>
