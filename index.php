<?php
require_once 'config.php';

// Параметры сортировки
$sort = $_GET['sort'] ?? 'newest';
$orderBy = 'p.uploaded_at DESC'; // По умолчанию: сначала новые

switch ($sort) {
    case 'oldest': $orderBy = 'p.uploaded_at ASC'; break;
    case 'title':  $orderBy = 'p.title ASC'; break;
    case 'newest': default: $orderBy = 'p.uploaded_at DESC'; break;
}

// Фильтрация по статусу
$whereClause = "WHERE p.status_code = 'approved'";
if ($isLoggedIn && $userRole === 'admin') {
    $whereClause = "WHERE 1"; // Админ видит все посты
}

$sql = "
    SELECT p.id, p.title, p.uploaded_at, p.status_code, u.name AS author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    $whereClause
    ORDER BY $orderBy
    LIMIT 10
";

$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Студенческий файлообменник</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.svg" alt="logotype" class="logo-img">
        </div>
        <div class="auth-buttons">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-text">Здравствуйте, <?= htmlspecialchars($userName) ?></span>
                <a href="logout.php" class="btn logout-btn">Выход</a>
            <?php else: ?>
                <button class="btn register-btn" onclick="openModal('registerModal'); closeModal('loginModal');">Регистрация</button>
                <button class="btn login-btn" onclick="openModal('loginModal'); closeModal('registerModal');">Вход</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="section-header">
        <h2 class="section-title">Учебные материалы</h2>
        <?php if ($isLoggedIn): ?>
            <a href="add.php" class="btn add-post-btn">
                <img src="images/+.svg" alt="add post" class="add-post-icon">
                <span>Добавить пост</span>
            </a>
        <?php else: ?>
            <button class="btn add-post-btn" onclick="openModal('loginModal'); closeModal('registerModal');">
                <img src="images/+.svg" alt="add post" class="add-post-icon">
                <span>Добавить пост</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Блок сортировки -->
    <div style="max-width: 1200px; margin: 0 auto 20px auto; padding: 0 20px; display: flex; justify-content: flex-end;">
        <form action="index.php" method="get">
            <select name="sort" onchange="this.form.submit()" style="padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Inter', sans-serif;">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Сначала старые</option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>По названию (А-Я)</option>
            </select>
        </form>
    </div>

    <div class="main-card">
        <?php foreach ($posts as $post): ?>
            <a href="post.php?id=<?= (int)$post['id'] ?>" class="card-link">
                <div class="inner-card">
                    <p class="added-date"><?= date('d.m.Y', strtotime($post['uploaded_at'])) ?></p>
                    <p class="lab-title"><?= htmlspecialchars($post['title']) ?></p>
                    <?php if ($isLoggedIn && $userRole === 'admin' && $post['status_code'] !== 'approved'): ?>
                        <p style="color: #dc3545; font-size: 14px; margin-top: 5px;">
                            <?= $post['status_code'] === 'moderation' ? 'На модерации' : 'Не одобрено' ?>
                        </p>
                    <?php endif; ?>
                    <button class="btn card-btn">Подробнее</button>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <button class="btn view-more-btn">
        <span>Показать еще</span>
        <img src="images/down-arrow.svg" alt="show more" class="view-more-icon">
    </button>

    <footer class="footer">
        <span class="footer-email">example@email.com</span>
        <span class="footer-dev">Разработано: Motti</span>
    </footer>

    <?php include_once 'modals.php'; ?>

    <script>
        // Функции для открытия/закрытия модальных окон
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Закрытие по клику вне окна
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                // Закрываем все модальные окна при клике вне контента
                closeModal('registerModal');
                closeModal('loginModal');
            }
        });
        
        // Очистка ошибок при открытии модального окна
        function clearModalForms() {
            // Очищаем ошибки
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            // Очищаем поля форм (кроме скрытых полей)
            document.querySelectorAll('#registerForm input, #loginForm input').forEach(input => {
                if (input.type !== 'submit' && input.type !== 'hidden') {
                    input.value = '';
                }
            });
        }
        
        // При нажатии на кнопки регистрации/входа очищаем формы
        document.querySelectorAll('.register-btn, .login-btn, .add-post-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                clearModalForms();
            });
        });
        
        // Ссылки внутри модальных окон для переключения
        document.querySelectorAll('#registerModal a[href="#"], #loginModal a[href="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                clearModalForms();
            });
        });

        // Активация кнопки регистрации при выборе чекбокса
        const agreeCheckbox = document.getElementById('agree');
        const registerButton = document.querySelector('#registerForm button[type="submit"]');

        if (agreeCheckbox && registerButton) {
            // Инициализация состояния кнопки при загрузке
            const registerModal = document.getElementById('registerModal');
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'style' && registerModal.style.display === 'block') {
                        registerButton.classList.toggle('active', agreeCheckbox.checked);
                    }
                });
            });
            observer.observe(registerModal, { attributes: true });
            
            agreeCheckbox.addEventListener('change', function() {
                registerButton.classList.toggle('active', this.checked);
            });
        }

        // Активация кнопки входа при заполнении полей
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            const loginButton = loginForm.querySelector('button[type="submit"]');
            const loginInputs = loginForm.querySelectorAll('input[required]');

            if (loginButton && loginInputs.length) {
                const checkLoginInputs = () => {
                    let allFilled = true;
                    loginInputs.forEach(input => {
                        if (input.value.trim() === '') {
                            allFilled = false;
                        }
                    });
                    loginButton.classList.toggle('active', allFilled);
                };

                loginForm.addEventListener('input', checkLoginInputs);

                const loginModal = document.getElementById('loginModal');
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        if (mutation.attributeName === 'style' && loginModal.style.display === 'block') {
                            checkLoginInputs();
                        }
                    });
                });
                observer.observe(loginModal, { attributes: true });
            }
        }

        // Отправка формы регистрации
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.querySelectorAll('#registerForm .error').forEach(el => el.textContent = '');
            
            const formData = new FormData(this);
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.errors) {
                    for (const [field, message] of Object.entries(result.errors)) {
                        const errorEl = document.getElementById(field + 'Error');
                        if (errorEl) errorEl.textContent = message;
                    }
                } else if (result.success) {
                    window.location.reload();
                }
            })
            .catch(error => console.error('Ошибка:', error));
        });

        // Отправка формы входа
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.querySelectorAll('#loginForm .error').forEach(el => el.textContent = '');
            
            const formData = new FormData(this);
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.errors) {
                    for (const [field, message] of Object.entries(result.errors)) {
                        const errorEl = document.getElementById('login' + field.charAt(0).toUpperCase() + field.slice(1) + 'Error');
                        if (errorEl) errorEl.textContent = message;
                    }
                } else if (result.success) {
                    window.location.reload();
                }
            })
            .catch(error => console.error('Ошибка:', error));
        });
    </script>
</body>
</html>