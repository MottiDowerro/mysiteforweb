<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['file_index']) || !is_numeric($_GET['file_index'])) {
    http_response_code(400);
    die("Некорректные параметры");
}

$id = (int)$_GET['id'];
$file_index = (int)$_GET['file_index'];

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

$fileNames = explode(',', $post['filename']);
$originalNames = explode(',', $post['original_name']);

if (!isset($fileNames[$file_index]) || !isset($originalNames[$file_index])) {
    http_response_code(404);
    die("Файл с таким индексом не найден");
}

$fileName = $fileNames[$file_index];
$originalName = $originalNames[$file_index];
$filePath = UPLOAD_DIR . $fileName;

if (!file_exists($filePath)) {
    http_response_code(404);
    die("Файл не найден на сервере");
}

// Устанавливаем заголовки для скачивания
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Читаем и отправляем файл
readfile($filePath);
exit;
?>
