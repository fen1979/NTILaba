<?php
//R::setup('mysql:host=localhost;dbname=nti_production', 'root', '8CwG24YwZG');

//phpinfo();
//echo 'session.gc_maxlifetime: ' . ini_get('session.gc_maxlifetime') . '<br>';
//echo 'session.cookie_lifetime: ' . ini_get('session.cookie_lifetime') . '<br>';
//echo 'session.gc_probability: ' . ini_get('session.gc_probability') . '<br>';
//echo 'session.gc_divisor: ' . ini_get('session.gc_divisor') . '<br>';
//exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="https://nti.icu">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Layout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
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
            /*top: 6.6%;*/
            top: 0;
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
<?php
include 'core/Routing.php';
// разборка таблицы которая была в самом начале на 3 подтаблицы
//$st = R::findAll('whnomenclature');
//foreach ($st as $value) {
//    $date_in = $value['manufacture_date'];
////    создаем и заполняем номенклатуру
//    $it = R::dispense(WH_ITEMS);
//    $it->part_name = $value['part_name'];
//    $it->part_value = $value['part_value'];
//    $it->mounting_type = $value['mounting_type'];
//    $it->footprint = $value['footprint'];
//    $it->manufacturer = !empty($value['manufacturer']) ? $value['manufacturer'] : 'Not Added Yet';
//    $it->manufacture_pn = $value['manufacture_pn'];
//
//    $quantity = $value['actual_qty'];
//    $ten_percent = $quantity * 0.10;
//    $it->min_qty = floor($ten_percent);
//    $sl_mo = $it->shelf_life = $value['shelf_life'] ?? 12;
//    $it->class_number = $value['class_number'];
//    $it->datasheet = !empty($value['datasheet']) ? $value['datasheet'] : 'Not Added Yet';
//    $it->description = !empty($value['description']) ? $value['description'] : 'Not Added Yet';
//    $it->notes = !empty($value['notes']) ? $value['notes'] : 'Not Added Yet';
//    $it->date_in = $date_in;
//    $it->item_image = $value['item_image'] ?? null;
//    $iid = R::store($it);
//
////    создаем и заполняем склад
//    $s = R::dispense(WAREHOUSE);
//    $s->items_id = $iid;
//    $id = ($value['owner'] == 'NTI') ? 14 : 2; // 14 = nti, 2 = flying
//    $owner_data = '{"name":"' . $value['owner'] . '", "id":"' . $id . '"}';
//    $s->owner = $owner_data;
//    $s->owner_pn = $value['owner_pn'];
//    $s->quantity = $value['actual_qty'];
//    if (strpos($value['part_name'], 'Resistor') !== false) {
//        $s->storage_box = 1;
//        $s->storage_shelf = 'SMT-1';
//    } else
//        if (strpos($value['part_name'], 'Capacitor') !== false) {
//            $s->storage_box = 2;
//            $s->storage_shelf = 'SMT-1';
//        } else
//            if (strpos($value['part_name'], 'Diod') !== false || strpos($value['part_name'], 'Micro Chip') !== false ||
//                strpos($value['part_name'], 'Oscilator') !== false) {
//                $s->storage_box = 3;
//                $s->storage_shelf = 'SMT-2';
//            } else
//                if (strpos($value['part_name'], 'Connector') !== false || strpos($value['part_name'], 'Pins') !== false) {
//                    $s->storage_box = 5;
//                    $s->storage_shelf = 'SMT-3';
//                } else {
//                    $s->storage_box = 4;
//                    $s->storage_shelf = 'SMT-2';
//                }
//
//    $s->storage_state = 'On Shelf';
//    $mf_date = $s->manufacture_date = $value['manufacture_date'];
//    $datetime = new DateTime($mf_date);
//    $datetime->add(new DateInterval("P{$sl_mo}M"));
//    $s->fifo = $datetime->format('Y-m-d H:i');
//    $s->date_in = $date_in;
//    $wid = R::store($s);
//
//
//    //    создаем и заполняем инвойс
//    $in = R::dispense(WH_INVOICE);
//    $in->items_id = $iid;
//    $in->quantity = $value['actual_qty'];
//    $in->warehouses_id = $wid;
//    $in->lot = 'N:' . date('m/Y') . ':TI~' . $iid;
//    $in->invoice = 'base flooding';
//    $in->supplier = '{"name":"Mouser Electronics", "id":"2"}';
//    $in->owner = $owner_data;
//    $in->date_in = $date_in;
//    R::store($in);
//}


