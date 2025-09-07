<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
$user = $_SESSION['user'];
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ретраки — Дашборд</title>

  <!-- 1) Спочатку базовий css -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=2">
  <!-- 2) Потім нова тема, щоб перекривала все зайве -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/theme.css?v=101">

  <script>window.BASE = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;</script>
  <script defer src="<?= $BASE ?>/assets/js/scripts.js?v=101"></script>
</head>
<body>

  <!-- Топбар для мобілки -->
  <header class="topbar">
    <button id="menuToggle" class="hamburger" aria-label="Меню" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="brand">Ретраки — інвентаризація</div>
    <div class="topbar-right" style="margin-left:auto;display:flex;align-items:center;gap:12px;">
      <span class="user">👤 <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a class="btn secondary" href="<?= $BASE ?>/auth/logout.php">Вийти</a>
    </div>
  </header>

  <div class="backdrop" id="backdrop"></div>

  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand" style="margin:6px 6px 14px;font-weight:800;">Ретраки</div>
      <nav>
        <a href="#" class="nav-link active" data-page="inventory">Залишки</a>
        <a href="#" class="nav-link" data-page="cart">Корзина</a>
        <a href="#" class="nav-link" data-page="history">Історія</a>

        <?php if (($user['role'] ?? '') === 'manager'): ?>
          <div class="divider"></div>
          <a href="#" class="nav-link" data-page="add_item">Додати товар</a>
          <a href="#" class="nav-link" data-page="manage_users">Користувачі</a>
          <a href="#" class="nav-link" data-page="manage_locations">Локації</a>
          <a href="#" class="nav-link" data-page="audit_items">Історія змін та видалення товару</a>
          <a href="#" class="nav-link" data-page="manage_categories">Категорії</a>
          <a href="#" class="nav-link" data-page="import_export">Імпорт / Експорт</a>
        <?php endif; ?>
      </nav>
    </aside>

    <main class="content">
      <div class="container" id="content">
        <?php include __DIR__ . '/pages/inventory.php'; ?>
      </div>
    </main>
  </div>
</body>
</html>
