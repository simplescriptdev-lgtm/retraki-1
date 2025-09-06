<?php
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
    // 🔧 МІГРАЦІЇ
    migrate_items_deleted_at($pdo);      // (було раніше, залишаємо)
    migrate_categories($pdo);            // ← НОВЕ
    migrate_items_category_id($pdo);     // ← НОВЕ

    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE,
        login TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK (role IN ("manager","user")),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

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
        -- category_id та deleted_at додаємо міграціями
    )');

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

    $pdo->exec('CREATE TABLE movement_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        movement_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        qty INTEGER NOT NULL,
        FOREIGN KEY (movement_id) REFERENCES movements(id),
        FOREIGN KEY (item_id) REFERENCES items(id)
    )');

    $pdo->exec('CREATE TABLE audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        meta TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
}

function seed_data(PDO $pdo): void {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, login, password, role) VALUES (:n,:e,:l,:p,:r)');
    $stmt->execute([':n'=>'Manager', ':e'=>'manager@example.com', ':l'=>'manager', ':p'=>password_hash('manager123', PASSWORD_DEFAULT), ':r'=>'manager']);

    $pdo->exec("INSERT INTO locations (name) VALUES ('Склад Ретраки'), ('Сервісний відділ')");
    $pdo->exec("INSERT INTO items (name, brand, sku, sector, notes, qty, photo) VALUES
        ('Колесо ведуче 230х70','Linde','W230-70','A1','PU, стандарт', 4, NULL),
        ('Зарядний пристрій 24В','Jungheinrich','C24-15','B2','Швидка зарядка', 2, NULL),
        ('Ролик напрямний','BT','RL-18','C3','Сталь, підшипник 6002', 12, NULL)
    ");
}

/* ====== МІГРАЦІЇ ====== */

// було раніше (для мʼяких видалень)
function migrate_items_deleted_at(PDO $pdo): void {
    $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll();
    $has = false;
    foreach ($cols as $c) if (strcasecmp((string)$c['name'], 'deleted_at') === 0) { $has = true; break; }
    if (!$has) $pdo->exec("ALTER TABLE items ADD COLUMN deleted_at TEXT DEFAULT NULL");
}

// НОВЕ: таблиця categories
function migrate_categories(PDO $pdo): void {
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='categories'")->fetchColumn();
    if (!$exists) {
        $pdo->exec('CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        // початкові приклади
        $pdo->exec("INSERT INTO categories (name) VALUES ('Шини/колеса'), ('Електрика'), ('Механіка')");
    }
}

// НОВЕ: колонка items.category_id + FK (логічний)
function migrate_items_category_id(PDO $pdo): void {
    $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll();
    $has = false;
    foreach ($cols as $c) if (strcasecmp((string)$c['name'], 'category_id') === 0) { $has = true; break; }
    if (!$has) {
        $pdo->exec("ALTER TABLE items ADD COLUMN category_id INTEGER DEFAULT NULL");
        // існуючим товарам проставимо першу категорію (якщо є)
        $first = $pdo->query("SELECT id FROM categories ORDER BY id LIMIT 1")->fetchColumn();
        if ($first) $pdo->exec("UPDATE items SET category_id = ".((int)$first)." WHERE category_id IS NULL");
    }
}
