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