<?php
// pages/manage_users.php — менеджер створює користувачів
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';
$err = $_SESSION['users_err'] ?? null; unset($_SESSION['users_err']);
$ok  = $_SESSION['users_ok'] ?? null;  unset($_SESSION['users_ok']);

$db = db();
$users = $db->query('SELECT id,name,login,email,role,created_at FROM users ORDER BY id DESC')->fetchAll();
?>
<h2>Користувачі</h2>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="post" action="/logic/users_logic.php" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
  <input type="hidden" name="action" value="create">
  <label>Імʼя<input name="name" required></label>
  <label>Логін<input name="login" required></label>
  <label>Email<input type="email" name="email"></label>
  <label>Пароль<input type="password" name="password" required></label>
  <label>Роль
    <select name="role" required>
      <option value="user">user</option>
      <option value="manager">manager</option>
    </select>
  </label>
  <button class="btn" type="submit">Створити</button>
</form>

<table class="table" style="margin-top:12px">
  <thead><tr><th>Імʼя</th><th>Логін</th><th>Email</th><th>Роль</th><th>Створено</th></tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['login']) ?></td>
        <td><?= htmlspecialchars($u['email'] ?: '—') ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
