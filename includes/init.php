<?php
$config = require __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_start();
}

function get_system_db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPath = $config['data_path'] . '/system.sqlite';
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        db_path TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');

    return $pdo;
}

function sanitize(null|string|int|float $value): string
{
    if ($value === null) {
        return '';
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function create_user_database(string $dbPath): void
{
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $schemaStatements = [
        'CREATE TABLE IF NOT EXISTS purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            reference TEXT,
            description TEXT,
            amount REAL NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            client_name TEXT,
            reference TEXT,
            description TEXT,
            amount REAL NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS bank_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            bank_name TEXT NOT NULL,
            description TEXT,
            amount REAL NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS cash_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            movement_type TEXT NOT NULL,
            description TEXT,
            amount REAL NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS payables (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            party_name TEXT NOT NULL,
            description TEXT,
            amount REAL NOT NULL,
            due_date TEXT,
            status TEXT DEFAULT "pending"
        )',
        'CREATE TABLE IF NOT EXISTS receivables (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            party_name TEXT NOT NULL,
            description TEXT,
            amount REAL NOT NULL,
            due_date TEXT,
            status TEXT DEFAULT "pending"
        )',
        'CREATE TABLE IF NOT EXISTS parties (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            contact_info TEXT,
            notes TEXT
        )',
        'CREATE TABLE IF NOT EXISTS vendors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            contact_info TEXT,
            notes TEXT
        )',
        'CREATE TABLE IF NOT EXISTS credits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_date TEXT NOT NULL,
            source TEXT,
            description TEXT,
            amount REAL NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS sales_returns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER,
            record_date TEXT NOT NULL,
            amount REAL NOT NULL,
            reason TEXT,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )'
    ];

    foreach ($schemaStatements as $statement) {
        $pdo->exec($statement);
    }
}

function get_user_db(string $path): PDO
{
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function require_admin(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: /access/index.php');
        exit;
    }
}

function require_user(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_db'])) {
        header('Location: /account-panel/index.php');
        exit;
    }
}
