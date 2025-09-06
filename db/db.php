<?php
// ===========================================
// db/db.php — єдиний файл для роботи з БД (SQLite)
// ===========================================
declare(strict_types=1);

const DB_FILE = __DIR__ . '/database.sqlite';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $needInit = !file_exists(DB_FILE);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($needInit) {
        init_schema($pdo);
        seed_data($pdo);
    }
    // 🔧 Міграція: додаємо items.deleted_at при потребі
    migrate_items_deleted_at($pdo);

    return $pdo;
}

function init_schema(PDO $pdo): void {
    // Користувачі
    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE,
        login TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK (role IN ("manager","user")),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    // Локації
    $pdo->exec('CREATE TABLE locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    // Товари
    $pdo->exec('CREATE TABLE items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        brand TEXT,
        sku TEXT,
        sector TEXT,
        notes TEXT,
        qty INTEGER NOT NULL DEFAULT 0,
        photo TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    // Транзакції
    $pdo->exec('CREATE TABLE movements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        from_location_id INTEGER,
        to_location_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (from_location_id) REFERENCES locations(id),
        FOREIGN KEY (to_location_id) REFERENCES locations(id)
    )');

    // Позиції транзакцій
    $pdo->exec('CREATE TABLE movement_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        movement_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        qty INTEGER NOT NULL,
        FOREIGN KEY (movement_id) REFERENCES movements(id),
        FOREIGN KEY (item_id) REFERENCES items(id)
    )');

    // Журнал дій
    $pdo->exec('CREATE TABLE audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        meta TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
}

function seed_data(PDO $pdo): void {
    $name = 'Manager';
    $login = 'manager';
    $email = 'manager@example.com';
    $pass = password_hash('manager123', PASSWORD_DEFAULT);
    $role = 'manager';

    $stmt = $pdo->prepare('INSERT INTO users (name, email, login, password, role) VALUES (:n,:e,:l,:p,:r)');
    $stmt->execute([':n'=>$name, ':e'=>$email, ':l'=>$login, ':p'=>$pass, ':r'=>$role]);

    $pdo->exec("INSERT INTO locations (name) VALUES ('Склад Ретраки'), ('Сервісний відділ')");

    $pdo->exec("INSERT INTO items (name, brand, sku, sector, notes, qty, photo) VALUES
        ('Колесо ведуче 230х70','Linde','W230-70','A1','PU, стандарт', 4, NULL),
        ('Зарядний пристрій 24В','Jungheinrich','C24-15','B2','Швидка зарядка', 2, NULL),
        ('Ролик напрямний','BT','RL-18','C3','Сталь, підшипник 6002', 12, NULL)
    ");
}

// 🔧 Міграція: додаємо колонку deleted_at у items, якщо її ще немає
function migrate_items_deleted_at(PDO $pdo): void {
    $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll();
    $hasDeletedAt = false;
    foreach ($cols as $c) {
        if (strcasecmp((string)$c['name'], 'deleted_at') === 0) { $hasDeletedAt = true; break; }
    }
    if (!$hasDeletedAt) {
        $pdo->exec("ALTER TABLE items ADD COLUMN deleted_at TEXT DEFAULT NULL");
    }
}
