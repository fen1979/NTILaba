<?php
require 'core/Routing.php';
function backupWarehouse()
{
    isset($_SESSION['userBean']) && isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR]) or header("Location: /order") and exit();
    isset($_SESSION['userBean']) or header("Location: /order") and exit();
    require 'warehouse/WareHouse.php';

// TODO добавить лог оборота деталей отдельно от лога программы и сделать отдельный вывод

    /* получение пользователя из сессии */
    $thisUser = $_SESSION['userBean'];
    $page = 'warehouse';
    $modalDelete = false;

    /* delete item from DB */
    if (isset($_POST['deleteItem']) && isset($_POST['password'])) {
        $args = WareHouse::putItemToArchive(_E($_POST['itemID']), $thisUser);
    }

    /* undo delete item */
    if (isset($_GET['undo']) && isset($_GET['bomid'])) {
        Undo::RestoreDeletedRecord(_E($_GET['bomid']));
        header("Location: /warehouse");
        exit();
    }

    /* get all from DB for view */
    $goods = R::findAll(WH_ITEMS, 'ORDER BY id ASC');
    $settings = getUserSettings($thisUser, WH_ITEMS);
    $noSettingsYet = 'You have not yet configured the output styles for this table, do you want to configure it?';
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
                top: 6.6%;
            }

            th, td {
                text-align: left;
                padding: 0 5px 0 5px;
                border: 1px solid #ddd;
            }

            tr:hover {
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
    <!-- NAVIGATION BAR -->
    <?php
    NavBarContent($page, $thisUser, null, Y['STOCK']);
    $t = 'Press the [+] button to add new item in storage, or CSV button to import file';
    /* DISPLAY MESSAGES FROM SYSTEM */
    DisplayMessage($args ?? null);
    ?>

    <div class="container-fluid">
        <h1 class="ms-2">Warehouse</h1>

        <!-- ВЫВОД ДАННЫХ ПОСЛЕ СОХРАНЕНИЯ ЗАПЧАСТИ В БД -->
        <?php if ($settings) { ?>
            <table class="custom-table" id="itemTable">
                <thead>
                <tr>
                    <th>
                        <button type="button" class="url btn btn-danger rounded" value="wh/the_item?newitem">
                            <i class="bi bi-plus"></i>
                        </button>
                    </th>
                    <th>
                        <button type="button" class="url btn btn-primary rounded" value="import-csv">
                            <i class="bi bi-filetype-csv"></i>
                        </button>
                    </th>
                    <th>
                        <button type="button" class="url btn btn-outline-dark rounded" value="lotinvoice?">
                            <i class="bi bi-list-task"></i>
                        </button>
                    </th>

                    <?php
                    // выводим заголовки согласно настройкам пользователя
                    foreach ($settings as $k => $set) {
                        echo '<th>' . L::TABLES(WH_ITEMS, $set) . '</th>';
                    }
                    ?>
                </tr>
                </thead>

                <tbody id="searchAnswer">
                <?php if (!empty($goods)) {
                    foreach ($goods as $item) {
                        $color = '';
                        if ((int)$item['actual_qty'] <= (int)$item['min_qty']) {
                            $color = 'danger';
                        } elseif ((int)$item['actual_qty'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                            $color = 'warning';
                        } ?>

                        <tr class="<?= $color; ?>">
                        <td>
                            <button type="button" class="btn btn-outline-danger delete-button" data-itemid="<?= $item['id']; ?>">
                                <i class="bi bi-trash3" data-itemid="<?= $item['id']; ?>"></i>
                            </button>
                        </td>
                        <td>
                            <button type="button" class="url btn btn-outline-warning" value="wh/the_item?edititem&itemid=<?= $item['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                        <td>
                            <button type="button" class="url btn btn-outline-info" value="lotinvoice?itemid=<?= $item['id']; ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>

                        <?php
                        // выводим таблицу согласно настройкам пользователя
                        foreach ($settings as $key => $set) {
                            if ($set == 'item_image') { ?>
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
                    } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

        <?php } else { ?>

            <div class="mt-3">
                <h3><?= $noSettingsYet ?></h3>
                <br>
                <button type="button" class="url btn btn-outline-info" value="setup?route-page=1">Configure it</button>
            </div>
        <?php } ?>
    </div>
    <?php

    // Футер
    footer($page);
    ?>
    <!--  модальное окно форма для удаления одного шага в проекте  -->
    <div class="modal" id="deleteModal" style="backdrop-filter: blur(15px); ">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Заголовок модального окна -->
                <div class="modal-header">
                    <h5 class="modal-title">Delete Item № <span id="itemId"></span></h5>
                    <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
                </div>

                <!-- Содержимое модального окна -->
                <div class="modal-body">
                    <h5 class="text-danger">Warning! This is irreversable operation!!!</h5>
                    <form action="/wh" method="post" autocomplete="off">
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
    ScriptContent($page);
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            dom.in("click", ".delete-button", function () {
                let itemId = this.getAttribute("data-itemid");
                dom.e("#itemID").value = itemId;
                dom.e("#itemId").textContent = itemId;

                /* Откройте модальное окно */
                dom.show("#deleteModal", "modal");
                dom.e("#password").focus();
            });
        });
    </script>
    </body>
    </html>
<?php }

// i =================================================================================================================
function backupFillItem()
{
    isset($_SESSION['userBean']) && $_SESSION['userBean']['app_role'] == ROLE_ADMIN or header("Location: /") and exit();
    require 'warehouse/WareHouse.php';
// TODO добавить лог оборота деталей отдельно от лога программы
    /* получение пользователя из сессии */
    $user = $_SESSION['userBean'];
    $page = 'view_item';
    $item = null;
    $hideSaveButton = false;
    $pageMode = 'Part information';

// добавление новой запчасти в БД
    if (isset($_POST['partToSave'])) {
        if ($_POST['partToSave'] == 'new') {
            $args = WareHouse::CreateNewWarehouseItem($_POST, $user);
            // if sved item was from ORDER-BOM
            if (isset($_GET['orid']) && isset($_GET['pid'])) {
                header("Location: /check_bom?orid=" . _E($_GET['orid']) . "&pid=" . _E($_GET['pid']));
                exit();
            }
        } else {
            $args = WareHouse::UpdateNomenclatureItem($_POST, $user);
        }
    }

// приходим из project-bom для добавления запчасти
    if (isset($_GET['item-id']) && isset($_GET['qty'])) {
        $projectBom = R::load(PROJECT_BOM, _E($_GET['item-id']));
        $owner = R::load(CLIENTS, $projectBom->customerid);
        $item['part_name'] = $projectBom['part_name'];
        $item['part_value'] = $projectBom['part_value'];
        $item['manufacturer'] = $projectBom['manufacturer'];
        $item['manufacture_pn'] = $projectBom['manufacture_pn'];
        $item['owner_pn'] = $projectBom['owner_pn'];
        $item['actual_qty'] = _E($_GET['qty']);
        $item['owner'] = $owner['name'];
        $item['note'] = $projectBom['note'];
        $item['invoice'] = _E($_GET['invoice']);
        $item['extra'] = $projectBom['description'];
    }

// приходим из списка склада для редактирования запчасти
    if (isset($_GET['itemid']) && !isset($_GET['newitem'])) {
        $item = R::load(WH_ITEMS, _E($_GET['itemid']));
        if (isset($_GET['view'])) {
            $hideSaveButton = true;
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
        </style>
    </head>
    <body>
    <!-- NAVIGATION BAR -->
    <?php
    $title = ['title' => $pageMode, 'app_role' => $user['app_role']];
    NavBarContent($page, $title, null, Y['STOCK']);
    /* DISPLAY MESSAGES FROM SYSTEM */
    DisplayMessage($args ?? null);
    ?>
    <div class="container-fluid my-3">
        <!-- Форма для ввода данных -->
        <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row">
                <div class="col-md-4" id="pasteArea" contenteditable="true">
                    <!-- part image -->
                    <img class="rounded add-img-style" id="item-image-preview" alt="Item image"
                         src="<?= !empty($item['item_image']) ? "/{$item['item_image']}" : '/public/images/goods.jpg' ?>">
                </div>

                <div class="col-md-8">
                    <!-- part name and value -->
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label for="part-name" class="form-label"><i class="bi bi-search"></i> Part Name <b class="text-danger">*</b></label>
                            <input type="text" class="searchThis form-control" id="part-name" name="partName" data-request="warehouse"
                                   value="<?= set_value('partName', $item['part_name'] ?? ''); ?>" required
                                   placeholder="Resistor">
                        </div>
                        <div class="col-auto">
                            <label for="part-type" class="form-label">
                                <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount, 
                        SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.
                         No Case sensitive!!!'; ?>
                                <i class="bi bi-info-circle text-primary" data-title="<?= $t; ?>"></i> Part Type <b class="text-danger">*</b>
                            </label>
                            <input type="text" class="form-control" id="mounting-type" name="mounting-type" required
                                   value="<?= set_value('mounting-type', $item['mounting_type'] ?? ''); ?>" placeholder="SMT, TH, CM, PM...">
                        </div>
                        <div class="col">
                            <label for="part-value" class="form-label"><i class="bi bi-search"></i> Part Value <b class="text-danger">*</b></label>
                            <input type="text" class="searchThis form-control" id="part-value" name="partValue" data-request="warehouse"
                                   value="<?= set_value('partValue', $item['part_value'] ?? ''); ?>" required
                                   placeholder="10M, 16W, 1%">
                        </div>
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
                        <?php
                        if (!empty($item['manufacture_pn']) || $new != '') {
                            $g_url = 'https://www.google.com/search?q=' . $item['manufacture_pn'] ?? $new . '&ie=UTF-8';
                            $octo_url = 'https://octopart.com/search?q=' . $item['manufacture_pn'] ?? $new . '&currency=USD&specs=0';
                        }
                        ?>
                        <div class="col-auto" style="padding-top: 2em">
                            <a role="button" id="search-item-goog" href="<?= $g_url ?? ''; ?>" class="btn btn-outline-warning" target="_blank">
                                <i class="bi bi-google" data-title="Search Item on Google"></i>
                            </a>
                            <a role="button" id="search-item-octo" href="<?= $octo_url ?? ''; ?>" class="btn btn-outline-info" target="_blank">
                                <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                            </a>
                        </div>
                        <div class="col">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="Manufacturer"
                                   value="<?= set_value('manufacturer', $item['manufacturer'] ?? '') ?>">
                        </div>
                    </div>
                    <!-- owner & owner part number -->
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label for="part-owner" class="form-label"><i class="bi bi-search"></i> Part Owner <b class="text-danger">*</b></label>
                            <input type="text" class="searchThis form-control" id="part-owner" placeholder="Part owner (REQUIRED)"
                                   name="owner" value="<?= set_value('owner', $item['owner'] ?? ''); ?>" data-request="owner" required>
                        </div>

                        <div class="col">
                            <label for="owner-pn" class="form-label"><i class="bi bi-search"></i> Owner P/N</label>
                            <?php $opn = (!empty($item['owner_pn']) ? $item['owner_pn'] : 'NTI') ?>
                            <input type="text" class="searchThis form-control" id="owner-pn" name="ownerPartName" data-request="warehouse"
                                   value="<?= set_value('ownerPartName', $opn); ?>" placeholder="Owner P/N (OPTIONAL)">
                        </div>

                        <div class="col">
                            <label for="supplier" class="form-label">Supplier</label>
                            <?php $supplier = (!empty($item['supplier']) ? $item['supplier'] : 'Uncnown') ?>
                            <input type="text" class="form-control" id="supplier" name="supplier"
                                   value="<?= set_value('supplier', $supplier); ?>" placeholder="Supplier (OPTIONAL)">
                        </div>
                    </div>

                    <!-- storage space -->
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label for="storage-shelf" class="form-label">Storage Shelf</label>
                            <input type="text" class="form-control" id="storage-shelf" placeholder="Storage shelf"
                                   name="storShelf" value="<?= set_value('storShelf', $item['storage_shelf'] ?? 'A0'); ?>">
                        </div>
                        <div class="col">
                            <label for="storage-box" class="form-label">Storage Box <b class="text-danger">*</b></label>
                            <input type="number" class="form-control" id="storage-box" placeholder="Storage box"
                                   name="storBox" min="1" required
                                   value="<?= set_value('storBox', $item['storage_box'] ?? ''); ?>">
                        </div>
                        <!-- item storage class number -->
                        <div class="col">
                            <label for="storage-class" class="form-label">Storage Class <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" id="storage-class" placeholder="Storage class (1,2,3)"
                                   name="partClassNumber" value="<?= set_value('partClassNumber', $item['class_number'] ?? '1'); ?>" required>
                        </div>
                        <!-- item storage state for now -->
                        <div class="col">
                            <label for="storage-state" class="form-label">Storage State</label>
                            <input type="text" class="form-control" id="storage-state" placeholder="Storage State (On Shelf)"
                                   name="storageState" value="<?= set_value('storageState', $item['storage_state'] ?? 'On Shelf'); ?>">
                        </div>
                    </div>
                    <!-- item amount values -->
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label for="actual-amount" class="form-label">Item quantity <b class="text-danger">*</b></label>
                            <input type="number" class="form-control" id="actual-amount" placeholder="Item quantity"
                                   name="amount" value="<?= set_value('amount', $item['actual_qty'] ?? '0'); ?>" required>
                        </div>
                        <div class="col">
                            <label for="minimum-amount" class="form-label">Minimum quantity <b class="text-danger">*</b></label>
                            <input type="number" class="form-control" id="minimum-amount" placeholder="Minimum quantity"
                                   name="minQTY" value="<?= set_value('minQTY', $item['min_qty'] ?? '1'); ?>" required>
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
                <!-- expaire date -->
                <div class="col-md-2">
                    <label for="exp-date" class="form-label">Exp Date <b class="text-danger">*</b></label>
                    <input type="datetime-local" class="form-control" id="exp-date" placeholder="Expaire Date"
                           name="expDate" value="<?= set_value('expDate', $item['exp_date'] ?? date('Y-m-d H:i')); ?>" required>
                </div>
                <!-- invoice numbers -->
                <div class="col-md-3">
                    <label for="invoice-number" class="form-label">Invoice Numbers</label>
                    <input type="text" class="form-control" id="invoice-number" placeholder="Invoice numbers"
                           name="invoice" value="<?= $item['invoice'] ?? ''; ?>" required>
                </div>
                <!-- part lots -->
                <div class="col-md-3">
                    <label for="part-lot" class="form-label">Part Lots</label>
                    <!--                <input type="text" class="form-control" id="part-lot" placeholder="Part lots"-->
                    <!--                       name="lots" value="--><?php //= set_value('lots', $item['lots'] ?? '');
                    ?><!--" >-->

                    <input type="text" class="form-control" id="part-lot" placeholder="Part lots" value="<?= $item['lots'] ?? ''; ?>" readonly>
                </div>
            </div>

            <?php if (!$hideSaveButton) { ?>
                <div class="mt-3">
                    <button type="submit" id="part-to-save-btn" class="btn btn-success form-control" name="partToSave" value="<?= $item->id ?? 'new'; ?>">
                        Save Item
                    </button>
                </div>
            <?php } ?>
            <input type="hidden" name="imageData" id="imageData">
        </form>

        <h3 class="mt-4 text-center border-bottom">Item Log information</h3>

        <div class="row mt-3">
            <div class="col">
                <div class="border p-3" id="warehouse-log-view">TODO: Warehouse log view</div>
            </div>
        </div>
    </div>

    <!-- The Search result Modal -->
    <div class="modal" id="searchModal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Search Result</h4>
                    <button type="button" class="btn-close" data-aj-dismiss="modal"></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body" id="searchAnswerGoods"></div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-aj-dismiss="modal">Close</button>
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
            document.doClick("#item-image-btn", "#item-image-file");
            document.doPreviewFile("item-image-file", "item-image-preview");

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
                    url: BASE_URL + "get_data",
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
        });

        function createSearchLinks(val) {
            $("#search-item-goog").attr("href", "https://www.google.com/search?q=" + encodeURIComponent(val) + "&ie=UTF-8");
            $("#search-item-octo").attr("href", "https://octopart.com/search?q=" + encodeURIComponent(val) + "&currency=USD&specs=0");
        }
    </script>
    </body>
    </html>
    <?php
}

function backupItemView()
{
    isset($_SESSION['userBean']) && $_SESSION['userBean']['app_role'] == ROLE_ADMIN or header("Location: /") and exit();
    /* получение пользователя из сессии */
    $user = $_SESSION['userBean'];
    $page = 'view_item';
    $item = R::load(WH_ITEMS, _E($_GET['itemid']));
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

            /* Модальное окно */
            .modal {
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

            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
            }

            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

        </style>
    </head>
    <body>
    <?php //NAVIGATION BAR
    $title = ['title' => 'Part information', 'app_role' => $user['app_role']];
    NavBarContent($page, $title, null, Y['STOCK']);
    ?>
    <div class="container-fluid my-3 px-3">
        <div class="row">
            <div class="col-md-4">
                <!-- part image -->
                <img class="rounded add-img-style" id="item-image-preview" alt="Item image"
                     src="<?= !empty($item['item_image']) ? "/{$item['item_image']}" : '/public/images/goods.jpg' ?>">
            </div>

            <div class="col-md-8">
                <!-- part name and value -->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <span class="form-label">Part Name</span>
                        <p class="form-control"><?= $item['part_name'] ?? 'L'; ?></p>
                    </div>
                    <div class="col-auto">
                        <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount, 
                        SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.'; ?>
                        <span class="form-label"><i class="bi bi-info-circle text-primary" data-title="<?= $t; ?>"></i> Mounting Type</span>
                        <p class="form-control"><?= $item['mounting_type'] ?? 'N'; ?></p>
                    </div>
                    <div class="col">
                        <span class="form-label">Part Value</span>
                        <p class=" form-control"><?= $item['part_value'] ?? '0'; ?></p>
                    </div>
                </div>

                <!-- manufacturer & mf part number-->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <span class="form-label"> Manufacturer P/N </span>
                        <p class=" form-control"><?= $item['manufacture_pn'] ?? 'N'; ?></p>
                    </div>

                    <div class="col-auto" style="padding-top: 1.2vw">
                        <button id="search-item-goog" class="btn btn-outline-warning">
                            <i class="bi bi-google" data-title="Search Item on Google"></i>
                        </button>
                        <button id="search-item-octo" class="btn btn-outline-info">
                            <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                        </button>
                    </div>

                    <div class="col">
                        <span class="form-label">Manufacturer</span>
                        <p class="form-control"><?= $item['manufacturer'] ?? 'N'; ?></p>
                    </div>
                    <?php
                    // выводим парт номера/номер если есть в поле откуда его подхватит
                    // скрипт открывающий все парт номера одновременно если он не один
                    if (!empty($item['manufacture_pn'])) {
                        echo '<span class="hidden" id="item-part-number">' . $item['manufacture_pn'] . '</span>';
                    }
                    ?>
                </div>
                <!-- owner & owner part number -->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <span class="form-label"> Part Owner</span>
                        <p class="form-control"><?= $item['owner'] ?? ''; ?></p>
                    </div>
                    <div class="col">
                        <span class="form-label"> Owner P/N</span>
                        <?php $opn = (!empty($item['owner_pn']) ? $item['owner_pn'] : 'NTI') ?>
                        <p class=" form-control"><?= $opn; ?></p>
                    </div>
                </div>
                <!-- storage space -->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <span class="form-label">Storage Shelf</span>
                        <p class="form-control"><?= $item['storage_shelf'] ?? 'A0'; ?></p>
                    </div>
                    <div class="col">
                        <span class="form-label">Storage Box</span>
                        <p class="form-control"><?= $item['storage_box'] ?? '0'; ?></p>
                    </div>
                    <!-- item storage class number -->
                    <div class="col">
                        <span class="form-label">Storage Class</span>
                        <p class="form-control"><?= $item['class_number'] ?? '1'; ?></p>
                    </div>
                    <div class="col">
                        <span class="form-label">Storage Status</span>
                        <p class="form-control"><?= $item['storage_state'] ?? 'On Shelf'; ?></p>
                    </div>
                </div>
                <!-- item amount values -->
                <div class="row g-3 mb-3">
                    <div class="col">
                        <?php $clor = ($item['actual_qty'] > 0) ? '' : 'danger'; ?>
                        <span class="form-label">Item quantity/meter </span>
                        <p class="form-control <?= $clor ?>"><?= (int)$item['actual_qty'] ?? '0'; ?></p>
                    </div>
                    <div class="col">
                        <span class="form-label">Minimum quantity/meter </span>
                        <p class="form-control"><?= (int)$item['min_qty'] ?? '1'; ?></p>
                    </div>
                </div>
                <!-- datasheet link & buttons take picture and search for item-->
                <div class="mb-3">
                    <span class="form-label">Datasheet Link</span>
                    <a role="button" target="_blank" class="btn btn-outline-info form-control" href="<?= $item['datasheet'] ?? ''; ?>">Link To Datasheet</a>
                </div>
            </div>
        </div>

        <!-- description  -->
        <div class="row mt-3">
            <div class="col">
                <span class="form-label">Description information</span>
                <?php $text = preg_replace(
                    '/(https?:\/\/\S+)/',
                    '<br><a href="$1">$1</a>',
                    $item['description']); ?>
                <div class="form-control p-3"><?= $text ?? ''; ?></div>
            </div>

            <div class="col">
                <span class="form-label">Additional information</span>
                <?php $text = preg_replace(
                    '/(https?:\/\/\S+)/',
                    '<br><a href="$1">$1</a>',
                    $item['notes']); ?>
                <div class="form-control p-3"><?= $text ?? ''; ?></div>
            </div>
        </div>

        <h3 class="mt-4 text-center border-bottom">Other information</h3>
        <div class="row mt-3 align-items-center">
            <!-- footprint -->
            <div class="col-md-2">
                <span class="form-label">Footprint</span>
                <p class="form-control"><?= $item['footprint'] ?? ''; ?></p>
            </div>
            <!-- manufacture date -->
            <div class="col-md-2">
                <span class="form-label">Date In </span>
                <p class="form-control"><?= $item['manufacture_date'] ?? ''; ?></p>
            </div>
            <!-- expaire date -->
            <div class="col-md-2">
                <span class="form-label">Exp Date </span>
                <p class="form-control"><?= $item['exp_date'] ?? ''; ?></p>
            </div>
            <!-- invoice number -->
            <div class="col-md-3">
                <span class="form-label">Invoice Numbers</span>
                <p class="form-control"><?= $item['invoice'] ?? '-'; ?></p>
            </div>
            <!-- part lot -->
            <div class="col-md-3">
                <span class="form-label"> Part Lots</span>
                <p class="form-control"><?= $item['lots'] ?? '-'; ?></p>
            </div>
        </div>

        <h3 class="mt-4 text-center border-bottom">Project reservation information</h3>
        <!-- TODO out put some data for this case -->
        <div class="row mt-3">
            <div class="col-md-6">
                <span class="form-label">Project Name - Version</span>
                <p class="form-control"></p>
            </div>
            <div class="col-md-6">
                <span class="form-label">Amount Reserved</span>
                <p class="form-control"></p>
            </div>
        </div>

        <h3 class="mt-4 text-center border-bottom">Log information</h3>

        <div class="row mt-3">
            <div class="col">
                <span class="form-label">Warehouse Log View</span>
                <div class="border p-3" id="warehouse-log-view">Warehouse log view</div>
            </div>
        </div>
    </div>

    <!-- Модальное окно -->
    <div id="popup-modal" class="modal">
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

    <?php
    /* SCRIPTS */
    ScriptContent($page);
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

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
        });

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
            const modal = dom.e("#popup-modal");
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
    <?php
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh; /* Высота экрана */
        }
        /*th, td{*/
        /*    height: 65px;*/
        /*}*/

        #table-fixed {
            position: absolute;
            top: 0;
            left: 0;
            width: 30%; /* Ширина фиксированной таблицы */
            overflow-y: auto; /* Вертикальный скролл */
            z-index: 100; /* Высший приоритет отображения */
            background-color: white; /* Предотвращение перекрытия фонами */
            white-space: break-spaces;
        }

        #table-fixed thead th {
            position: sticky;
            top: 0;
            background-color: #c7dfec; /* Цвет фона заголовков */
            z-index: 101; /* Заголовок выше остального контента таблицы */
        }

        #table-fixed th, #table-fixed td {
            text-align: left;
            padding: 0 5px;
            border: 1px solid #ddd;
        }

        #table-fixed tr:hover {
            cursor: pointer;
            background: #baecf6;
        }

        #table-fixed tr.clickable:hover {
            background: #0739ff;
        }

        #table-moving-container {
            margin-left: 41%; /* Отступ слева на ширину первой таблицы */
            overflow: auto; /* Скролл во всех направлениях */
            position: relative;
            z-index: 99; /* Меньше z-index первой таблицы */
            padding-top: 0; /* Убедитесь, что вторая таблица начинается сразу после первой */
        }

        #table-moving {
            width: 200%; /* Ширина второй таблицы, чтобы показать горизонтальный скролл */
            /*white-space: nowrap; !* Для предотвращения переноса строк *!*/
        }

        #table-moving thead th {
            position: sticky;
            top: 0;
            background-color: #c7dfec; /* Цвет фона заголовков */
            z-index: 100; /* Заголовок выше остального контента таблицы */
        }

        #table-moving th, #table-moving td {
            text-align: left;
            padding: 0 5px;
            border: 1px solid #ddd;
        }

        #table-moving tr:hover {
            cursor: pointer;
            background: #baecf6;
        }

        #table-moving tr.clickable:hover {
            background: #0739ff;
        }
    </style>
</head>
<body>
<div id="table-fixed-container">
    <table id="table-fixed">
        <thead>
        <tr>
            <th>Image</th>
            <th>Value</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (R::findAll(WH_ITEMS) as $item) { ?>
            <tr class="clickable">
                <td><?= $item['item_image'] ?></td>
                <td><?= "{$item['part_name']}, {$item['part_value']}, {$item['mounting_type']}, {$item['footprint']}" ?></td>
                <td><?= $item['description'] ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<div id="table-moving-container">
    <table id="table-moving">
        <thead>
        <tr>
            <th>Data</th>
            <th>Name</th>
            <th>Value</th>
            <th>Type</th>
            <th>Footprint</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (R::findAll(WH_ITEMS) as $item) { ?>
            <tr class="clickable">
                <td><?= $item['date_in'] ?></td>
                <td><?= $item['part_name'] ?></td>
                <td><?= $item['part_value'] ?></td>
                <td><?= $item['mounting_type'] ?></td>
                <td><?= $item['footprint'] ?></td>
                <td><?= $item['description'] ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
