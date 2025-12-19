<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT filename, original_name 
    FROM posts 
    WHERE id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден");
}

$filePath = UPLOAD_DIR . $post['filename'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die("Файл не найден");
}

// Устанавливаем заголовки для скачивания
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $post['original_name'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Читаем и отправляем файл
readfile($filePath);
exit;
?>