<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
if (($user['role'] ?? '') !== 'manager') { http_response_code(403); echo 'Доступ лише для менеджера'; exit; }

$db = db();
$err = $_SESSION['cat_err'] ?? null; unset($_SESSION['cat_err']);
$ok  = $_SESSION['cat_ok']  ?? null; unset($_SESSION['cat_ok']);
$cats = $db->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll();
?>
<h2>Категорії</h2>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="post" action="/logic/categories_logic.php" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
  <input type="hidden" name="action" value="create">
  <label>Назва категорії<input name="name" required></label>
  <button class="btn" type="submit">Створити</button>
</form>

<table class="table" style="margin-top:12px">
  <thead><tr><th>Назва</th><th>Створено</th></tr></thead>
  <tbody>
    <?php foreach ($cats as $c): ?>
      <tr><td><?= htmlspecialchars($c['name']) ?></td><td><?= htmlspecialchars($c['created_at']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
