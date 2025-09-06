<?php
// logic/add_item_logic.php — обробка додавання товару
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

if ($name === '') {
    $_SESSION['add_item_err'] = 'Вкажіть назву.';
    header('Location: /dashboard.php?open=add_item'); exit;
}

$photoPath = null;
if (!empty($_FILES['photo']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/items';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    $ext   = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $fname = 'item_' . uniqid() . '.' . strtolower($ext ?: 'jpg');
    $dest  = $uploadDir . '/' . $fname;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        $_SESSION['add_item_err'] = 'Не вдалося зберегти фото (перевірте папку uploads/items).';
        header('Location: /dashboard.php?open=add_item'); exit;
    }
    $photoPath = '/uploads/items/' . $fname; // ВАЖЛИВО: конкатенація через .
}

$db = db();
$stmt = $db->prepare('INSERT INTO items (name, brand, sku, sector, notes, qty, photo)
                      VALUES (:n,:b,:s,:se,:no,:q,:p)');
$stmt->execute([
    ':n'=>$name, ':b'=>$brand, ':s'=>$sku, ':se'=>$sector,
    ':no'=>$notes, ':q'=>$qty, ':p'=>$photoPath
]);

$_SESSION['add_item_ok'] = 'Товар додано.';
header('Location: /dashboard.php?open=add_item');
