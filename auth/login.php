<?php
/* user login case */
if (isset($_POST['userName']) && isset($_POST['userPassword'])) {
    $deviceType = _E($_POST['user-agent']);
    $name = _E($_POST['userName']);
    $pass = _E($_POST['userPassword']);

    $user = R::findOne(USERS, "user_name = ?", [$name]);
    if ($user && password_verify($pass, $user['user_hash'])) {
        // Регенерация ID сессии при каждом входе (после успешной аутентификации, например)
        session_regenerate_id(true);

        $_SESSION['userBean'] = $user;
        $_SESSION['preview_mode'] = true;

        $details = 'User named: ' . $user->user_name . ', Logged in successfully on: ' . date('Y/m/d') . ' at ' . date('h:i');
        $details .= '<br>User device type is: ' . $deviceType . ', ';
        $details .= getServerData();
        logAction($user->user_name, 'LOGIN', OBJECT_TYPE[11], $details);

        // Проверяем, существует ли сохраненный URL для перенаправления
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = ltrim($_SESSION['redirect_after_login'], '/'); // Удаляем начальный слэш, если он есть
            unset($_SESSION['redirect_after_login']); // Удаляем из сессии, чтобы не было повторных перенаправлений
            redirectTo($redirect_url);
        } else {
            // смотрим где было последнее посещение и переходим туда
            if ($user->last_action) {
                $url = (strpos($user->last_action, 'assy_flow_pdf') === false && strpos($user->last_action, 'route_card') === false)
                    ? $user->last_action : $user->link;
                redirectTo(ltrim($url, '/'));
            } else {
                redirectTo($user->link);
            }
        }
    } else {
        $error = 'Password or Name incorrect. Please try again!';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>

    <?php HeadContent('login'); ?>
    <style>
        .eye-box {
            position: relative;
        }

        .eye {
            position: absolute;
            top: 56%;
            right: 5%;
        }

        .cookie-consent-container {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #0857c3;
            color: #fff;
            text-align: center;
            padding: 10px;
            z-index: 1000;
            display: none;
        }

        .content-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .cookie-buttons {
            text-align: center;
            cursor: pointer;
            border-radius: 100px;
            background: #fff;
            color: #0857c3;
            font-size: 16px;
            padding: 13px 53px;
            border: solid 1px transparent;
        }

        .cookie-buttons:hover {
            background: #0857c3;
            color: #fff;
            border: solid 1px #fff;
        }

    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
<?php
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div id="login-form">
                        <h5 class="card-title text-center mb-4">Login</h5>
                        <form method="post" id="login-form">
                            <input type="hidden" name="user-agent" class="user-agent" value="">
                            <div class="mb-3">
                                <label for="loginUsername" class="form-label">Name</label>
                                <input type="text" class="form-control" id="loginUsername" name="userName" required>
                            </div>
                            <div class="mb-3 eye-box">
                                <label for="loginPassword" class="form-label">Password</label>
                                <input type="password" class="form-control pi" id="loginPassword" name="userPassword" required>
                                <i class="bi bi-eye eye"></i>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success form-control" id="login">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="cookieConsentContainer" class="cookie-consent-container">
    <div class="content-container">
        <p>This site uses cookies to improve your experience. By continuing to use our site, you consent to the use of cookies.</p>
        <button class="cookie-buttons" id="acceptCookieConsent">Confirm</button>
        <button class="cookie-buttons" id="declineCookieConsent">Declaine</button>
    </div>
</div>

<?php ScriptContent('login'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // добавляем Keypress для нажатия entеr на странице
        dom.doClick("body", "#login");
        // добавляем тип устройства с которого зашли
        dom.e(".user-agent", function () {
            const userAgent = navigator.userAgent;
            if (/mobile/i.test(userAgent)) {
                this.value = "Mobile";
            } else if (/tablet/i.test(userAgent)) {
                this.value = "Tablet";
            } else {
                this.value = "Desktop";
            }
        });
    });

    window.onload = function () {
        let consent = getCookie("cookieConsent");
        if (consent !== "true") {
            document.getElementById("cookieConsentContainer").style.display = "block";
        }

        document.getElementById("acceptCookieConsent").onclick = function () {
            setCookie("cookieConsent", "true", 365);
            document.getElementById("cookieConsentContainer").style.display = "none";
        };

        document.getElementById("declineCookieConsent").onclick = function () {
            document.getElementById("cookieConsentContainer").style.display = "none";
        };
    }

    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            let date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        let nameEQ = name + "=";
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

</script>
</body>
</html>