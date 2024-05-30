<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();
require 'WareHouse.php';
/* получение пользователя из сессии */
$thisUser = $_SESSION['userBean'];
$page = 'import_csv';
/* NTI P/N */
$ourPN = ['NCAP' => 'Capacitor', 'NRES' => 'Resistor', 'NDIO' => 'Diode', 'NIC' => 'Micro Chip', 'NTR' => 'Transistor',
    'NCR' => 'Oscilator', 'NFU' => 'Fuse', 'NFB' => 'Ferrite bead', 'NCON' => 'Connector', 'NIND' => 'Inductor', 'NPIN' => 'Pins',
    'NW' => 'Wires', 'NTUBE' => 'Shrink Tube', 'NON' => 'Other'];
/* warehouse table column names */
$tableHead = ['part_name', 'part_value', 'part_type', 'footprint', 'manufacture_pn',
    'manufacturer', 'owner_pn', 'actual_qty', 'min_qty',
    'storage_shelf', 'storage_box', 'class_number', 'datasheet', 'owner', 'invoice',
    'description', 'notes', 'manufacture_date', 'exp_date', 'date_in'];
/* page labels */
$labels = [
    'part_name' => 'Part Name',
    'part_value' => 'Part Value',
    'part_type' => 'Part Type',
    'footprint' => 'Footprint',
    'manufacture_pn' => 'Manufacture PN',
    'manufacturer' => 'Manufacturer',
    'owner_pn' => 'Owner PN',
    'actual_qty' => 'Amount',
    'min_qty' => 'Min Qty',
    'storage_shelf' => 'Storage Shelf',
    'storage_box' => 'Storage Box (Only Digits)',
    'class_number' => 'Class Number',
    'datasheet' => 'Link to datasheet',
    'owner' => 'Owner',
    'invoice' => 'Invoice',
    'description' => 'Description',
    'notes' => 'Notes',
    'manufacture_date' => 'Manufacture Date',
    'exp_date' => 'Exp Date',
    'date_in' => 'Date In'
];

