<?php
// config/db.php
// PDO connection using values from .env

// Simple .env loader (returns array of key=>value)
function load_dotenv($path) {
    $env = [];
    if (!file_exists($path)) return $env;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Strip surrounding quotes if present
        if ((substr($val, 0, 1) === '"' && substr($val, -1) === '"') ||
            (substr($val, 0, 1) === "'" && substr($val, -1) === "'")) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }
    return $env;
}

// load .env from project root
$rootEnvPath = dirname(__DIR__) . '/.env';
$env = load_dotenv($rootEnvPath);

// fallback defaults
$dbHost = $env['DB_HOST'] ?? '127.0.0.1';
$dbName = $env['DB_NAME'] ?? 'rfid_monitoring';
$dbUser = $env['DB_USER'] ?? 'root';
$dbPass = $env['DB_PASS'] ?? '';
$charset = 'utf8mb4';

// configure timezone if provided
if (!empty($env['APP_TIMEZONE'])) {
    date_default_timezone_set($env['APP_TIMEZONE']);
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // try to log to file (logs folder should be writable)
    $msg = '[' . date('Y-m-d H:i:s') . '] DB Connection Error: ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(dirname(__DIR__) . '/../logs/system.log', $msg, FILE_APPEND);
    // fail fast with a simple message (do not reveal sensitive info)
    http_response_code(500);
    die('Database connection failed. Check logs.');
}
