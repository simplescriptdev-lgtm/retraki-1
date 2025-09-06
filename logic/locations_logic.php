<?php
// logic/locations_logic.php — створення локацій
declare(strict_types=1);
session_start();
require_once __DIR__ . '/helpers.php';
require_manager();

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $_SESSION['loc_err'] = 'Вкажіть назву.';
        header('Location: /dashboard.php?open=manage_locations'); exit;
    }

    $db = db();
    try {
        $stmt = $db->prepare('INSERT INTO locations (name) VALUES (:n)');
        $stmt->execute([':n'=>$name]);
        $_SESSION['loc_ok'] = 'Локацію створено.';
    } catch (Throwable $e) {
        $_SESSION['loc_err'] = 'Помилка: ' . $e->getMessage();
    }
    header('Location: /dashboard.php?open=manage_locations'); exit;
}
header('Location: /dashboard.php?open=manage_locations');
