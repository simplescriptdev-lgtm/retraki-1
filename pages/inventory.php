<?php
// pages/inventory.php — список товарів у вигляді карток
declare(strict_types=1);
require_once __DIR__ . '/../db/db.php';
session_start();

$db = db();
$items = $db->query('SELECT * FROM items ORDER BY created_at DESC')->fetchAll();
?>
<div class="row" style="justify-content:space-between;align-items:center;margin-bottom:12px">
  <h2>Залишки</h2>
  <form method="get" action="/pages/inventory.php" style="display:flex;gap:8px">
    <input type="text" name="q" placeholder="Пошук по назві/артикулу...">
    <button class="btn secondary">Пошук</button>
  </form>
</div>
<div class="grid">
<?php foreach ($items as $it): ?>
  <div class="card">
    <?php if ($it['photo']): ?>
      <img src="<?= htmlspecialchars($it['photo']) ?>" alt="Фото товару">
    <?php else: ?>
      <img src="https://placehold.co/600x400?text=No+Photo" alt="Фото товару">
    <?php endif; ?>
    <div class="body">
      <div class="row" style="justify-content:space-between">
        <strong><?= htmlspecialchars($it['name']) ?></strong>
        <span class="badge">К-сть: <?= (int)$it['qty'] ?></span>
      </div>
      <div class="row"><span class="badge"><?= htmlspecialchars($it['brand'] ?: '—') ?></span><span class="badge">SKU: <?= htmlspecialchars($it['sku'] ?: '—') ?></span></div>
      <small>Сектор: <?= htmlspecialchars($it['sector'] ?: '—') ?></small>
      <div class="row">
        <form method="post" action="/logic/cart_logic.php" style="display:flex;gap:6px;align-items:center">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
          <input type="number" name="qty" min="1" max="<?= max(1,(int)$it['qty']) ?>" value="1" style="width:90px">
          <button class="btn" type="submit">У корзину</button>
        </form>
        <form method="get" action="/pages/item.php">
          <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
          <button class="btn secondary" type="submit">Деталі</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
