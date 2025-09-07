<?php
// logic/export.php — експорт у CSV або Excel (HTML .xls)
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

/** helpers **/
function send_csv(string $filename, array $header, array $rows): void {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  // UTF-8 BOM для коректного відкриття в Excel
  echo "\xEF\xBB\xBF";
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
  echo '<table border="1"><thead><tr>';
  foreach ($header as $h) echo '<th>'.htmlspecialchars($h).'</th>';
  echo '</tr></thead><tbody>';
  foreach ($rows as $r) {
    echo '<tr>';
    foreach ($r as $cell) echo '<td>'.htmlspecialchars((string)$cell).'</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  exit;
}

/** data builders **/
if ($type === 'inventory') {
  // список товарів без видалених
  $stmt = $db->query("
    SELECT i.id, i.name, i.brand, i.sku, i.sector, i.notes, i.qty, IFNULL(c.name,'') AS category, i.created_at
      FROM items i
 LEFT JOIN categories c ON c.id = i.category_id
     WHERE i.deleted_at IS NULL
  ORDER BY i.id ASC
  ");
  $rows = $stmt->fetchAll();

  $header = ['ID','Назва','Бренд','Артикул','Сектор','Нотатки','Кількість','Категорія','Створено'];
  $data = [];
  foreach ($rows as $r) {
    $data[] = [
      $r['id'], $r['name'], $r['brand'], $r['sku'], $r['sector'],
      $r['notes'], $r['qty'], $r['category'], $r['created_at']
    ];
  }

  $fname = 'inventory_'.date('Y-m-d_His').'.'.($format==='xls'?'xls':'csv');
  if ($format === 'xls') send_xls($fname, $header, $data);
  else send_csv($fname, $header, $data);
}

/* movements with details:
   кожен рядок — одна позиція movement_items
   колонки: move_id, дата, користувач, з_локації, в_локацію, item_id, назва, sku, qty
*/
if ($type === 'movements') {
  // спочатку — шапки переміщень
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
  // індекс для швидкого доступу
  $mIndex = [];
  foreach ($mov as $m) $mIndex[$m['id']] = $m;

  // деталі
  $stmt = $db->query("
    SELECT mi.movement_id, mi.item_id, mi.qty,
           IFNULL(i.name,'') AS item_name,
           IFNULL(i.sku,'')  AS item_sku
      FROM movement_items mi
 LEFT JOIN items i ON i.id = mi.item_id
  ORDER BY mi.movement_id ASC, mi.id ASC
  ");
  $rows = $stmt->fetchAll();

  $header = ['MoveID','Дата','Хто','Звідки','Куди','ItemID','Назва товару','SKU','Кількість'];
  $data = [];
  foreach ($rows as $r) {
    $m = $mIndex[$r['movement_id']] ?? ['created_at'=>'','user_name'=>'','from_loc'=>'','to_loc'=>''];
    $data[] = [
      $r['movement_id'],
      $m['created_at'], $m['user_name'], $m['from_loc'], $m['to_loc'],
      $r['item_id'], $r['item_name'], $r['item_sku'], $r['qty']
    ];
  }

  $fname = 'movements_'.date('Y-m-d_His').'.'.($format==='xls'?'xls':'csv');
  if ($format === 'xls') send_xls($fname, $header, $data);
  else send_csv($fname, $header, $data);
}

// якщо щось не зійшлось:
http_response_code(400);
echo 'Невірний тип або формат.';
