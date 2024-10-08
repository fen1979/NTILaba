<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
$page = 'wh_log';

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, 'movement-log', WH_LOGS, 100);

// Выполнение запроса и получение результатов
$logs = R::find(WH_LOGS, 'ORDER BY date_in ASC ' . $pagination);

// get user settings for preview table
//$settings = getUserSettings($user, WH_LOGS);
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
NavBarContent(['title' => 'Warehouse Information',
    'active_btn' => Y['LOG'],
    'page_tab' => $_GET['page'] ?? null,
    'record_id' => null,
    'user' => $user,
    'page_name' => $page]); ?>


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
        <?php
        /**
         * Отображение логов склада
         * @param array $result
         * @return void
         */
        function viewWarehouseLogs(array $result): void
        {
            /**
             * Проверяет, является ли строка валидным JSON
             * @param string $data
             * @return bool
             */
            $isValidJson = function (string $data): bool {
                json_decode($data);
                return (json_last_error() === JSON_ERROR_NONE);
            };

            if ($result) {
                foreach ($result as $log) { ?>
                    <tr class="item-list accordion-toggle">
                        <td><?= $log['action'] ?></td>
                        <td><?= $log['user_name'] ?></td>
                        <td><?= $log['items_id'] ?></td>
                        <td><?= $log['date_in'] ?></td>
                    </tr>

                    <tr class="accordion-content">
                        <?php
                        // Инициализация переменных
                        $wh_data = $invoiceData = $itemDataBefore = $itemDataAfter = [];

                        // Проверка наличия данных
                        if (!empty($log['warehouse_data']) && !empty($log['invoice_data']) && $isValidJson($log['invoice_data'])) {
                            $wh_data = json_decode($log['warehouse_data'], true);
                            $invoiceData = json_decode($log['invoice_data'], true);
                        }

                        if (!empty($log['items_data'])) {
                            // Преобразование JSON-строки обратно в массив
                            $item = json_decode($log['items_data'], true);

                            if (!empty($item['item_data_before']) && !empty($item['item_data_after'])) {
                                // Доступ к данным до и после изменений
                                $itemDataBefore = $item['item_data_before'];
                                $itemDataAfter = $item['item_data_after'];
                            }
                        }

                        // Отображение данных до и после изменений
                        if (!empty($itemDataBefore) || !empty($itemDataAfter)) {
                            echo '<td><p>Item Data Before Change</p>';
                            foreach ($itemDataBefore as $key => $value) {
                                echo "<p>$key --> $value</p>";
                            }
                            echo '</td>';

                            echo '<td><p>Item Data After Change</p>';
                            foreach ($itemDataAfter as $key => $value) {
                                echo "<p>$key --> $value</p>";
                            }
                            echo '</td>';
                        } elseif (!empty($item)) {
                            echo '<td colspan="2">';
                            foreach ($item as $key => $value) {
                                echo "<p>$key --> $value</p>";
                            }
                            echo '</td>';
                        }

                        // Отображение данных инвойса
                        echo '<td><p>Invoice Table Data</p>';
                        if (!empty($invoiceData)) {
                            foreach ($invoiceData as $key => $value) {
                                echo "<p>$key --> $value</p>";
                            }
                        } else {
                            echo '<p>Invoice: ' . $log['invoice_data'] . '</p>';
                        }
                        echo '</td>';

                        // Отображение данных склада
                        echo '<td><p>Warehouse Table Data</p>';
                        if (!empty($wh_data)) {
                            foreach ($wh_data as $key => $value) {
                                echo "<p>$key --> $value</p>";
                            }
                        }
                        echo '</td>';
                        ?>
                    </tr>
                <?php }
            } else {
                echo '<h2>Unable to find data by search!</h2>';
            }
        }

        // cal the logs
        viewWarehouseLogs($logs);
        ?>
        </tbody>
    </table>
    <!-- pagination buttons -->
    <?= $paginationButtons ?>
</div>

<?php
// FOOTER AND SCRIPTS
PAGE_FOOTER($page); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Инициализация обработчиков событий при загрузке страницы
        dom.setAccordionListeners(".accordion-toggle", ".accordion-content", "click");
    });
</script>
</body>
</html>
