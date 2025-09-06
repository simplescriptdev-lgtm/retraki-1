<?php
// logic/helpers.php — спільні помічники
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../db/db.php';

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function require_manager(): void {
    $u = current_user();
    if (($u['role'] ?? '') !== 'manager') {
        http_response_code(403);
        echo 'Доступ лише для менеджера'; exit;
    }
}

function json_response($data): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
