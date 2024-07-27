<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR]);
$page = 'logs';
/* получение пользователя из сессии */
$thisUser = $_SESSION['userBean'];
$orderid = $settings = null;

//$max_id = R::getCell('SELECT MAX(id) FROM logs');
//// Установите новое автоинкрементное значение
//$new_auto_increment = $max_id + 1;
//R::exec('ALTER TABLE logs AUTO_INCREMENT = ?', [$new_auto_increment]);

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, 'logs', LOGS, 50);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>

    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: pre-wrap;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 7%;
        }

        th:last-child, td:last-child {
            text-align: right;
            padding-right: 1rem;
        }

        th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }
    </style>
</head>
<body>

<?php
// NAVIGATION BAR
$navBarData['title'] = 'Actions Information';
$navBarData['active_btn'] = Y['LOG'];
//$navBarData['page_tab'] = $_GET['page'] ?? null;
//$navBarData['record_id'] = null;
$navBarData['user'] = $thisUser;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<main class="container-fluid">
    <table>
        <thead>
        <tr>
            <th>User</th>
            <th>Action</th>
            <th>Object Type</th>
            <th>Details</th>
            <th>Date/Time</th>
        </tr>
        </thead>
        <tbody id="searchAnswer">

        <?php
        $logs = R::findAll(LOGS, 'ORDER BY date DESC ' . $pagination);
        foreach ($logs as $log) {
            ?>
            <tr class="item-list">
                <td class="text-primary"><?= $log['user']; ?></td>
                <td class="text-primary"><?= $log['action']; ?></td>
                <td class="text-primary"><?= $log['object_type']; ?></td>
                <td><?= $log['details']; ?></td>
                <td class="text-primary"><?= $log['date']; ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <?php
    /* pagination */
    echo $paginationButtons;
    /* Футер */
    footer($page);
    ?>
</main>

<?php
/* SCRIPTS */
ScriptContent($page);
?>
</body>
</html>
