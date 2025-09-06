<?php
// pages/edit_item.php — форма редагування товару (менеджер)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
if (($user['role'] ?? '') !== 'manager') { http_response_code(403); echo 'Доступ лише для менеджера'; exit; }

$id = (int)($_GET['id'] ?? 0);
$db = db();
$st = $db->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
$st->execute([':id' => $id]);
$it = $st->fetch();
if (!$it) { echo '<p>Товар не знайдено або видалений.</p>'; exit; }

$ok  = $_SESSION['edit_ok']  ?? null; unset($_SESSION['edit_ok']);
$err = $_SESSION['edit_err'] ?? null; unset($_SESSION['edit_err']);
?>
<h2>Редагувати товар #<?= (int)$it['id'] ?></h2>

<?php if ($ok): ?><div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<form method="post" action="/logic/edit_item_logic.php" enctype="multipart/form-data">
  <input type="hidden" name="action" value="update">
  <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">

  <div class="row" style="gap:12px;align-items:flex-start;flex-wrap:wrap">
    <label style="flex:1">Назва<input name="name" value="<?= htmlspecialchars($it['name']) ?>" required></label>
    <label style="flex:1">Фірма<input name="brand" value="<?= htmlspecialchars($it['brand'] ?? '') ?>"></label>
    <label style="flex:1">Артикул<input name="sku" value="<?= htmlspecialchars($it['sku'] ?? '') ?>"></label>
    <label style="flex:1">Сектор<input name="sector" value="<?= htmlspecialchars($it['sector'] ?? '') ?>"></label>
    <label style="flex:1">Кількість<input type="number" min="0" name="qty" value="<?= (int)$it['qty'] ?>" required></label>
  </div>
  <label>Нотатки<textarea name="notes" rows="3"><?= htmlspecialchars($it['notes'] ?? '') ?></textarea></label>

  <div class="row" style="gap:16px;align-items:flex-start;flex-wrap:wrap;margin-top:8px">
    <div>
      <div style="margin-bottom:6px">Поточне фото:</div>
      <?php if ($it['photo']): ?>
        <img src="<?= htmlspecialchars($it['photo']) ?>" style="width:220px;border:1px solid #1f2937;border-radius:8px">
      <?php else: ?>
        <div class="badge">Немає фото</div>
      <?php endif; ?>
    </div>
    <label>Нове фото (необовʼязково)<input type="file" name="photo" accept="image/*"></label>
  </div>

  <div class="actions">
    <button class="btn" type="submit">Зберегти зміни</button>
    <a class="btn secondary" href="/dashboard.php?open=inventory">Скасувати</a>
  </div>
</form>
