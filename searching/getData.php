<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
// подключение страницы вывода данных
require_once 'result-view.php';

// поиск совпадений имен проектов в БД
if (isset($_POST['unit_name']) && isset($_POST['revision']) && isset($_POST['verification'])) {
    $res = json_encode(['exists' => false, 'unit_id' => '0']);
    $unit_name = _E($_POST['unit_name']);
    $unit_vers = _E($_POST['revision']);
    $unit = R::findOne(PROJECTS, 'projectname LIKE ?', [$unit_name]);

    if ($unit && $unit['revision'] == $unit_vers) {
        $res = json_encode(['exists' => true, 'unit_id' => $unit['id']]);
    }
    exit($res);
}

// условие для поиска данных по запросу из поля поиска в нав баре
// так же обработка запроса поиска по БД из полей форм на страницах
if (isset($_POST['suggest']) && isset($_POST['request'])) {
    $request = _E($_POST['request']);
    $mySearchString = _E($_POST['suggest']);

    switch ($request) {

        case 'owner':
        case 'customer':
            {
                // поиск по клиенту
                /* search for order creation page */
                $col = ['name', 'contact', 'information', 'priority'];
                viewCustomer(dynamicSearch(CLIENTS, $col, $mySearchString), $col, $mySearchString);
            }
            break;

        case 'supplier':
        case 'manufacturer':
            {
                // поиск по поставщикам - производителям
                viewSupplier(dynamicSearch(SUPPLIERS, ['name'], $mySearchString), $request, $mySearchString);
            }
            break;

        case 'priority':
            {
                // поиск в таблице клиенты по полю приорити
                /* search for order creation page */
                $colForSearch = ['priority'];
                $col = ['name', 'contact', 'information', 'priority'];
                viewCustomer(dynamicSearch(CLIENTS, $colForSearch, $mySearchString), $col, $mySearchString);
            }
            break;

        case 'project':
            {
                /* search project, for order creation page */
                $col = ['projectname', 'customername', 'revision'];
                viewLineOfUnit(dynamicSearch(PROJECTS, $col, $mySearchString), $col, $mySearchString);
            }
            break;

        case 'project_nav':
            {
                /* search for project view page */
                $col = ['projectname', 'customername', 'date_in'];
                viewFullUnit(dynamicSearch(PROJECTS, $col, $mySearchString), $_SESSION['userBean']);

                if (!mb_strlen($mySearchString)) {
                    viewFullUnit(R::findAll(PROJECTS, 'ORDER BY date_in ASC'), $col);
                }
            }
            break;

        case 'order_nav':
            {
                /* search for orders view page */
                $col = ['id', 'project_name', 'customer_name', 'purchase_order', 'date_in'];
                viewOrder(dynamicSearch(ORDERS, $col, $mySearchString), $_SESSION['userBean']);
            }
            break;
        case 'order_id_search':
            {
                /* search for orders view page */
                $col = ['id'];
                viewOrder(dynamicSearch(ORDERS, $col, $mySearchString), $_SESSION['userBean']);
            }
            break;

        case 'project_bom':
            {
                /* search for project BOM filling  */
                viewPartsForUnitBOM(SearchWarehouseItems($mySearchString, WH_ITEMS, WAREHOUSE));
            }
            break;

        case 'wh_nav':
        case 'warehouse':
            {
                /* search for warehouse creation, updation, view page */
                viewStorageItems(SearchWarehouseItems($mySearchString, WH_ITEMS, WAREHOUSE), $mySearchString, $request, $_SESSION['userBean']);
            }
            break;

        case 'logs_nav':
            {
                /* search for logs view page */
                $col = ['date', 'user', 'action', 'object_type'];
                viewLogs(dynamicSearch(LOGS, $col, $mySearchString));
            }
            break;

        case 'resources_nav':
            {
                /* search for logs view page */
                $col = ['group_name', 'key_name', 'value', 'detail'];
                viewResources(dynamicSearch('resources', $col, $mySearchString));
            }
            break;

        case 'wh_log_nav':
            {
                /* search for logs view page */
                $col = ['items_id', 'user_name', 'action', 'date_in'];
                viewWarehouseLogs(dynamicSearch(WH_LOGS, $col, $mySearchString));
            }
            break;

        case 'get-images':
        case 'tools-images':
            {
                list($table, $column) = $request == 'get-images' ? [WH_ITEMS, 'item_image'] : [TOOLS, 'image'];
                try {
                    $itemImages = getAllImagesSavedInDB($table, $column);
                    echo itemImagesForChoose($itemImages);
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage();
                }
            }
            break;

        case 'tools':
            {
                // поиск по таблице инструменты
                $col = ['manufacturer_name', 'device_model', 'device_type'];
                viewToolsTable(dynamicSearch(TOOLS, $col, $mySearchString));
            }
            break;

        case 'tools-click':
            {
                // вывод таблицы инструмента на странице создания проекта
                viewToolsTable(R::findAll(TOOLS));
            }
            break;
        default:
            echo '<h3 data-info="text from search engine">No results were found for your search query.</h3>';
            break;
    }
    exit();
}

// function for any search in DB
function dynamicSearch($tableName, $columns, $searchString)
{
    // Проверяем, что имя таблицы и столбцы допустимы
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        throw new InvalidArgumentException("Недопустимое имя таблицы");
    }

    foreach ($columns as $column) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new InvalidArgumentException("Недопустимое имя столбца");
        }
    }

    // Строим часть запроса WHERE
    $whereParts = array_map(function ($column) use ($searchString) {
        return "$column LIKE :search";
    }, $columns);

    $whereClause = implode(' OR ', $whereParts);

    // Выполняем запрос
    $sql = "SELECT * FROM $tableName WHERE $whereClause";
    return R::getAll($sql, [':search' => '%' . $searchString . '%']);
}

// function for search in warehouse only!!!
function SearchWarehouseItems($searchTerm, $table_one, $table_two)
{
    // SQL-запрос для поиска в двух таблицах и объединения результатов
    // Используем * для выбора всех полей из обеих таблиц
    $query = "
    SELECT wn.*, w.*, wt.type_name
    FROM $table_one wn
    LEFT JOIN $table_two w ON wn.id = w.items_id
    LEFT JOIN whtypes wt ON wt.id = w.wh_types_id
    WHERE wn.part_name LIKE ?
       OR wn.part_value LIKE ?
       OR wn.mounting_type LIKE ?
       OR wn.manufacture_pn LIKE ?
       OR w.owner LIKE ?
       OR w.owner_pn LIKE ?";
//    ORDER BY STR_TO_DATE(w.fifo, '%Y-%m-%d %H:%i') ASC"; // если нужна сортировка по fifo

    $q = '%' . $searchTerm . '%';
    $params = [$q, $q, $q, $q, $q, $q];
    // Возвращение результатов в виде массива
    return R::getAll($query, $params);
}

/**
 * Получает уникальные непустые значения из указанного поля таблицы.
 *
 * @param string $tableName Имя таблицы.
 * @param string $fieldName Имя поля, содержащего пути к файлам.
 * @return array Уникальные непустые значения поля.
 */
function getAllImagesSavedInDB(string $tableName, string $fieldName): array
{
    // Проверка корректности имен таблицы и поля
    if (empty($tableName) || empty($fieldName)) {
        throw new InvalidArgumentException("Table name and field name must be provided.");
    }
    // Выполнение запроса и получение результатов
    return R::getCol("SELECT DISTINCT $fieldName FROM $tableName WHERE $fieldName IS NOT NULL AND $fieldName !=''");
}

exit();