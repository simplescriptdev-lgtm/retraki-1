<?php
// logic/manage_users_logic.php — створення/видалення з урахуванням наявності deleted_at
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'manager') {
  http_response_code(403);
  echo 'Доступ лише для менеджера';
  exit;
}

$db = db();

/** Допоміжна: чи є колонка у таблиці */
function has_column(PDO $db, string $table, string $column): bool {
  $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($cols as $c) {
    if (($c['name'] ?? '') === $column) return true;
  }
  return false;
}

/** Якщо немає deleted_at — нічого страшного, будемо видаляти хардом */
$hasDeletedAt = has_column($db, 'users', 'deleted_at');

$action = $_POST['action'] ?? '';

if ($action === 'create_user') {
  $name  = trim($_POST['name']  ?? '');
  $login = trim($_POST['login'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');
  $role  = ($_POST['role'] ?? 'user');

  if ($name === '' || $login === '' || $pass === '') {
    $_SESSION['users_err'] = 'Заповніть імʼя, логін і пароль.';
    header('Location: /dashboard.php?open=manage_users'); exit;
  }
  if (!in_array($role, ['user','manager'], true)) { $role = 'user'; }

  // Унікальність логіна
  $st = $db->prepare('SELECT COUNT(*) FROM users WHERE login = :l'.($hasDeletedAt ? ' AND deleted_at IS NULL' : ''));
  $st->execute([':l'=>$login]);
  if ((int)$st->fetchColumn() > 0) {
    $_SESSION['users_err'] = 'Логін уже використовується.';
    header('Location: /dashboard.php?open=manage_users'); exit;
  }

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $ins = $db->prepare('INSERT INTO users (name, login, email, password, role, created_at) VALUES (:n,:l,:e,:p,:r, CURRENT_TIMESTAMP)');
  $ins->execute([':n'=>$name, ':l'=>$login, ':e'=>$email, ':p'=>$hash, ':r'=>$role]);

  $_SESSION['users_ok'] = 'Користувача створено.';
  header('Location: /dashboard.php?open=manage_users'); exit;
}

if ($action === 'delete_user') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $_SESSION['users_err'] = 'Невірний ідентифікатор.';
    header('Location: /dashboard.php?open=manage_users'); exit;
  }

  if ($id === (int)($user['id'] ?? 0)) {
    $_SESSION['users_err'] = 'Не можна видалити себе.';
    header('Location: /dashboard.php?open=manage_users'); exit;
  }

  // Не дозволяємо видалити останнього менеджера
  $sqlManagers = 'SELECT COUNT(*) FROM users WHERE role="manager"';
  if ($hasDeletedAt) $sqlManagers .= ' AND deleted_at IS NULL';
  $mgrCnt = (int)$db->query($sqlManagers)->fetchColumn();

  $stRole = $db->prepare('SELECT role FROM users WHERE id=:id'.($hasDeletedAt ? ' AND deleted_at IS NULL' : ''));
  $stRole->execute([':id'=>$id]);
  $role = $stRole->fetchColumn();

  if ($role === 'manager' && $mgrCnt <= 1) {
    $_SESSION['users_err'] = 'Не можна видалити останнього менеджера.';
    header('Location: /dashboard.php?open=manage_users'); exit;
  }

  if ($hasDeletedAt) {
    $del = $db->prepare('UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
    $del->execute([':id'=>$id]);
  } else {
    $del = $db->prepare('DELETE FROM users WHERE id = :id');
    $del->execute([':id'=>$id]);
  }

  $_SESSION['users_ok'] = 'Користувача видалено.';
  header('Location: /dashboard.php?open=manage_users'); exit;
}

$_SESSION['users_err'] = 'Невідома дія.';
header('Location: /dashboard.php?open=manage_users');
