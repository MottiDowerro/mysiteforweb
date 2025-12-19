<?php
require_once 'config.php';

// Проверка авторизации
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// Получение ошибок из сессии
$errors = [];
if (isset($_SESSION['add_form_errors'])) {
    $errors = $_SESSION['add_form_errors'];
    unset($_SESSION['add_form_errors']);
}

// Получение старых данных из сессии
$oldData = [];
if (isset($_SESSION['add_form_data'])) {
    $oldData = $_SESSION['add_form_data'];
    unset($_SESSION['add_form_data']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить пост - Студенческий файлообменник</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        body { background-color: #ffffff; }
        .form-container {
            max-width: 1200px; margin: 40px auto; padding: 40px 20px;
            background-color: #ffffff; border-radius: 20px;
        }
        #title {
            font-size: 48px; font-weight: 500; line-height: 60px;
            border: none; border-radius: 0; padding: 20px 0; width: 100%;
        }
        #title:focus { outline: none; }
        #description {
            border: none; resize: none; padding: 20px 0;
            color: #838383; font-size: 16px; width: 100%;
        }
        #description:focus { outline: none; }
        .form-group { margin-bottom: 30px; }
        
        /* --- File Upload --- */
        .file-upload-frame {
            min-width: 513px;
            width: auto;
            display: inline-flex; /* Let the container grow with its content */
            min-height: 119px;
            height: auto;
            border-radius: 10px;
            border: 2px dashed #A6C81E;
            padding: 20px;
            flex-direction: column;
            justify-content: space-between;
            background-color: #ffffff;
            box-sizing: border-box;
        }
        
        .file-items-container {
            display: flex;
            align-items: center;
            gap: 10px;
            /* overflow-x: auto; Removed */
            padding-bottom: 10px; /* For scrollbar */
        }
        
        .file-types-text {
            font-family: 'Inter', sans-serif; color: #838383;
            font-size: 14px; text-align: center; width: 100%;
        }
        
        input[type="file"] { display: none; }

        .add-file-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 184px;
            height: 44px;
            border-radius: 6px;
            background: rgba(166, 200, 30, 0.2);
            color: #A6C81E;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            gap: 10px;
            flex-shrink: 0;
            cursor: pointer;
        }
        
        .file-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 44px;
            border-radius: 6px;
            padding: 0 16px;
            background: rgba(0, 0, 0, 0.03);
            box-sizing: border-box;
            flex-shrink: 0;
        }
        .file-chip-name {
            font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #181818;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .file-chip-size {
            font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #838383;
            white-space: nowrap;
        }
        .file-chip-delete {
            font-family: sans-serif; font-size: 24px; font-weight: 300; color: #838383;
            cursor: pointer; line-height: 1;
        }
        
        /* --- Submit Area --- */
        .submit-container { display: flex; align-items: center; gap: 20px; margin-top: 40px; }
        .btn-submit {
            width: 196px; height: 60px; background-color: #A6C81E; color: white;
            border: none; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 600;
            opacity: 1; transition: opacity 0.3s ease; box-sizing: border-box;
        }
        .btn-submit:disabled { opacity: 0.3; cursor: not-allowed; }
        .form-note { font-family: 'Inter', sans-serif; font-weight: 400; font-size: 16px; color: #838383; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo"> <a href="index.php"><img src="images/logo.svg" alt="logotype" class="logo-img"></a> </div>
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-text">Привет, <?= htmlspecialchars($userName) ?>!</span>
                <a href="logout.php" class="btn logout-btn">Выход</a>
            <?php else: ?>
                <a href="index.php" class="btn register-btn">Регистрация</a>
                <a href="index.php" class="btn login-btn">Вход</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="form-container">
        <form action="handler.php" method="post" enctype="multipart/form-data" id="add-post-form">
            <div class="form-group">
                <input type="text" id="title" name="title" placeholder="Название" value="<?= htmlspecialchars($oldData['title'] ?? '') ?>" required>
                <?php if (isset($errors['title'])): ?> <div class="error"><?= htmlspecialchars($errors['title']) ?></div> <?php endif; ?>
            </div>

            <div class="form-group">
                <textarea id="description" name="description" placeholder="Описание" required><?= htmlspecialchars($oldData['description'] ?? '') ?></textarea>
                <?php if (isset($errors['description'])): ?> <div class="error"><?= htmlspecialchars($errors['description']) ?></div> <?php endif; ?>
            </div>

            <div class="form-group">
                <input type="file" id="file" name="files[]" required multiple>
                <div class="file-upload-frame">
                    <div class="file-items-container" id="file-items-container">
                        <!-- File chips will be rendered here by JS -->
                    </div>
                    <small class="file-types-text">Допустимые типы файл: zip, doc, docx, xls, xlsx, pdf, jpg, png.</small>
                </div>
                <?php if (isset($errors['file'])): ?> <div class="error"><?= htmlspecialchars($errors['file']) ?></div> <?php endif; ?>
            </div>

            <div class="submit-container">
                <button type="submit" class="btn-submit" id="submit-btn">Опубликовать пост</button>
                <span class="form-note">Все поля обязательны для заполнения.</span>
            </div>
        </form>
    </div>

    <footer class="footer add-post-footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>

    <script>
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('description');
        const fileInput = document.getElementById('file');
        const fileItemsContainer = document.getElementById('file-items-container');
        const submitButton = document.getElementById('submit-btn');

        let currentFiles = [];

        function validateForm() {
            const isTitleValid = titleInput.value.trim() !== '';
            const isDescriptionValid = descriptionInput.value.trim() !== '';
            const isFileValid = currentFiles.length > 0;
            submitButton.disabled = !(isTitleValid && isDescriptionValid && isFileValid);
        }
        
        function renderFileItems() {
            fileItemsContainer.innerHTML = ''; // Clear current items
            
            currentFiles.forEach((file, index) => {
                const chip = document.createElement('div');
                chip.className = 'file-chip';
                chip.innerHTML = `
                    <span class="file-chip-name">${file.name}</span>
                    <span class="file-chip-size">${formatFileSize(file.size)}</span>
                    <span class="file-chip-delete" data-index="${index}">×</span>
                `;
                fileItemsContainer.appendChild(chip);
            });

            // Add the "Add file" button at the end
            const addButton = document.createElement('div');
            addButton.className = 'add-file-btn';
            addButton.innerHTML = `
                <img src="images/+.svg" alt="add icon" style="width: 14px; height: 14px;">
                <span>Добавить файл</span>
            `;
            addButton.addEventListener('click', () => fileInput.click());
            fileItemsContainer.appendChild(addButton);
            
            validateForm();
        }

        fileInput.addEventListener('change', () => {
            for (const file of fileInput.files) {
                currentFiles.push(file);
            }
            // Update the actual file input to reflect the current file list
            updateFileInput();
            renderFileItems();
        });

        fileItemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('file-chip-delete')) {
                const index = parseInt(e.target.dataset.index, 10);
                currentFiles.splice(index, 1); // Remove file from array
                updateFileInput();
                renderFileItems();
            }
        });

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            currentFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        // Add input listeners for validation
        titleInput.addEventListener('input', validateForm);
        descriptionInput.addEventListener('input', validateForm);

        // Initial Render
        renderFileItems();
    </script>
</body>
</html>