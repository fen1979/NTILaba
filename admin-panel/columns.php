<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
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
$navBarData['title'] = 'Columns';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="main-container">
    <main class="container-fluid content">
        <?php $t = 'In the table display settings, fields can be moved using drag and drop. 
The location of the fields in the settings table will be saved according to the settings! 
The information will be displayed based on the order of the fields when saving.'; ?>
        <h3 class="p-3">Settings for displaying information in tables. <i class="bi bi-info-circle fs-5" data-title="<?= $t; ?>"></i></h3>
        <div class="row mb-3">
            <div class="col-2 p-2">
                <h5>
                    Select the table to configure:
                </h5>
            </div>
            <div class="col-10">
                <form action="" method="post" id="select-form">
                    <input type="hidden" name="table-name" id="table-name">
                    <input type="hidden" name="sel_tab" id="table-selector">
                    <button type="button" name="sel_tab" value="<?= PRODUCT_UNIT ?>" class="dob btn btn-outline-primary ms-2">Product Unit</button>
                    <button type="button" name="sel_tab" value="<?= ORDERS ?>" class="dob btn btn-outline-primary ms-2">Orders</button>
                    <button type="button" name="sel_tab" value="<?= UNITS_BOM ?>" class="dob btn btn-outline-primary ms-2">Order and Units BOM</button>
                    <button type="button" name="sel_tab" value="<?= TOOLS ?>" class="dob btn btn-outline-primary ms-2">Tools</button>
                    <button type="button" name="sel_tab" value="<?= CLIENTS ?>" class="dob btn btn-outline-primary ms-2">Customers</button>
                    <button type="button" name="sel_tab" value="<?= WH_ITEMS ?>" class="dob btn btn-outline-primary ms-2">Warehouse</button>

                    <!--                    <button type="button" name="sel_tab" disabled value="routeactions" class="dob btn btn-outline-secondary ms-2">Rout Actions</button>-->
                    <!--                    <button type="button" name="sel_tab" disabled value="users" class="dob btn btn-outline-secondary ms-2">Users</button>-->
                    <!--                    <button type="button" name="sel_tab" disabled value="projectsteps" class="dob btn btn-outline-secondary ms-2">Units Data</button>-->
                    <!--                    <button type="button" name="sel_tab" disabled value="history" class="dob btn btn-outline-secondary ms-2">Units History</button>-->
                    <button type="button" name="sel_tab" value="order_details" class="dob btn btn-outline-secondary ms-2">Order Details</button>
                    <!--                    <button type="button" name="sel_tab" id="da" value="" class=" btn btn-outline-secondary ms-2">Orders Data</button>-->

                    <button type="button" name="" value="" class="btn btn-outline-primary ms-2">Priority Out</button>
                </form>
            </div>
        </div>

        <?php
        // fixme добавить в склад колонки таблицу инвойса тоже

        if (isset($_POST['sel_tab'])) {
            // check if table exists in DB
            $tableExists = R::getAll("SHOW TABLES LIKE '" . _E($_POST["sel_tab"]) . "'");

            if (count($tableExists) > 0) {
                /* настройки вывода от пользователя */
                $settings = getUserSettings($user, _E($_POST['sel_tab'])); ?>

                <form action="" method="post">
                    <div class="p-3">
                        <table class="custom-table w-100">
                            <thead class="bg-light">
                            <tr class="text-left align-middle">
                                <th scope="col" class="p-2">Table for configure: <b style="color: #dc3545; "><?= $_POST['table-name']; ?></b></th>
                            </tr>
                            <tr class="align-middle">
                                <th scope="col" class="border-end text-left p-2">Name</th>
                                <th scope="col" class="text-left p-2">Enable Filter</th>
                                <th scope="col" class="text-left p-2">Colum Name</th>

                                <th style="float: right; margin-top: -1.5rem; padding-right: 1rem;">
                                    <button type="submit" class="btn btn-success" name="save-settings" value="<?= $_POST['sel_tab']; ?>">
                                        Save Table Settings
                                    </button>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Функция для получения структуры таблицы
                            function getTableColumns($tableName): array
                            {
                                return R::inspect($tableName);
                            }

                            // Имена таблиц
                            $tables = [WAREHOUSE, WH_ITEMS];
                            $tableColumns = [];

                            if ($_POST['sel_tab'] == WH_ITEMS) {
                                // Получаем данные о полях из нескольких таблиц и объединяем их
                                foreach ($tables as $table) {
                                    $tableColumns = array_merge($tableColumns, getTableColumns($table));
                                }
                            } elseif ($_POST['sel_tab'] == 'order_details') {
                                $tables = [ORDERS, PRODUCT_UNIT];
                                // Получаем данные о полях из нескольких таблиц и объединяем их
                                foreach ($tables as $table) {
                                    $tableColumns = array_merge($tableColumns, getTableColumns($table));
                                }
                            } else {
                                // Получаем данные о полях из одной таблицы
                                $tableColumns = getTableColumns(_E($_POST['sel_tab']));
                            }

                            // Извлечение имен столбцов
                            $columnNames = array_keys($tableColumns);
                            // Вывод имен столбцов
                            $tab = $_POST['sel_tab'];
                            $array_D = $columnNames;

                            if ($settings) {
                                // Извлекаем ключи из настроек пользователя, так как настройки теперь ассоциативный массив
                                $array_A = array_keys($settings);
                                $array_B = $columnNames;
                                // Находим элементы, которые есть в B, но нет в A
                                $diff = array_diff($array_B, $array_A);
                                // Объединяем массивы
                                $array_D = array_merge($array_A, $diff);
                            }

                            foreach ($array_D as $columnName) {
                                $f = SR::getResourceValue($tab, $columnName);
                                if (!empty($f)) {
                                    $ch = $fc = '';
                                    if (isset($settings[$columnName])) {
                                        $ch = 'checked';
                                        $fc = $settings[$columnName] === 'filter' ? 'checked' : '';
                                    }
                                    ?>
                                    <tr class="text-left align-middle border-bottom">
                                        <td class="fs-5 me-3 p-2">
                                            <input class="form-check-input ms-2" type="checkbox" name="selected-colums[]" value="<?= $columnName; ?>" <?= $ch; ?>>
                                        </td>
                                        <td class="fs-5 me-3 p-2">
                                            <input type="checkbox" class="form-check-input " name="enable-filter[<?= $columnName; ?>]" value="filter" <?= $fc; ?>>
                                        </td>
                                        <td class="ms-2"><?= SR::getResourceValue($tab, $columnName); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Скрытые поля для порядка строк -->
                    <input type="hidden" name="rowOrder" id="rowOrder" value="">
                </form>
            <?php }
        }
        ?>

    </main>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm();
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>
</body>
</html>