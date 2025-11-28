<?php
$config = parse_ini_file('config/parameters.ini');
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']};charset=utf8", 
                   $config['login'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . htmlspecialchars($e->getMessage()));
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.svg" alt="logotype" class="logo-img">
        </div>
        <div class="auth-buttons">
            <button class="btn register-btn">Регистрация</button>
            <button class="btn login-btn">Вход</button>
        </div>
    </header>

    <main class="post-detail">
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <p><strong>Дата добавления:</strong> <?= date('d.m.Y H:i', strtotime($post['upload_date'])) ?></p>
        <p><strong>Автор:</strong> Студент (временно)</p>
        <p><strong>Описание:</strong><br><?= nl2br(htmlspecialchars($post['description'])) ?></p>

        <?php if (/* ПОЗЖЕ: isset($_SESSION['user']) */ true): ?>
            <p>
                <a class="btn" href="uploads/<?= htmlspecialchars($post['filename']) ?>" download>
                    Скачать файл: <?= htmlspecialchars($post['original_name']) ?>
                </a>
            </p>
        <?php else: ?>
            <p>Файл доступен только авторизованным пользователям.</p>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>
</body>
</html>