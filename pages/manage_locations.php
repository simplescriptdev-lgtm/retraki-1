<?php
// pages/manage_locations.php — менеджер створює локації
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';
$err = $_SESSION['loc_err'] ?? null; unset($_SESSION['loc_err']);
$ok  = $_SESSION['loc_ok'] ?? null;  unset($_SESSION['loc_ok']);

$db = db();
$locs = $db->query('SELECT * FROM locations ORDER BY id DESC')->fetchAll();
?>
<h2>Локації</h2>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="post" action="/logic/locations_logic.php" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
  <input type="hidden" name="action" value="create">
  <label>Назва локації<input name="name" required></label>
  <button class="btn" type="submit">Створити</button>
</form>

<table class="table" style="margin-top:12px">
  <thead><tr><th>Назва</th><th>Створено</th></tr></thead>
  <tbody>
    <?php foreach ($locs as $l): ?>
      <tr><td><?= htmlspecialchars($l['name']) ?></td><td><?= htmlspecialchars($l['created_at']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
