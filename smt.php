<?php

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
// RT0402FRE071KL, MC0402WGF1001TCE
// CPF0402B22K6E1
//$pn = 'RT0402FRE071KL';
//$in = '08394';
//$part_value = '1K 1% 1/16W';
//$owner_pn = 'NRES1';
////$r = R::findOne(STORAGE, 'manufacture_pn LIKE ? AND invoice LIKE ?', ['%'.$pn.'%', '%'.$in.'%']);
//$r = R::findOne(STORAGE, 'part_value = ? AND owner_pn = ? AND manufacture_pn LIKE ?', [
//    $part_value, $owner_pn, '%'.$pn.'%'
//]);
//
//var_dump($r->invoice);
//
//
//if (!empty($r->invoice)) {
//    $inv = explode(',', $r->invoice);
//    if (!empty($in) && !in_array($in, $inv))
//        $inv[] = $in;
//
//    $r->invoice = implode(',', $inv);
//} else {
//    $r->invoice = $in ?? '';
//}
//
//echo '<br>';
//var_dump($r->invoice);
//echo date('y/m/d');
//$st = R::findAll(WH_NOMENCLATURE);
//foreach ($st as $key => $value) {
//    if ($value['actual_qty'] != 0) {
//        $s = R::dispense(WAREHOUSE);
//        $s->items_id = $key; // Ссылка на товар.
//        $s->lot = 'N:' . date('m/Y') . ':TI~' . $key;
//        $s->invoice = 'base flooding';
//        $s->supplier = '{"name": "Mouser", "id":"1"}';   // name/id
//        $id = ($value['owner'] == 'NTI') ? 14 : 2; // 14 = nti, 2 = flying
//        $s->owner = '{"name":"' . $value['owner'] . '", "id":"' . $id . '"}';   // /name
//        $s->owner_pn = $value['owner_pn'];
//        $s->quantity = $value['actual_qty']; //  Количество товара в поставке.
//
//        if (strpos($value['part_name'], 'Resistor') !== false) {
//            $s->storage_box = 1;
//            $s->storage_shelf = 'SMT-1';
//        } else
//            if (strpos($value['part_name'], 'Capacitor') !== false) {
//                $s->storage_box = 2;
//                $s->storage_shelf = 'SMT-1';
//            } else
//                if (strpos($value['part_name'], 'Diod') !== false || strpos($value['part_name'], 'Micro Chip') !== false ||
//                    strpos($value['part_name'], 'Oscilator') !== false) {
//                    $s->storage_box = 3;
//                    $s->storage_shelf = 'SMT-2';
//                } else
//                    if (strpos($value['part_name'], 'Connector') !== false || strpos($value['part_name'], 'Pins') !== false) {
//                        $s->storage_box = 5;
//                        $s->storage_shelf = 'SMT-3';
//                    } else {
//                        $s->storage_box = 4;
//                        $s->storage_shelf = 'SMT-2';
//                    }
//
//
//        $s->manufacture_date = $value['manufacture_date'];
//        $s->expaire_date = $value['exp_date'];
//        $s->date_in = $value['date_in'];
//        //R::store($s);
//
//        $quantity = $value['actual_qty'];
//        $ten_percent = $quantity * 0.10;
//        $rounded_ten_percent = floor($ten_percent); // Или можно использовать intval($ten_percent)
//
//        //R::exec("UPDATE stock SET min_qty = ? WHERE id = ?", [$rounded_ten_percent, $key]);
//    }
//}

// заполнение динамической таблицы склада
//$st = R::findAll(WH_INVOICE);
//foreach ($st as $value) {
//
//    if ($value['quantity'] != 0) {
//        $s = R::dispense(WAREHOUSE);
//        $s->items_id = $value['items_id']; // Ссылка на товар.
//        // Получение значения поля manufacture_pn из таблицы whnomenclature, где id=12
//        $s->manufacture_pn = R::getCell('SELECT manufacture_pn FROM whnomenclature WHERE id = ?', [$value['items_id']]);
//        $s->owner = $value['owner'];
//        $s->owner_pn = $value['owner_pn'];
//        $s->quantity = $value['quantity'];
//        $s->storage_box = $value['storage_box'];
//        $s->storage_shelf = $value['storage_shelf'];
//        $s->date_in = $value['date_in'];
//
//        $sl_mo = 12;
//        // Создание объекта DateTime из строки
//        $datetime = new DateTime($value['manufacture_date']);
//        // Добавление месяцев к дате
//        $datetime->add(new DateInterval("P{$sl_mo}M"));
//
//        // Преобразование даты обратно в строку
//        $s->fifo = $datetime->format('Y-m-d H:i');
//        //R::store($s);
//        //R::exec("UPDATE stock SET min_qty = ? WHERE id = ?", [$rounded_ten_percent, $key]);
//    }
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
    'whitems' => ['manufacture_pn', 'part_name', 'part_value', 'part_type'],
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
       OR wn.part_type LIKE ?
       OR w.manufacture_pn LIKE ?
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

$settings = getUserSettings($_SESSION['userBean'], WH_ITEMS);
?>
<form action="">
    <input type="text" name="search">
    <button type="submit">su</button>
</form>

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
                $infoData = json_encode([
                    'partName' => $item['part_name'],
                    'partValue' => $item['part_value'],
                    'footprint' => $item['footprint'],
                    'part-type' => $item['part_type'],
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
                    if ((int)$item['actual_qty'] <= (int)$item['min_qty']) {
                        $color = 'danger';
                    } elseif ((int)$item['actual_qty'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
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
                        ?>
                    </tr>
                    <?php
                }
            }
        } else {
            if ($request != 'warehouse') {
                ?>
                <h4 class="py-3 px-3">Ooops seams this item not exist yet.</h4>
                <a href="/warehouse/the_item?newitem=<?= $searchString; ?>" role="button" class="m-3 p-3 btn btn-outline-secondary">
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
