<?php
// auth/login.php — обробка логіну
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php'); exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    $_SESSION['error'] = 'Введіть логін/емейл і пароль.';
    header('Location: /index.php'); exit;
}

$db = db();
// Дозволяємо логін або email
$stmt = $db->prepare('SELECT * FROM users WHERE login = :id OR email = :id LIMIT 1');
$stmt->execute([':id' => $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Невірні дані для входу.';
    header('Location: /index.php'); exit;
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'role' => $user['role'],
];

header('Location: /dashboard.php');
