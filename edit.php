<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Некорректный ID");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT p.*, u.name AS author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    die("Пост не найден");
}

$canEditDelete = $isLoggedIn && ($userRole === 'admin' || $userId === $post['user_id']);

if (!$canEditDelete) {
    http_response_code(403);
    die("У вас нет прав для редактирования этого поста.");
}

$errors = [];
$success_message = '';

// Handle AJAX file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    header('Content-Type: application/json');
    $fileIndexToDelete = (int)($_POST['file_index'] ?? -1);

    if ($fileIndexToDelete === -1) {
        echo json_encode(['success' => false, 'message' => 'Некорректный индекс файла.']);
        exit;
    }

    if (!$canEditDelete) {
        echo json_encode(['success' => false, 'message' => 'У вас нет прав для удаления этого файла.']);
        exit;
    }

    try {
        $currentOriginalNames = explode(',', $post['original_name']);
        $currentFileNames = explode(',', $post['filename']);

        if (!isset($currentFileNames[$fileIndexToDelete]) || empty($currentFileNames[$fileIndexToDelete])) {
            echo json_encode(['success' => false, 'message' => 'Файл не найден.']);
            exit;
        }

        $fileToRemove = $currentFileNames[$fileIndexToDelete];
        $filePath = UPLOAD_DIR . $fileToRemove;

        // Remove from arrays
        unset($currentOriginalNames[$fileIndexToDelete]);
        unset($currentFileNames[$fileIndexToDelete]);

        // Re-index and join
        $newOriginalNames = implode(',', array_filter($currentOriginalNames));
        $newFileNames = implode(',', array_filter($currentFileNames));

        $pdo->beginTransaction();

        $updateFileStmt = $pdo->prepare("UPDATE posts SET original_name = ?, filename = ? WHERE id = ?");
        $updateFileStmt->execute([$newOriginalNames, $newFileNames, $id]);

        // Delete physical file
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        exit;
    }
}


