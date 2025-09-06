<?php
// pages/history.php — історія переміщень
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';

$db = db();
$rows = $db->query('SELECT m.id, m.created_at, u.name as user_name, lf.name as from_name, lt.name as to_name
                    FROM movements m
                    JOIN users u ON u.id = m.user_id
                    LEFT JOIN locations lf ON lf.id = m.from_location_id
                    LEFT JOIN locations lt ON lt.id = m.to_location_id
                    ORDER BY m.id DESC')->fetchAll();
?>
<h2>Історія переміщень</h2>
<table class="table">
  <thead><tr><th>Дата</th><th>Хто здійснив</th><th>Звідки</th><th>Куди</th><th>Деталі</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['created_at']) ?></td>
      <td><?= htmlspecialchars($r['user_name']) ?></td>
      <td><?= htmlspecialchars($r['from_name'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['to_name'] ?: '—') ?></td>
      <td><a class="btn secondary" href="/pages/movement_details.php?id=<?= (int)$r['id'] ?>">Деталі</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
