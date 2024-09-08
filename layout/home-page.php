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
            background-color: transparent; /* Изменение фона при наведении */
            color: #000000;
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
    <div class="row text-center" id="btn-container">
        <?php foreach (SR::getAllResourcesInGroup('home_btns') as $link => $text) {
            echo '<div class="col-12 col-md-3 mb-4">';
            echo '<a href="' . $link . '" target="_blank" class="link-button">';
            echo $text;
            echo '</a>';
            echo '</div>';
        } ?>
    </div>
</div>
<?php PAGE_FOOTER($page); ?>
</body>
</html>
