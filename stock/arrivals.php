<?php
isset($_SESSION['userBean']) && $_SESSION['userBean']['app_role'] == ROLE_ADMIN or header("Location: /warehouse") and exit();
require 'WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'view_item';
$item = null;
$hideSaveButton = false;
$pageMode = 'Part information';

$item = R::load(WH_NOMENCLATURE, 1);
$lots = R::findAll(WAREHOUSE, 'items_id = ?', [1]);
$logs = R::findAll(WAREHOUSE_LOGS, 'items_id = ?', [1]);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>

    <style>
        .add-img-style {
            width: auto;
            max-width: 100%;
        }

        #searchAnswerGoods p:hover {
            cursor: pointer;
            background: #023786;
            color: white;
            border-radius: 4px;
        }

        /* СТИЛИ ДЛЯ ВЫВОДА ПРОЕКТОВ В ТАБЛИЦЕ */
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: normal;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 6.5%;
        }

        th:last-child, td:last-child {
            text-align: right;
            padding-right: 1rem;
        }

        th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }

        /* Модальное окно */
        #blocked-w.modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        #blocked-w .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        #blocked-w .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        #blocked-w .close:hover,
        #blocked-w .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<!-- NAVIGATION BAR -->
<?php
$title = ['title' => $pageMode, 'app_role' => $user['app_role']];
NavBarContent($page, $title, $item->id ?? null, Y['STOCK']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid my-3 border-top">



    <!-- Форма для ввода данных -->
    <div class="row mt-2">
        <!-- ITEM FILL FORM CONTAINER -->
        <div class="col-6 border-end">
            <form action="" method="post" enctype="multipart/form-data" autocomplete="off">


                <div class="mb-3">
                    <label for="part-name" class="form-label"><i class="bi bi-search"></i> Part Name <b class="text-danger">*</b></label>
                    <input type="text" class="searchThis form-control" id="part-name" name="partName" data-request="warehouse"
                           value="<?= set_value('partName', $item['part_name'] ?? ''); ?>" required
                           placeholder="Resistor">
                </div>

                <div class="mb-3">
                    <label for="part-type" class="form-label">
                        <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount, 
                        SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.
                         No Case sensitive!!!'; ?>
                        <i class="bi bi-info-circle text-primary" data-title="<?= $t; ?>"></i> Part Type <b class="text-danger">*</b>
                    </label>
                    <input type="text" class="form-control" id="part-type" name="part-type" required
                           value="<?= set_value('part-type', $item['part_type'] ?? ''); ?>" placeholder="SMT, TH, CM, PM...">
                </div>

                <div class="mb-3">
                    <label for="part-value" class="form-label"><i class="bi bi-search"></i> Part Value <b class="text-danger">*</b></label>
                    <input type="text" class="searchThis form-control" id="part-value" name="partValue" data-request="warehouse"
                           value="<?= set_value('partValue', $item['part_value'] ?? ''); ?>" required
                           placeholder="10M, 16W, 1%">
                </div>

                <div class="mb-3">
                    <label for="part-owner" class="form-label"><i class="bi bi-search"></i> Part Owner <b class="text-danger">*</b></label>
                    <input type="text" class="searchThis form-control" id="part-owner" placeholder="Part owner (REQUIRED)"
                           name="owner" value="<?= set_value('owner', $item['owner'] ?? ''); ?>" data-request="owner" required>
                </div>

                <div class="mb-3">
                    <label for="owner-pn" class="form-label"><i class="bi bi-search"></i> Owner P/N</label>
                    <?php $opn = (!empty($item['owner_pn']) ? $item['owner_pn'] : '') ?>
                    <input type="text" class="searchThis form-control" id="owner-pn" name="ownerPartName" data-request="warehouse"
                           value="<?= set_value('ownerPartName', $opn); ?>" placeholder="Owner P/N (OPTIONAL)">
                </div>

                <div class="mb-3">
                    <label for="supplier" class="form-label">Supplier</label>
                    <?php $supplier = (!empty($item['supplier']) ? $item['supplier'] : (!empty($item['owner']) ? $item['owner'] : '')) ?>
                    <input type="text" class="form-control" id="supplier" name="supplier"
                           value="<?= set_value('supplier', $supplier); ?>" placeholder="Supplier (OPTIONAL)">
                </div>

                <div class="mb-3">
                    <label for="storage-shelf" class="form-label">Storage Shelf <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="storage-shelf" placeholder="Storage shelf Required (any)"
                           name="storShelf" value="<?= set_value('storShelf', $item['storage_shelf'] ?? ''); ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="storage-box" class="form-label">Storage Box <b class="text-danger">*</b></label>
                    <input type="number" class="form-control" id="storage-box" placeholder="Storage box"
                           name="storBox" min="1" required
                           value="<?= set_value('storBox', $item['storage_box'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="storage-class" class="form-label">Storage Class <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="storage-class" placeholder="Storage class (1,2,3)"
                           name="partClassNumber" value="<?= set_value('partClassNumber', $item['class_number'] ?? '1'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="storage-state" class="form-label">Storage State</label>
                    <input type="text" class="form-control" id="storage-state" placeholder="Storage State (On Shelf)"
                           name="storageState" value="<?= set_value('storageState', $item['storage_state'] ?? 'On Shelf'); ?>">
                </div>

                <div class="mb-3">
                    <label for="actual-amount" class="form-label">Item quantity <b class="text-danger">*</b></label>
                    <input type="number" class="form-control" id="actual-amount" placeholder="Item quantity"
                           name="amount" value="<?= $item['actual_qty'] ?? '0'; ?>" step="any" required>
                </div>

                <div class="mb-3">
                    <label for="minimum-amount" class="form-label">Minimum quantity <b class="text-danger">*</b></label>
                    <input type="number" class="form-control" id="minimum-amount" placeholder="Minimum quantity" step="any"
                           name="minQTY" value="<?= set_value('minQTY', $item['min_qty'] ?? '1'); ?>" required>
                </div>


                <!-- manufacturer & mf part number-->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <?php $new = !empty($_GET['newitem']) ? _E($_GET['newitem']) : ''; ?>
                        <label for="manufacturer-part-number" class="form-label"><i class="bi bi-search"></i> Manufacture P/N <b class="text-danger">*</b></label>
                        <input type="text" class="searchThis form-control" id="manufacturer-part-number" name="MFpartName" data-request="warehouse"
                               value="<?= set_value('MFpartName', $item['manufacture_pn'] ?? $new); ?>" required
                               placeholder="Manufacturer part number">
                    </div>

                    <div class="col-auto" style="padding-top: 2em">
                        <?php
                        if (!empty($item['manufacture_pn']) || $new != '') {
                            // выводим парт номера/номер если есть в поле откуда его подхватит
                            // скрипт открывающий все парт номера одновременно если он не один
                            echo '<span class="hidden" id="item-part-number">' . ($item['manufacture_pn'] ?? $new) . '</span>';
                            ?>
                            <button id="search-item-goog" class="btn btn-outline-warning">
                                <i class="bi bi-google" data-title="Search Item on Google"></i>
                            </button>
                            <button id="search-item-octo" class="btn btn-outline-info">
                                <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                            </button>
                        <?php } else { ?>
                            <a role="button" id="search-item-goog" href="" class="btn btn-outline-warning" target="_blank">
                                <i class="bi bi-google" data-title="Search Item on Google"></i>
                            </a>
                            <a role="button" id="search-item-octo" href="" class="btn btn-outline-info" target="_blank">
                                <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                            </a>
                        <?php } ?>
                    </div>

                    <div class="col">
                        <label for="manufacturer" class="form-label">Manufacturer</label>
                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="Manufacturer"
                               value="<?= set_value('manufacturer', $item['manufacturer'] ?? '') ?>">
                    </div>
                </div>


                <!-- datasheet link & buttons take picture and search for item-->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <label for="datasheet-link" class="form-label">Datasheet Link</label>
                        <input type="text" class="form-control" id="datasheet-link" placeholder="Datasheet link"
                               name="datasheet" value="<?= set_value('datasheet', $item['datasheet'] ?? ''); ?>">
                    </div>
                    <div class="col-auto" style="padding-top: 2rem">
                        <button type="button" id="item-image-btn" class="btn btn-outline-primary">Take Item Picture</button>
                        <input type="file" id="item-image-file" name="item-image" class="hidden">
                    </div>
                </div>
                <!-- description -->
                <div class="row mt-3">
                    <div class="col">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control p-3" id="description" placeholder="Description"
                                  name="description"><?= set_value('description', $item['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="col">
                        <label for="notes" class="form-label">Additional information</label>
                        <textarea class="form-control p-3" id="notes" placeholder="Notes"
                                  name="notes"><?= set_value('notes', $item['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- OTHER ITEM INFORMATION -->
                <h3 class="mt-4 text-center border-bottom">Other information</h3>
                <div class="row mt-3 align-items-center">
                    <!-- footprint -->
                    <div class="col-md-2">
                        <label for="footprint" class="form-label">Footprint</label>
                        <input type="text" class="form-control" id="footprint" placeholder="0402"
                               name="footprint" value="<?= set_value('footprint', $item['footprint'] ?? ''); ?>">
                    </div>
                    <!-- manufacture date -->
                    <div class="col-md-2">
                        <label for="date-in" class="form-label">Manufactured date <b class="text-danger">*</b></label>
                        <input type="datetime-local" class="form-control" id="date-in" placeholder="Manufactured Date" name="manufacturedDate"
                               value="<?= set_value('manufacturedDate', $item['manufacture_date'] ?? date('Y-m-d H:i')); ?>" required>
                    </div>
                    <!--i expaire date fixme переименовать поле в бд и при добавлении в посте-->
                    <div class="col-md-2">
                        <label for="exp-date" class="form-label">Shelf Life<b class="text-danger">*</b></label>
                        <input type="number" class="form-control" id="exp-date" placeholder="Shelf life in month"
                               name="expDate" value="<?= set_value('expDate', $item['exp_date'] ?? 12); ?>" required>
                    </div>
                    <!-- invoice numbers -->
                    <div class="col-md-3">
                        <label for="invoice-number" class="form-label">Invoice Number <b class="text-danger">*</b></label>
                        <input type="text" class="form-control" id="invoice-number" placeholder="Invoice number"
                               name="invoice" value="<?= $item['invoice'] ?? ''; ?>" required>
                    </div>
                    <!-- part lots -->
                    <div class="col-md-3">
                        <label for="part-lot" class="form-label">Part Lot</label>
                        <input type="text" class="form-control" name="partLot" id="part-lot" placeholder="Part lot (OPTIONAL)" value="<?= $item['lots'] ?? ''; ?>">
                    </div>
                </div>
                <?php if (!$hideSaveButton) { ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-danger delete-button" data-itemid="<?= $item->id; ?>">
                            Delete Item <i class="bi bi-trash3" data-itemid="<?= $item->id; ?>"></i>
                        </button>

                        <button type="submit" id="part-to-save-btn" class="btn btn-success" name="save-item" value="<?= $item->id ?? 'new'; ?>">
                            Update Item Information
                        </button>
                    </div>
                <?php } ?>

                <input type="hidden" name="imageData" id="imageData">
            </form>
        </div>

        <!--i IMAGE CONTAINER-->
        <div class="col-4 border-start">
            <div id="pasteArea" contenteditable="true" class="mb-4 border-bottom">
                <!-- part image -->
                <img class="rounded add-img-style" id="item-image-preview" alt="Item image"
                     src="<?= !empty($item['item_image']) ? "/{$item['item_image']}" : '/public/images/goods.jpg' ?>">
            </div>

            <?php if (!isset($_GET['new-item']) && !isset($_GET['newitem'])) { ?>
                <div class="btn-outline-danger p-2 rounded">
                    <!-- ITEM UPDATE FORM CONTAINER -->
                    <h4 class="">FOR NEW ARRIVALS ONLY</h4>
                    <form action="/smt" method="post" id="arrived-form">
                        <input type="hidden" name="item-id" value="<?= $item->id; ?>">
                        <div class="mb-2">
                            <label for="new-supplier" class="form-label">Supplier</label>
                            <input type="text" id="new-supplier" name="new-supplier" value="" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label for="new-lot" class="form-label">Manufacturer Lot</label>
                            <input type="text" id="new-lot" name="new-lot" value="" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label for="new-invoice" class="form-label">Invoice <b class="text-danger">*</b></label>
                            <input type="text" id="new-invoice" name="new-invoice" value="" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="new-amount" class="form-label">Quantity <b class="text-danger">*</b></label>
                            <input type="text" id="new-amount" name="new-amount" value="" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="new-mf_date" class="form-label">Manufacture Date</label>
                            <input type="datetime-local" id="new-mf_date" name="new-mf_date" placeholder="Manufactured Date" value="<?= date('Y-m-d H:i'); ?>" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <button type="button" class="btn btn-success form-control" name="updating-quantity">Update Item Quantity</button>
                        </div>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ITEM INVOICES AND LOG CONTAINER -->
    <h3 class="mt-4 text-center border-bottom">Invoices information</h3>

    <div class="container-fluid mt-2">
        <table class="p-3">
            <!-- header -->
            <thead>
            <tr>
                <th>Lot ID</th>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>Owner</th>
                <th>Owner P/N</th>
                <th>QTY</th>
                <th>Shelf</th>
                <th>Box</th>
                <th>Mnf. Date</th>
                <th>Use for</th>
                <th>Date In</th>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            // сделать переход при клике на строку в просмотр запчасти но с данными только по этому инвойсу
            if (!empty($lots)) {
                foreach ($lots as $line) {
                    $owner = json_decode($line['owner']);
                    $sup = json_decode($line['supplier']);
                    ?>
                    <tr class="item-list">
                        <td><?= $line['lot']; ?></td>
                        <td><?= $line['invoice']; ?></td>
                        <td><?= $sup->name; ?></td>
                        <td><?= $owner->name; ?></td>
                        <td><?= $line['owner_pn']; ?></td>
                        <td><?= $line['quantity']; ?></td>
                        <td><?= $line['storage_shelf']; ?></td>
                        <td><?= $line['storage_box']; ?></td>
                        <td><?= $line['manufacture_date']; ?></td>
                        <td><?= $line['expaire_date']; ?></td>
                        <td><?= $line['date_in']; ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
    </div>

    <h3 class="mt-4 text-center border-bottom">Item Movements information</h3>

    <div class="container-fluid mt-2">

        <table class="p-3">
            <!-- header -->
            <thead>
            <tr>
                <th>Item Id</th>
                <th>Lot ID</th>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>QTY</th>
                <th>Action</th>
                <th>Moved From</th>
                <th>Moved To</th>
                <th>User</th>
                <th>Date In</th>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            // сделать переход при клике на строку в просмотр запчасти но с данными только по этому инвойсу
            if (!empty($logs)) {
                foreach ($logs as $line) {
                    echo '<tr>';
                    echo '<td>' . $line['items_id'] . '</td>';
                    echo '<td>' . $line['lot'] . '</td>';
                    echo '<td>' . $line['invoice'] . '</td>';
                    echo '<td>' . $line['supplier'] . '</td>';
                    echo '<td>' . $line['quantity'] . '</td>';
                    echo '<td>' . $line['action'] . '</td>';
                    echo '<td>' . $line['from'] . '</td>';
                    echo '<td>' . $line['to'] . '</td>';
                    echo '<td>' . $line['user'] . '</td>';
                    echo '<td>' . $line['date_in'] . '</td>';
                    echo '</tr>';
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>


<!-- The Search result Modal -->
<div class="modal" id="searchModal">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Search Result</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body" id="searchAnswerGoods"></div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно #blocked-w -->
<div id="blocked-w" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p>For this site to function correctly, please allow multiple tabs to be opened in your browser.
            The first time you click the button, a browser warning will appear and you will need to select Allow.
            <br>
            If there was no notification or you were redirected to an open page!
            Look at the icons in the address bar on the right in the corner there should be a crossed out screen,
            click on it and check the "Allow" option.</p>
    </div>
</div>

<!--  модальное окно форма для удаления одного шага в проекте  -->
<div class="modal" id="deleteModal" style="backdrop-filter: blur(15px); ">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Заголовок модального окна -->
            <div class="modal-header">
                <h5 class="modal-title">Delete Item № <span id="itemId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <h5 class="text-danger">Warning! This is irreversable operation!!!</h5>
                <form action="/warehouse" method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autofocus autocomplete="new-password">
                        <input type="hidden" class="form-control" id="itemID" name="itemID" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary" name="deleteItem">Delete Item</button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
/* SCRIPTS */
ScriptContent('view_item');
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // скрипт удаления записи в БД
        dom.in("click", ".delete-button", function () {
            let itemId = this.getAttribute("data-itemid");
            dom.e("#itemID").value = itemId;
            dom.e("#itemId").textContent = itemId;

            /* Откройте модальное окно */
            dom.show("#deleteModal", "modal");
            dom.e("#password").focus();
        });

        /* код вставки изображений скопированных на сайтах */
        document.getElementById('pasteArea').addEventListener('paste', function (e) {
            e.preventDefault();
            let items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let index in items) {
                let item = items[index];
                if (item.kind === 'file') {
                    let blob = item.getAsFile();
                    let reader = new FileReader();
                    reader.onload = function (event) {
                        // Кодируем изображение в base64 и вставляем в скрытое поле
                        document.getElementById('imageData').value = event.target.result;
                    };
                    reader.readAsDataURL(blob);
                    // Создаем URL для Blob и Выводим изображение
                    document.getElementById('item-image-preview').src = URL.createObjectURL(blob);
                }
            }
        });

        // search modal constanta for use
        const searchModal = new bootstrap.Modal('#searchModal', {
            keyboard: true
        });

        // creation links for searching elements
        dom.in("change", "#manufacturer-part-number", function () {
            createSearchLinks(this.value);
        });

        // кнопки выбора фото пользователя и Обработчик обновления превью
        dom.doClick("#item-image-btn", "#item-image-file");
        dom.doPreviewFile("item-image-file", "item-image-preview");

        // Обработка клика по результату поиска запчасти
        $(document).on("click", "#searchAnswerGoods p.part", function () {
            // Извлекаем и парсим данные из атрибута data-info
            let info = JSON.parse($(this).attr('data-info'));

            // Устанавливаем полученные значения в поля ввода
            $("#part-name").val(info.partName); // Устанавливаем
            $("#part-value").val(info.partValue); // Устанавливаем
            $("#footprint").val(info.footprint); // Устанавливаем
            $("#manufacturer-part-number").val(info.MFpartName); // Устанавливаем
            $("#manufacturer").val(info.manufacturer); // Устанавливаем
            $("#owner-pn").val(info.ownerPartName); // Устанавливаем
            $("#actual-amount").val(info.amount); // Устанавливаем
            $("#minimum-amount").val(info.minQTY); // Устанавливаем
            $("#storage-shelf").val(info.storShelf); // Устанавливаем
            $("#storage-box").val(info.storBox); // Устанавливаем
            $("#storage-class").val(info.partClassNumber); // Устанавливаем
            $("#storage-state").val(info.storState); // Устанавливаем
            $("#datasheet-link").val(info.datasheet); // Устанавливаем
            $("#description").val(info.extra); // Устанавливаем
            $("#date-in").val(info.manufacturedDate); // Устанавливаем
            $("#exp-date").val(info.expDate); // Устанавливаем
            $("#invoice-number").val(info.invoice); // Устанавливаем
            $("#part-owner").val(info.owner); // Устанавливаем

            // Очищаем результаты поиска
            searchModal.hide();
            createSearchLinks(info.MFpartName);
        });

        // Обработка клика по результату поиска клиента
        $(document).on("click", "#searchAnswerGoods p.customer", function () {
            // Извлекаем и парсим данные из атрибута data-info
            let info = JSON.parse($(this).attr('data-info'));
            // Устанавливаем полученные значения в поля ввода
            $("#part-owner").val(info.name); // Устанавливаем имя клиента
            // Очищаем результаты поиска
            searchModal.hide();
        });

        // fixme Main Search filed search engine request/response
        $(document).on("keyup", ".searchThis", function () {
            let search = $(this).val();
            let req = $(this).data("request");

            // Если поле поиска пустое, скрываем блок с результатами и не выполняем AJAX-запрос
            if (!search) {
                searchModal.hide();
                return;
            }

            // Выполняем AJAX-запрос только если поле поиска не пустое
            $.post({
                url: BASE_URL + "searching/getData.php",
                data: {"suggest": search, "request": req},
                beforeSend: function (xhr) {
                    xhr.overrideMimeType("text/plain; charset=utf-8");
                },
                success: function (result) {
                    searchModal.show();
                    // Проверяем, не пустой ли результат
                    if (result.trim() === '') {
                        searchModal.hide();
                    } else {
                        $("#searchAnswerGoods").html(result).show();
                    }
                },
                error: function (error) {
                    console.error(error);
                }
            });
        });

        // check if user wrong writed part type in ti input
        document.querySelector("#part-type").addEventListener("change", function () {
            // Приводим к верхнему регистру для универсальности сравнения
            let val = this.value.toUpperCase();
            const types = ["SMT", "TH", "CM", "PM", "SOLDER", "CRIMP", "LM"];
            // Проверяем, есть ли введенное значение в массиве types
            if (types.includes(val)) {
                // Если значение есть в массиве, делаем кнопку активной (удаляем атрибут disabled)
                this.classList.remove("danger");
                document.querySelector("#part-to-save-btn").removeAttribute("disabled");
                this.value = val;
            } else {
                // Если значения нет в массиве, делаем кнопку неактивной (добавляем атрибут disabled)
                this.classList.add("danger");
                document.querySelector("#part-to-save-btn").disabled = true;
            }
        });

        // Получаем строку парт-номеров из базы данных
        if (dom.e("#item-part-number")) {
            const partNumbersString = dom.e("#item-part-number").textContent;
            // Разделяем строку на отдельные парт-номера
            const partNumbers = partNumbersString.split(',').map(partNumber => partNumber.trim()).filter(partNumber => partNumber !== '');

            // Обработчик для кнопки Google
            dom.in("click", "#search-item-goog", function () {
                const googUrl = "https://www.google.com/search?q="; // Базовый URL для поиска в Google
                const extUrl = "&ie=UTF-8"; // Базовый URL для поиска в Google
                openTabs(googUrl, partNumbers, extUrl);
            });
            // Обработчик для кнопки Octopart
            dom.in("click", "#search-item-octo", function () {
                const octopartSearchUrl = "https://octopart.com/search?q="; // Базовый URL для поиска на Octopart
                const extUrl = "&currency=USD&specs=0"; // Базовый URL для поиска на Octopart
                openTabs(octopartSearchUrl, partNumbers, extUrl);
            });
        }

        // Проверка localStorage для определения, было ли показано модальное окно
        if (!localStorage.getItem('popupDisplayed')) {
            showModal();
        }

        dom.in("submit", "#arrived-form", function (e) {
            // e.preventDefault();
        });
    });

    function createSearchLinks(val) {
        $("#search-item-goog").attr("href", "https://www.google.com/search?q=" + encodeURIComponent(val) + "&ie=UTF-8");
        $("#search-item-octo").attr("href", "https://octopart.com/search?q=" + encodeURIComponent(val) + "&currency=USD&specs=0");
    }

    // Функция для открытия новых вкладок
    function openTabs(searchUrl, partNumbers, extUrl) {
        if (partNumbers.length === 0) {
            alert('No part numbers available.');
            return;
        }
        partNumbers.forEach(partNumber => {
            const url = searchUrl + encodeURIComponent(partNumber) + extUrl;
            window.open(url, '_blank');
        });
    }

    // Функция для отображения модального окна
    function showModal() {
        const modal = dom.e("#blocked-w");
        const span = dom.e(".close");
        modal.style.display = "block";
        span.onclick = function () {
            modal.style.display = "none";
            localStorage.setItem('popupDisplayed', 'true'); // Сохранение состояния в localStorage
        }
        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
                localStorage.setItem('popupDisplayed', 'true'); // Сохранение состояния в localStorage
            }
        }
    }
</script>
</body>
</html>