function searchInventory($searchTerm, $tables, $mainTable, $fieldsTable, $sort)
{
    // Создание части SQL-запроса для JOIN и WHERE условий
    $joinClauses = [];
    $whereClauses = [];
    $params = [];
    $selectFields = [];

    // Основные поля для выборки
    $selectFields[] = "$mainTable.*";

    foreach ($tables as $table => $fields) {
        if ($table !== $mainTable) {
            $joinClauses[] = "LEFT JOIN $table ON $table.items_id = $mainTable.id";
        }

        // Создание WHERE условий для каждого поля в таблице
        foreach ($fields as $field) {
            $whereClauses[] = "$table.$field LIKE ?";
            $params[] = '%' . $searchTerm . '%';
        }

        // Добавление полей для выборки
        if (isset($fieldsTable[$table])) {
            foreach ($fieldsTable[$table] as $field) {
                $selectFields[] = "$table.$field";
            }
        }
    }

    // Построение полного SQL-запроса
    $query = "
        SELECT " . implode(', ', $selectFields) . "
        FROM $mainTable
        " . implode(' ', $joinClauses) . "
        WHERE " . implode(' OR ', $whereClauses) . "
        ORDER BY {$sort['field']} {$sort['direction']}
    ";

    // Возвращение результатов в виде массива
    return R::getAll($query, $params);
}

// Пример использования функции поиска
$tables = [
    'whitems' => ['manufacture_pn', 'part_name', 'part_value', 'mounting_type'],
    'warehouse' => ['owner', 'owner_pn']
];
$mainTable = 'whitems';
$fieldsTable = [
    'warehouse' => ['owner', 'owner_pn', 'quantity', 'storage_box', 'storage_shelf']
];
$sort = ['field' => 'warehouse.fifo', 'direction' => 'ASC'];


