<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'manager') {
  http_response_code(403); 
  exit('Доступ лише для менеджера');
}

$db = db();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
  // Додавання нової категорії
  $name = trim($_POST['name'] ?? '');
  if ($name !== '') {
    $st = $db->prepare("INSERT INTO categories (name, created_at) VALUES (:n, datetime('now'))");
    $st->execute([':n' => $name]);
  }

} elseif ($action === 'update') {
  // Редагування назви
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  if ($id && $name !== '') {
    $st = $db->prepare("UPDATE categories SET name = :n WHERE id = :id");
    $st->execute([':n' => $name, ':id' => $id]);
  }

} elseif ($action === 'delete') {
  // Видалення категорії
  $id = (int)($_POST['id'] ?? 0);
  if ($id) {
    $st = $db->prepare("DELETE FROM categories WHERE id = :id");
    $st->execute([':id' => $id]);
  }
}

// Після будь-якої дії — повертаємось назад
header('Location: /dashboard.php?open=manage_categories');
exit;
