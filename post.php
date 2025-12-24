<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS author_name, s.label AS status_label
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN statuses s ON p.status_code = s.code
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

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

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
            max-width: 1200px; margin: 20px auto 40px auto; padding: 0 20px 40px 20px;
            background-color: #ffffff;
        }
        .post-title {
            font-family: 'Inter', sans-serif;
            font-size: 48px;
            font-weight: 500;
            line-height: 60px;
            letter-spacing: 0;
            padding: 0 0 0px 0;
            width: 100%;
            margin: 0;
        }
        .post-description {
            color: black; font-size: 16px; width: 100%;
            padding: 20px 0 40px 0; line-height: 1.5;
        }
        .post-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-style: normal;
            color: #838383;
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0px;
            margin-bottom: 15px;
        }
        .meta-dot {
            width: 4px;
            height: 4px;
            background-color: #838383;
            border-radius: 50%;
        }
        .form-group { margin-bottom: 30px; }
        label {
            display: block; margin-bottom: 8px; font-family: 'Inter', sans-serif;
            font-weight: 500; font-size: 16px; color: #495057;
        }
        
        .file-display-chip {
            display: inline-flex; align-items: center; gap: 10px;
            height: 44px; border-radius: 6px; padding: 0 16px;
            background: rgba(166, 200, 30, 0.2);
            box-sizing: border-box;
            text-decoration: none;
        }
        .file-display-chip:hover {
            background: rgba(166, 200, 30, 0.3);
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
            width: 140px; height: 44px; display: flex; align-items: center; justify-content: center;
            border-radius: 6px; text-decoration: none; color: white;
            font-size: 14px; font-weight: 600;
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

        <!-- Отображение статуса -->
        <div style="margin-top: 10px;">
            <?php 
                $statusColor = ($post['status_code'] === 'approved') ? '#28a745' : (($post['status_code'] === 'rejected') ? '#dc3545' : '#ffc107');
            ?>
            <span style="display: inline-block; padding: 5px 10px; border-radius: 4px; background-color: <?= $statusColor ?>; color: white; font-size: 14px; font-family: 'Inter', sans-serif;">
                <?= htmlspecialchars($post['status_label'] ?? 'На модерации') ?>
            </span>
        </div>

        <?php
            $months = [
                1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня',
                7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
            ];
            $timestamp = strtotime($post['uploaded_at']);
            $formatted_date = date('d', $timestamp) . ' ' . $months[(int)date('n', $timestamp)];
        ?>
        <p class="post-meta">
            <span>Добавлено <?= $formatted_date ?></span>
            <span class="meta-dot"></span>
            <span><?= htmlspecialchars($post['author_name']) ?></span>
        </p>

        <?php if (!empty($post['description'])): ?>
            <div class="post-description">
                <?= nl2br(htmlspecialchars($post['description'])) ?>
            </div>
        <?php endif; ?>

        <?php
        // 1. Получаем все файлы, связанные с этим постом, из новой таблицы
        $stmt_files = $pdo->prepare("SELECT * FROM post_files WHERE post_id = ? ORDER BY original_name ASC");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="form-group">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-start;">
                <?php if (empty($files)): ?>
                    <p>К этому посту не прикреплены файлы.</p>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <a class="file-display-chip" href="download.php?file_id=<?= $file['id'] ?>">
                            <span class="file-name"><?= htmlspecialchars($file['original_name']) ?></span>
                            <span class="file-size"><?= formatFileSize($file['file_size']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
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
</body>
</html>