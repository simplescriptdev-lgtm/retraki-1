<?php
// logic/export.php — експорт у CSV або Excel (HTML .xls) з фото
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';

$user = current_user();
if (($user['role'] ?? '') !== 'manager') {
  http_response_code(403); echo 'Доступ лише для менеджера'; exit;
}

$type   = $_GET['type']   ?? 'inventory'; // inventory | movements
$format = $_GET['format'] ?? 'csv';       // csv | xls
$db = db();

/* -------- helpers -------- */

// Повний URL для картинки (з відносного шляху /uploads/..)
function absolute_url(?string $path): string {
  if (!$path) return '';
  if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host . $path;
}

function send_csv(string $filename, array $header, array $rows): void {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo "\xEF\xBB\xBF"; // UTF-8 BOM
  $out = fopen('php://output', 'w');
  fputcsv($out, $header, ';');
  foreach ($rows as $r) fputcsv($out, $r, ';');
  fclose($out);
  exit;
}

function send_xls(string $filename, array $header, array $rows): void {
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo "\xEF\xBB\xBF";
  echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
  echo '<table border="1"><thead><tr>';
  foreach ($header as $h) echo '<th>'.htmlspecialchars($h).'</th>';
  echo '</tr></thead><tbody>';
  foreach ($rows as $r) {
    echo '<tr>';
    // НЕ екрануємо $cell, щоб <img> рендерився в Excel
    foreach ($r as $cell) echo '<td>'.$cell.'</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  exit;
}

/* -------- data builders -------- */

// ========== INVENTORY ==========
if ($type === 'inventory') {
  // Додаємо i.photo у вибірку
  $stmt = $db->query("
    SELECT i.id, i.name, i.brand, i.sku, i.sector, i.notes, i.qty, i.photo,
           IFNULL(c.name,'') AS category, i.created_at
      FROM items i
 LEFT JOIN categories c ON c.id = i.category_id
     WHERE i.deleted_at IS NULL
  ORDER BY i.id ASC
  ");
  $rows = $stmt->fetchAll();

  // Колонки: тримаємо «Фото» перед «Створено», як у вас
  $header = ['ID','Назва','Бренд','Артикул','Сектор','Нотатки','Кількість','Категорія','Фото','Створено'];
  $data = [];

  foreach ($rows as $r) {
    $url = absolute_url($r['photo'] ?? '');
    $photoCell = ($format === 'xls')
      ? ($url ? '<img src="'.htmlspecialchars($url).'" width="120" />' : '')
      : $url; // у CSV — просто URL

    $data[] = [
      $r['id'], $r['name'], $r['brand'], $r['sku'], $r['sector'],
      $r['notes'], $r['qty'], $r['category'], $photoCell, $r['created_at']
    ];
  }

  $fname = 'inventory_'.date('Y-m-d_His').'.'.($format==='xls'?'xls':'csv');
  if ($format === 'xls') send_xls($fname, $header, $data);
  else                   send_csv($fname, $header, $data);
}

// ========== MOVEMENTS (з деталями позицій) ==========
if ($type === 'movements') {
  $mov = $db->query("
    SELECT m.id, m.created_at,
           IFNULL(u.name,'')  AS user_name,
           IFNULL(lf.name,'') AS from_loc,
           IFNULL(lt.name,'') AS to_loc
      FROM movements m
 LEFT JOIN users u  ON u.id  = m.user_id
 LEFT JOIN locations lf ON lf.id = m.from_location_id
 LEFT JOIN locations lt ON lt.id = m.to_location_id
  ORDER BY m.id ASC
  ")->fetchAll();
  $mIndex = [];
  foreach ($mov as $m) $mIndex[$m['id']] = $m;

  $stmt = $db->query("
    SELECT mi.movement_id, mi.item_id, mi.qty,
           IFNULL(i.name,'')  AS item_name,
           IFNULL(i.sku,'')   AS item_sku,
           IFNULL(i.photo,'') AS item_photo
      FROM movement_items mi
 LEFT JOIN items i ON i.id = mi.item_id
  ORDER BY mi.movement_id ASC, mi.id ASC
  ");
  $rows = $stmt->fetchAll();

  $header = ['MoveID','Дата','Хто','Звідки','Куди','ItemID','Назва товару','SKU','Кількість','Фото'];
  $data = [];

  foreach ($rows as $r) {
    $m = $mIndex[$r['movement_id']] ?? ['created_at'=>'','user_name'=>'','from_loc'=>'','to_loc'=>''];
    $url = absolute_url($r['item_photo']);
    $photoCell = ($format === 'xls')
      ? ($url ? '<img src="'.htmlspecialchars($url).'" width="120" />' : '')
      : $url;

    $data[] = [
      $r['movement_id'], $m['created_at'], $m['user_name'], $m['from_loc'], $m['to_loc'],
      $r['item_id'], $r['item_name'], $r['item_sku'], $r['qty'], $photoCell
    ];
  }

  $fname = 'movements_'.date('Y-m-d_His').'.'.($format==='xls'?'xls':'csv');
  if ($format === 'xls') send_xls($fname, $header, $data);
  else                   send_csv($fname, $header, $data);
}

// Якщо сюди дійшли — невірний тип/формат
http_response_code(400);
echo 'Невірний тип або формат.';
