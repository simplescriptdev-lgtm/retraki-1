<?php
// pages/inventory.php — список товарів (з категоріями) у новому темному стилі
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
$db   = db();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT i.*, c.name AS category_name
          FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
         WHERE i.deleted_at IS NULL";
$params = [];
if ($q !== '') {
  $sql .= " AND (i.name LIKE :q OR i.sku LIKE :q OR i.brand LIKE :q OR i.sector LIKE :q OR c.name LIKE :q)";
  $params[':q'] = "%{$q}%";
}
$sql .= " ORDER BY i.created_at DESC";

$st = $db->prepare($sql);
$st->execute($params);
$items = $st->fetchAll();

$ok  = $_SESSION['inv_ok']  ?? null; unset($_SESSION['inv_ok']);
$err = $_SESSION['inv_err'] ?? null; unset($_SESSION['inv_err']);
?>
<div class="toolbar">
  <h2>Залишки</h2>
  <form method="get" action="/pages/inventory.php" class="search">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Пошук по назві / артикулу / бренду / сектору / категорії…">
    <button class="btn secondary" type="submit">Пошук</button>
  </form>
</div>



<?php if ($ok):  ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid">
<?php foreach ($items as $it): ?>
  <div class="card">

    <div class="media">
      <?php if (!empty($it['photo'])): ?>
        <img src="<?= htmlspecialchars($it['photo']) ?>" alt="Фото товару">
      <?php else: ?>
        <img src="https://placehold.co/640x400?text=No+Photo" alt="Фото товару">
      <?php endif; ?>
    </div>

    <div class="body">
      <div class="title"><?= htmlspecialchars($it['name']) ?></div>

      <?php
        $qty = (int)$it['qty'];
        $qtyClass = $qty <= 3 ? 'danger' : ($qty <= 10 ? 'warn' : 'success');
      ?>
      <div class="meta">
        <span class="badge"><?= htmlspecialchars($it['category_name'] ?: 'Без категорії') ?></span>
        <span class="badge <?= $qtyClass ?>">К-сть: <?= $qty ?></span>
        <span class="badge">SKU: <?= htmlspecialchars($it['sku'] ?: '—') ?></span>
        <span class="badge"><?= htmlspecialchars($it['brand'] ?: '—') ?></span>
      </div>

      <div class="small">Сектор: <?= htmlspecialchars($it['sector'] ?: '—') ?></div>

      <div class="actions">
        <!-- ДОДАТИ В КОРЗИНУ -->
        <form method="post" action="/logic/cart_logic.php" class="row" style="gap:6px;align-items:center">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
          <input type="number" name="qty" min="1" max="<?= max(1, (int)$it['qty']) ?>" value="1" style="width:90px">
          <button class="btn primary" type="submit">У корзину</button>
        </form>

        <!-- ДЕТАЛІ (SPA) -->
        <a href="#" class="btn secondary nav-link"
           data-page="item"
           data-query="id=<?= (int)$it['id'] ?>">Деталі</a>

        <?php if (($user['role'] ?? '') === 'manager'): ?>
          <!-- РЕДАГУВАТИ -->
          <form method="get" action="/pages/edit_item.php">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button class="btn secondary" type="submit">Редагувати</button>
          </form>

          <!-- ВИДАЛИТИ -->
          <form method="post" action="/logic/edit_item_logic.php" onsubmit="return confirm('Підтвердити видалення?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button class="btn danger" type="submit">Видалити</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
