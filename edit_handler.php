<?php
require_once 'config.php';

if (!$isLoggedIn) {
    http_response_code(403);
    die("Доступ запрещен: требуется авторизация.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID поста");
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT user_id, status_code FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден");
}

$canEdit = ($userRole === 'admin' || $userId === $post['user_id']);
if (!$canEdit) {
    http_response_code(403);
    die("У вас нет прав для редактирования этого поста.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    header('Content-Type: application/json');
    $fileIdToDelete = (int)($_POST['file_id'] ?? 0);

    if ($fileIdToDelete <= 0) {
        echo json_encode(['success' => false, 'message' => 'Некорректный ID файла.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT file_path, user_id FROM post_files WHERE id = ? AND post_id = ?");
        $stmt->execute([$fileIdToDelete, $id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            throw new Exception("Файл не найден или не принадлежит этому посту.");
        }

        // Дополнительная проверка, что только автор или админ может удалить
        if ($userRole !== 'admin' && $userId !== $file['user_id']) {
            throw new Exception("У вас нет прав для удаления этого файла.");
        }

        $deleteStmt = $pdo->prepare("DELETE FROM post_files WHERE id = ?");
        $deleteStmt->execute([$fileIdToDelete]);
        
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $errors = [];

    if (empty($title)) {
        $errors['title'] = "Заголовок не может быть пустым.";
    }

    // Обработка новых файлов (аналогично handler.php)
    $newFiles = $_FILES['files'] ?? [];

    if (!empty($newFiles['name'][0])) {
         for ($i = 0; $i < count($newFiles['name']); $i++) {
            if ($newFiles['error'][$i] !== UPLOAD_ERR_OK) continue; // Пропускаем пустые слоты
            
            $pathInfo = pathinfo($newFiles['name'][$i]);
            $extension = strtolower($pathInfo['extension'] ?? '');
            
            if ($newFiles['size'][$i] > MAX_FILE_SIZE) {
                $errors['file'] = 'Файл ' . $newFiles['name'][$i] . ' слишком большой.';
            } elseif (!in_array($extension, ALLOWED_EXTENSIONS)) {
                $errors['file'] = 'Недопустимое расширение для файла ' . $newFiles['name'][$i];
            }
         }
    }

    if (!empty($errors)) {
        $_SESSION['edit_form_errors'] = $errors;
        $_SESSION['edit_form_data'] = ['title' => $title, 'description' => $description];
        header("Location: edit.php?id=$id");
        exit;
    }
    
    try {
        $pdo->beginTransaction();

        $newStatusCode = $post['status_code'];
        if ($userRole === 'admin' && isset($_POST['status_code'])) {
            $newStatusCode = $_POST['status_code'];
        }

        $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, description = ?, status_code = ? WHERE id = ?");
        $updateStmt->execute([$title, $description, $newStatusCode, $id]);

        if (!empty($newFiles['name'][0])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            for ($i = 0; $i < count($newFiles['name']); $i++) {
                if ($newFiles['error'][$i] !== UPLOAD_ERR_OK) continue;

                $originalName = $newFiles['name'][$i];
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $uniqueName = uniqid('file_', true) . '.' . $fileExtension;
                $uploadPath = UPLOAD_DIR . $uniqueName;

                if (move_uploaded_file($newFiles['tmp_name'][$i], $uploadPath)) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO post_files (post_id, user_id, original_name, unique_name, file_path, file_size, file_type)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $id, $userId, $originalName, $uniqueName, $uploadPath, 
                        $newFiles['size'][$i], $newFiles['type'][$i]
                    ]);
                } else {
                    throw new Exception("Не удалось загрузить файл $originalName");
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['edit_success_message'] = "Пост успешно обновлен!";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['edit_form_errors'] = ['db' => 'Ошибка при обновлении: ' . $e->getMessage()];
    }

    header("Location: edit.php?id=$id");
    exit;
}

http_response_code(405);
die('Метод не разрешен.');
?>
