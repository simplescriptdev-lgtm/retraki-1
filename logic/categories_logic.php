<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';
require_manager();

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { $_SESSION['cat_err'] = 'Вкажіть назву.'; header('Location: /dashboard.php?open=manage_categories'); exit; }

    $db = db();
    try {
        $st = $db->prepare('INSERT INTO categories (name) VALUES (:n)');
        $st->execute([':n'=>$name]);
        $_SESSION['cat_ok'] = 'Категорію створено.';
    } catch (Throwable $e) {
        $_SESSION['cat_err'] = 'Помилка: ' . $e->getMessage();
    }
    header('Location: /dashboard.php?open=manage_categories'); exit;
}
header('Location: /dashboard.php?open=manage_categories');
