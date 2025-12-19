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

// Валидация данных
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($name)) $errors['name'] = 'Имя обязательно';
if (empty($email)) {
    $errors['email'] = 'Email обязателен';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Неверный формат email';
}

if (empty($password)) {
    $errors['password'] = 'Пароль обязателен';
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Пароль должен быть не менее 6 символов';
} elseif ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Пароли не совпадают';
}

// Проверка уникальности email
if (empty($errors['email'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Пользователь с таким email уже зарегистрирован';
    }
}

if (!empty($errors)) {
    echo json_encode(['errors' => $errors]);
    exit;
}

// Хеширование пароля и сохранение пользователя
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, $phone, $passwordHash]);

// Авторизация после регистрации
$userId = $pdo->lastInsertId();
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_role'] = 'user';

echo json_encode(['success' => true]);
?>