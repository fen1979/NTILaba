<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'warehouse/WareHouse.php';
$page = 'po_replenishment';
$order = $project = $v = $consignment = $items = $track = null;

// попадаем сюда после создания заказа или заглушки Р.О.
if (isset($_GET['orid'])) {
    $order = R::load(ORDERS, _E($_GET['orid']));
    $project = R::load(PROJECTS, $order->projects_id);
    $v = _if(($order && $project), "Order ID: $order->id, Project: $project->projectname", '');
}

// save replenishment data
if (isset($_POST['save-list-items'])) {
    try {
        $consignment = WareHouse::createNewReplenishmentList($_POST, $user, $order, $project);
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage($e->getMessage(), 'danger', false);
    }
}

// get all items for order
if ($order || $consignment) {
    $items = R::findAll(PO_AIRRVAL, 'orders_id = ? OR consignment = ?', [$order->id, $consignment]);
}

// попадаем сюда со страницы traaking list
if (isset($_GET['tid'])) {
    $track = R::load(TRACK_DATA, _E($_GET['tid']));
}

// make XML and save in order folder
if (isset($_POST['make-xmls'])) {
    if (WareHouse::makeXLSXfileAndSave($order->id)) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('File was created successfully you can download file or print');
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
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .container-r {
            width: 95%;
            padding: 20px;
            margin: 20px auto;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            color: #ffcc00;
        }

        .form-group {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        label {
            display: inline-block;
            width: 200px;
        }

        input[type="text"], select {
            width: 45%;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 5px;
        }

        input[type="datetime-local"], input[type="number"] {
            width: 20%;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 5px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #555;
        }

        .table-container {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f9f9f9;
        }

        .btn-group {
            text-align: right;
        }

        button {
            padding: 10px 20px;
            font-size: 15px;
            margin-left: 10px;
            background-color: #ffcc00;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
        }

        a {
            padding: 10px 20px;
            font-size: 15px;
            margin-left: 10px;
            background-color: #0080ff;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
        }

        .fieldset {
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 3rem;
            position: relative;
        }

        .legend {
            font-weight: bold;
            padding: 0 5px;
            background-color: #fff;
            position: absolute;
            top: -10px;
            left: 10px;
            color: #555;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Preliminary check of arrival', 'user' => $user, 'page_name' => $page]); ?>

<div class="container-r">
    <div class="header">Incoming invoice</div>
    <!-- ФОРМА ВНЕСЕНИЯ ПРЕДМЕТА В ПОСЫЛКЕ -->
    <form action="" method="post">
        <input type="hidden" id="owner_id" name="owner_id" value="<?= set_value('owner_id') ?>">

        <div class="form-group">
            <label for="staging_id">Document type</label>
            <select id="staging_id" name="staging_id">
                <?php foreach (SR::getAllResourcesInGroup('staging', true) as $u) { ?>
                    <option value="<?= $u['key_name'] ?>">
                        <?= $u['value'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="owner">Owner/Customer</label>
            <input type="text" id="owner" name="owner" value="<?= set_value('owner') ?>" class="searchThis" data-request="owner"
                   placeholder="Owner/Customer" required>
        </div>

        <div class="form-group">
            <label for="consignment">Invoice Number</label>
            <?php $doc = !empty($track->asmahta) ? $track->asmahta : ''; ?>
            <input type="text" id="consignment" name="consignment" value="<?= set_value('consignment', $doc) ?>"
                   placeholder="Incoming invoice number" required>
        </div>

        <div class="form-group">
            <label for="for_whom">For what project/order</label>
            <input type="text" id="for_whom" name="for_whom" value="<?= set_value('for_whom', $v ?? ''); ?>"
                   placeholder="For what project/orde" required>
        </div>

        <div class="form-group">
            <label for="date_in">Incoming Date</label>
            <input type="datetime-local" id="date_in" name="date_in" value="<?= set_value('date_in', date('Y-m-d H:i')); ?>" required>
        </div>

        <div class="section-title">Arrived Items Information</div>

        <div class="form-group">
            <label for="makat">Makat</label>
            <input type="text" id="makat" name="makat" placeholder="Makat" required>
        </div>

        <div class="form-group">
            <label for="manufacture_pn">Manufacture P/N</label>
            <input type="text" id="manufacture_pn" name="manufacture_pn" placeholder="Manufacture P/N">
        </div>

        <div class="form-group">
            <label for="notes">Note/Description</label>
            <input type="text" id="notes" name="notes" placeholder="Note or Description">
        </div>

        <div class="form-group">
            <label for="declared_qty">Declared quantity</label>
            <input type="number" name="declared_qty" id="declared_qty" min="1" required>
        </div>

        <div class="form-group">
            <label for="actual_qty">Actual quantity</label>
            <input type="number" name="actual_qty" id="actual_qty" min="1" required>
        </div>

        <div class="section-title">Additional Informationt</div>

        <div class="form-group">
            <?php $t = 'Please indicate the type of packaging in which the parcel arrived.'; ?>
            <label for="package_type" data-title="<?= $t ?>"><i class="bi bi-info-circle text-primary"></i> Type of packaging</label>
            <input type="text" name="package_type" id="package_type" value="<?= set_value('package_type') ?>"
                   placeholder="Type of packaging">
        </div>

        <div class="form-group">
            <?php $t = 'Describe the actual storage location'; ?>
            <label for="storage_place" data-title="<?= $t ?>"><i class="bi bi-info-circle text-primary"></i> Storage location</label>
            <input type="text" name="storage_place" id="storage_place" value="<?= set_value('storage_place') ?>"
                   placeholder="Storage location">
        </div>

        <div class="form-group">
            <?php $t = 'Description of defects of received goods for preservation'; ?>
            <label for="defects" data-title="<?= $t ?>"><i class="bi bi-info-circle text-primary"></i> Description of defects</label>
            <input type="text" name="defects" id="defects" value="<?= set_value('defects') ?>"
                   placeholder="Description of defects">
        </div>

        <div class="fieldset">
            <div class="legend">Default warehouse configuration</div>
            <div class="form-group">
                <label for="warehouse_type">Warehouse</label>
                <select id="warehouse_type" name="warehouse_type">
                    <?php foreach (R::findAll(WH_TYPES) as $type): ?>
                        <option value="<?= $type['id'] ?>">
                            <?= $type['type_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="forward_to">Forward to </label>
                <select id="forward_to" name="forward_to">
                    <?php foreach (R::findAll(USERS) as $u) {
                        if ($u['id'] != 1) { ?>
                            <option value="<?= $u['id'] ?>">
                                <?= $u['user_name'] ?>
                            </option>
                        <?php }
                    } ?>
                </select>
            </div>
        </div>

        <div class="btn-group mt-2">
            <button type="button" id="print-invoice">Create invoice document &nbsp; <i class="bi bi-filetype-xlsx"></i></button>
            <button type="submit" name="save-list-items" class="success">Add Item</button>

            <?php
            if ($order) {
                $url = "storage/orders/$order->order_folder/staging_{$order->id}_.xlsx";
                $d = (is_file($url)) ? '' : 'hidden';
                ?>
                <a role="button" href="<?= $url; ?>" download class="text-white <?= $d; ?>" id="download_link">
                    File ready click for download <i class="bi bi-cloud-download-fill"></i>
                </a>

                <a role="button" href="/po-arrival-print?orid=<?= $order->id ?>" target="_blank" class="text-white">
                    Print Document<i class="bi bi-cloud-download-fill"></i>
                </a>
            <?php } ?>
        </div>
    </form>

    <!-- ТАБЛИЦА ВНЕСЕННЫХ ПРЕДМЕТОВ ИЗ ПОСЫЛКИ -->
    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Consignment</th>
                <th>For whom</th>
                <th>Date in</th>
                <th>Part number</th>
                <th>Manufacture P/N</th>
                <th>Notes</th>
                <th>Declared qty</th>
                <th>Aqtual qty</th>
                <th>Package</th>
                <th>Storage</th>
                <th>Warehouse</th>
                <th>Deffect</th>
                <th>User</th>
                <th>Difference</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($items) {
                $s = 1;
                foreach ($items as $item) {
                    list($difference, $qty) = WareHouse::getQtyDifference($item['declared_qty'], $item['actual_qty']);
                    ?>
                    <tr class="<?= $difference ?>">
                        <td><?= $s++; ?></td>
                        <td><?= $item['owner_name'] ?></td>
                        <td><?= $item['consignment'] ?></td>
                        <td><?= $item['for_whom'] ?></td>
                        <td><?= $item['date_in'] ?></td>
                        <td><?= $item['part_number'] ?></td>
                        <td><?= $item['manufacture_pn'] ?></td>
                        <td><?= $item['notes'] ?></td>
                        <td><?= $item['declared_qty'] ?></td>
                        <td><?= $item['actual_qty'] ?></td>
                        <td><?= $item['package_type'] ?></td>
                        <td><?= $item['storage_place'] ?></td>
                        <td><?= R::load(WH_TYPES, $item['warehouse_type'])->type_name; ?></td>
                        <td><?= $item['deffect'] ?></td>
                        <td><?= json_decode($item['user'], true)['name'] ?></td>
                        <td><?= $qty ?></td>
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// MODAL FOR SEARCH RESPONCE ANSWER
SearchResponceModalDialog($page, 'search-responce');

// FOOTER
PAGE_FOOTER($page);

// если мы пришли сюда после создания РО то открываем доп вкладку для печати данных о заказе
if (isset($_GET['orid']) && $order && !isset($_SESSION['pdf_printed'])) {
    $_SESSION['pdf_printed'] = 1;
    $url = "/order_pdf?pid=$order->projects_id&orid=$order->id";
    echo "<script>window.onload = function(){window.open('$url', '_blank');}; </script>";
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Обработка клика по результату поиска запчасти
        dom.in("click", "#search-responce tr.part", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                // Устанавливаем полученные значения в поля ввода
                dom.e("#item_id").value = info.item_id;
                dom.e("#part-name").value = info.part_name;
                dom.e("#part-value").value = info.part_value;
                dom.e("#mounting-type").value = info.mounting_type;
                dom.e("#manufacture-part-number").value = info.manufacture_part_number;
                dom.e("#manufacturer").value = info.manufacturer;
                dom.e("#footprint").value = info.footprint;
                dom.e("#minimun-quantity").value = info.minimal_quantity;
                dom.e("#description").value = info.description;
                dom.e("#notes").value = info.notes;
                dom.e("#datasheet").value = info.datasheet;
                dom.e("#shelf-life").value = info.shelf_life;
                dom.e("#storage-class").value = info.storage_class;
                dom.e("#storage-state").value = info.storage_state;
                dom.e("#owner").value = info.owner;
                if (info.owner_part_name && info.owner_part_name.trim() !== '') {
                    // Получаем список опций
                    const options = dom.e("#owner-pn-list");
                    // Оставляем только буквы для сравнения
                    const searchText = info.owner_part_name.replace(/[^a-zA-Z]/g, '');
                    // Проходим по всем опциям в списке
                    for (let i = 0; i < options.length; i++) {
                        // Оставляем только буквы для сравнения в значении опции
                        const optionValue = options[i].value;

                        // Проверяем, начинается ли значение опции с нужного текста
                        if (optionValue.startsWith(searchText)) {
                            // Устанавливаем опцию как выбранную и выходим из цикла
                            options[i].selected = true;
                            break;
                        }
                    }
                }
                dom.e("#quantity").value = info.quantity;
                dom.e("#storage-box").value = info.storage_box;
                dom.e("#storage-shelf").value = info.storage_shelf;

                // Очищаем результаты поиска
                dom.hide("#searchModal");
                createSearchLinks(info.MFpartName);
            }
        });

        // Обработка клика по результату поиска клиента
        dom.in("click", "#search-responce tr.customer", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                dom.e("#owner").value = info.name; // Устанавливаем имя клиента
                dom.e("#owner_id").value = info.clientID; // Устанавливаем ID клиента
                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        });

        // Обработка клика по результату поиска supplier/manufacturer
        dom.in("click", "#search-responce tr.supplier", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                if (info.is_request === 'supplier') {
                    dom.e("#supplier").value = info.supplier_name; // Устанавливаем имя поставщика
                    dom.e("#supplier-id").value = info.supplier_id; // Устанавливаем имя поставщика
                }
                if (info.is_request === 'manufacturer') {
                    dom.e("#manufacturer").value = info.supplier_name; // Устанавливаем имя производителя
                }
                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        });

        // Send request to create XML files
        dom.in("click", "#print-invoice", function () {
            // Create a new form element
            const form = document.createElement('form');

            // Set the form's action and method
            form.action = ''; // Замените на ваш URL для обработки запроса
            form.method = 'POST';

            // Create a hidden input field
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'make-xmls';
            input.value = '1'; // Значение, которое вы хотите отправить

            // Append the input field to the form
            form.appendChild(input);

            // Append the form to the body (необходимо для отправки)
            document.body.appendChild(form);

            // Submit the form
            form.submit();
        });


    });
</script>
</body>
</html>
