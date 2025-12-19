<?php
require_once 'config.php';

// Проверка авторизации
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit;
}

$errors = [];
$oldData = [
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? '')
];

// Валидация заголовка
if (empty($oldData['title'])) {
    $errors['title'] = 'Заголовок обязателен для заполнения';
} elseif (strlen($oldData['title']) > 255) {
    $errors['title'] = 'Заголовок не должен превышать 255 символов';
}

// Валидация описания
if (strlen($oldData['description']) > 1000) {
    $errors['description'] = 'Описание не должно превышать 1000 символов';
}

// Валидация файла
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors['file'] = 'Файл обязателен для загрузки';
} elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errors['file'] = 'Ошибка при загрузке файла. Код ошибки: ' . $_FILES['file']['error'];
} else {
    $file = $_FILES['file'];
    
    // Проверка размера файла
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors['file'] = 'Файл слишком большой. Максимальный размер: 10MB';
    }
    
    // Проверка расширения
    $pathInfo = pathinfo($file['name']);
    $extension = strtolower($pathInfo['extension'] ?? '');
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors['file'] = 'Недопустимое расширение файла. Разрешенные: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Проверка MIME-типа
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors['file'] = 'Недопустимый тип файла';
    }
    
    // Проверка на вредоносный контент
    if ($extension === 'php' || strpos($file['name'], '.php') !== false) {
        $errors['file'] = 'Загрузка PHP файлов запрещена';
    }
}

// Если есть ошибки, сохраняем их в сессии и возвращаем на форму
if (!empty($errors)) {
    $_SESSION['add_form_errors'] = $errors;
    $_SESSION['add_form_data'] = $oldData;
    header('Location: add.php');
    exit;
}

// Создаем папку uploads, если ее нет
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Генерируем уникальное имя для файла
$originalName = $_FILES['file']['name'];
$fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$uniqueName = uniqid() . '.' . $fileExtension;
$uploadPath = UPLOAD_DIR . $uniqueName;

// Перемещаем файл
if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
    $errors['file'] = 'Не удалось сохранить файл';
    $_SESSION['add_form_errors'] = $errors;
    $_SESSION['add_form_data'] = $oldData;
    header('Location: add.php');
    exit;
}

// Сохраняем пост в БД
try {
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, title, description, filename, original_name) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $oldData['title'],
        $oldData['description'],
        $uniqueName,
        $originalName
    ]);
    
    $postId = $pdo->lastInsertId();
    
    // Перенаправляем на страницу созданного поста
    header("Location: post.php?id=" . $postId);
    exit;
    
} catch (PDOException $e) {
    // В случае ошибки удаляем загруженный файл
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    $errors['db'] = 'Ошибка при сохранении в базу данных';
    $_SESSION['add_form_errors'] = $errors;
    $_SESSION['add_form_data'] = $oldData;
    header('Location: add.php');
    exit;
}
?>