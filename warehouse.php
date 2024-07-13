<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'order');
require 'stock/WareHouse.php';
/* получение пользователя из сессии */
$thisUser = $_SESSION['userBean'];
$page = 'warehouse';

// SQL-запрос для получения всех записей из nomenclature (whitems) с прикрепленными записями из warehouse
$items = WH_ITEMS;
$warehouse = WAREHOUSE;
$whtypes = WH_TYPES;
$type_query = '';
$conditions = [];

// фильтрация по складам
if (isset($_GET['wh-type'])) {
    $wh_type = _E($_GET['wh-type']);
    $type_query = ' WHERE wn.warehouses_id = ' . $wh_type;
    $conditions = ['query' => 'warehouses_id = ?', 'data' => $wh_type];
}

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, $page, WH_ITEMS, 50, $conditions);

//$query = "
//    SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf
//    FROM $items wn
//    LEFT JOIN $warehouse w ON w.items_id = wn.id
//    AND w.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
//    AND w.fifo = (
//        SELECT MIN(w2.fifo)
//        FROM $warehouse w2
//        WHERE w2.items_id = wn.id
//        AND w2.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
//    )
//    $type_query
//    ORDER BY wn.id ASC
//    $pagination
//";
$query = "
    SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf, wt.type_name
    FROM $items wn
    LEFT JOIN $warehouse w ON w.items_id = wn.id
    AND w.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
    AND w.fifo = (
        SELECT MIN(w2.fifo)
        FROM $warehouse w2
        WHERE w2.items_id = wn.id
        AND w2.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
    )
    LEFT JOIN $whtypes wt ON wt.id = wn.warehouses_id
    $type_query
    ORDER BY wn.id ASC
    $pagination
";


// Выполнение запроса и получение результатов
$goods = R::getAll($query);


// get user settings for preview table
$settings = getUserSettings($thisUser, WH_ITEMS);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            white-space: nowrap;
        }

        table thead tr th {
            /* Important */
            background-color: #c7dfec;
            position: sticky;
            z-index: 100;
            top: 6.6%;
        }

        th, td {
            text-align: left;
            padding: 0 5px 0 5px;
            border: 1px solid #ddd;
        }

        tr:hover {
            cursor: pointer;
            background: #baecf6;
        }

        td.clickable:hover {
            background: #0739ff;
        }

        .notice {
            white-space: pre-wrap;
        }

        .active-filter {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent($page, $thisUser, null, Y['STOCK']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="container-fluid">
    <!-- кнопки фильтрации по складам -->
    <div class="d-flex align-items-center mb-2">
        <h1 class="ms-2 me-3">Warehouse filters</h1>
        <div class="d-inline">
            <a href="warehouse" class="btn btn-outline-secondary btn-sm ms-1" id="warehouse-btn">Warehouse</a>
            <?php
            foreach (R::findAll(WH_TYPES) as $row) {
                $type = $row['type_name'];
                $id = $row['id'];
                echo '<a href="warehouse?wh-type=' . $id . '" class="btn btn-outline-secondary btn-sm ms-1" id="a' . $id . '">' . $type . '</a>';
            }
            ?>
        </div>
    </div>

    <!-- ВЫВОД ДАННЫХ ПОСЛЕ СОХРАНЕНИЯ ЗАПЧАСТИ В БД -->
    <?php if ($settings) { ?>
        <table class="custom-table">
            <thead>
            <tr>
                <th>Warehouse</th>
<!--                <th>ID</th>-->
                <?php
                // выводим заголовки согласно настройкам пользователя
                foreach ($settings as $k => $set) {
                    echo '<th>' . L::TABLES(WH_ITEMS, $set) . '</th>';
                }
                ?>
            </tr>
            </thead>

            <tbody id="searchAnswer">
            <?php if (!empty($goods)) {
                foreach ($goods as $item) {
                    $color = '';
                    if ((int)$item['quantity'] <= (int)$item['min_qty']) {
                        $color = 'danger';
                    } elseif ((int)$item['quantity'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                        $color = 'warning';
                    } ?>

                    <tr class="<?= $color; ?>" data-id="<?= $item['id']; ?>" id="row-<?= $item['id']; ?>">
<!--                    <td>--><?php //= $item['id']; ?><!--</td>-->
                    <td><?= $item['type_name']; ?></td>
                    <?php
                    // выводим таблицу согласно настройкам пользователя
                    foreach ($settings as $key => $set) {
                        if ($set == 'item_image') { ?>
                            <td>
                                <?php $img_href = ($item['part_type'] == 'SMT') ? '/public/images/smt.webp' : '/public/images/pna_en.webp' ?>
                                <img src="<?= $item['item_image'] ?? $img_href; ?>" alt="goods" width="100" height="auto">
                            </td>
                        <?php } elseif ($set == 'datasheet') {
                            ?>
                            <td><a type="button" class="btn btn-outline-info" href="<?= $item['datasheet'] ?> " target="_blank">Open Datasheet</a></td>
                            <?php
                        } else {
                            // output data from two tables warehouse and whitems
                            if ($set == 'owner' && !empty($item[$set])) {
                                // get owner name from json data set
                                $wh = json_decode($item[$set])->name;
                            } else {
                                $wh = $item[$set] ?? '';
                            }
                            // print data to page
                            echo '<td>' . $wh . '</td>';
                        }
                    }
                } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>

        <!-- pagination buttons -->
        <?= $paginationButtons ?>
    <?php } else { ?>

        <div class="mt-3">
            <h3>You have not yet configured the output styles for this table, do you want to configure it?</h3>
            <br>
            <button type="button" class="url btn btn-outline-info" value="setup?route-page=1">Configure it</button>
        </div>
    <?php } ?>
</div>

<button type="button" class="url hidden" value="" id="routing-btn"></button>
<?php
// Футер
footer($page);

/* SCRIPTS */
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Выбираем таблицу с id searchAnswer
        const tBody = document.getElementById('searchAnswer');

        // Добавляем делегированный обработчик событий на таблицу
        tBody.addEventListener('click', function (event) {
            // Проверяем, был ли клик по ссылке
            if (event.target.tagName.toLowerCase() === 'a') {
                return; // Прекращаем выполнение функции, если клик был по ссылке
            }

            // Находим родительский <tr> элемент
            let row = event.target;
            while (row && row.tagName.toLowerCase() !== 'tr') {
                row = row.parentElement;
            }

            // Если <tr> элемент найден и у него есть data-id
            if (row && row.dataset.id) {
                // Получаем значение data-id
                const dataId = row.dataset.id;
                let btn = dom.e("#routing-btn");
                btn.value = "warehouse/the_item?itemid=" + dataId
                btn.click();
            }
        });

        // указатель активного фильтра складов
        const urlParams = new URLSearchParams(window.location.search);
        const whType = urlParams.get('wh-type');

        if (whType) {
            const activeButton = dom.e('#a' + whType);
            if (activeButton) {
                activeButton.classList.add('active-filter');
            }
        } else {
            const defaultButton = dom.e('#warehouse-btn');
            if (defaultButton) {
                defaultButton.classList.add('active-filter');
            }
        }
    });
</script>
</body>
</html>
