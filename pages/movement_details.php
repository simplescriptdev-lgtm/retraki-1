<?php
// pages/movement_details.php — деталі конкретного переміщення
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';
$id = (int)($_GET['id'] ?? 0);
$db = db();
$head = $db->prepare('SELECT m.*, u.name as user_name, lf.name as from_name, lt.name as to_name
                      FROM movements m
                      JOIN users u ON u.id = m.user_id
                      LEFT JOIN locations lf ON lf.id = m.from_location_id
                      LEFT JOIN locations lt ON lt.id = m.to_location_id
                      WHERE m.id = :id');
$head->execute([':id'=>$id]);
$h = $head->fetch();
if (!$h) { echo '<p>Не знайдено.</p>'; exit; }
$items = $db->prepare('SELECT mi.qty, i.name, i.sku FROM movement_items mi JOIN items i ON i.id = mi.item_id WHERE mi.movement_id = :id');
$items->execute([':id'=>$id]);
$list = $items->fetchAll();
?>
<h2>Транзакція #<?= (int)$h['id'] ?></h2>
<p><b>Дата:</b> <?= htmlspecialchars($h['created_at']) ?><br>
<b>Хто:</b> <?= htmlspecialchars($h['user_name']) ?><br>
<b>Звідки:</b> <?= htmlspecialchars($h['from_name'] ?: '—') ?><br>
<b>Куди:</b> <?= htmlspecialchars($h['to_name'] ?: '—') ?></p>

<table class="table">
  <thead><tr><th>Товар</th><th>SKU</th><th>К-сть</th></tr></thead>
  <tbody>
  <?php foreach ($list as $row): ?>
    <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['sku'] ?: '—') ?></td><td><?= (int)$row['qty'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p><a class="btn secondary" href="/dashboard.php" >Назад</a></p>
