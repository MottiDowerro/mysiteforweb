<?php
session_start();
header('Content-Type: application/json');

$config = parse_ini_file('config/parameters.ini');
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']};charset=utf8", 
                   $config['login'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['errors' => ['db' => 'Ошибка подключения к БД']]);
    exit;
}

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email)) $errors['email'] = 'Email обязателен';
if (empty($password)) $errors['password'] = 'Пароль обязателен';

if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        echo json_encode(['success' => true]);
    } else {
        $errors['email'] = 'Неверный email или пароль';
        echo json_encode(['errors' => $errors]);
    }
} else {
    echo json_encode(['errors' => $errors]);
}
?>