<?php
function_exists('EnsureUserIsAuthenticated') ? EnsureUserIsAuthenticated($_SESSION, 'userBean') : die('Forbidden 372.5');
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'import_csv';

// получаем из пост запроса поля с именами для внесения данных в БД
// имена полей идентичны странице добавления 1 товара на склад
$tableHead = [
    'manufacture-part-number',
    'owner',
    'quantity',
    'part-value',

    // not requaired values
    'part-name',
    'part-type',
    'manufacturer',
    'footprint',
    'minimun-quantity',
    'description',
    'notes',
    'datasheet',
    'shelf-life',
    'storage-class',
    'storage-state',
    'owner-part-name',
    //'owner-part-key',
    'storage-box',
    'storage-shelf',
    'manufactured-date',
    'part-lot',
    'delivery_note',
    'supplier'
];

// названия полей на странице для удобства чтения данных
// используется только на этой странице, не учитывается при добавлении в БД
$labels = [
    'part-name' => 'Part Name',
    'part-value' => 'Part Value',
    'part-type' => 'Part Type',
    'footprint' => 'Footprint',
    'manufacture-part-number' => 'Manufacture PN',
    'manufacturer' => 'Manufacturer',
    'owner-part-name' => 'Owner PN',
    //'owner-part-key' => 'Owner Part Key (optional)',
    'quantity' => 'Amount',
    'minimun-quantity' => 'Minimum Quantity',
    'storage_shelf' => 'Storage Shelf',
    'storage_box' => 'Storage Box (Only Digits)',
    'shelf-life' => 'Storage Life (Month)',
    'storage-state' => 'Storage State (On Shelf - default)',
    'storage-class' => 'Storage Class',
    'datasheet' => 'Link to datasheet',
    'description' => 'Description',
    'notes' => 'Notes',
    'owner' => 'Owner',
    'supplier' => 'Supplier',
    'delivery_note' => 'Delivery Note',
    'lot' => 'Lot',
    'manufacture-date' => 'Manufacture Date',
];

