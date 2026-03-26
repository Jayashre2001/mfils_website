<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Starting test...<br>";

$path = __DIR__ . '/config.php';
echo "2. Looking for config at: " . $path . "<br>";

if (file_exists($path)) {
    echo "3. Config file found!<br>";
    require_once $path;
    echo "4. Config loaded successfully<br>";
} else {
    echo "3. Config file NOT found!<br>";
    die("Config file missing");
}

echo "5. Testing database connection...<br>";
try {
    $pdo = db();
    echo "6. Database connected!<br>";
} catch (Exception $e) {
    echo "6. Database error: " . $e->getMessage() . "<br>";
}

phpinfo();