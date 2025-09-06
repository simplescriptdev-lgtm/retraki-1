<?php
// logic/cart_logic.php — корзина та переміщення
declare(strict_types=1);
session_start();
require_once __DIR__ . '/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cart   = $_SESSION['cart'] ?? [];

switch ($action) {
    case 'add':
        $item_id = (int)($_POST['item_id'] ?? 0);
        $qty     = max(1, (int)($_POST['qty'] ?? 1));
        if ($item_id > 0) {
            $cart[$item_id] = ($cart[$item_id] ?? 0) + $qty;
        }
        $_SESSION['cart'] = $cart;
        header('Location: /dashboard.php?open=cart'); exit;

    case 'remove':
        $item_id = (int)($_POST['item_id'] ?? 0);
        unset($cart[$item_id]);
        $_SESSION['cart'] = $cart;
        header('Location: /dashboard.php?open=cart'); exit;

    case 'clear':
        $_SESSION['cart'] = [];
        header('Location: /dashboard.php?open=cart'); exit;

    case 'move':
        $to = (int)($_POST['to_location'] ?? 0);
        if (!$to || empty($cart)) {
            $_SESSION['cart_error'] = 'Оберіть локацію і додайте товари.';
            header('Location: /dashboard.php?open=cart'); exit;
        }

        $db = db();
        $db->beginTransaction();
        try {
            $u = current_user();
            $stmt = $db->prepare('INSERT INTO movements (user_id, from_location_id, to_location_id)
                                  VALUES (:u, NULL, :to)');
            $stmt->execute([':u'=>$u['id'], ':to'=>$to]);
            $mid = (int)$db->lastInsertId();

            $stmtItem = $db->prepare('SELECT id, qty FROM items WHERE id = :id');
            $stmtUpd  = $db->prepare('UPDATE items SET qty = :q WHERE id = :id');
            $stmtAdd  = $db->prepare('INSERT INTO movement_items (movement_id,item_id,qty) VALUES (:m,:i,:q)');

            foreach ($cart as $item_id => $qty) {
                $stmtItem->execute([':id'=>$item_id]);
                $it = $stmtItem->fetch();
                if (!$it) throw new Exception('Товар не знайдено');
                $newQty = max(0, (int)$it['qty'] - (int)$qty);
                $stmtUpd->execute([':q'=>$newQty, ':id'=>$item_id]);
                $stmtAdd->execute([':m'=>$mid, ':i'=>$item_id, ':q'=>$qty]);
            }

            $db->commit();
            $_SESSION['cart'] = [];
            $_SESSION['cart_success'] = 'Переміщення успішно виконано.';
        } catch (Throwable $e) {
            $db->rollBack();
            $_SESSION['cart_error'] = 'Помилка: ' . $e->getMessage();
        }
        header('Location: /dashboard.php?open=cart'); exit;

    default:
        header('Location: /dashboard.php?open=cart'); exit;
}
