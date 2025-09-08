<?php
// pages/inventory.php — список товарів (з категоріями) у новому темному стилі
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
$db   = db();

/* --------- фільтри --------- */
$q   = trim($_GET['q']   ?? '');
$cat = trim($_GET['cat'] ?? '');        // '' | 'all' | <id>

/* --------- категорії (для панелі фільтра) --------- */
$cats = $db->query("SELECT id, name FROM categories ORDER BY name COLLATE NOCASE")->fetchAll(PDO::FETCH_ASSOC);

/* --------- SQL під фільтри --------- */
$sql = "SELECT i.*, c.name AS category_name
          FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
         WHERE i.deleted_at IS NULL";
$params = [];

if ($q !== '') {
  $sql .= " AND (i.name LIKE :q OR i.sku LIKE :q OR i.brand LIKE :q OR i.sector LIKE :q OR c.name LIKE :q)";
  $params[':q'] = "%{$q}%";
}
if ($cat !== '' && $cat !== 'all' && ctype_digit($cat)) {
  $sql .= " AND i.category_id = :cat";
  $params[':cat'] = (int)$cat;
}
$sql .= " ORDER BY i.created_at DESC";

$st = $db->prepare($sql);
$st->execute($params);
$items = $st->fetchAll();

/* --------- флеші --------- */
$ok  = $_SESSION['inv_ok']  ?? null; unset($_SESSION['inv_ok']);
$err = $_SESSION['inv_err'] ?? null; unset($_SESSION['inv_err']);
?>

<!-- Шапка: заголовок + пошук -->
<div class="toolbar">
  <h2>Залишки</h2>

  <!-- пошук через dashboard, щоб залишатися в SPA -->
  <form method="get" action="/dashboard.php" class="search">
    <input type="hidden" name="open" value="inventory">
    <?php if ($cat !== '' && $cat !== 'all' && ctype_digit($cat)): ?>
      <input type="hidden" name="cat" value="<?= (int)$cat ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Пошук по назві / артикулу / бренду / сектору / категорії…">
    <button class="btn secondary" type="submit">Пошук</button>
  </form>
</div>

<!-- Панель фільтрів по категоріях -->
<div class="filterbar">
  <?php
    // «Усе» — скидає ВСЕ (і cat, і q)
    $isAll = ($cat === '' || $cat === 'all');
  ?>
  <a href="#" class="chip <?= $isAll ? 'active' : '' ?> nav-link"
     data-page="inventory" data-query="">Усе</a>

  <?php foreach ($cats as $c): 
        $active = (!$isAll && (string)$c['id'] === $cat);
        // зберігаємо пошуковий запит у лінку цієї категорії? за ТЗ — фільтр за категорією;
        // якщо хочеш зберігати q — додай &q=<?=urlencode($q)
  ?>
    <a href="#" class="chip nav-link <?= $active ? 'active' : '' ?>"
       data-page="inventory"
       data-query="cat=<?= (int)$c['id'] ?>">
       <?= htmlspecialchars($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($ok):  ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid">
<?php foreach ($items as $it): ?>
  <div class="card">
    <!-- Фото -->
    <div class="media">
      <?php if (!empty($it['photo'])): ?>
        <img src="<?= htmlspecialchars($it['photo']) ?>" alt="Фото товару">
      <?php else: ?>
        <img src="https://placehold.co/640x400?text=No+Photo" alt="Фото товару">
      <?php endif; ?>
    </div>

    <!-- Тіло картки -->
    <div class="body">
      <div class="title"><?= htmlspecialchars($it['name']) ?></div>

      <?php
        $qty = (int)$it['qty'];
        $qtyClass = $qty <= 3 ? 'danger' : ($qty <= 10 ? 'warn' : 'success');
      ?>
      <div class="meta">
        <span class="badge"><?= htmlspecialchars($it['category_name'] ?: 'Без категорії') ?></span>
        <span class="badge <?= $qtyClass ?>">К-сть: <?= $qty ?></span>
        <span class="badge">SKU: <?= htmlspecialchars($it['sku'] ?: '—') ?></span>
        <span class="badge"><?= htmlspecialchars($it['brand'] ?: '—') ?></span>
      </div>

      <div class="small">Сектор: <?= htmlspecialchars($it['sector'] ?: '—') ?></div>

      <!-- Дії -->
      <div class="actions row">
        <!-- у корзину -->
        <form method="post" action="/logic/cart_logic.php" class="row" style="gap:6px;align-items:center">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
          <input type="number" name="qty" min="1" max="<?= max(1,(int)$it['qty']) ?>" value="1" style="width:90px">
          <button class="btn primary" type="submit">У корзину</button>
        </form>

        <!-- Деталі (SPA) -->
        <a href="#" class="btn secondary nav-link"
           data-page="item"
           data-query="id=<?= (int)$it['id'] ?>">Деталі</a>

        <?php if (($user['role'] ?? '') === 'manager'): ?>
          <form method="get" action="/pages/edit_item.php" class="row">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button class="btn secondary" type="submit">Редагувати</button>
          </form>
          <form method="post" action="/logic/edit_item_logic.php" class="row"
                onsubmit="return confirm('Підтвердити видалення?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button class="btn danger" type="submit">Видалити</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
