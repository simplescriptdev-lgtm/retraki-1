<?php
// pages/cart.php — перегляд корзини та оформлення переміщення (fixed: без вкладених форм)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$db = db();
$cart = $_SESSION['cart'] ?? [];
$ids  = array_map('intval', array_keys($cart));

$items = [];
if ($ids) {
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $db->prepare('SELECT * FROM items WHERE id IN (' . $in . ')');
  $stmt->execute($ids);
  $items = $stmt->fetchAll();
}

$locations = $db->query('SELECT * FROM locations ORDER BY name')->fetchAll();
$err = $_SESSION['cart_error']  ?? null; unset($_SESSION['cart_error']);
$ok  = $_SESSION['cart_success']?? null; unset($_SESSION['cart_success']);
?>
<h2>Корзина</h2>

<?php if ($err): ?>
  <div class="alert error"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert" style="background:#062e0d;color:#86efac"><?= htmlspecialchars($ok) ?></div>
<?php endif; ?>

<?php if (!$items): ?>
  <p>Корзина порожня.</p>
<?php else: ?>
  <table class="table">
    <thead>
      <tr><th>Товар</th><th>SKU</th><th>На складі</th><th>К-сть до переміщення</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $it): $qty = (int)($cart[$it['id']] ?? 0); ?>
      <tr>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= htmlspecialchars($it['sku'] ?: '—') ?></td>
        <td><?= (int)$it['qty'] ?></td>
        <td><?= $qty ?></td>
        <td>
          <form method="post" action="/logic/cart_logic.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
            <button class="btn danger" type="submit">Видалити</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ОКРЕМА форма ПЕРЕМІЩЕННЯ -->
  <form method="post" action="/logic/cart_logic.php" class="row" style="margin-top:12px;gap:12px;flex-wrap:wrap">
    <input type="hidden" name="action" value="move">
    <label style="min-width:240px">
      Куди перемістити:
      <select name="to_location" required>
        <option value="">— Оберіть локацію —</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="btn" type="submit">Здійснити переміщення</button>
  </form>

  <!-- ОКРЕМА форма ОЧИСТИТИ -->
  <form method="post" action="/logic/cart_logic.php" style="margin-top:8px">
    <input type="hidden" name="action" value="clear">
    <button class="btn secondary" type="submit">Очистити корзину</button>
  </form>
<?php endif; ?>
