<?php
require_once 'config.php';

if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    http_response_code(400);
    die("Некорректный ID файла");
}

$file_id = (int)$_GET['file_id'];

$stmt = $pdo->prepare("SELECT * FROM post_files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    die("Файл не найден в базе данных");
}

$filePath = $file['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    // Можно добавить логирование этой ошибки, т.к. это рассинхрон между БД и файловой системой
    die("Файл не найден на сервере. Обратитесь к администратору.");
}

header('Content-Description: File Transfer');
header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

ob_clean();
flush();

readfile($filePath);
exit;
?>
