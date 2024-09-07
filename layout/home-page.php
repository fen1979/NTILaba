<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'home';
$role = $user['app_role'];
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .link-button {
            width: 250px;
            height: 250px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px; /* Большой размер шрифта */
            text-align: center;
            background-color: #007bff; /* Синий фон как пример */
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
        }

        .link-button:hover {
            background-color: #0056b3; /* Изменение фона при наведении */
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['active_btn' => Y['ORDER'], 'user' => $thisUser, 'page_name' => $page]); ?>

<div class="container">
    <div class="row text-center">
        <!-- Link 1 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="order" target="_blank" class="link-button">
                ORDERS
            </a>
        </div>
        <!-- Link 2 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/project" target="_blank" class="link-button">
                PROJECTS, assembly manuals
            </a>
        </div>
        <!-- Link 3 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/wh" target="_blank" class="link-button">
                WAREHOUSE
            </a>
        </div>
        <!-- Link 4 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/task_list" target="_blank" class="link-button">
                TASKS
            </a>
        </div>
        <!-- Link 5 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/pioi" target="_blank" class="link-button">
                CREATIONS
            </a>
        </div>
        <!-- Link 6 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/setup?route-page=1" target="_blank" class="link-button">
                SETTINGS
            </a>
        </div>
        <!-- Link 7 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/tracking" target="_blank" class="link-button">
                TRACKING
            </a>
        </div>
        <!-- Link 8 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/" target="_blank" class="link-button">
                uno
            </a>
        </div>
        <!-- Link 9 -->
        <div class="col-12 col-md-4 mb-4">
            <a href="/" target="_blank" class="link-button">
                uno
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
