<?php
require_once 'config.php';

$stmt = $pdo->query("
    SELECT p.id, p.title, p.uploaded_at, u.name AS author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
    LIMIT 10
");
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
                <span class="welcome-text">Привет, <?= htmlspecialchars($userName) ?>!</span>
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

    <div class="main-card">
        <?php foreach ($posts as $post): ?>
            <a href="post.php?id=<?= (int)$post['id'] ?>" class="card-link">
                <div class="inner-card">
                    <p class="added-date"><?= date('d.m.Y', strtotime($post['uploaded_at'])) ?></p>
                    <p class="lab-title"><?= htmlspecialchars($post['title']) ?></p>
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

    <!-- Модальное окно регистрации (скрыто по умолчанию) -->
    <div id="registerModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('registerModal')">&times;</span>
            <h2>Регистрация</h2>
            <form id="registerForm">
                <div>
                    <input type="text" name="name" placeholder="Имя" required>
                    <div class="error" id="nameError"></div>
                </div>
                <div>
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="error" id="emailError"></div>
                </div>
                <div>
                    <input type="tel" name="phone" placeholder="Телефон">
                    <div class="error" id="phoneError"></div>
                </div>
                <div>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div class="error" id="passwordError"></div>
                </div>
                <div>
                    <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>
                    <div class="error" id="confirmPasswordError"></div>
                </div>
                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>
            <p style="margin-top: 15px; text-align: center; font-size: 14px;">
                Уже есть аккаунт? <a href="#" onclick="openModal('loginModal'); closeModal('registerModal');" style="color: #007bff;">Войти</a>
            </p>
        </div>
    </div>

    <!-- Модальное окно входа (скрыто по умолчанию) -->
    <div id="loginModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('loginModal')">&times;</span>
            <h2>Вход</h2>
            <form id="loginForm">
                <div>
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="error" id="loginEmailError"></div>
                </div>
                <div>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div class="error" id="loginPasswordError"></div>
                </div>
                <button type="submit" class="btn">Войти</button>
            </form>
            <p style="margin-top: 15px; text-align: center; font-size: 14px;">
                Нет аккаунта? <a href="#" onclick="openModal('registerModal'); closeModal('loginModal');" style="color: #007bff;">Зарегистрироваться</a>
            </p>
        </div>
    </div>

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