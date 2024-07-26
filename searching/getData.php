<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
// подключение страницы вывода данных
require_once 'result-view.php';

// поиск совпадений имен проектов в БД
if (isset($_POST['project_name']) && isset($_POST['verification'])) {
    $res = json_encode(['exists' => false]);
    $projectName = _E($_POST['project_name']);
    $project = R::find(PROJECTS, 'projectname LIKE ?', [$projectName]);

    if ($project) {
        $res = json_encode(['exists' => true]);
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
                viewCustomer(dynamicSearch(CLIENTS, $col, $mySearchString), $col);
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
                viewCustomer(dynamicSearch(CLIENTS, $colForSearch, $mySearchString), $col);
            }
            break;

        case 'project':
            {
                /* search project, for order creation page */
                $col = ['projectname', 'customername', 'revision'];
                viewLineProject(dynamicSearch(PROJECTS, $col, $mySearchString), $col);
            }
            break;

        case 'project_nav':
            {
                /* search for project view page */
                $col = ['projectname', 'customername', 'date_in'];
                viewFullProject(dynamicSearch(PROJECTS, $col, $mySearchString), $_SESSION['userBean']);

                if (!mb_strlen($mySearchString)) {
                    viewFullProject(R::findAll(PROJECTS, 'ORDER BY date_in ASC'), $col);
                }
            }
            break;

        case 'order_nav':
            {
                /* search for orders view page */
                $col = ['id', 'project_name', 'customer_name', 'date_in'];
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
                viewPartsForProjectBOM(SearchWarehouseItems($mySearchString, WH_ITEMS, WAREHOUSE));
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

        case 'wh_log_nav':
            {
                /* search for logs view page */
                $col = ['items_id', 'user_name', 'action', 'date_in'];
                viewWarehouseLogs(dynamicSearch(WH_LOGS, $col, $mySearchString));
            }
            break;

        case 'get-images':
            {
                /* вывод всех существующих изображений записанных в БД в товарах */
                $itemImages = R::getCol('SELECT item_image FROM ' . WH_ITEMS . ' WHERE item_image IS NOT NULL AND item_image != ""');
                echo itemImagesForChoose($itemImages);
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
    $query = "
    SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf, w.fifo, wt.type_name
    FROM whitems wn
    LEFT JOIN warehouse w ON wn.id = w.items_id
    LEFT JOIN whtypes wt ON wt.id = w.wh_types_id
    WHERE wn.part_name LIKE ?
       OR wn.part_value LIKE ?
       OR wn.mounting_type LIKE ?
       OR wn.manufacture_pn LIKE ?
       OR w.owner LIKE ?
       OR w.owner_pn LIKE ?
    ORDER BY w.fifo ASC";

    $q = '%' . $searchTerm . '%';
    $params = [$q, $q, $q, $q, $q, $q];
    // Возвращение результатов в виде массива
    return R::getAll($query, $params);
}

exit();