/* user upload the file after filling form */
if (isset($_POST['importCsvFile'])) {
    // i 'db_field_name1' => 'csv_column_name1',
    // конвертируем пришедшие данные в массив
    $fieldsMapping = [];
    foreach ($_POST as $key => $name) {
        $fieldsMapping[$key] = $name;
    }

    /* сохраняем файл с данными для работы */
    if (!empty($_FILES['file_csv']['name'][0])) {
        $tmp_name = $_FILES['file_csv']['tmp_name'];
        $uploadedFile = TEMP_FOLDER . basename($_FILES['file_csv']['name']);
        $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

        if ($fileType == 'csv') {
            // если файл соответствует требованиям сохраняем в ТМП папку
            $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
            if ($uploadSuccess) {

                if (($handle = fopen($uploadedFile, "r")) !== false) {
                    // Чтение заголовков файла
                    $headers = fgetcsv($handle, 1000);
                    $columnIndexes = [];

                    // Определение индексов нужных колонок
                    foreach ($fieldsMapping as $dbField => $csvColumnName) {
                        $index = array_search($csvColumnName, $headers);
                        if ($index !== FALSE) {
                            $columnIndexes[$dbField] = $index;
                        }
                    }

                    // Чтение и обработка каждой строки файла
                    $items = 0;
                    while (($data = fgetcsv($handle, 1000)) !== FALSE) {
                        $rowData = [];
                        // создаем массив для заполнения БД из строки файла
                        foreach ($columnIndexes as $dbField => $index) {
                            $rowData[$dbField] = $data[$index];
                        }

                        // смотрим если такой запчасть есть уже в БД
                        $result = WareHouse::CheckDuplicates($rowData);

                        // если нет то создаем новый
                        if ($result[0]) {
                            // записываем данные в БД
                            WareHouse::CreateNewWarehouseItem($rowData, $user);
                            $items++;
                        }

                        // если запчасть уже имеется но такой поставки не было то,
                        // заносим новые данные в таблицы
                        if ($result[0] === 'exist') {
                            $rowData['item_id'] = $result[1];
                            // переписываем данные в БД
                            try {
                                WareHouse::ReplenishInventory($rowData, $user);
                            } catch (\RedBeanPHP\RedException\SQL $e) {
                                // message collector (text/ color/ auto_hide = true)
                                _flashMessage('Error: ' . $e->getMessage(), 'danger');
                            }
                            $items++;
                        }
                        // если записи полностью совпадают с существующими в БД
                        // пропускаем запись и переходим к следующей
                        if (!$result[0]) continue;
                    }
                    // закрываем соединение
                    fclose($handle);
                }
            }

            // удаляем временный файл CSV
            array_map('unlink', glob(TEMP_FOLDER . '*.*'));
            // выводим сообщение пользователю
            if ($items > 0) {
                $args = ['info' => "Import for: $items items success", 'color' => 'success'];
            } else {
                $args = ['info' => 'No items added!', 'color' => 'warning'];
            }

        } else {
            $args = ['info' => 'Error, File format wrong! Only .csv', 'color' => 'danger'];
        }
    }
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
            .main-container {
                display: flex;
                flex-direction: column;
                min-height: 100%;
            }

            .content {
                flex: 1;
                padding: 0;
            }

            .info {
                background: #0dcaf073;
            }
        </style>
    </head>
    <body>
    <?php

    // title for plus button
    $t = 'Press the [+] button to add new item in storage, or CSV button to import file';
    // NAVIGATION BAR
    NavBarContent([
        'title' => 'Import an CSV file',
        'active_btn' => Y['STOCK'],
        'page_tab' => $_GET['page'] ?? null,
        'record_id' => $item->id ?? null,
        'user' => $user,
        'page_name' => $page]); ?>

    <div class="main-container">
        <main class="container-fluid content">
        <pre class="fs-5 p-2 m-2 info text-danger rounded">
            Please enter the names of the fields you want to import into the database.
            Attention! The field names must exactly match the names in your file, CASE SANSITIVE!!!
            All fields left blank will be automatically filled with a zero value or the minimum value if these to be numbers!
            All fields after importing the file will be available for editing on the 'Warehouse' page.</pre>

            <form action="/import-csv" method="POST" class="container mt-4 mb-4" autocomplete="on" enctype="multipart/form-data">
                <?php foreach ($tableHead as $field) {
                    // массив полей которые обязательны к заполнению
                    $arr = ['part-value', 'manufacture-part-number', 'owner', 'quantity'];
                    $required = (in_array($field, $arr)) ? 'required' : '';
                    ?>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-2">
                                <label for="<?= $field; ?>" class="form-label me-2">
                                    <?php echo $t = ($labels[$field] ?? ucfirst(str_replace('_', ' ', $field))); ?>:
                                </label>
                            </div>
                            <div class="col-10">
                                <input type="text" class="form-control" id="<?= $field; ?>" name="<?= $field; ?>" <?= $required; ?>
                                       placeholder="<?= $t; ?>">
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-5">
                            <label for="file_csv" class="form-label me-2 text-danger">
                                Attention!!! Choose only '.csv' files! A different format may cause the data
                                import to fail and you will have to fill out the form again!
                            </label>
                        </div>
                        <div class="col-7">
                            <input type="file" class="form-control" id="file_csv" name="file_csv" required>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn" class="btn btn-primary form-control" name="importCsvFile" disabled>Import</button>
            </form>
        </main>
    </div>
    <?php
    // SCRIPTS and Футер
    PAGE_FOOTER($page); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            dom.in("change", "#file_csv", function () {
                dom.e("#btn").disabled = false;
            });
        });
    </script>
    </body>
    </html>
