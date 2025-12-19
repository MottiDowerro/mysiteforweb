<?php
require_once 'config.php';

// Проверка авторизации
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// Получение ошибок из сессии
$errors = [];
if (isset($_SESSION['add_form_errors'])) {
    $errors = $_SESSION['add_form_errors'];
    unset($_SESSION['add_form_errors']);
}

// Получение старых данных из сессии
$oldData = [];
if (isset($_SESSION['add_form_data'])) {
    $oldData = $_SESSION['add_form_data'];
    unset($_SESSION['add_form_data']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить пост - Студенческий файлообменник</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background-color: #0056b3;
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

    <div class="form-container">
        <h1>Добавить новый пост</h1>
        
        <form action="handler.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Заголовок *</label>
                <input type="text" id="title" name="title" 
                       value="<?= htmlspecialchars($oldData['title'] ?? '') ?>" required>
                <?php if (isset($errors['title'])): ?>
                    <div class="error"><?= htmlspecialchars($errors['title']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description"><?= htmlspecialchars($oldData['description'] ?? '') ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <div class="error"><?= htmlspecialchars($errors['description']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="file">Файл *</label>
                <input type="file" id="file" name="file" required>
                <small>Максимальный размер: 10MB. Разрешенные форматы: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, JPEG, PNG</small>
                <?php if (isset($errors['file'])): ?>
                    <div class="error"><?= htmlspecialchars($errors['file']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">Добавить пост</button>
        </form>
    </div>

    <footer class="footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>
</body>
</html>