/* user upload the file after filling form */
if (isset($_POST['importCsvFile'])) {
    // i 'db_field_name1' => 'csv_column_name1',
    // converting post to assoc array
    $fieldsMapping = [];
    foreach ($_POST as $key => $name) {
        $fieldsMapping[$key] = $name;
    }

    /* uploading the file */
    if (!empty($_FILES['file_csv']['name'][0])) {
        $tmp_name = $_FILES['file_csv']['tmp_name'];
        $uploadedFile = TEMP_FOLDER . basename($_FILES['file_csv']['name']);
        $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

        if ($fileType == 'csv') {
            // save file to temp dir
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

                        //i  изменить проверку так как она не всегда адекватна для импорта!!!
                        if (!empty($rowData['part_value']) && !empty($rowData['manufacture_pn']) && !empty($rowData['owner'])) {

                            // смотрим если такой запчасть есть уже в БД
                            $result = WareHouse::checkDuplicates($rowData);

                            // если нет то создаем новый
                            if ($result[0]) {
                                // записываем данные в БД
                                $goods = R::dispense(WH_NOMENCLATURE);
                                // fixme только для наших запчастей (костыль решить что с этим делать)
                                $goods->part_name = $rowData['part_name'] ?? getNtiCustomNumberForItem($ourPN, $rowData); // optional
                                $goods->part_value = $rowData['part_value']; // required
                                $goods->part_type = $rowData['part_type'] ?? ''; // optional
                                $goods->footprint = $rowData['footprint'] ?? '0000'; // optional
                                $goods->manufacturer = $rowData['manufacturer'] ?? 'Unknown'; // optional
                                $goods->manufacture_pn = $rowData['manufacture_pn']; // required
                                $goods->owner_pn = $rowData['owner_pn'] ?? ''; // optional
                                $goods->actual_qty = $rowData['actual_qty'] ?? 0; // required
                                $goods->min_qty = getMinimalQtyForThisItem($rowData); // optional
                                $goods->storage_shelf = $rowData['storage_shelf'] ?? 'N/A';
                                $goods->storage_box = $rowData['storage_box'] ?? 1;
                                $goods->class_number = $rowData['class_number'] ?? 1;
                                $goods->datasheet = $rowData['datasheet'] ?? '';
                                $goods->description = $rowData['description'] ?? '';
                                $goods->notes = $rowData['notes'] ?? '';
                                $goods->owner = $rowData['owner']; // required
                                $goods->invoice = $rowData['invoice'] ?? ''; // required/optional
                                //$goods->lots = getActualLotFromDB(); // хз как это сделать пока что но надо привязать этот параметр к инвойсам
                                $goods->manufacture_date = $rowData['manufacture_date'] ?? date('Y-m-d H:i');
                                $goods->exp_date = $rowData['exp_date'] ?? date('Y-m-d H:i');
                                $goods->date_in = $rowData['date_in'] ?? date('Y-m-d H:i');

                                // saving data to DB
                               // R::store($goods);
                                $items++;
                            }

                            // if exist but invoice not exist (like new arrival)
                            if ($result[0] === 'exist') {
                                // переписываем данные в БД
                                $goods = R::load(WH_NOMENCLATURE, $result[1]);
                                $goods->actual_qty = $goods->actual_qty + (float)$rowData['actual_qty'] ?? 0.0;
                                if (!empty($goods->invoice)) {
                                    $in = explode(',', $goods->invoice);
                                    if (!empty($rowData['invoice']) && !in_array($rowData['invoice'], $in))
                                        $in[] = $rowData['invoice'];

                                    $goods->invoice = implode(',', $in);
                                } else {
                                    $goods->invoice = $rowData['invoice'] ?? '';
                                }

                                // saving line in DB
                                // R::store($goods);
                                $items++;
                            }

                            // if fully identical skip this iteration
                            if (!$result[0]) continue;

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
//                            $goods->part_type = $rowData['part_type'] ?? '';
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
//                            $goods->invoice = $rowData['invoice'] ?? '';
//                            $goods->manufacture_date = $rowData['manufacture_date'] ?? date('Y-m-d H:i');
//                            $goods->exp_date = $rowData['exp_date'] ?? date('Y-m-d H:i');
//                            $goods->date_in = $rowData['date_in'] ?? date('Y-m-d H:i');
//
//                            // saving data to DB
//                            //R::store($goods);
//                            $items++;
                        } else {
                            $colName = empty($rowData['part_value']) ? 'Part Value' : (empty($rowData['manufacture_pn']) ? 'Manufacturer P/N' : 'Owner');
                            $args = ['info' => "The $colName is empty!", 'color' => 'danger'];
                        }
                    }
                    // закрываем соединение
                    fclose($handle);
                }
            }

            // удаляем временный файл CSV
            array_map('unlink', glob(TEMP_FOLDER . '*.*'));
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

function getNtiCustomNumberForItem($ourPN, $rowData)
{
    // fixme Добавляем part name  если оно не указано и наш парт номер присутствует
    // fixme только для наших запчастей (костыль решить что с этим делать)
    foreach ($ourPN as $key => $val) {
        $pos = stripos($rowData['owner_pn'] ?? '', $key);
        if ($pos !== false) {
            return $val;
        }
    }
    return 'NONE';
}

function getMinimalQtyForThisItem($rowData): float
{
    // высчитываем минимальное значение от общего количества
    return (int)(!empty($rowData['actual_qty']) && $rowData['actual_qty'] != 0 && empty($rowData['min_qty'])) ?
        ((int)$rowData['actual_qty'] / 3) : 0;
}
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
<!-- NAVIGATION BAR -->
<?php
$title = ['title' => 'Import an CSV file', 'app_role' => $thisUser['app_role']];
NavBarContent($page, $title, null, Y['STOCK']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
$t = 'Press the [+] button to add new item in storage, or CSV button to import file';
?>

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
                $arr = ['part_value', 'manufacture_pn', 'owner'];
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
        <?php
        // Футер
        footer($page);
        ?>
    </main>
</div>
<?php
/* SCRIPTS */
ScriptContent($page);
?>
<script>
    $(document).ready(function () {
        $(document).on("change", "#file_csv", function () {
            $("#btn").removeAttr("disabled");
        });
    });
</script>
</body>
</html>
