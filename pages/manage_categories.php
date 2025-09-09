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

<h2>Категорії</h2>

<form method="post" action="/logic/manage_categories_logic.php" class="row" style="gap:8px;margin-bottom:16px">
  <input type="hidden" name="action" value="create">
  <input type="text" name="name" placeholder="Назва категорії" required>
  <button class="btn primary">Створити</button>
</form>

<table class="table">
  <thead>
    <tr>
      <th>Назва</th>
      <th>Створено</th>
      <th>Дії</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $rows = $db->query("SELECT * FROM categories ORDER BY created_at ASC")->fetchAll();
    foreach ($rows as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['created_at']) ?></td>
        <td style="display:flex;gap:6px;">
          <!-- Редагувати -->
          <form method="post" action="/logic/manage_categories_logic.php" style="display:inline-flex;gap:4px;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" required style="width:140px">
            <button class="btn secondary">Зберегти</button>
          </form>
          <!-- Видалити -->
          <form method="post" action="/logic/manage_categories_logic.php" onsubmit="return confirm('Видалити категорію?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn danger">Видалити</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

