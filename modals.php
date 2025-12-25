<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('registerModal')">&times;</span>
        <div class="modal-header">
            <a href="#" class="active" onclick="event.preventDefault();">Регистрация</a>
            <a href="#" onclick="event.preventDefault(); openModal('loginModal'); closeModal('registerModal');">Авторизация</a>
        </div>
        <form id="registerForm">
            <div class="form-group">
                <input type="text" name="name" placeholder="Ваше имя" required>
                <div class="error" id="nameError"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="error" id="emailError"></div>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Телефон">
                    <div class="error" id="phoneError"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div class="error" id="passwordError"></div>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
                    <div class="error" id="confirmPasswordError"></div>
                </div>
            </div>
            <div class="form-group-checkbox">
                <input type="checkbox" id="agree" name="agree" required>
                <label for="agree">Согласен на обработку <span class="green-text">персональных данных</span></label>
            </div>
            <button type="submit" class="btn">Зарегистрироваться</button>
            <p class="form-footer-text">Все поля обязательны для заполнения</p>
        </form>
    </div>
</div>

<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('loginModal')">&times;</span>
        <div class="modal-header">
            <a href="#" onclick="event.preventDefault(); openModal('registerModal'); closeModal('loginModal');">Регистрация</a>
            <a href="#" class="active" onclick="event.preventDefault();">Авторизация</a>
        </div>
        <form id="loginForm">
            <div class="form-row">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="error" id="loginEmailError"></div>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div class="error" id="loginPasswordError"></div>
                </div>
            </div>
            <button type="submit" class="btn">Войти</button>
            <p class="form-footer-text">Все поля обязательны для заполнения</p>
        </form>
    </div>
</div>