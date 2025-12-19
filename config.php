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

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'user';
?>