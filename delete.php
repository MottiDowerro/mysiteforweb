<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID поста.");
}

$id = (int)$_GET['id'];

// Fetch post data to get associated files and user_id for authorization
$stmt = $pdo->prepare("SELECT user_id, filename FROM posts WHERE id = ?");
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

    // Delete associated files from the server
    if (!empty($post['filename'])) {
        $filesToDelete = explode(',', $post['filename']);
        foreach ($filesToDelete as $file) {
            $filePath = UPLOAD_DIR . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    // Delete post record from the database
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $deleteStmt->execute([$id]);

    $pdo->commit();
    
    // Redirect to home page with a success message
    header("Location: index.php?message=Пост успешно удален.");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Ошибка при удалении поста: " . $e->getMessage());
}
?>