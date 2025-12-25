<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); die("Некорректный ID");
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404); die("Пост не найден");
}

$canEdit = $isLoggedIn && ($userRole === 'admin' || $userId === $post['user_id']);
if (!$canEdit) {
    http_response_code(403); die("У вас нет прав для редактирования этого поста.");
}

$stmt_files = $pdo->prepare("SELECT id, original_name, file_size FROM post_files WHERE post_id = ? ORDER BY original_name ASC");
$stmt_files->execute([$id]);
$existingFiles = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

$errors = $_SESSION['edit_form_errors'] ?? [];
$oldData = $_SESSION['edit_form_data'] ?? [];
$success_message = $_SESSION['edit_success_message'] ?? '';
unset($_SESSION['edit_form_errors'], $_SESSION['edit_form_data'], $_SESSION['edit_success_message']);

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024; $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать пост - <?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        body { background-color: #ffffff; }
        .form-container { max-width: 1200px; margin: 40px auto; padding: 40px 20px; background-color: #ffffff; border-radius: 20px; }
        #title { font-size: 48px; font-weight: 500; line-height: 60px; border: none; border-radius: 0; padding: 20px 0; width: 100%; }
        #title:focus { outline: none; }
        #description { border: none; resize: none; padding: 20px 0; color: #838383; font-size: 16px; width: 100%; }
        #description:focus { outline: none; }
        .form-group { margin-bottom: 30px; }
        .file-upload-frame { min-width: 513px; width: auto; display: inline-flex; min-height: 119px; height: auto; border-radius: 10px; border: 2px dashed #A6C81E; padding: 20px; flex-direction: column; justify-content: space-between; background-color: #ffffff; box-sizing: border-box; }
        .file-items-container { display: flex; align-items: center; gap: 10px; padding-bottom: 10px; }
        .file-types-text { font-family: 'Inter', sans-serif; color: #838383; font-size: 14px; text-align: center; width: 100%; }
        input[type="file"] { display: none; }
        .add-file-btn { display: flex; align-items: center; justify-content: center; width: 184px; height: 44px; border-radius: 6px; background: rgba(166, 200, 30, 0.2); color: #A6C81E; font-family: 'Inter', sans-serif; font-weight: 500; gap: 10px; flex-shrink: 0; cursor: pointer; }
        .file-chip { display: flex; align-items: center; gap: 10px; height: 44px; border-radius: 6px; padding: 0 16px; background: rgba(0, 0, 0, 0.03); box-sizing: border-box; flex-shrink: 0; }
        .file-chip-name { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #181818; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-chip-size { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #838383; white-space: nowrap; }
        .file-chip-delete { font-family: sans-serif; font-size: 24px; font-weight: 300; color: #838383; cursor: pointer; line-height: 1; }
        .submit-container { display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 40px; }
        .btn-submit { width: 196px; height: 60px; background-color: #A6C81E; color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; }
        .success { color: #28a745; font-size: 16px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo"> <a href="index.php"><img src="images/logo.svg" alt="logotype" class="logo-img"></a> </div>
        <div class="auth-buttons">
            <span class="welcome-text">Привет, <?= htmlspecialchars($userName) ?>!</span>
            <a href="logout.php" class="btn logout-btn">Выход</a>
        </div>
    </header>

    <div style="max-width: 1200px; margin: 30px auto 0 auto; padding: 0 20px;">
        <?php if (!empty($success_message)): ?> <div class="success"><?= htmlspecialchars($success_message) ?></div> <?php endif; ?>
        <?php if (!empty($errors['db'])): ?> <div class="error"><?= htmlspecialchars($errors['db']) ?></div> <?php endif; ?>

        <div class="form-group" style="margin-bottom: 0;">
            <input form="edit-post-form" type="text" id="title" name="title" placeholder="Название" value="<?= htmlspecialchars($oldData['title'] ?? $post['title']) ?>" required>
            <?php if (isset($errors['title'])): ?> <div class="error" style="font-size: 16px;"><?= htmlspecialchars($errors['title']) ?></div> <?php endif; ?>
        </div>
        <div class="form-group">
            <textarea form="edit-post-form" id="description" name="description" placeholder="Описание"><?= htmlspecialchars($oldData['description'] ?? $post['description']) ?></textarea>
        </div>
        
        <?php if ($userRole === 'admin'): ?>
        <div class="form-group">
            <label for="status_code" style="font-weight: 500;">Статус поста (админ):</label>
            <select form="edit-post-form" id="status_code" name="status_code" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">
                <?php
                $statuses = $pdo->query("SELECT * FROM statuses")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($statuses as $status): ?>
                    <option value="<?= $status['code'] ?>" <?= $post['status_code'] === $status['code'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-container" style="margin-top: 20px; padding-top: 0;">
        <form action="edit_handler.php?id=<?= $id ?>" method="post" enctype="multipart/form-data" id="edit-post-form">
            <div class="form-group">
                <input type="file" id="file" name="files[]" multiple>
                <div class="file-upload-frame">
                    <div class="file-items-container" id="file-items-container"></div>
                    <small class="file-types-text">Допустимые типы: zip, doc, docx, xls, xlsx, pdf, jpg, png.</small>
                </div>
                 <?php if (isset($errors['file'])): ?> <div class="error"><?= htmlspecialchars($errors['file']) ?></div> <?php endif; ?>
            </div>

            <div class="submit-container">
                <button type="submit" class="btn-submit" id="submit-btn">Сохранить изменения</button>
            </div>
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('file');
        const fileItemsContainer = document.getElementById('file-items-container');

        // This array holds objects for both existing and newly added files
        // isNew: true/false, data: (File object or {id, name, size})
        let fileManager = {
            files: [],
            
            init: function(existingFiles) {
                this.files = existingFiles.map(f => ({ isNew: false, data: f }));
                this.render();
            },

            add: function(newFiles) {
                for (const file of newFiles) {
                    this.files.push({ isNew: true, data: file });
                }
                this.render();
            },

            remove: async function(index) {
                const fileToRemove = this.files[index];
                if (!fileToRemove) return;

                if (fileToRemove.isNew) {
                    this.files.splice(index, 1);
                    this.render();
                } else {
                    // It's an existing file, delete via AJAX
                    if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие необратимо.')) return;
                    
                    const fileId = fileToRemove.data.id;
                    try {
                        const response = await fetch('edit_handler.php?id=<?= $id ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=delete_file&file_id=${fileId}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            this.files.splice(index, 1);
                            this.render();
                        } else {
                            alert('Ошибка при удалении файла: ' + result.message);
                        }
                    } catch (error) {
                        alert('Сетевая ошибка при удалении файла.');
                    }
                }
            },

            render: function() {
                fileItemsContainer.innerHTML = '';
                this.files.forEach((file, index) => {
                    const name = file.isNew ? file.data.name : file.data.original_name;
                    const size = _formatFileSize(file.data.size);
                    const chip = document.createElement('div');
                    chip.className = 'file-chip';
                    chip.innerHTML = `
                        <span class="file-chip-name">${name}</span>
                        <span class="file-chip-size">${size}</span>
                        <span class="file-chip-delete" data-index="${index}">×</span>
                    `;
                    fileItemsContainer.appendChild(chip);
                });
                
                const addButton = document.createElement('div');
                addButton.className = 'add-file-btn';
                addButton.innerHTML = `<img src="images/+.svg" alt="add"><span>Добавить файл</span>`;
                addButton.onclick = () => fileInput.click();
                fileItemsContainer.appendChild(addButton);
                this.updateFileInput();
            },

            updateFileInput: function() {
                const dataTransfer = new DataTransfer();
                this.files.forEach(file => {
                    if (file.isNew) dataTransfer.items.add(file.data);
                });
                fileInput.files = dataTransfer.files;
            }
        };

        function _formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        fileInput.addEventListener('change', (e) => fileManager.add(e.target.files));
        fileItemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('file-chip-delete')) {
                fileManager.remove(parseInt(e.target.dataset.index, 10));
            }
        });

        const existingFiles = <?= json_encode($existingFiles) ?>;
        fileManager.init(existingFiles);

    </script>
</body>
</html>