<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    echo $timer ?? '';
    ?>
    <style>
        .custom-table thead th,
        .custom-table tbody td {
            display: inline-flex;

        }

        .d-flex {
            justify-content: space-between;
            align-items: center;
        }

        .profile-image-placeholder {
            width: 25vw;
            height: 25vw;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .profile-image-placeholder img {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<?php
/* NAVIGATION PANEL */
$title = ['title' => 'Settings', 'app_role' => $user['app_role'], 'link' => $user['link']];
NavBarContent($page, $title);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
const START_PAGE = ['order' => 'Orders', 'project' => 'Projects', 'wh' => 'Warehouse', 'wiki' => 'Wiki'];
?>

<div class="main-container">
    <main class="container-fluid content">
        <div class="container-fluid mt-3">
            <h2 class="text-center">Account settings</h2>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4">
                        <div class="profile-image-placeholder">
                            <img src="/public/images/ips.webp" alt="Profile" id="profile-img">
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Выбор страницы после входа -->
                        <div class="mb-3">
                            <label for="landingPageSelect" class="form-label">Start Page</label>
                            <select class="form-select" id="landingPageSelect" name="link-pages">
                                <?php foreach (START_PAGE as $key => $text): ?>
                                    <option value="<?= $key ?>" <?= ($user['link'] == $key) ? 'selected' : ''; ?>><?= $text ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Выбор вывода логотипа проекта на странице PROJECTS.PHP  -->
                        <div class="mb-3">
                            <label for="project-preview" class="form-label">Project Preview</label>
                            <select class="form-select" id="project-preview" name="project-preview">
                                <option value="docs" <?= ($user['preview'] == 'docs') ? 'selected' : ''; ?>>Documentation</option>
                                <option value="image" <?= ($user['preview'] == 'image') ? 'selected' : ''; ?>>Last Step Photo</option>
                            </select>
                        </div>

                        <!-- Включение/отключение темного режима -->
                        <div class="mb-3 form-check form-switch">
                            <?php $ckd = '';
                            if ($user['view_mode'] == 'dark') {
                                $ckd = 'checked';
                            } ?>
                            <label class="form-check-label" for="darkModeSwitch">Dark Mode</label>
                            <input class="form-check-input" type="checkbox" id="darkModeSwitch" name="dark-mode" value="dark" <?= $ckd; ?> disabled>
                        </div>

                        <!-- Включение/отключение звука -->
                        <div class="mb-3 form-check form-switch">
                            <?php $ckd = '';
                            if ($user['sound'] == '1') {
                                $ckd = 'checked';
                            } ?>
                            <label class="form-check-label" for="soundSwitch">Sound</label>
                            <input class="form-check-input" type="checkbox" id="soundSwitch" name="sound" value="1" <?= $ckd; ?>>
                        </div>

                        <!-- form buttons -->
                        <div class="mb-3">
                            <button type="button" id="btn-take-image" class="btn btn-secondary">
                                <i class="bi bi-camera"></i>
                                Выбрать фото
                            </button>
                            <button type="button" class="btn btn-primary" id="change-password">
                                <i class="bi bi-passport"></i> Change Password
                            </button>
                            <!-- Кнопка сохранения настроек -->
                            <button type="submit" class="btn btn-outline-success" name="user-account-settings">Save settings</button>
                        </div>
                    </div>
                </div>

                <!-- form hidden fields -->
                <input type="file" id="file-input" name="profile-img" accept="image/*" hidden>
            </form>

            <!-- password changing form -->
            <div class="row hidden" id="password-form">
                <div class="offset-4 col-8">
                    <h4>Password changing form</h4>
                    <form action="" method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp">
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_1" class="form-label">Password <i class="bi bi-eye eye"></i></label>
                            <input type="password" class="form-control pi" id="password_1" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_2" class="form-label">Re-Password <i class="bi bi-eye eye"></i></label>
                            <input type="password" class="form-control pi" id="password_2" name="re-password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="check" name="send-mail" value="1">
                            <label class="form-check-label" for="check">Sed to mail</label>
                        </div>
                        <button type="submit" class="btn btn-primary" name="update-user-password" id="pass-btn" disabled>Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm($_GET['route-page'] ?? 1);
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        // кнопки выбора фото пользователя и Обработчик обновления превью
        document.doClick("#btn-take-image", "#file-input");
        document.doPreviewFile("file-input", "profile-img");

        // делаем видимой форму смены пароля
        document.querySelector("#change-password").addEventListener("click", function () {
            document.querySelector("#password-form").classList.remove("hidden");
        });

        const password1 = document.getElementById('password_1');
        const password2 = document.getElementById('password_2');
        const emailInput = document.getElementById('email');
        const checkbox = document.getElementById('check');
        const submitButton = document.querySelector('#pass-btn');

        function validateForm() {
            // Проверка совпадения паролей
            const passwordsMatch = password1.value === password2.value && password1.value !== '';
            // Проверка валидности имейла
            const emailValid = emailInput.checkValidity(); // возвращает true, если поле валидно
            // Проверка состояния чекбокса
            const checkboxChecked = checkbox.checked;

            // Управление атрибутом required для имейла
            emailInput.required = checkboxChecked;

            // Управление доступностью кнопки
            submitButton.disabled = !(passwordsMatch && (!checkboxChecked || (checkboxChecked && emailValid)));
        }

        // Добавляем обработчики на все поля формы
        password1.addEventListener('input', validateForm);
        password2.addEventListener('input', validateForm);
        emailInput.addEventListener('input', validateForm);
        checkbox.addEventListener('change', validateForm);
    });
</script>
</body>
</html>