// Handle main form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $errors[] = "Заголовок не может быть пустым.";
    }

    $newOriginalNames = explode(',', $post['original_name']);
    $newFileNames = explode(',', $post['filename']);

    // Handle new file uploads
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            $fileName = $_FILES['files']['name'][$key];
            $fileTmpName = $_FILES['files']['tmp_name'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            $fileError = $_FILES['files']['error'][$key];
            $fileType = $_FILES['files']['type'][$key];

            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError !== 0) {
                $errors[] = "Ошибка загрузки файла '{$fileName}'. Код ошибки: {$fileError}.";
                continue;
            }

            if ($fileSize > MAX_FILE_SIZE) {
                $errors[] = "Файл '{$fileName}' слишком большой. Максимальный размер: " . formatFileSize(MAX_FILE_SIZE);
                continue;
            }

            if (!in_array($fileExt, ALLOWED_EXTENSIONS) || !in_array($fileType, ALLOWED_MIME_TYPES)) {
                $errors[] = "Недопустимый тип файла '{$fileName}'. Разрешены: " . implode(', ', ALLOWED_EXTENSIONS);
                continue;
            }

            $fileNameNew = uniqid('', true) . "." . $fileExt;
            $fileDestination = UPLOAD_DIR . $fileNameNew;

            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                $newOriginalNames[] = $fileName;
                $newFileNames[] = $fileNameNew;
            } else {
                $errors[] = "Не удалось переместить загруженный файл '{$fileName}'.";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, description = ?, original_name = ?, filename = ? WHERE id = ?");
            $updateStmt->execute([
                $title,
                $description,
                implode(',', array_filter($newOriginalNames)),
                implode(',', array_filter($newFileNames)),
                $id
            ]);

            $pdo->commit();
            $success_message = "Пост успешно обновлен!";
            // Refresh post data to reflect changes
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка при обновлении поста: " . $e->getMessage();
        }
    }
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать пост - <?= htmlspecialchars($post['title']) ?></title>
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
        .submit-container { display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 40px; }
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

    <div style="max-width: 1200px; margin: 30px auto 0 auto; padding: 0 20px;">
        <div class="form-group" style="margin-bottom: 0;">
            <input form="edit-post-form" type="text" id="title" name="title" placeholder="Название" value="<?= htmlspecialchars($post['title']) ?>" required>
            <?php if (isset($errors['title'])): ?> <div class="error" style="font-size: 16px;"><?= htmlspecialchars($errors['title']) ?></div> <?php endif; ?>
        </div>
        <div class="form-group">
            <textarea form="edit-post-form" id="description" name="description" placeholder="Описание" required><?= htmlspecialchars($post['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?> <div class="error"><?= htmlspecialchars($errors['description']) ?></div> <?php endif; ?>
        </div>
    </div>

    <div class="form-container" style="margin-top: 20px; padding-top: 0;">
        <form action="edit.php?id=<?= $id ?>" method="post" enctype="multipart/form-data" id="edit-post-form">
            <div class="form-group">
                <input type="file" id="file" name="files[]" multiple>
                <div class="file-upload-frame">
                    <div class="file-items-container" id="file-items-container">
                        <!-- File chips will be rendered here by JS -->
                    </div>
                    <small class="file-types-text">Допустимые типы файл: zip, doc, docx, xls, xlsx, pdf, jpg, png.</small>
                </div>
                <?php // if (isset($errors['file'])): ?> <?php // <div class="error"><?= htmlspecialchars($errors['file']) ?></div> <?php // endif; ?>
            </div>

            <div class="submit-container">
                <button type="submit" class="btn-submit" id="submit-btn">Сохранить изменения</button>
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

        // This array will hold objects for both existing and newly added files
        // Existing files will have { isExisting: true, name: '...', size: '...', originalName: '...', fileName: '...' }
        // New files will have { isExisting: false, fileObject: File, name: '...', size: '...' }
        let currentFiles = [];

        // Initialize currentFiles with existing files from PHP
        <?php
        $originalNames = explode(',', $post['original_name']);
        $fileNames = explode(',', $post['filename']);
        $fileSizes = []; // This would ideally come from the database or be fetched
        foreach ($fileNames as $idx => $fileName) {
            $filePath = UPLOAD_DIR . $fileName;
            if (file_exists($filePath)) {
                $fileSizes[$idx] = filesize($filePath);
            } else {
                $fileSizes[$idx] = 0; // Or some placeholder
            }
        }

        foreach ($originalNames as $index => $originalName):
            if (empty($originalName)) continue;
            // Need to pass filename and size for existing files to JS
        ?>
            currentFiles.push({
                isExisting: true,
                index: <?= $index ?>, // Index in the PHP array
                originalName: '<?= addslashes(htmlspecialchars($originalName)) ?>',
                fileName: '<?= addslashes($fileNames[$index]) ?>',
                size: <?= $fileSizes[$index] ?> // Pass actual size
            });
        <?php endforeach; ?>

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function validateForm() {
            const isTitleValid = titleInput.value.trim() !== '';
            const isDescriptionValid = descriptionInput.value.trim() !== '';
            // At least one file must exist (either existing or newly added)
            const isFileValid = currentFiles.length > 0;
            submitButton.disabled = !(isTitleValid && isDescriptionValid && isFileValid);
        }

        function renderFileItems() {
            fileItemsContainer.innerHTML = ''; // Clear current items

            currentFiles.forEach((file, index) => {
                const chip = document.createElement('div');
                chip.className = 'file-chip';
                
                let nameToDisplay = file.isExisting ? file.originalName : file.fileObject.name;
                let sizeToDisplay = file.isExisting ? formatFileSize(file.size) : formatFileSize(file.fileObject.size);
                
                let deleteIcon = '';
                if (file.isExisting) {
                    deleteIcon = `<span class="file-chip-delete existing-file-delete" data-file-index="${file.index}">×</span>`;
                } else {
                    deleteIcon = `<span class="file-chip-delete new-file-delete" data-internal-index="${index}">×</span>`;
                }

                chip.innerHTML = `
                    <span class="file-chip-name">${nameToDisplay}</span>
                    <span class="file-chip-size">${sizeToDisplay}</span>
                    ${deleteIcon}
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
                currentFiles.push({
                    isExisting: false,
                    fileObject: file,
                    name: file.name,
                    size: file.size
                });
            }
            updateFileInputForSubmission();
            renderFileItems();
        });

        fileItemsContainer.addEventListener('click', async (e) => {
            if (e.target.classList.contains('existing-file-delete')) {
                const fileIndex = e.target.dataset.fileIndex;
                if (confirm('Вы уверены, что хотите удалить этот существующий файл?')) {
                    const postId = <?= $id ?>;
                    try {
                        const response = await fetch('edit.php?id=' + postId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete_file&file_index=' + fileIndex
                        });
                        const result = await response.json();
                        if (result.success) {
                            // Remove from currentFiles array based on the PHP index
                            currentFiles = currentFiles.filter(file => !(file.isExisting && file.index == fileIndex));
                            renderFileItems();
                            alert('Файл удален.');
                        } else {
                            alert('Ошибка при удалении файла: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Произошла ошибка сети.');
                    }
                }
            } else if (e.target.classList.contains('new-file-delete')) {
                const internalIndex = parseInt(e.target.dataset.internalIndex, 10);
                // Remove new file from currentFiles array based on its internal index
                currentFiles.splice(internalIndex, 1);
                updateFileInputForSubmission(); // Re-sync the actual file input
                renderFileItems();
            }
        });

        function updateFileInputForSubmission() {
            const dataTransfer = new DataTransfer();
            currentFiles.forEach(file => {
                if (!file.isExisting) {
                    dataTransfer.items.add(file.fileObject);
                }
            });
            fileInput.files = dataTransfer.files;
        }

        // Add input listeners for validation
        titleInput.addEventListener('input', validateForm);
        descriptionInput.addEventListener('input', validateForm);

        // Initial Render
        renderFileItems();

        <?php if (!empty($success_message)): ?>
            alert('<?= htmlspecialchars($success_message) ?>');
            // Optionally, redirect to clear the message from the URL
            // window.location.href = 'edit.php?id=<?= $id ?>';
        <?php endif; ?>
    </script>
</body>