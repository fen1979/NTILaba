<?php
isset($_SESSION['userBean']) && isUserRole(ROLE_ADMIN) or header("Location: /order") and exit();
require 'stock/WareHouse.php';
/* получение пользователя из сессии */
$thisUser = $_SESSION['userBean'];
$page = 'warehouse';

// SQL-запрос для получения всех записей из nomenclature (whitems) с прикрепленными записями из warehouse
$items = WH_ITEMS;
$warehouse = WAREHOUSE;
$pagination = '';

// Параметры пагинации
if (isset($_GET['limit']))
    $limit = (int)$_GET['limit'];
else
    $limit = 50;

if ($limit != 0) {
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $limit;
    $pagination = "LIMIT $limit OFFSET $offset";
    // SQL-запрос для получения общего количества записей
    $totalResult = R::count(WH_ITEMS);
    $totalPages = ceil($totalResult / $limit);
}

$query = "
        SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf
        FROM $items wn
        LEFT JOIN $warehouse w ON w.items_id = wn.id
        AND w.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
        AND w.fifo = (
            SELECT MIN(w2.fifo)
            FROM $warehouse w2
            WHERE w2.items_id = wn.id
            AND w2.fifo > DATE_SUB(NOW(), INTERVAL wn.shelf_life MONTH)
        )
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

        .pagination {
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #007bff;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }

        .pagination a:hover:not(.active) {
            background-color: #ddd;
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
    <h1 class="ms-2">Warehouse</h1>

    <!-- ВЫВОД ДАННЫХ ПОСЛЕ СОХРАНЕНИЯ ЗАПЧАСТИ В БД -->
    <?php if ($settings) { ?>
        <table class="custom-table">
            <thead>
            <tr>
                <th>ID</th>
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
                    if ((int)$item['actual_qty'] <= (int)$item['min_qty']) {
                        $color = 'danger';
                    } elseif ((int)$item['actual_qty'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                        $color = 'warning';
                    } ?>

                    <tr class="<?= $color; ?>" data-id="<?= $item['id']; ?>" id="row-<?= $item['id']; ?>">
                    <td><?= $item['id']; ?></td>
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

        <?php
        if ($limit != 0) {
            $limit_n = (isset($_GET['limit'])) ? '&limit=' . $_GET['limit'] : '';
            ?>
            <!-- Пагинация -->
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="warehouse?page=<?= $currentPage - 1 . $limit_n; ?>">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="warehouse?page=<?= $i . $limit_n; ?>" class="<?= $i == $currentPage ? 'active' : ''; ?>"><?= $i; ?></a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="warehouse?page=<?= $currentPage + 1 . $limit_n; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>

        <?php }
    } else { ?>

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
                btn.value = "warehouse/the_item?edititem&itemid=" + dataId
                btn.click();
            }
        });
    });
</script>
</body>
</html>
