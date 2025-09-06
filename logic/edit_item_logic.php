<?php
// logic/edit_item_logic.php — update/delete + audit
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';

$user = current_user();
if (($user['role'] ?? '') !== 'manager') { http_response_code(403); echo 'Доступ лише для менеджера'; exit; }

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$db = db();

if ($action === 'update') {
    // 1) Завантажуємо "до"
    $st = $db->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
    $st->execute([':id'=>$id]);
    $before = $st->fetch();
    if (!$before) { $_SESSION['edit_err'] = 'Товар не знайдено.'; header('Location: /dashboard.php?open=inventory'); exit; }

    // 2) Нові значення
    $name   = trim($_POST['name']   ?? '');
    $brand  = trim($_POST['brand']  ?? '');
    $sku    = trim($_POST['sku']    ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $qty    = max(0, (int)($_POST['qty'] ?? 0));
    $notes  = trim($_POST['notes']  ?? '');

    if ($name === '') { $_SESSION['edit_err'] = 'Вкажіть назву.'; header('Location: /pages/edit_item.php?id='.$id); exit; }

    // 3) Фото (опційно)
    $photoPath = $before['photo'];
    if (!empty($_FILES['photo']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../uploads/items';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
        $ext   = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $fname = 'item_' . uniqid() . '.' . strtolower($ext ?: 'jpg');
        $dest  = $uploadDir . '/' . $fname;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $_SESSION['edit_err'] = 'Не вдалося зберегти фото.'; header('Location: /pages/edit_item.php?id='.$id); exit;
        }
        $photoPath = '/uploads/items/' . $fname;
    }

    // 4) Оновлення
    $upd = $db->prepare('UPDATE items
        SET name=:n, brand=:b, sku=:s, sector=:se, notes=:no, qty=:q, photo=:p
        WHERE id=:id AND deleted_at IS NULL');
    $upd->execute([
        ':n'=>$name, ':b'=>$brand, ':s'=>$sku, ':se'=>$sector, ':no'=>$notes, ':q'=>$qty, ':p'=>$photoPath, ':id'=>$id
    ]);

    // 5) «Після»
    $st = $db->prepare('SELECT * FROM items WHERE id = :id');
    $st->execute([':id'=>$id]);
    $after = $st->fetch();

    // 6) Порівняння та аудит
    $diff = [];
    foreach (['name','brand','sku','sector','notes','qty','photo'] as $field) {
        if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
            $diff[$field] = ['before'=>$before[$field] ?? null, 'after'=>$after[$field] ?? null];
        }
    }

    $meta = [
        'item_id' => $id,
        'action'  => 'item_update',
        'by'      => ['id'=>$user['id'], 'name'=>$user['name']],
        'diff'    => $diff,
        'at'      => date('c'),
    ];
    $ins = $db->prepare('INSERT INTO audit_log (user_id, action, meta) VALUES (:u,:a,:m)');
    $ins->execute([':u'=>$user['id'], ':a'=>'item_update', ':m'=>json_encode($meta, JSON_UNESCAPED_UNICODE)]);

    $_SESSION['edit_ok'] = 'Зміни збережено.';
    header('Location: /pages/edit_item.php?id='.$id); exit;
}

if ($action === 'delete') {
    // Мʼяке видалення
    $st = $db->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
    $st->execute([':id'=>$id]);
    $before = $st->fetch();
    if (!$before) { $_SESSION['inv_err'] = 'Товар не знайдено або вже видалений.'; header('Location: /dashboard.php?open=inventory'); exit; }

    $db->prepare('UPDATE items SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id'=>$id]);

    // Аудит
    $meta = [
        'item_id' => $id,
        'action'  => 'item_delete',
        'by'      => ['id'=>$user['id'], 'name'=>$user['name']],
        'before'  => $before,
        'at'      => date('c'),
    ];
    $ins = $db->prepare('INSERT INTO audit_log (user_id, action, meta) VALUES (:u,:a,:m)');
    $ins->execute([':u'=>$user['id'], ':a'=>'item_delete', ':m'=>json_encode($meta, JSON_UNESCAPED_UNICODE)]);

    $_SESSION['inv_ok'] = 'Товар видалено.';
    header('Location: /dashboard.php?open=inventory'); exit;
}

$_SESSION['inv_err'] = 'Невідома дія.';
header('Location: /dashboard.php?open=inventory');
