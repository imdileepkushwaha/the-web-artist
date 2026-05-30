<?php

require_once __DIR__ . '/admin-schema.php';

function twaDetectAppEnvironment(): string
{
    $forced = getenv('TWA_ENV');

    if ($forced !== false && $forced !== '') {
        return strtolower($forced) === 'local' ? 'local' : 'server';
    }

    if (PHP_SAPI === 'cli') {
        return 'local';
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    $host = preg_replace('/:\d+$/', '', $host);

    if (in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
        return 'local';
    }

    if (preg_match('/\.(local|test|localhost)$/', $host)) {
        return 'local';
    }

    return 'server';
}

function twaLoadDatabaseConfig(): array
{
    $environment = twaDetectAppEnvironment();
    $configFile = __DIR__ . '/database.' . $environment . '.php';

    if (!is_file($configFile)) {
        throw new RuntimeException('Database config file not found: ' . $configFile);
    }

    $config = require $configFile;

    if (!is_array($config)) {
        throw new RuntimeException('Invalid database config: ' . $configFile);
    }

    $config['environment'] = $environment;

    return $config;
}

$dbConfig = twaLoadDatabaseConfig();

if (!defined('DB_ENV')) {
    define('DB_ENV', $dbConfig['environment']);
}

if (!defined('DB_HOST')) {
    define('DB_HOST', $dbConfig['host']);
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $dbConfig['name']);
}

if (!defined('DB_USER')) {
    define('DB_USER', $dbConfig['user']);
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $dbConfig['pass']);
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', $dbConfig['charset'] ?? 'utf8mb4');
}

function getDbConnection(): PDO
{
    static $conn = null;

    if ($conn instanceof PDO) {
        return $conn;
    }

    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    ensureAllAdminTables($conn);

    return $conn;
}

function ensureEnquiriesTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS enquiries (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        service VARCHAR(100) NOT NULL,
        message TEXT,
        status ENUM('new', 'read', 'contacted', 'closed') NOT NULL DEFAULT 'new',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = $conn->query('SHOW COLUMNS FROM enquiries')->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('status', $columns, true)) {
        $conn->exec("ALTER TABLE enquiries ADD COLUMN status ENUM('new', 'read', 'contacted', 'closed') NOT NULL DEFAULT 'new' AFTER message");
    }

    if (!in_array('admin_notes', $columns, true)) {
        $conn->exec('ALTER TABLE enquiries ADD COLUMN admin_notes TEXT NULL AFTER status');
    }

    if (!in_array('updated_at', $columns, true)) {
        $conn->exec('ALTER TABLE enquiries ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    }

    ensureEnquiryNotesTable($conn);
}

function ensureEnquiryNotesTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS enquiry_notes (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        enquiry_id INT(11) UNSIGNED NOT NULL,
        note TEXT NOT NULL,
        created_by VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_enquiry_id (enquiry_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $legacyNotes = $conn->query("SELECT id, admin_notes FROM enquiries WHERE admin_notes IS NOT NULL AND TRIM(admin_notes) != ''")->fetchAll();

    foreach ($legacyNotes as $row) {
        $check = $conn->prepare('SELECT COUNT(*) AS count FROM enquiry_notes WHERE enquiry_id = :enquiry_id');
        $check->execute([':enquiry_id' => $row['id']]);

        if ((int) ($check->fetch()['count'] ?? 0) === 0) {
            $insert = $conn->prepare('INSERT INTO enquiry_notes (enquiry_id, note, created_by) VALUES (:enquiry_id, :note, :created_by)');
            $insert->execute([
                ':enquiry_id' => $row['id'],
                ':note' => $row['admin_notes'],
                ':created_by' => 'admin',
            ]);
        }
    }
}
