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
NavBarContent(['title' => 'Columns', 'user' => $user, 'page_name' => $page]); ?>

<div class="container-fluid content">
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

                <button type="button" name="sel_tab" value="<?= PROJECTS ?>" class="dob btn btn-outline-primary ms-2">Project</button>
                <button type="button" name="sel_tab" value="<?= ORDERS ?>" class="dob btn btn-outline-primary ms-2">Orders</button>
                <button type="button" name="sel_tab" value="<?= PROJECT_BOM ?>" class="dob btn btn-outline-primary ms-2">Order and Project BOM</button>
                <button type="button" name="sel_tab" value="<?= TOOLS ?>" class="dob btn btn-outline-primary ms-2">Tools</button>
                <button type="button" name="sel_tab" value="<?= CLIENTS ?>" class="dob btn btn-outline-primary ms-2">Customers</button>
                <button type="button" name="sel_tab" value="<?= WH_ITEMS ?>" class="dob btn btn-outline-primary ms-2">Warehouse</button>
                <button type="button" name="sel_tab" value="<?= TRACK_DATA ?>" class="dob btn btn-outline-primary ms-2">Track List</button>
                <!--<button type="button" name="sel_tab" disabled value="users" class="dob btn btn-outline-secondary ms-2">Users</button>-->
                <!--<button type="button" name="sel_tab" disabled value="projectsteps" class="dob btn btn-outline-secondary ms-2">Units Data</button>-->
                <!--<button type="button" name="sel_tab" disabled value="history" class="dob btn btn-outline-secondary ms-2">Units History</button>-->
                <!--<button type="button" name="sel_tab" id="da" value="" class=" btn btn-outline-secondary ms-2">Orders Data</button>-->

                <!--<button type="button" name="sel_tab" value="order_details" class="dob btn btn-outline-primary ms-2">Order Details</button>-->
                <!--<button type="button" name="sel_tab" value="priority" class="dob btn btn-outline-primary ms-2">Priority Out</button>-->
                <!--<button type="button" name="sel_tab" value="buttons" class="dob btn btn-outline-primary ms-2">Priority Out</button>-->
            </form>
        </div>
    </div>

    <?php
    // распределение запросов на вывод таблиц для конфигурации
    if (isset($_POST['sel_tab']) || isset($_POST['priority']) || isset($_POST['order_details'])) {
        // сделать разделение на разные таблицы но вывод в одну таблицу-форму для универсальности
        if (isset($_POST['sel_tab'])) {
            createTableSelectionForm(_E($_POST['sel_tab']), _E($_POST['table-name']), $user);
        }

        if (isset($_POST['priority'])) {
            createPriorityColumnSelectionForm();
        }
        if (isset($_POST['order_details'])) {
            createOrderDetailsSelectionForm();
        }
    }

    function createTableSelectionForm($post, $tab_name, $user): void
    {
        // fixme добавить в склад колонки таблицу инвойса тоже
        // check if table exists in DB
        $tableExists = R::getAll("SHOW TABLES LIKE '" . $post . "'");

        if (count($tableExists) > 0) {
            /* настройки вывода от пользователя */
            $settings = getUserSettings($user, $post); ?>

            <form action="" method="post">
                <div class="p-3">
                    <table class="custom-table w-100">
                        <thead class="bg-light">
                        <tr class="text-left align-middle">
                            <th scope="col" class="p-2">Table for configure: <b style="color: #dc3545; "><?= $tab_name; ?></b></th>
                        </tr>
                        <tr class="align-middle">
                            <th scope="col" class="border-end text-left p-2">Name</th>
                            <th scope="col" class="text-left p-2">Enable Filter</th>
                            <th scope="col" class="text-left p-2">Colum Name</th>

                            <th style="float: right; margin-top: -1.5rem; padding-right: 1rem;">
                                <button type="submit" class="btn btn-success" name="save-settings" value="<?= $post; ?>">
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

                        if ($post == WH_ITEMS) {
                            // Получаем данные о полях из нескольких таблиц и объединяем их
                            foreach ($tables as $table) {
                                $tableColumns = array_merge($tableColumns, getTableColumns($table));
                            }
                        } elseif ($post == 'order_details') {
                            $tables = [ORDERS, PROJECTS];
                            // Получаем данные о полях из нескольких таблиц и объединяем их
                            foreach ($tables as $table) {
                                $tableColumns = array_merge($tableColumns, getTableColumns($table));
                            }
                        } else {
                            // Получаем данные о полях из одной таблицы
                            $tableColumns = getTableColumns($post);
                        }

                        // Извлечение имен столбцов
                        $columnNames = array_keys($tableColumns);
                        // Вывод имен столбцов
                        $tab = $post;
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
            <?php
            // if tables isn't exist or something wet wrong!
        } else {
            ?>
            <h2 class="p-2 text-warning">Maybe table <?= $tab_name ?> not exist yet or something went wrong!</h2>
            <?php
        }
    }

    function createPriorityColumnSelectionForm(): void
    {

        echo 'hi createPriorityColumnSelectionForm';
    }

    function createOrderDetailsSelectionForm(): void
    {
        echo 'hi createOrderDetailsSelectionForm';
    }

    ?>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm();
// Футер
PAGE_FOOTER($page); ?>
</body>
</html>