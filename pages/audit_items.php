<?php
// pages/audit_items.php — історія змін та видалень товару (fixed warnings)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
if (($user['role'] ?? '') !== 'manager') { http_response_code(403); echo 'Доступ лише для менеджера'; exit; }

$db = db();
$rows = $db->query("
  SELECT id, user_id, action, meta, created_at
    FROM audit_log
   WHERE action IN ('item_update','item_delete')
ORDER BY id DESC
")->fetchAll();

function summarize_diff(array $diff): string {
    $parts = [];
    foreach ($diff as $field => $pair) {
        $beforeVal = $pair['before'] ?? '';
        $afterVal  = $pair['after']  ?? '';

        // нормалізуємо до рядків
        $beforeStr = is_array($beforeVal) ? '[дані]' : (string)$beforeVal;
        $afterStr  = is_array($afterVal)  ? '[дані]' : (string)$afterVal;

        if ($field === 'photo') {
            $beforeStr = ($beforeStr !== '') ? 'було' : '—';
            $afterStr  = ($afterStr  !== '') ? 'стало' : '—';
        }

        // жодної інтерполяції — тільки конкатенація
        $parts[] = $field . ': "' . $beforeStr . '" → "' . $afterStr . '"';
    }
    return $parts ? implode('; ', $parts) : '—';
}
?>
<h2>Історія змін та видалення товару</h2>

<?php if (!$rows): ?>
  <div class="alert" style="background:#1f2937;color:#cbd5e1">Поки що немає записів.</div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th>Дата</th>
        <th>Дія</th>
        <th>Користувач</th>
        <th>ID товару</th>
        <th>Зміни</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
        $m = json_decode($r['meta'] ?? '{}', true) ?: [];
        $who    = $m['by']['name'] ?? ('#'.$r['user_id']);
        $itemId = $m['item_id']    ?? '—';
        $changes = ($r['action'] === 'item_update')
          ? summarize_diff((array)($m['diff'] ?? []))
          : 'Видалено (поля збережені у записі)';
      ?>
      <tr>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><?= ($r['action'] === 'item_update') ? 'Редагування' : 'Видалення' ?></td>
        <td><?= htmlspecialchars($who) ?></td>
        <td><?= htmlspecialchars((string)$itemId) ?></td>
        <td><?= htmlspecialchars($changes) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
