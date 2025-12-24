<?php
require_once 'config.php';

// 1. Проверяем наличие и корректность file_id
if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    http_response_code(400);
    die("Некорректный ID файла");
}

$file_id = (int)$_GET['file_id'];

// 2. Ищем файл в новой таблице post_files
$stmt = $pdo->prepare("SELECT * FROM post_files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    die("Файл не найден в базе данных");
}

// 3. Составляем путь и проверяем наличие файла на диске
$filePath = $file['file_path']; // Путь уже хранится в базе

if (!file_exists($filePath)) {
    http_response_code(404);
    // Можно добавить логирование этой ошибки, т.к. это рассинхрон между БД и файловой системой
    die("Файл не найден на сервере. Обратитесь к администратору.");
}

// 4. Устанавливаем заголовки и отдаем файл
header('Content-Description: File Transfer');
header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Очищаем буфер вывода перед отправкой файла
ob_clean();
flush();

// Читаем и отправляем файл
readfile($filePath);
exit;
?>
