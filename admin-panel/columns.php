<?php
/**
 * Генерирует форму для настройки отображения столбцов выбранной таблицы.
 *
 * @param string $tableIdentifier Идентификатор таблицы (значение `sel_tab`).
 * @param string $tableName Отображаемое название таблицы.
 * @param mixed $user Объект текущего пользователя.
 * @return void
 */
function createTableSelectionForm(string $tableIdentifier, string $tableName, mixed $user): void
{
    // Проверяем существование таблицы или набора таблиц
    $relatedTables = getRelatedTables($tableIdentifier);
    if (!$relatedTables) {
        echo '<h2 class="p-2 text-warning">Не удалось определить связанные таблицы для ' . htmlspecialchars($tableName) . '.</h2>';
        return;
    }

    // Проверка существования всех связанных таблиц
    foreach ($relatedTables as $table) {
        $tableExists = R::getAll("SHOW TABLES LIKE ?", [$table]);
        if (count($tableExists) === 0) {
            echo '<h2 class="p-2 text-warning">Таблица ' . htmlspecialchars($table) . ' не существует или произошла ошибка!</h2>';
            return;
        }
    }

    // Получение пользовательских настроек
    $settings = getUserSettings($user, $tableIdentifier);

    // Получение всех столбцов из связанных таблиц
    // fixme: Получаем ассоциативный массив [columnName => tableName]
    $columnsMapping = getAllTableColumns($relatedTables);

    // Определение порядка столбцов на основе настроек пользователя
    if ($settings) {
        $configuredColumns = array_keys($settings);
        $additionalColumns = array_diff(array_keys($columnsMapping), $configuredColumns);
        $finalColumns = array_merge($configuredColumns, $additionalColumns);
    } else {
        // fixme: Здесь точно все присваивается
        $finalColumns = array_keys($columnsMapping);
    }

    // Генерация формы
    ?>
    <form action="" method="post">
        <div class="p-3">
            <table class="custom-table w-100">
                <thead class="bg-light">
                <tr class="text-left align-middle">
                    <th scope="col" class="p-2">
                        Table for configure: <b style="color: #dc3545;"><?= htmlspecialchars($tableName); ?></b>
                    </th>
                </tr>
                <tr class="align-middle">
                    <th scope="col" class="border-end text-left p-2">Enable</th>
                    <th scope="col" class="text-left p-2">Enable Filter</th>
                    <th scope="col" class="text-left p-2">Column Name</th>
                    <th style="float: right; margin-top: -1.5rem; padding-right: 1rem;">
                        <button type="submit" class="btn btn-success" name="save-settings" value="<?= $tableIdentifier; ?>">
                            Save Table Settings
                        </button>
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($finalColumns as $columnName) {
                    // Получаем имя таблицы для текущего столбца
                    if (!isset($columnsMapping[$columnName])) {
                        // Столбец не найден в маппинге, пропускаем
                        //_flashMessage("Столбец '$columnName' не найден в маппинге для таблицы '$tableIdentifier'.", 'danger');
                        continue;
                    }
                    $tableForColumn = $columnsMapping[$columnName];

                    // Получаем значение ресурса
                    $resourceValue = SR::getResourceValue($tableForColumn, $columnName);
                    if (empty($resourceValue)) {
                        // Логирование отсутствующего ресурса
                        //_flashMessage("Ресурс для столбца '$columnName' в таблице '$tableForColumn' пустой.", 'danger');
                        continue; // Пропустить столбцы без ресурса
                    }

                    // Определяем, включен ли столбец и фильтр
                    $isEnabled = isset($settings[$columnName]);
                    $isFilterEnabled = $isEnabled && ($settings[$columnName] === 'filter');
                    ?>
                    <tr class="text-left align-middle border-bottom">
                        <td class="fs-5 me-3 p-2">
                            <input class="form-check-input ms-2" type="checkbox" name="selected-columns[]"
                                   value="<?= $columnName; ?>" <?= $isEnabled ? 'checked' : ''; ?>>
                        </td>
                        <td class="fs-5 me-3 p-2">
                            <input type="checkbox" class="form-check-input" name="enable-filter[<?= $columnName; ?>]"
                                   value="filter" <?= $isFilterEnabled ? 'checked' : ''; ?>>
                        </td>
                        <td class="ms-2"><?= htmlspecialchars($resourceValue); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <!-- Скрытые поля для порядка строк -->
        <input type="hidden" name="rowOrder" id="rowOrder" value="">
    </form>
    <?php
}

/**
 * Возвращает массив связанных таблиц на основе идентификатора таблицы.
 * Добавьте новые идентификаторы и связанные таблицы здесь
 *
 * @param string $tableIdentifier Идентификатор таблицы.
 * @return array|null Массив связанных таблиц или null, если не определено.
 */
function getRelatedTables(string $tableIdentifier): ?array
{
    // Определение связанных таблиц на основе идентификатора
    $related = [
        WH_ITEMS => [WAREHOUSE, WH_ITEMS], // настройки вывода склада
        'order_details' => [ORDERS, PROJECTS], // настройки вывода на печать данных о заказе
        'priority' => [ORDERS], // настройки вывода данных в таблице приорити

        // Добавьте новые идентификаторы и связанные таблицы здесь
    ];

    return $related[$tableIdentifier] ?? [$tableIdentifier];
}

/**
 * Получает все столбцы из набора таблиц.
 *
 * @param array $tables Массив названий таблиц.
 * @return array Ассоциативный массив [columnName => tableName].
 */
function getAllTableColumns(array $tables): array
{
    $columns = [];
    foreach ($tables as $table) {
        $tableColumns = R::inspect($table);
        foreach ($tableColumns as $columnName => $columnInfo) {
            // Проверка на дублирование столбцов
            if (!isset($columns[$columnName])) {
                $columns[$columnName] = $table; // Связываем столбец с таблицей
            }
            //else {
            // Если столбец уже существует, можно добавить массив таблиц или оставить первое вхождение
            // Для простоты оставим первое вхождение
            // Альтернативно:
            // $columns[$columnName][] = $table;
            // }
        }
    }
    return $columns;
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
    <h3 class="p-3">Settings for displaying information in tables. <i class="bi bi-info-circle fs-5" data-title="<?= htmlspecialchars($t); ?>"></i></h3>
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

                <button type="button" name="sel_tab" value="order_details" class="dob btn btn-outline-primary ms-2">Order Details</button>
                <button type="button" name="sel_tab" value="priority" class="dob btn btn-outline-primary ms-2">Priority Out</button>
                <!--<button type="button" name="sel_tab" value="buttons" class="dob btn btn-outline-primary ms-2">Priority Out</button>-->
            </form>
        </div>
    </div>

    <?php
    // Проверка, является ли запрос POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Проверка наличия необходимых POST-параметров
        // Обработка выбора таблицы для конфигурации
        if (isset($_POST['sel_tab'])) {
            $selectedTab = _E($_POST['sel_tab']); // _E() — это функция экранирования
            $tabName = _E($_POST['table-name']); // Название таблицы для отображения
            createTableSelectionForm($selectedTab, $tabName, $user);
        }
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
