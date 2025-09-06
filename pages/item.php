<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';
$id = (int)($_GET['id'] ?? 0);
$db = db();
$stmt = $db->prepare('SELECT i.*, c.name AS category_name
                        FROM items i
                   LEFT JOIN categories c ON c.id = i.category_id
                       WHERE i.id = :id');
$stmt->execute([':id'=>$id]);
$it = $stmt->fetch();
if (!$it) { echo '<p>Товар не знайдено.</p>'; exit; }
?>
<h2><?= htmlspecialchars($it['name']) ?></h2>
<div class="row" style="gap:16px;align-items:flex-start">
  <div style="max-width:420px;width:100%">
    <?php if ($it['photo']): ?>
      <img src="<?= htmlspecialchars($it['photo']) ?>" style="width:100%;border-radius:12px;border:1px solid #1f2937">
    <?php else: ?>
      <img src="https://placehold.co/800x600?text=No+Photo" style="width:100%;border-radius:12px;border:1px solid #1f2937">
    <?php endif; ?>
  </div>
  <div style="flex:1">
    <p><b>Бренд:</b> <?= htmlspecialchars($it['brand'] ?: '—') ?></p>
    <p><b>Артикул:</b> <?= htmlspecialchars($it['sku'] ?: '—') ?></p>
    <p><b>Сектор:</b> <?= htmlspecialchars($it['sector'] ?: '—') ?></p>
    <p><b>Нотатки:</b><br><?= nl2br(htmlspecialchars($it['notes'] ?: '—')) ?></p>
    <p><b>Кількість на залишку:</b> <?= (int)$it['qty'] ?></p>
    <p><b>Категорія:</b> <?= htmlspecialchars($it['category_name'] ?: '—') ?></p>
    <form method="post" action="/logic/cart_logic.php" class="row">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
      <input type="number" name="qty" min="1" max="<?= max(1,(int)$it['qty']) ?>" value="1" style="width:120px">
      <button class="btn" type="submit">У корзину</button>
      <a class="btn secondary" href="/dashboard.php">Назад</a>
    </form>
  </div>
</div>
