<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден");
}

// Проверка прав доступа для скачивания (пример)
$canDownload = $isLoggedIn && $userRole === 'user'; // или другая логика по варианту
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .welcome-text {
            margin-right: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.svg" alt="logotype" class="logo-img">
        </div>
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-text">Привет, <?= htmlspecialchars($userName) ?>!</span>
                <a href="logout.php" class="btn logout-btn">Выход</a>
            <?php else: ?>
                <a href="index.php" class="btn register-btn">Регистрация</a>
                <a href="index.php" class="btn login-btn">Вход</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="post-detail" style="padding: 20px; max-width: 800px; margin: 0 auto;">
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <p><strong>Дата добавления:</strong> <?= date('d.m.Y H:i', strtotime($post['uploaded_at'])) ?></p>
        <p><strong>Автор:</strong> <?= htmlspecialchars($post['author_name']) ?></p>
        <p><strong>Описание:</strong><br><?= nl2br(htmlspecialchars($post['description'])) ?></p>

        <?php if ($canDownload): ?>
            <p>
                <a class="btn" href="uploads/<?= htmlspecialchars($post['filename']) ?>" download>
                    Скачать файл: <?= htmlspecialchars($post['original_name']) ?>
                </a>
            </p>
        <?php else: ?>
            <p>Для скачивания файла необходимо <a href="index.php" class="login-btn">авторизоваться</a></p>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>
</body>
</html>