<?php
// подключение Базы Данных МаринаДБ
require_once "../core/rb-mysql.php";

// database name = !!!-> nti_production <-!!!
R::setup('mysql:host=localhost;dbname=nti_production', 'root', '8CwG24YwZG');
// R::freeze( true ); /* тут выключение режима заморозки */
if (!R::testConnection()) {
    exit ('No database connection');
}
session_start();

require_once '../core/Resources.php';
require_once '../core/Utility.php';
require_once 'result-view.php';

// search if project name exist in DB
if (isset($_POST['project_name']) && isset($_POST['verification'])) {
    $res = json_encode(['exists' => false]);
    $projectName = _E($_POST['project_name']);
    $project = R::find(PROJECTS, 'projectname LIKE ?', [$projectName]);

    if ($project) {
        $res = json_encode(['exists' => true]);
    }
    //echo $res;
    exit($res);
}


if (isset($_POST['suggest']) && isset($_POST['request'])) {
    $request = _E($_POST['request']);
    $mySearchString = _E($_POST['suggest']);

    switch ($request) {
        case 'owner':
        case 'customer':
            {
                /* search for order creation page */
                $col = ['name', 'contact', 'information', 'priority'];
                viewCustomer(dynamicSearch(CLIENTS, $col, $mySearchString), $col);
            }
            break;

        case 'supplier':
        case 'manufacturer':
            {
                /* search for suppliers and manufactirers */
                viewSupplier(dynamicSearch(SUPPLIERS, ['name'], $mySearchString), $request);
            }
            break;

        case 'priority':
            {
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

        case 'warehouse_nav':
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

        default:
            echo 'No Result by search';
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
    SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf, wt.type_name
    FROM $table_one wn
    LEFT JOIN whtypes wt ON wt.id = wn.warehouses_id
    LEFT JOIN $table_two w ON wn.id = w.items_id
    WHERE wn.part_name LIKE ?
       OR wn.part_value LIKE ?
       OR wn.part_type LIKE ?
       OR wn.manufacture_pn LIKE ?
       OR w.owner LIKE ?
       OR w.owner_pn LIKE ?
    ORDER BY w.fifo ASC
";
    $q = '%' . $searchTerm . '%';
    $params = [$q, $q, $q, $q, $q, $q];
    // Возвращение результатов в виде массива
    return R::getAll($query, $params);
}

exit();