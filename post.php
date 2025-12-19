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

// Проверка прав доступа (только автор или админ может удалять/редактировать)
$canEditDelete = $isLoggedIn && ($userRole === 'admin' || $userId === $post['user_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> - Студенческий файлообменник</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .post-detail {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .post-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .file-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
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

    <main class="post-detail">
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <p><strong>Дата добавления:</strong> <?= date('d.m.Y H:i', strtotime($post['uploaded_at'])) ?></p>
        <p><strong>Автор:</strong> <?= htmlspecialchars($post['author_name']) ?></p>
        
        <?php if (!empty($post['description'])): ?>
            <div class="post-content">
                <h3>Описание:</h3>
                <p><?= nl2br(htmlspecialchars($post['description'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="file-info">
            <h3>Прикрепленный файл:</h3>
            <p><strong>Имя файла:</strong> <?= htmlspecialchars($post['original_name']) ?></p>
            <p><strong>Размер:</strong> 
                <?php
                $filePath = UPLOAD_DIR . $post['filename'];
                if (file_exists($filePath)) {
                    $size = filesize($filePath);
                    if ($size < 1024) {
                        echo $size . ' байт';
                    } elseif ($size < 1048576) {
                        echo round($size / 1024, 2) . ' КБ';
                    } else {
                        echo round($size / 1048576, 2) . ' МБ';
                    }
                } else {
                    echo 'Неизвестно';
                }
                ?>
            </p>
            <p>
                <a class="btn" href="download.php?id=<?= $post['id'] ?>">
                    Скачать файл
                </a>
            </p>
        </div>

        <?php if ($canEditDelete): ?>
            <div class="post-actions">
                <h3>Действия:</h3>
                <a href="edit.php?id=<?= $post['id'] ?>" class="btn">Редактировать</a>
                <a href="delete.php?id=<?= $post['id'] ?>" 
                   class="btn" 
                   onclick="return confirm('Вы уверены, что хотите удалить этот пост?')">
                    Удалить
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>
</body>
</html>