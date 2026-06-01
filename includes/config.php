<?php
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'IBT Project');
define('STUDENT_NAME', $_ENV['STUDENT_NAME'] ?? 'Aram Stepan Derandonyan');
define('STUDENT_SPECIALTY', $_ENV['STUDENT_SPECIALTY'] ?? 'ISN');
define('STUDENT_FN', $_ENV['STUDENT_FN'] ?? 'XXXXXXXX');

define('CEREBRAS_API_KEY', $_ENV['CEREBRAS_API_KEY'] ?? '');
define('CEREBRAS_MODEL',   $_ENV['CEREBRAS_MODEL']   ?? 'llama3.1-70b');

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'ibt_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
