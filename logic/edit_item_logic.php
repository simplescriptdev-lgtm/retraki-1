<?php
// logic/edit_item_logic.php — update/delete + audit + redirect + category
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';

$user = current_user();
if (($user['role'] ?? '') !== 'manager') {
    http_response_code(403);
    echo 'Доступ лише для менеджера';
    exit;
}

$db      = db();
$action  = $_POST['action'] ?? '';
$id      = (int)($_POST['id'] ?? 0);

/**
 * Зберегти фото, якщо завантажено.
 * Повертає відносний шлях типу `/uploads/items/....jpg` або $currentPath (якщо нове фото не завантажено).
 */
function save_photo_if_uploaded(string $field, ?string $currentPath): ?string {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $currentPath; // фото не міняли
    }

    $tmp = $_FILES[$field]['tmp_name'];
    if (!is_uploaded_file($tmp)) return $currentPath;

    $dir = __DIR__ . '/../uploads/items';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $ext  = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION) ?: 'jpg');
    $name = 'item_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!@move_uploaded_file($tmp, $dest)) {
        return $currentPath; // не вдалось — повертаємо старе
    }
    // В БД зберігаємо відносний веб-шлях
    return '/uploads/items/' . $name;
}

if ($action === 'update') {
    // 1) Завантажуємо "до"
    $st = $db->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
    $st->execute([':id' => $id]);
    $before = $st->fetch();
    if (!$before) {
        $_SESSION['edit_err'] = 'Товар не знайдено.';
        header('Location: /dashboard.php?open=inventory'); exit;
    }

    // 2) Нові значення
    $name        = trim($_POST['name']        ?? '');
    $brand       = trim($_POST['brand']       ?? '');
    $sku         = trim($_POST['sku']         ?? '');
    $sector      = trim($_POST['sector']      ?? '');
    $qty         = max(0, (int)($_POST['qty'] ?? 0));
    $notes       = trim($_POST['notes']       ?? '');
    $category_id = ($_POST['category_id'] ?? '') === '' ? null : (int)$_POST['category_id'];

    if ($name === '') {
        $_SESSION['edit_err'] = 'Вкажіть назву.';
        header('Location: /pages/edit_item.php?id=' . $id); exit;
    }

    // 3) Фото (опційно)
    $photoPath = save_photo_if_uploaded('photo', $before['photo'] ?? null);

    // 4) Оновлення
    $upd = $db->prepare('UPDATE items
        SET name=:n, brand=:b, sku=:s, sector=:se, notes=:no, qty=:q, photo=:p, category_id=:cat
        WHERE id=:id AND deleted_at IS NULL');
    $upd->execute([
        ':n'   => $name,
        ':b'   => $brand,
        ':s'   => $sku,
        ':se'  => $sector,
        ':no'  => $notes,
        ':q'   => $qty,
        ':p'   => $photoPath,
        ':cat' => $category_id,
        ':id'  => $id
    ]);

    // 5) «Після»
    $st = $db->prepare('SELECT * FROM items WHERE id = :id');
    $st->execute([':id' => $id]);
    $after = $st->fetch();

    // 6) Порівняння та аудит
    $diff = [];
    foreach (['name','brand','sku','sector','notes','qty','photo','category_id'] as $field) {
        if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
            $diff[$field] = ['before' => $before[$field] ?? null, 'after' => $after[$field] ?? null];
        }
    }
    $meta = [
        'item_id' => $id,
        'action'  => 'item_update',
        'by'      => ['id' => $user['id'], 'name' => $user['name']],
        'diff'    => $diff,
        'at'      => date('c'),
    ];
    $ins = $db->prepare('INSERT INTO audit_log (user_id, action, meta) VALUES (:u,:a,:m)');
    $ins->execute([':u'=>$user['id'], ':a'=>'item_update', ':m'=>json_encode($meta, JSON_UNESCAPED_UNICODE)]);

    // 7) флеш + редирект
    $_SESSION['edit_ok'] = $_SESSION['inv_ok'] = 'Зміни збережено.';
    $redirect = $_POST['redirect_to'] ?? '/dashboard.php?open=inventory';
    header('Location: ' . $redirect); exit;
}

if ($action === 'delete') {
    // Мʼяке видалення
    $st = $db->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
    $st->execute([':id'=>$id]);
    $before = $st->fetch();
    if (!$before) {
        $_SESSION['inv_err'] = 'Товар не знайдено або вже видалений.';
        header('Location: /dashboard.php?open=inventory'); exit;
    }

    $db->prepare('UPDATE items SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id')
       ->execute([':id'=>$id]);

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
