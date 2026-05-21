<?php
// Configuration & Environment Loader

// 1. Simple .env parser
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Strip quotes if any
            if (preg_match('/^"($$[^"$$]*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^\'($$[^\'$$]*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// 2. Database configuration from individual env vars
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''),
    'name' => getenv('DB_NAME') ?: 'tracer'
];

return [
    'db' => $dbConfig,
    'cors_origin' => getenv('CORS_ORIGIN') ?: 'http://localhost:3000',
    'google_client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'session_secret' => getenv('SESSION_SECRET') ?: 'ganti-dengan-string-random-yang-panjang',
    'import_ttl_minutes' => (int)(getenv('IMPORT_TTL_MINUTES') ?: 15)
];
