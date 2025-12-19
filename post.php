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
        body { background-color: #ffffff; }
        .view-container {
            max-width: 1200px; margin: 40px auto; padding: 40px 20px;
            background-color: #ffffff;
        }
        .post-title {
            font-size: 48px; font-weight: 500; line-height: 60px;
            padding: 0 0 20px 0; width: 100%; margin: 0;
        }
        .post-description {
            color: #838383; font-size: 16px; width: 100%;
            padding: 20px 0; line-height: 1.5;
        }
        .post-meta {
            font-family: 'Inter', sans-serif; color: #838383; font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 30px; }
        label {
            display: block; margin-bottom: 8px; font-family: 'Inter', sans-serif;
            font-weight: 500; font-size: 16px; color: #495057;
        }
        
        .file-display-chip {
            display: inline-flex; align-items: center; gap: 10px;
            height: 44px; border-radius: 6px; padding: 0 16px;
            background: rgba(0, 0, 0, 0.03); box-sizing: border-box;
        }
        .file-name {
            font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #181818;
        }
        .file-size {
            font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #838383;
        }
        .download-btn {
            display: flex; align-items: center; justify-content: center;
            width: 184px; height: 44px; border-radius: 6px;
            background: rgba(166, 200, 30, 0.2); color: #A6C81E;
            font-family: 'Inter', sans-serif; font-weight: 500;
            gap: 10px; text-decoration: none;
        }

        .post-actions {
            margin-top: 40px; display: flex; gap: 20px;
        }
        .action-btn {
            width: 196px; height: 60px; display: flex; align-items: center; justify-content: center;
            border-radius: 10px; text-decoration: none; color: white;
            font-size: 16px; font-weight: 600;
        }
        .edit-btn { background-color: #A6C81E; }
        .delete-btn { background-color: #dc3545; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo"> <a href="index.php"><img src="images/logo.svg" alt="logotype" class="logo-img"></a> </div>
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

    <main class="view-container">
        <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
        
        <p class="post-meta">
            Добавлено: <?= date('d.m.Y H:i', strtotime($post['uploaded_at'])) ?> &nbsp;&nbsp;|&nbsp;&nbsp; Автор: <?= htmlspecialchars($post['author_name']) ?>
        </p>

        <?php if (!empty($post['description'])): ?>
            <div class="post-description">
                <?= nl2br(htmlspecialchars($post['description'])) ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Прикрепленный файл:</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="file-display-chip">
                    <span class="file-name"><?= htmlspecialchars($post['original_name']) ?></span>
                    <span class="file-size" id="file-size"></span>
                </div>
                <a class="download-btn" href="download.php?id=<?= $post['id'] ?>">
                    Скачать файл
                </a>
            </div>
        </div>

        <?php if ($canEditDelete): ?>
            <div class="post-actions">
                <a href="edit.php?id=<?= $post['id'] ?>" class="action-btn edit-btn">Редактировать</a>
                <a href="delete.php?id=<?= $post['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Вы уверены, что хотите удалить этот пост?')">
                    Удалить
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer add-post-footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>

    <script>
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        const fileSizeInBytes = <?= file_exists(UPLOAD_DIR . $post['filename']) ? filesize(UPLOAD_DIR . $post['filename']) : 0 ?>;
        document.getElementById('file-size').textContent = formatFileSize(fileSizeInBytes);
    </script>
</body>
</html>
