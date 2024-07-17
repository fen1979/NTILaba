<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'wh');
require 'WareHouseLog.php';
$user = $_SESSION['userBean'];
$page = 'wh_log';

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, 'movement-log', WAREHOUSE_LOGS, 100);

// Выполнение запроса и получение результатов
$logs = R::find(WAREHOUSE_LOGS, 'ORDER BY date_in ASC ' . $pagination);

// get user settings for preview table
$settings = getUserSettings($user, WAREHOUSE_LOGS);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        /* СТИЛИ ДЛЯ ВЫВОДА ТАБЛИЦ */
        .modal-body {
            /* убираем падинги от бутстрапа */
            padding: 0;
        }

        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: normal;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 0;
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

        .accordion-content {
            display: none;
            background-color: #f9f9f9;
        }

        .accordion-content td {
            padding: 8px 16px;
            border-top: none;
        }

        .accordion-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
<!-- NAVIGATION BAR -->
<?php
$title = ['title' => 'Warehouse Information', 'app_role' => $user['app_role']];
NavBarContent($page, $title, null, Y['LOG']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid border-top">
    <table>
        <thead>
        <tr>
            <th>Action</th>
            <th>User</th>
            <th>Item ID</th>
            <th>Date In</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log) { ?>
            <tr class="item-list accordion-toggle">
                <td><?= $log['action'] ?></td>
                <td><?= $log['user_name'] ?></td>
                <td><?= $log['items_id'] ?></td>
                <td><?= $log['date_in'] ?></td>
            </tr>
            <tr class="accordion-content">
                <?php
                // если данные существуют
                if ($log['warehouse_data'] && $log['invoice_data']) {
                    $wh_data = json_decode($log['warehouse_data']);
                }

                if ($log['items_data']) {
                    // Преобразование JSON-строки обратно в массив
                    $item = json_decode($log['items_data'], true);

                    if (!empty($item['item_data_before']) && !empty($item['item_data_after'])) {
                        // Доступ к данным до изменений
                        $itemDataBefore = $item['item_data_before'];
                        // Доступ к данным после изменений
                        $itemDataAfter = $item['item_data_after'];
                    }
                }

                // item data column
                if (!empty($item)) {
                    if (!empty($itemDataBefore) || !empty($itemDataAfter)) {
                        //если есть item до и после
                        echo '<td>';
                        foreach ($itemDataBefore as $key => $wh) {
                            echo "<p>$key --> $wh</p>";
                        }
                        echo '</td>';

                        echo '<td>';
                        foreach ($itemDataAfter as $key => $wh) {
                            echo "<p>$key --> $wh</p>";
                        }
                        echo '</td>';
                    } else {

                        // если есть только item
                        echo '<td colspan="2">';
                        foreach ($item as $key => $wh) {
                            echo "<p>$key --> $wh</p>";
                        }
                        echo '</td>';
                    }
                } ?>

                <td>
                    <p>Invoice: <?= $log['invoice_data'] ?></p>
                </td>
                <td>
                    <?php
                    // warehouse data column
                    if (!empty($wh_data)) {
                        foreach ($wh_data as $key => $wh) {
                            echo "<p>$key --> $wh</p>";
                        }
                    } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <!-- pagination buttons -->
    <?= $paginationButtons ?>
</div>

<?php
footer($page);

// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

/* SCRIPTS */
ScriptContent('arrivals');
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggles = document.querySelectorAll('.accordion-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('click', function () {
                const content = this.nextElementSibling;

                // Close all open accordions except the one clicked
                document.querySelectorAll('.accordion-content').forEach(acc => {
                    if (acc !== content) {
                        acc.style.display = 'none';
                    }
                });

                // Toggle the clicked accordion
                content.style.display = content.style.display === 'table-row' ? 'none' : 'table-row';
            });
        });
    });
</script>
</body>
</html>
