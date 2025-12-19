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

// Валидация файлов
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    $errors['file'] = 'Как минимум один файл обязателен для загрузки';
} else {
    $files = $_FILES['files'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors['file'] = 'Ошибка при загрузке файла ' . $files['name'][$i];
            continue;
        }

        if ($files['size'][$i] > MAX_FILE_SIZE) {
            $errors['file'] = 'Файл ' . $files['name'][$i] . ' слишком большой. Максимальный размер: 10MB';
        }
        
        $pathInfo = pathinfo($files['name'][$i]);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            $errors['file'] = 'Недопустимое расширение для файла ' . $files['name'][$i];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            $errors['file'] = 'Недопустимый тип для файла ' . $files['name'][$i];
        }

        if ($extension === 'php' || strpos($files['name'][$i], '.php') !== false) {
            $errors['file'] = 'Загрузка PHP файлов запрещена';
        }
    }
}

if (!empty($errors)) {
    $_SESSION['add_form_errors'] = $errors;
    $_SESSION['add_form_data'] = $oldData;
    header('Location: add.php');
    exit;
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$originalNames = [];
$uniqueNames = [];
$uploadedPaths = [];

$files = $_FILES['files'];
$fileCount = count($files['name']);

for ($i = 0; $i < $fileCount; $i++) {
    $originalName = $files['name'][$i];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $uniqueName = uniqid() . '.' . $fileExtension;
    $uploadPath = UPLOAD_DIR . $uniqueName;

    if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
        $originalNames[] = $originalName;
        $uniqueNames[] = $uniqueName;
        $uploadedPaths[] = $uploadPath;
    } else {
        // В случае ошибки удалить уже загруженные файлы
        foreach ($uploadedPaths as $path) {
            unlink($path);
        }
        $errors['file'] = 'Не удалось сохранить файл ' . $originalName;
        $_SESSION['add_form_errors'] = $errors;
        $_SESSION['add_form_data'] = $oldData;
        header('Location: add.php');
        exit;
    }
}

$originalNamesStr = implode(',', $originalNames);
$uniqueNamesStr = implode(',', $uniqueNames);

try {
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, title, description, filename, original_name) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $oldData['title'],
        $oldData['description'],
        $uniqueNamesStr,
        $originalNamesStr
    ]);
    
    $postId = $pdo->lastInsertId();
    
    header("Location: post.php?id=" . $postId);
    exit;
    
} catch (PDOException $e) {
    foreach ($uploadedPaths as $path) {
        unlink($path);
    }
    
    $errors['db'] = 'Ошибка при сохранении в базу данных';
    $_SESSION['add_form_errors'] = $errors;
    $_SESSION['add_form_data'] = $oldData;
    header('Location: add.php');
    exit;
}
?>