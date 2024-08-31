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
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Orders', 'user' => $user, 'page_name' => $page, 'btn_title' => 'order']); ?>

<div class="main-container">
    <main class="container-fluid content">
        here was vasya and vasya says xyz
    </main>
</div>
<?php
// MODAL WINDOW WITH ROUTE FORM AND CREATION FORM
deleteModalRouteForm('/new_order');
// Футер
PAGE_FOOTER($page);
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.doSubmit('#create-btn', '#create-form');
    });
</script>
</body>
</html>