<?php
//i  backup
//                            $goods = WareHouse::findItemInWareHouseByManufacturePN($rowData['manufacture_pn'], $rowData['owner_pn'] ?? '');
//                            if (!$goods) {
//                                // если нет то создаем новый
//                                $goods = R::dispense(STORAGE);
//                            }
//                            // записываем или переписываем данные в БД
//                            // Добавление $rowData в базу данных
//                            $goods->part_name = $rowData['part_name'] ?? $pn;
//                            $goods->part_value = $rowData['part_value'];
//                            $goods->mounting_type = $rowData['mounting_type'] ?? '';
//                            $goods->footprint = $rowData['footprint'] ?? '0000';
//                            $goods->manufacturer = $rowData['manufacturer'] ?? 'Unknown';
//                            $goods->manufacture_pn = $rowData['manufacture_pn'];
//                            $goods->owner_pn = $rowData['owner_pn'] ?? '';
//                            $goods->actual_qty = $rowData['actual_qty'] ?? 0;
//                            $goods->min_qty = $minQTY;
//                            $goods->storage_shelf = $rowData['storage_shelf'] ?? 'N/A';
//                            $goods->storage_box = $rowData['storage_box'] ?? 1;
//                            $goods->class_number = $rowData['class_number'] ?? 1;
//                            $goods->datasheet = $rowData['datasheet'] ?? '';
//                            $goods->extra = $rowData['extra'] ?? '';
//                            $goods->owner = $rowData['owner'];
//                            $goods->consignment = $rowData['consignment'] ?? '';
//                            $goods->manufacture_date = $rowData['manufacture_date'] ?? date('Y-m-d H:i');
//                            $goods->exp_date = $rowData['exp_date'] ?? date('Y-m-d H:i');
//                            $goods->date_in = $rowData['date_in'] ?? date('Y-m-d H:i');
//
//                            // saving data to DB
//                            //R::store($goods);
//                            $items++;
/*
'item_id', // 'part_name',
    'imageData', // 'part_value',
    'item-image', // 'mounting_type',
    'owner-id', // 'footprint',
    'supplier-id', // 'manufacture_pn',
    'part-name', // 'manufacturer',
    'part-value', // 'owner_pn',
    'part-type', // 'quantity', // actual_qty - was
    'manufacture-part-number', // 'min_qty',
    'manufacturer', // 'storage_shelf',
    'footprint', // 'storage_box',
    'minimun-quantity', // 'shelf_life',
    'description', // 'storage_state',
    'notes', // 'class_number',
    'datasheet', // 'datasheet',
    'shelf-life', // 'description',
    'storage-class', // 'notes',
    'storage-state', // 'owner',
    'owner', // 'supplier',
    'owner-part-name', // 'consignment',
    'quantity', // 'lot',
    'storage-box', // 'manufacture_date',
    'storage-shelf', // 'date_in'
    */

// бекап из сохранения новой запчасти

//                                $goods = R::dispense(WH_ITEMS);
//                                // fixme только для наших запчастей (костыль решить что с этим делать)
//                                $goods->part_name = $rowData['part_name'] ?? getNtiCustomNumberForItem($ourPN, $rowData); // optional
//                                $goods->part_value = $rowData['part_value']; // required
//                                $goods->mounting_type = $rowData['mounting_type'] ?? ''; // optional
//                                $goods->footprint = $rowData['footprint'] ?? '0000'; // optional
//                                $goods->manufacturer = $rowData['manufacturer'] ?? 'Unknown'; // optional
//                                $goods->manufacture_pn = $rowData['manufacture_pn']; // required
//                                $goods->owner_pn = $rowData['owner_pn'] ?? ''; // optional
//                                $goods->actual_qty = $rowData['actual_qty'] ?? 0; // required
//                                $goods->min_qty = getMinimalQtyForThisItem($rowData); // optional
//                                $goods->storage_shelf = $rowData['storage_shelf'] ?? 'N/A';
//                                $goods->storage_box = $rowData['storage_box'] ?? 1;
//                                $goods->class_number = $rowData['class_number'] ?? 1;
//                                $goods->datasheet = $rowData['datasheet'] ?? '';
//                                $goods->description = $rowData['description'] ?? '';
//                                $goods->notes = $rowData['notes'] ?? '';
//                                $goods->owner = $rowData['owner']; // required
//                                $goods->consignment = $rowData['consignment'] ?? ''; // required/optional
//                                //$goods->lots = getActualLotFromDB(); // хз как это сделать пока что но надо привязать этот параметр к инвойсам
//                                $goods->manufacture_date = $rowData['manufacture_date'] ?? date('Y-m-d H:i');
//                                $goods->exp_date = $rowData['exp_date'] ?? date('Y-m-d H:i');
//                                $goods->date_in = $rowData['date_in'] ?? date('Y-m-d H:i');
// saving data to DB
// R::store($goods);


// бекап если запчасть имеется в БД


//                                $goods = R::load(WH_ITEMS, $result[1]);
//                                $goods->actual_qty = $goods->actual_qty + (float)$rowData['actual_qty'] ?? 0.0;
//                                if (!empty($goods->consignment)) {
//                                    $in = explode(',', $goods->consignment);
//                                    if (!empty($rowData['consignment']) && !in_array($rowData['consignment'], $in))
//                                        $in[] = $rowData['consignment'];
//
//                                    $goods->consignment = implode(',', $in);
//                                } else {
//                                    $goods->consignment = $rowData['consignment'] ?? '';
//                                }
// saving line in DB
// R::store($goods);