<?php
session_start();

$config = parse_ini_file('config/parameters.ini');

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']};charset=utf8", 
                   $config['login'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . htmlspecialchars($e->getMessage()));
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'user';
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
    'application/zip',
    'application/x-rar-compressed',
    'image/jpeg',
    'image/png'
]);
?>