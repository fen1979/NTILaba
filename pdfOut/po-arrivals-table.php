<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'warehouse/WareHouse.php';
// Загружаем записи из БД
$items = R::findAll(PO_AIRRVAL, 'orders_id = ?', [$_GET['orid']]);

if ($items) { ?>
    <!doctype html>
    <html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
    <head>
        <?php HeadContent('receipt_note'); ?>
        <style>
            /* Основные стили для таблицы */
            table {
                width: 100%;
                border-collapse: collapse;
                /*font-size: 10px; !* Уменьшение размера шрифта *!*/
            }

            th, td {
                text-align: left;
                padding: 4px; /* Уменьшение отступов */
                border: 1px solid #ddd;
                word-wrap: break-word; /* Перенос длинных слов */
            }

            th {
                background-color: #f2f2f2;
            }

            /* Подсветка строки при наведении */
            .item-list:hover {
                background: #0d6efd;
                color: white;
            }

            /* Стили для печати */
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }

                table {
                    width: 100%;
                    font-size: 8px; /* Дополнительное уменьшение размера шрифта для печати */
                    page-break-inside: avoid; /* Предотвращение разрывов страницы внутри таблицы */
                }

                th, td {
                    padding: 2px;
                }

                .item-list:hover {
                    background: none; /* Отключение подсветки при печати */
                }

                /* Убираем лишние отступы */
                * {
                    margin: 0;
                    padding: 0;
                }

                /* Отключение разрывов страницы внутри таблицы */
                tr {
                    page-break-inside: avoid;
                }

                /* Отключаем разрывы страницы для таблицы */
                table {
                    page-break-after: auto;
                    page-break-before: auto;
                }
            }

        </style>
    </head>
    <body class="p-3 mt-3">
    <h3 class="mb-3">Receipt note for order No <?= $_GET['orid'] ?></h3>

    <table class="mb-3" id="raw-data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Owner</th>
            <th>SKU</th>
            <th>For whom</th>
            <th>Date in</th>
            <th>Part number</th>
            <th>Manufacture P/N</th>
            <th>Notes</th>
            <th>Declared qty</th>
            <th>Aqtual qty</th>
            <th>Package</th>
            <th>Storage</th>
            <th>Warehouse</th>
            <th>Deffect</th>
            <th>User</th>
            <th>Difference</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $s = 1;
        foreach ($items as $item) {
            list($difference, $qty) = WareHouse::getQtyDifference($item['declared_qty'], $item['actual_qty']);
            ?>
            <tr class="<?= $difference ?> item-list">
                <td><?= $s++; ?></td>
                <td><?= $item['owner_name'] ?></td>
                <td><?= $item['consignment'] ?></td>
                <td><?= $item['for_whom'] ?></td>
                <td><?= $item['date_in'] ?></td>
                <td><?= $item['part_number'] ?></td>
                <td><?= $item['manufacture_pn'] ?></td>
                <td><?= $item['notes'] ?></td>
                <td><?= $item['declared_qty'] ?></td>
                <td><?= $item['actual_qty'] ?></td>
                <td><?= $item['package_type'] ?></td>
                <td><?= $item['storage_place'] ?></td>
                <td><?= R::load(WH_TYPES, $item['warehouse_type'])->type_name; ?></td>
                <td><?= $item['deffect'] ?></td>
                <td><?= json_decode($item['user'], true)['name'] ?></td>
                <td><?= $qty ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <!--  Table of non-conformities or defects of goods -->
    <h3 class="mb-3">Table of non-conformities or defects of goods</h3>

    <table class="mb-3" id="error-data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Owner</th>
            <th>SKU</th>
            <th>For whom</th>
            <th>Date in</th>
            <th>Part number</th>
            <th>Manufacture P/N</th>
            <th>Notes</th>
            <th>Declared qty</th>
            <th>Aqtual qty</th>
            <th>Package</th>
            <th>Storage</th>
            <th>Warehouse</th>
            <th>Deffect</th>
            <th>User</th>
            <th>Difference</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $s = 1;
        foreach ($items as $item) {
            list($difference, $qty) = WareHouse::getQtyDifference($item['declared_qty'], $item['actual_qty']);
            if ($qty > 0) { ?>
                <tr class="<?= $difference ?> item-list">
                    <td><?= $s++; ?></td>
                    <td><?= $item['owner_name'] ?></td>
                    <td><?= $item['consignment'] ?></td>
                    <td><?= $item['for_whom'] ?></td>
                    <td><?= $item['date_in'] ?></td>
                    <td><?= $item['part_number'] ?></td>
                    <td><?= $item['manufacture_pn'] ?></td>
                    <td><?= $item['notes'] ?></td>
                    <td><?= $item['declared_qty'] ?></td>
                    <td><?= $item['actual_qty'] ?></td>
                    <td><?= $item['package_type'] ?></td>
                    <td><?= $item['storage_place'] ?></td>
                    <td><?= R::load(WH_TYPES, $item['warehouse_type'])->type_name; ?></td>
                    <td><?= $item['deffect'] ?></td>
                    <td><?= json_decode($item['user'], true)['name'] ?></td>
                    <td><?= $qty ?></td>
                </tr>
            <?php }
        } ?>
        </tbody>
    </table>

    <div class="mt-4" id="btn-box">
        <button class="btn btn-outline-diliny" id="print-all" value="1">Print All</button>
        <button class="btn btn-outline-diliny" id="print-err" value="2">Print non-conformities</button>
    </div>
    <?php ScriptContent(); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // реакция на событие клик на кнопках печати таблиц
            dom.in("click", "#print-all,#print-err", function () {
                // была нажата кнопка распечатать все
                if (this.value === "1") {
                    dom.hide("#btn-box");
                }
                // была нажата кнопка распечатать только проблемные данные
                if (this.value === "2") {
                    dom.hide("#btn-box");
                    dom.hide("#raw-data-table");
                }
                // запрос печати
                window.print();
                // таймер появления кнопок после печати
                setTimeout(function () {
                    dom.show("#raw-data-table");
                    dom.show("#btn-box");
                }, 500);
            });

            // скрываем столбцы в которых нет данных
            dom.hideEmptyColumnsInTable("#raw-data-table");
            dom.hideEmptyColumnsInTable("#error-data-table");
        });
    </script>
    </body>
    </html>
<?php } ?>
