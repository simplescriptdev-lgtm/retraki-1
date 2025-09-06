<?php
// pages/add_item.php — форма додавання товару (доступно менеджеру)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';

$err = $_SESSION['add_item_err'] ?? null; unset($_SESSION['add_item_err']);
$ok  = $_SESSION['add_item_ok'] ?? null;  unset($_SESSION['add_item_ok']);
?>
<h2>Додати товар</h2>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="post" action="/logic/add_item_logic.php" enctype="multipart/form-data">
  <div class="row" style="gap:12px;align-items:flex-start;flex-wrap:wrap">
    <label style="flex:1">Назва<input name="name" required></label>
    <label style="flex:1">Фірма<input name="brand"></label>
    <label style="flex:1">Артикул<input name="sku"></label>
    <label style="flex:1">Сектор<input name="sector"></label>
    <label style="flex:1">Кількість на залишку<input type="number" min="0" name="qty" value="0" required></label>
  </div>
  <label>Нотатки<textarea name="notes" rows="3"></textarea></label>
  <label>Фото товару<input type="file" name="photo" accept="image/*"></label>
  <div class="actions">
    <button class="btn" type="submit">Зберегти</button>
  </div>
</form>
