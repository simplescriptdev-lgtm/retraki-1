<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/helpers.php';
require_manager();

$name   = trim($_POST['name']   ?? '');
$brand  = trim($_POST['brand']  ?? '');
$sku    = trim($_POST['sku']    ?? '');
$sector = trim($_POST['sector'] ?? '');
$qty    = max(0, (int)($_POST['qty'] ?? 0));
$notes  = trim($_POST['notes']  ?? '');
$cat_id = (int)($_POST['category_id'] ?? 0);

if ($name === '' || $cat_id <= 0) {
    $_SESSION['add_item_err'] = 'Вкажіть назву і категорію.';
    header('Location: /dashboard.php?open=add_item'); exit;
}

// валідація існування категорії
$db = db();
$chk = $db->prepare('SELECT 1 FROM categories WHERE id = :id');
$chk->execute([':id'=>$cat_id]);
if (!$chk->fetchColumn()) {
    $_SESSION['add_item_err'] = 'Обрана категорія не існує.';
    header('Location: /dashboard.php?open=add_item'); exit;
}

$photoPath = null;
if (!empty($_FILES['photo']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/items';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $fname = 'item_' . uniqid() . '.' . strtolower($ext ?: 'jpg');
    $dest = $uploadDir . '/' . $fname;
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        $_SESSION['add_item_err'] = 'Не вдалося зберегти фото.'; header('Location: /dashboard.php?open=add_item'); exit;
    }
    $photoPath = '/uploads/items/' . $fname;
}

$stmt = $db->prepare('INSERT INTO items (name, brand, sku, sector, notes, qty, photo, category_id)
                      VALUES (:n,:b,:s,:se,:no,:q,:p,:c)');
$stmt->execute([
    ':n'=>$name, ':b'=>$brand, ':s'=>$sku, ':se'=>$sector, ':no'=>$notes,
    ':q'=>$qty, ':p'=>$photoPath, ':c'=>$cat_id
]);

$_SESSION['add_item_ok'] = 'Товар додано.';
header('Location: /dashboard.php?open=add_item');
