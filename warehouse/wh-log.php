<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'wh');
require 'WareHouseLog.php';
$user = $_SESSION['userBean'];
$page = 'wh_log';

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, 'movement-log', WH_LOGS, 100);

// Выполнение запроса и получение результатов
$logs = R::find(WH_LOGS, 'ORDER BY date_in ASC ' . $pagination);

// get user settings for preview table
$settings = getUserSettings($user, WH_LOGS);

/**
 * Проверяет, является ли строка валидным JSON.
 *
 * @param string $string Строка для проверки.
 * @return bool Возвращает true, если строка валидный JSON, иначе false.
 */
function isValidJson(string $data): bool
{
    json_decode($data);
    return (json_last_error() === JSON_ERROR_NONE);
}

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

        /* ACCORDION CONTENT STYLES */
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
<?php
// NAVIGATION BAR
$navBarData['title'] = 'Warehouse Information';
$navBarData['active_btn'] = Y['LOG'];
$navBarData['page_tab'] = $_GET['page'] ?? null;
$navBarData['record_id'] = null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid border-top" id="wh_logs">
    <table>
        <thead>
        <tr>
            <th>Action</th>
            <th>User</th>
            <th>Item ID</th>
            <th>Date In</th>
        </tr>
        </thead>
        <tbody id="searchAnswer">
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
                $invoiceData = false;
                if ($log['warehouse_data'] && $log['invoice_data']) {
                    $wh_data = json_decode($log['warehouse_data']);
                    if (isValidJson($log['invoice_data'])) {
                        $invoiceData = json_decode($log['invoice_data']);
                    }
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
                        echo '<td><p>Item Data Before Change</p>';
                        foreach ($itemDataBefore as $key => $wh) {
                            echo "<p>$key --> $wh</p>";
                        }
                        echo '</td>';

                        echo '<td><p>Item Data After Change</p>';
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
                }

                // invoice table data
                echo '<td><p>Invoice Table Data</p>';
                if ($invoiceData) {
                    foreach ($invoiceData as $key => $wh) {
                        echo "<p>$key --> $wh</p>";
                    }
                } else {
                    echo '<p>Invoice: ' . $log['invoice_data'] . '</p>';
                }
                echo '</td>';
                // END invoice table data
                ?>

                <td>
                    <p>Warehouse Table Data</p>
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
/* SCRIPTS */
ScriptContent('arrivals');
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Инициализация обработчиков событий при загрузке страницы
        dom.setAccordionListeners(".accordion-toggle", ".accordion-content", "click");
    });
</script>
</body>
</html>
