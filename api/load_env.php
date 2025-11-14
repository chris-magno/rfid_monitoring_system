<?php
// api/load_env.php

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    throw new Exception(".env file not found");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value, "\"' "); // remove quotes
    $_ENV[$key] = $value;
    putenv("$key=$value");
}