function SearchWarehouseItems($searchTerm)
{
    // SQL-запрос для поиска в двух таблицах и объединения результатов
    $query = "
    SELECT wn.*, w.owner, w.owner_pn, w.quantity, w.storage_box, w.storage_shelf
    FROM whitems wn
    LEFT JOIN warehouse w ON wn.id = w.items_id
    WHERE wn.part_name LIKE ? 
       OR wn.part_value LIKE ?
       OR wn.mounting_type LIKE ?
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

// Пример использования функции поиска
if (isset($_GET['search'])) {
    //$result = SearchWarehouseItems($_GET['search']);
    $result = searchInventory($_GET['search'], $tables, $mainTable, $fieldsTable, $sort);
}

if (isset($_GET['owner-part-key'])) {
    GetNtiPartNumberForItem($_GET['owner-part-key']);
}

$settings = getUserSettings($_SESSION['userBean'], WH_ITEMS);
// поиск и вывод последнего парт номера клиента тест функции
function GetNtiPartNumberForItem($key)
{
// Составление SQL-запроса
    $sql = "SELECT MAX(CAST(SUBSTRING(owner_pn, LENGTH(:key) + 1) AS UNSIGNED)) AS max_number FROM warehouse WHERE owner_pn LIKE :keyPattern";

// Выполнение запроса и получение результата
    $maxNumber = R::getCell($sql, [':key' => $key, ':keyPattern' => $key . '%']);

// Вывод результата
    if (!empty($maxNumber))
        print_r(["key" => $key, "maxNumber" => $maxNumber]);
}

function checkDuplicates($data): array
{
    // Поля из формы для поиска
    $part_value = $data['part_value'] ?? null;
    $manufacture_pn = $data['manufacture_pn'] ?? null;
    $owner_pn = $data['owner_pn'] ?? null;
    $invoice = $data['invoice'] ?? null;

    // Имена таблиц для запроса
    $wh_item = WH_ITEMS; // поля для поиска: part_value, manufacture_pn
    $wh_invoice = WH_INVOICE; // поля для поиска: invoice, owner
    $warehouse = WAREHOUSE; // поля для поиска: owner_pn, owner

    // Создаем шаблоны для поиска с учетом любых разделителей и местоположения
    $search_part_value = '%' . $part_value . '%';
    $search_manufacture_pn = '%' . $manufacture_pn . '%';
    $search_owner_pn = '%' . $owner_pn . '%';
    $search_invoice = '%' . $invoice . '%';

    // SQL-запрос для поиска полного совпадения
    $sqlFullMatch = "SELECT w.* FROM $warehouse w JOIN $wh_item wi ON wi.id = w.items_id
        JOIN $wh_invoice win ON win.id = w.invoice_id WHERE
            wi.part_value LIKE ? AND wi.manufacture_pn LIKE ? AND
            w.owner_pn LIKE ? AND win.invoice LIKE ?";

    // Выполнение запроса и получение результата
    $fullMatch = R::getRow($sqlFullMatch, [$search_part_value, $search_manufacture_pn, $search_owner_pn, $search_invoice]);

    // Если найдено полное совпадение
    if ($fullMatch) {
        return [false];
    }

    // SQL-запрос для поиска частичного совпадения (без учета invoice)
    $sqlPartialMatch = "SELECT w.* FROM $warehouse w JOIN $wh_item wi ON wi.id = w.items_id
        WHERE wi.part_value LIKE ? AND wi.manufacture_pn LIKE ? AND w.owner_pn LIKE ?";

    // Выполнение запроса и получение результата
    $partialMatch = R::getRow($sqlPartialMatch, [$search_part_value, $search_manufacture_pn, $search_owner_pn]);

    // Если найдено частичное совпадение
    if ($partialMatch) {
        return ['exist', $partialMatch['id']];
    }

    // Если ничего не найдено в БД
    return [true];
}

function dropAutoincrementInWarehouse()
{
    // Найдите максимальное значение id
    $max_id = R::getCell('SELECT MAX(id) FROM whitems');
    // Установите новое автоинкрементное значение
    $new_auto_increment = $max_id + 1;
    R::exec('ALTER TABLE whitems AUTO_INCREMENT = ?', [$new_auto_increment]);

    // Найдите максимальное значение id
    $max_id = R::getCell('SELECT MAX(id) FROM warehouse');
    // Установите новое автоинкрементное значение
    $new_auto_increment = $max_id + 1;
    R::exec('ALTER TABLE warehouse AUTO_INCREMENT = ?', [$new_auto_increment]);

    // Найдите максимальное значение id
    $max_id = R::getCell('SELECT MAX(id) FROM whinvoice');
    // Установите новое автоинкрементное значение
    $new_auto_increment = $max_id + 1;
    R::exec('ALTER TABLE whinvoice AUTO_INCREMENT = ?', [$new_auto_increment]);
}

//dropAutoincrementInWarehouse();

//

// заполенение БОМа проекта подготовка метода для переноса на страницу БОМ проекта
// после адаптции условие для внесения новой запчасти будет отсутствие ID запчасти при отправке данных со страницы
// так как при выборе существующей ЗЧ имеется ID то при его отсутствии это будет новая ЗЧ
$pbo = R::findAll(PROJECT_BOM);
foreach ($pbo as $b) {
    //$pb = R::dispense('prbom');
    //$pb->item_id = ; // номер в каталоге запчастей
    //$pb->wh_first_id = $b->; // номер на складе самый первый пришедший не равный 0
    //$pb->project_id = $b->projects_id; // номер проекта
    //$pb->owner_id = $b->customerid; // номер клиента
    //$pb->required_qty = $b->amount; // требуемое кол-во
    //$pb->item_in_work = $b->item_in_work; //
    //$pb->designator = !empty($b->notes) ? $b->notes: null; // наименование запчастей на плате для установки (SMT only)
    //$pb->
    //$pb->date_in = $b->date_in; //
echo $b->item_in_work;
//R::store($pb);

}

?>
<form action="">
    <input type="text" name="search">
    <br>
    <?php $t = 'Name of the spare part in the NTI company. 
                It is important to choose the appropriate name for the correct numbering of the incoming product/spare part. 
                If this number is not available or if the spare part/product belongs to another customer, select the "OTHERS" option'; ?>
    <select name="owner-part-key" id="owner-part-key" class="input" data-title="<?= $t ?>" required>
        <?php
        foreach (NTI_PN as $val => $name): ?>
            <option value="<?= $val ?>"><?= $name ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <button type="submit">su</button>
</form>
<br><br><br>
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
        <?php
        if ($result) {

            foreach ($result as $item) {
                $d = checkDuplicates($item);
                if ($d[0]) {
                    $infoData = json_encode([
                        'partName' => $item['part_name'],
                        'partValue' => $item['part_value'],
                        'footprint' => $item['footprint'],
                        'mounting-type' => $item['mounting_type'],
                        'MFpartName' => $item['manufacture_pn'],
                        'manufacturer' => $item['manufacturer'],
                        'ownerPartName' => $item['owner_pn'],
                        'amount' => $item['actual_qty'],
                        'minQTY' => $item['min_qty'],
                        'storShelf' => $item['storage_shelf'],
                        'storBox' => $item['storage_box'],
                        'storState' => $item['storage_state'],
                        'storageClass' => $item['class_number'],
                        'datasheet' => $item['datasheet'],
                        'description' => $item['description'],
                        'notes' => $item['notes'],
                        'manufacturedDate' => $item['manufacture_date'],
                        'shelfLife' => $item['shelf_life'],
                        'invoice' => $item['invoice'],
                        'lot' => $item['lots'],
                        'owner' => $item['owner']
                    ]);
                    if ($request == 'warehouse') {
                        // вывод результатов поиска на страницу просмотра элемента
                        ?>
                        <p class="part border-bottom p-2" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
                            Part Name: <span class="text-info me-2"><?= $item['part_name']; ?></span>
                            Manufacture P/N: <span class="text-info me-2"><?= $item['manufacture_pn']; ?></span>
                            Value: <span class="text-info me-2"><?= $item['part_value']; ?></span>
                            Owner: <span class="text-info me-2"><?= $item['owner']; ?></span>
                            Owner P/N: <span class="text-info me-2"><?= $item['owner_pn']; ?></span>
                            Exp date: <span class="text-info me-2"><?= $item['exp_date']; ?></span>
                            Amount: <span class="text-info me-2"><?= $item['actual_qty']; ?></span>
                            Storage State: <span class="text-info me-2"><?= $item['storage_state']; ?></span>
                        </p>
                        <?php
                    } else {
                        // вывод результата поиска на страницу просмотра всей БД
                        $color = '';
                        if ((int)$item['quantity'] <= (int)$item['min_qty']) {
                            $color = 'danger';
                        } elseif ((int)$item['quantity'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                            $color = 'warning';
                        }
                        ?>
                        <!-- это полный набор переменных выводимых из БД -->
                        <tr class="<?= $color; ?>" data-id="<?= $item['id']; ?>" id="row-<?= $item['id']; ?>">
                            <td><?= $item['id']; ?></td>
                            <?php
                            // выводим таблицу согласно настройкам пользователя
                            foreach ($settings as $set) {
                                if ($set == 'item_image') {
                                    ?>
                                    <td>
                                        <?php $img_href = ($item['mounting_type'] == 'SMT') ? '/public/images/smt.webp' : '/public/images/pna_en.webp' ?>
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
                            ?>
                        </tr>
                        <?php
                    }
                }
            }
        } else {
            if ($request != 'warehouse') {
                ?>
                <h4 class="py-3 px-3">Ooops seams this item not exist yet.</h4>
                <a href="/wh/the_item?newitem=<?= $searchString; ?>" role="button" class="m-3 p-3 btn btn-outline-secondary">
                    Do you want to create this Item?
                </a>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="libs/ajeco-re-max.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

    });

</script>
</body>
</html>
