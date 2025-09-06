<?php
// logic/users_logic.php — створення користувачів (NULL email + перевірки)
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';
require_manager();

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $name     = trim($_POST['name']  ?? '');
    $login    = trim($_POST['login'] ?? '');
    $emailRaw = trim($_POST['email'] ?? '');
    $password = $_POST['password']   ?? '';
    $role     = $_POST['role']       ?? 'user';

    if ($name === '' || $login === '' || $password === '') {
        $_SESSION['users_err'] = 'Заповніть обовʼязкові поля: Ім’я, Логін, Пароль.';
        header('Location: /dashboard.php?open=manage_users'); exit;
    }

    // Порожній email -> NULL, щоб не порушувати UNIQUE
    $email = ($emailRaw === '') ? null : $emailRaw;

    $db = db();

    // 1) Перевірка унікальності логіну
    $st = $db->prepare('SELECT 1 FROM users WHERE login = :l LIMIT 1');
    $st->execute([':l' => $login]);
    if ($st->fetchColumn()) {
        $_SESSION['users_err'] = 'Такий логін вже існує. Оберіть інший.';
        header('Location: /dashboard.php?open=manage_users'); exit;
    }

    // 2) Перевірка унікальності email (лише якщо не NULL)
    if ($email !== null) {
        $st = $db->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        if ($st->fetchColumn()) {
            $_SESSION['users_err'] = 'Такий email вже існує. Вкажіть інший або залиште порожнім.';
            header('Location: /dashboard.php?open=manage_users'); exit;
        }
    }

    try {
        $stmt = $db->prepare('INSERT INTO users (name, email, login, password, role)
                              VALUES (:n, :e, :l, :p, :r)');
        // биндинг із врахуванням NULL
        $stmt->bindValue(':n', $name,  PDO::PARAM_STR);
        $stmt->bindValue(':l', $login, PDO::PARAM_STR);
        $stmt->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(':r', $role,  PDO::PARAM_STR);
        if ($email === null) {
            $stmt->bindValue(':e', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':e', $email, PDO::PARAM_STR);
        }
        $stmt->execute();

        $_SESSION['users_ok'] = 'Користувача створено.';
    } catch (Throwable $e) {
        // На всяк випадок зеленіша помилка
        $_SESSION['users_err'] = 'Помилка при створенні: ' . $e->getMessage();
    }

    header('Location: /dashboard.php?open=manage_users'); exit;
}

header('Location: /dashboard.php?open=manage_users');
