<?php
// pages/manage_users.php — створення та керування користувачами (з урахуванням відсутності deleted_at)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'manager') {
  header('Location: /dashboard.php?open=inventory');
  exit;
}

$ok  = $_SESSION['users_ok']  ?? null; unset($_SESSION['users_ok']);
$err = $_SESSION['users_err'] ?? null; unset($_SESSION['users_err']);

$db = db();

/** Перевіряємо, чи є колонка deleted_at */
$cols = $db->query("PRAGMA table_info(users)")->fetchAll();
$hasDeletedAt = false;
foreach ($cols as $c) {
  if ((string)($c['name'] ?? $c['1'] ?? '') === 'deleted_at') { $hasDeletedAt = true; break; }
}

/** Формуємо запит з урахуванням колонки deleted_at */
$sql = "SELECT id, name, login, email, role, created_at FROM users";
if ($hasDeletedAt) { $sql .= " WHERE deleted_at IS NULL"; }
$sql .= " ORDER BY created_at DESC";
$users = $db->query($sql)->fetchAll();
?>
<h2>Користувачі</h2>

<?php if ($ok): ?>
  <div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div>
<?php endif; ?>
<?php if ($err): ?>
  <div class="alert error"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- Створення користувача -->
<form method="post" action="/logic/manage_users_logic.php" class="row" style="gap:12px;align-items:flex-end;flex-wrap:wrap">
  <input type="hidden" name="action" value="create_user">
  <label>Імʼя
    <input name="name" required>
  </label>
  <label>Логін
    <input name="login" required>
  </label>
  <label>Email
    <input type="email" name="email" placeholder="необовʼязково">
  </label>
  <label>Пароль
    <input type="password" name="password" required>
  </label>
  <label>Роль
    <select name="role" required>
      <option value="user">user</option>
      <option value="manager">manager</option>
    </select>
  </label>
  <button class="btn" type="submit">Створити</button>
</form>

<!-- Список користувачів -->
<table class="table" style="margin-top:12px">
  <thead>
    <tr>
      <th>Імʼя</th>
      <th>Логін</th>
      <th>Email</th>
      <th>Роль</th>
      <th>Створено</th>
      <th style="width:1%">Дії</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['login']) ?></td>
        <td><?= htmlspecialchars($u['email'] ?: '—') ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td style="white-space:nowrap">
          <?php if ((int)$u['id'] !== (int)($user['id'] ?? 0)): ?>
            <form method="post" action="/logic/manage_users_logic.php"
                  onsubmit="return confirm('Видалити користувача «<?= htmlspecialchars($u['name']) ?>»?');"
                  style="display:inline">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn danger" type="submit">Видалити</button>
            </form>
          <?php else: ?>
            <span class="badge">це ви</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
