<?php
// pages/edit_item.php — охайна сторінка редагування з темою і поверненням назад
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }

require_once __DIR__ . '/../db/db.php';
$db = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /dashboard.php?open=inventory'); exit; }

// товар
$st = $db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
$st->execute([':id'=>$id]);
$item = $st->fetch();
if (!$item) { header('Location: /dashboard.php?open=inventory'); exit; }

// категорії для select
$cats = $db->query("SELECT id, name FROM categories ORDER BY name COLLATE NOCASE ASC")->fetchAll();
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Редагувати товар #<?= (int)$item['id'] ?></title>

  <!-- ВАЖЛИВО: абсолютні шляхи, щоб не було /pages/assets/... -->
  <link rel="stylesheet" href="/assets/css/style.css?v=2">
  <link rel="stylesheet" href="/assets/css/theme.css?v=102">

  <style>
    .wrap{max-width:900px;margin:24px auto;padding:0 16px;}
    .card{background:#0b1220;border:1px solid #1c2534;border-radius:16px;padding:16px}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .row .col{flex:1 1 220px}
    .preview{width:220px;height:220px;border-radius:12px;overflow:hidden;background:#0f172a;border:1px solid #1f2937}
    .preview img{width:100%;height:100%;object-fit:cover}
    .toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .toolbar a{white-space:nowrap}
    label{display:block;margin-bottom:6px;color:#cbd5e1;font-weight:600}
    input[type="text"], input[type="number"], textarea, select{width:100%}
    textarea{min-height:90px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toolbar">
      <h2>Редагувати товар #<?= (int)$item['id'] ?></h2>
      <a class="btn secondary" href="/dashboard.php?open=inventory">← Повернутись до каталогу</a>
    </div>

    <?php if (!empty($_SESSION['inv_ok'])): ?>
      <div class="alert" style="background:#062e0d;color:#86efac">
        <?= htmlspecialchars($_SESSION['inv_ok']); unset($_SESSION['inv_ok']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['inv_err'])): ?>
      <div class="alert error">
        <?= htmlspecialchars($_SESSION['inv_err']); unset($_SESSION['inv_err']); ?>
      </div>
    <?php endif; ?>

    <!-- ВАЖЛИВО: абсолютний action -->
    <form class="card" method="post" action="/logic/edit_item_logic.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
      <!-- куди повертатись після збереження -->
      <input type="hidden" name="redirect_to" value="/dashboard.php?open=inventory">

      <div class="row">
        <div class="col">
          <label>Назва</label>
          <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
        </div>
        <div class="col">
          <label>Бренд</label>
          <input type="text" name="brand" value="<?= htmlspecialchars($item['brand'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Артикул</label>
          <input type="text" name="sku" value="<?= htmlspecialchars($item['sku'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Сектор</label>
          <input type="text" name="sector" value="<?= htmlspecialchars($item['sector'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Кількість</label>
          <input type="number" name="qty" min="0" value="<?= (int)$item['qty'] ?>">
        </div>
        <div class="col">
          <label>Категорія</label>
          <select name="category_id">
            <option value="">Без категорії</option>
            <?php foreach($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$item['category_id']===(int)$c['id']?'selected':'' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row" style="margin-top:10px">
        <div class="col">
          <label>Нотатки</label>
          <textarea name="notes"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
        </div>
        <div class="col" style="max-width:260px">
          <label>Поточне фото</label>
          <div class="preview">
            <?php if (!empty($item['photo'])): ?>
              <img src="<?= htmlspecialchars($item['photo']) ?>" alt="Фото">
            <?php else: ?>
              <img src="https://placehold.co/400x400?text=No+Photo" alt="Фото">
            <?php endif; ?>
          </div>
          <div style="margin-top:8px">
            <label>Нове фото (необов’язково)</label>
            <input type="file" name="photo" accept="image/*">
          </div>
        </div>
      </div>

      <div class="row" style="margin-top:16px;justify-content:flex-end">
        <a class="btn secondary" href="/dashboard.php?open=inventory">Скасувати</a>
        <button class="btn primary" type="submit">Зберегти зміни</button>
      </div>
    </form>
  </div>
</body>
</html>
