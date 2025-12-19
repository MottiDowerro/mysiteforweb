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
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .welcome-text {
            margin-right: 15px;
            color: #333;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        form .btn {
            width: 100%;
            margin-top: 15px;
        }
    </style>
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
                <button class="btn register-btn">Регистрация</button>
                <button class="btn login-btn">Вход</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="section-header">
        <h2 class="section-title">Учебные материалы</h2>
        <button class="btn add-post-btn">
            <img src="images/+.svg" alt="add post" class="add-post-icon">
            <span>Добавить пост</span>
        </button>
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

    <!-- Модальное окно регистрации -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
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
        </div>
    </div>

    <!-- Модальное окно входа -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
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
        </div>
    </div>

    <script>
        // Открытие/закрытие модальных окон
        document.querySelectorAll('.register-btn, .login-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.classList.contains('register-btn') ? 'registerModal' : 'loginModal';
                document.getElementById(modalId).style.display = 'block';
            });
        });

        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal').style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
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