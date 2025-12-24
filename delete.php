<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID поста.");
}

$id = (int)$_GET['id'];

// Fetch post data for user_id authorization
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден.");
}

// Authorization check
// $isLoggedIn, $userRole, $userId are from config.php
$canEditDelete = $isLoggedIn && ($userRole === 'admin' || $userId === $post['user_id']);

if (!$canEditDelete) {
    http_response_code(403);
    die("У вас нет прав для удаления этого поста.");
}

try {
    $pdo->beginTransaction();

    // 1. Получаем список всех файлов, связанных с постом
    $stmt_files = $pdo->prepare("SELECT file_path FROM post_files WHERE post_id = ?");
    $stmt_files->execute([$id]);
    $filesToDelete = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

    // 2. Удаляем каждый физический файл с диска
    foreach ($filesToDelete as $file) {
        if (!empty($file['file_path']) && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }

    // 3. Удаляем саму запись о посте. Записи в post_files удалятся каскадно благодаря ON DELETE CASCADE.
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $deleteStmt->execute([$id]);

    $pdo->commit();
    
    // Redirect to home page with a success message
    header("Location: index.php?message=Пост и все связанные файлы были успешно удалены.");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // TODO: Log the detailed error message
    die("Ошибка при удалении поста. Обратитесь к администратору.");
}
?>