<?php
// pages/audit_items.php — історія змін та видалень товару з модальним переглядом деталей "до" видалення
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

        $beforeStr = is_array($beforeVal) ? '[дані]' : (string)$beforeVal;
        $afterStr  = is_array($afterVal)  ? '[дані]' : (string)$afterVal;

        if ($field === 'photo') {
            $beforeStr = ($beforeStr !== '') ? 'було' : '—';
            $afterStr  = ($afterStr  !== '') ? 'стало' : '—';
        }
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
        <th>Зміни / Деталі</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
        $m = json_decode($r['meta'] ?? '{}', true) ?: [];
        $who    = $m['by']['name'] ?? ('#'.$r['user_id']);
        $itemId = $m['item_id']    ?? '—';
        $isDelete = ($r['action'] === 'item_delete');
        $changes = ($r['action'] === 'item_update')
          ? summarize_diff((array)($m['diff'] ?? []))
          : 'Видалено';
      ?>
      <tr>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><?= $isDelete ? 'Видалення' : 'Редагування' ?></td>
        <td><?= htmlspecialchars($who) ?></td>
        <td><?= htmlspecialchars((string)$itemId) ?></td>
        <td>
          <?php if ($isDelete): ?>
            <!-- Кнопка відкриває модальне вікно; дані "до" пакуємо в JSON у <script type="application/json"> -->
            <button class="btn secondary open-deleted-details" data-audit-id="<?= (int)$r['id'] ?>">Деталі</button>
            <script type="application/json" id="audit-json-<?= (int)$r['id'] ?>">
              <?= json_encode($m['before'] ?? new stdClass(), JSON_UNESCAPED_UNICODE) ?>
            </script>
          <?php else: ?>
            <?= htmlspecialchars($changes) ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- Модальне вікно (легкий inline-стиль, щоб не чіпати глобальні css) -->
<div id="deletedItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:16px">
  <div style="max-width:720px;width:100%;background:#0b1220;border:1px solid #1f2937;border-radius:12px;overflow:hidden">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #1f2937">
      <strong>Деталі видаленого товару</strong>
      <button id="modalCloseBtn" class="btn secondary" type="button">Закрити</button>
    </div>
    <div id="deletedItemBody" style="padding:16px">
      <!-- вміст підставляється JS -->
    </div>
  </div>
</div>

<script>
// JS: відкриваємо модалку та підставляємо дані з JSON
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.open-deleted-details');
  if (!btn) return;

  const id = btn.getAttribute('data-audit-id');
  const holder = document.getElementById('audit-json-' + id);
  if (!holder) return;

  /** @type {{name?:string,brand?:string,sku?:string,sector?:string,notes?:string,qty?:number,photo?:string,created_at?:string}} */
  let data = {};
  try { data = JSON.parse(holder.textContent || '{}') || {}; } catch (_) {}

  const body = document.getElementById('deletedItemBody');
  const img  = (data.photo && typeof data.photo === 'string')
    ? '<img src="' + data.photo + '" alt="Фото товару" style="width:260px;max-height:220px;object-fit:cover;border:1px solid #1f2937;border-radius:8px;background:#0a0f1e">'
    : '<div class="badge">Немає фото</div>';

  body.innerHTML = `
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      <div>${img}</div>
      <div style="flex:1;min-width:240px">
        <p><b>Назва:</b> ${escapeHtml(data.name || '—')}</p>
        <p><b>Бренд:</b> ${escapeHtml(data.brand || '—')}</p>
        <p><b>Артикул:</b> ${escapeHtml(data.sku || '—')}</p>
        <p><b>Сектор:</b> ${escapeHtml(data.sector || '—')}</p>
        <p><b>Кількість на момент видалення:</b> ${Number.isFinite(+data.qty) ? +data.qty : '—'}</p>
        <p><b>Створено:</b> ${escapeHtml(data.created_at || '—')}</p>
        <p><b>Нотатки:</b><br>${escapeHtml(data.notes || '—').replace(/\\n/g,'<br>')}</p>
      </div>
    </div>
  `;

  const modal = document.getElementById('deletedItemModal');
  modal.style.display = 'flex';
});

document.getElementById('modalCloseBtn').addEventListener('click', () => {
  document.getElementById('deletedItemModal').style.display = 'none';
});
document.getElementById('deletedItemModal').addEventListener('click', (e) => {
  if (e.target.id === 'deletedItemModal') {
    e.currentTarget.style.display = 'none';
  }
});

function escapeHtml(str) {
  return String(str)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'", '&#039;');
}
</script>
