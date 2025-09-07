<?php
// dashboard.php — головний екран
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ретраки — Дашборд</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script defer src="/assets/js/scripts.js"></script>
</head>
<body>
  <header class="topbar">
    <div>Ретраки — інвентаризація</div>
    <div class="topbar-right">
      <span class="user">👤 <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a class="btn" href="/auth/logout.php">Вийти</a>
    </div>
  </header>
  <div class="layout">
    <aside class="sidebar">
      <nav>
        <a href="#" data-page="inventory" class="nav-link active">Залишки</a>
        <a href="#" data-page="cart" class="nav-link">Корзина</a>
        <a href="#" data-page="history" class="nav-link">Історія</a>
        <?php if ($user['role'] === 'manager'): ?>
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
    <main id="content" class="content">
      <?php include __DIR__ . '/pages/inventory.php'; ?>
    </main>
  </div>
</body>
</html>
