<?php
isset($_SESSION['userBean']) && isUserRole(ROLE_ADMIN) or header("Location: /order") and exit();
require 'stock/WareHouse.php';

// TODO добавить лог оборота деталей отдельно от лога программы и сделать отдельный вывод

/* получение пользователя из сессии */
$thisUser = $_SESSION['userBean'];
$page = 'warehouse';
$modalDelete = false;

/* get all from DB for view */
$goods = R::findAll(WH_NOMENCLATURE, 'ORDER BY id ASC');
$settings = getUserSettings($thisUser, WH_NOMENCLATURE);
$noSettingsYet = 'You have not yet configured the output styles for this table, do you want to configure it?';
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <!--suppress CssUnusedSymbol -->
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
    </style>
</head>
<body>
<!-- NAVIGATION BAR -->
<?php
NavBarContent($page, $thisUser, null, Y['STOCK']);
$t = 'Press the [+] button to add new item in storage, or CSV button to import file';
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
                    echo '<th>' . L::TABLES(WH_NOMENCLATURE, $set) . '</th>';
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
                            echo '<td>' . $item[$set] . '</td>';
                        }
                    }
                } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>

    <?php } else { ?>

        <div class="mt-3">
            <h3><?= $noSettingsYet ?></h3>
            <br>
            <button type="button" class="url btn btn-outline-info" value="setup?route-page=1">Configure it</button>
        </div>
    <?php } ?>
</div>
<?php

// Футер
footer($page);
?>
<button type="button" class="url hidden" value="" id="routing-btn"></button>
<?php
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