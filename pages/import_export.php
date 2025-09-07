<?php
// pages/import_export.php — експорт даних
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

$user = $_SESSION['user'] ?? null;
// якщо треба дозволити не лише менеджеру — закоментуй перевірку
if (($user['role'] ?? '') !== 'manager') { http_response_code(403); echo 'Доступ лише для менеджера'; exit; }
?>
<h2>Імпорт / Експорт</h2>

<div class="card" style="margin-bottom:16px">
  <div class="body">
    <h3 style="margin-top:0">Експорт — Залишки товару</h3>
    <p>Вивантажити актуальний список товарів (без видалених) із категоріями.</p>
    <div class="row" style="gap:8px;flex-wrap:wrap">
      <a class="btn" href="/logic/export.php?type=inventory&format=xls">Excel</a>
      <a class="btn secondary" href="/logic/export.php?type=inventory&format=csv">CSV</a>
    </div>
  </div>
</div>

<div class="card">
  <div class="body">
    <h3 style="margin-top:0">Експорт — Історія переміщення та деталі</h3>
    <p>Кожен рядок містить одну позицію переміщення (із шапкою транзакції).</p>
    <div class="row" style="gap:8px;flex-wrap:wrap">
      <a class="btn" href="/logic/export.php?type=movements&format=xls">Excel</a>
      <a class="btn secondary" href="/logic/export.php?type=movements&format=csv">CSV</a>
    </div>
  </div>
</div>
