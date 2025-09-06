<?php
// index.php — сторінка входу
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db/db.php'; // ініціалізує БД

$err = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ретраки — Вхід</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
  <div class="login-box">
    <h1>Ретраки — інвентаризація</h1>
    <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post" action="/auth/login.php">
      <label>Логін або Email</label>
      <input type="text" name="identifier" required>
      <label>Пароль</label>
      <input type="password" name="password" required>
      <button type="submit">Увійти</button>
    </form>
    <p class="hint">Стартовий менеджер: <b>manager / manager123</b></p>
    <p class="hint"><b>created by Artem Shmat</b></p>
  </div>
</body>
</html>
