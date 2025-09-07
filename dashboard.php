<?php
// dashboard.php — головний екран
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
$user = $_SESSION['user'];

// Базовий префікс шляху (щоб все працювало навіть якщо проект у підпапці)
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ретраки — Дашборд</title>

  <!-- Спочатку старі стилі (якщо є) -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=1">
  <!-- Потім нова тема, щоб перекривала старі -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/theme.css?v=8">

  <!-- Глобальний BASE для скриптів/посилань -->
  <script>window.BASE = <?= json_encode($BASE, JSON_UNESCAPED_SLASHES) ?>;</script>
  <!-- Основний SPA-скрипт із делегуванням кліків та мобільним меню -->
  <script defer src="<?= $BASE ?>/assets/js/scripts.js?v=12"></script>
</head>
<body>

  <!-- Топбар (видимий на мобільних) -->
  <header class="topbar">
    <button id="menuToggle" class="hamburger" aria-label="Меню" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="brand">Ретраки — інвентаризація</div>
    <div class="topbar-right" style="margin-left:auto; display:flex; align-items:center; gap:12px;">
      <span class="user">👤 <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a class="btn secondary" href="<?= $BASE ?>/auth/logout.php">Вийти</a>
    </div>
  </header>

  <!-- Бекдроп для офф-канвас меню -->
  <div class="backdrop" id="backdrop"></div>

  <div class="app">
    <!-- Бокове меню -->
    <aside class="sidebar" id="sidebar">
      <div class="brand" style="margin:6px 6px 14px; font-weight:800;">Ретраки</div>
      <nav>
        <a href="#" data-page="inventory" class="nav-link active">Залишки</a>
        <a href="#" data-page="cart" class="nav-link">Корзина</a>
        <a href="#" data-page="history" class="nav-link">Історія</a>

        <?php if (($user['role'] ?? '') === 'manager'): ?>
          <div class="divider"></div>
          <a href="#" data-page="add_item" class="nav-link">Додати товар</a>
          <a href="#" data-page="manage_users" class="nav-link">Користувачі</a>
          <a href="#" data-page="manage_locations" class="nav-link">Локації</a>
          <a href="#" data-page="audit_items" class="nav-link">Історія змін та видалення товару</a>
          <a href="#" data-page="manage_categories" class="nav-link">Категорії</a>
          <a href="#" data-page="import_export" class="nav-link">Імпорт / Експорт</a>
        <?php endif; ?>
      </nav>
    </aside>

    <!-- Контент -->
    <main class="content">
      <div id="content">
        <?php include __DIR__ . '/pages/inventory.php'; ?>
      </div>
    </main>
  </div>
</body>
</html>
