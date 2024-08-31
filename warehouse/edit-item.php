<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'WareHouse.php';
$page = 'edit_item';
$pageMode = 'edit';
$item = null;

// check for not in use boxes in storage
// called from ajax metod by clicking on storage box field
if (isset($_POST['search-for-storage-box'])) {
    exit(WareHouse::getEmptyBoxForItem($_POST));
}

// редактирование запчасти в БД
if (isset($_POST['save-edited-item']) && !empty($_POST['item_id'])) {
    WareHouse::UpdateNomenclatureItem($_POST, $user);
}

// EDITING ITEM DATA
if (!empty($_GET['item_id'])) {
    $item = R::load(WH_ITEMS, _E($_GET['item_id']));
    $whs = R::findAll(WAREHOUSE, 'items_id = ? AND quantity != 0', [$item->id]);
    $wh = R::findOne(WAREHOUSE, 'items_id = ?', [$item->id]);
    $lot = R::findOne(WH_DELIVERY, 'items_id = ?', [$item->id]);
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

        .input {
            display: block;
            width: 100%;
            padding: .375rem .75rem;
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.5;
            background-clip: padding-box;
            border: .05em solid #ced4da;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            margin: .3em;

        }

        /* СТИЛИ ДЛЯ ВЫВОДА ТАБЛИЦ */
        .modal-body {
            /* убираем падинги от бутстрапа */
            padding: 0;
        }

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
            top: 0;
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

        #pasteArea {
            height: 30rem;
            background-image: url(/public/images/drop-here.png);
            background-repeat: no-repeat;
            background-position: center;
        }

        .input-labels {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Edit Part Information',
    'page_tab' => $_GET['page'] ?? null,
    'record_id' => $item->id,
    'user' => $user,
    'page_name' => $page]); ?>


<div class="container-fluid border-top">
    <div class="row">
        <div class="col-5">
            <!-- part image -->
            <?php $hide = !empty($item->item_image) ? '' : 'hidden' ?>
            <img class="rounded add-img-style <?= $hide ?>" id="item-image-preview" alt="Item image"
                 src="<?= $item->item_image ?? '' ?>">
            <div id="pasteArea" contenteditable="true" class="mb-4 border-bottom"></div>
        </div>

        <div class="col-2 mt-2 pe-3">
            <!-- other information -->
            <div class="mb-3">
                <?php
                // Функция создания ссылок на новые парт номера
                $mfn = explode(',', $item['manufacture_pn']);
                if (!empty($mfn)) {
                    foreach ($mfn as $l) { ?>
                        <a role="button" href="https://www.google.com/search?q=<?= $l ?>&ie=UTF-8" class="btn btn-outline-gold input" target="_blank">
                            <i class="bi bi-google" data-title="Search Item on Google"></i>
                            &nbsp; Search Item on Google
                        </a>
                        <a role="button" href="https://octopart.com/search?q=<?= $l ?>&currency=USD&specs=0" class="btn btn-outline-gold input" target="_blank">
                            <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                            &nbsp; Search Item on Octopart
                        </a>
                        <?php
                    }
                }
                ?>
            </div>

            <div class="mb-3">
                <button type="button" id="item-image-btn" class="btn btn-outline-primary input">Upload Item Picture</button>
            </div>

            <div class="mb-3">
                <button type="button" id="db-image-btn" class="btn btn-outline-info input" data-request="get-images">Choose Item Picture</button>
            </div>

            <!-- TODO дублирование записи на складе для одновременного хранения одной запчасти в разных местах или коробках или на разных складах -->

            <div class="mb-3">
                <button type="button" id="dublicate-btn" class="btn btn-outline-dark input">Dublicate Item Information</button>
            </div>
        </div>
        <div class="col-5">
            <form action="" method="post" enctype="multipart/form-data" autocomplete="off" id="item-form">
                <!--             id for editing only -->
                <input type="hidden" name="item_id" value="<?= $item->id ?? ''; ?>">
                <input type="hidden" name="wh_id" value="<?= $wh->id ?? ''; ?>">
                <!--             hidden data -->
                <input type="hidden" name="imageData" id="imageData">
                <input type="file" name="item-image" id="item-image-file" class="hidden">
                <input type="hidden" name="image-path" id="item-image-path">
                <input type="hidden" name="owner-id" id="owner-id"/>
                <?php $supplier_id = isset($lot->supplier) ? json_decode($lot->supplier)->id : ''; ?>
                <input type="hidden" name="supplier-id" id="supplier-id" value="<?= $supplier_id ?>"/>

                <!--             item data -->
                <label for="part-name">Part Name <b class="text-danger">*</b></label>
                <input type="text" placeholder="Part name"
                       name="part-name" id="part-name" class="input searchThis" data-request="warehouse"
                       value="<?= $item->part_name ?? '' ?>"/>
                <label for="part-value">Part Value <b class="text-danger">*</b></label>
                <input type="text" placeholder="Part value"
                       name="part-value" id="part-value" class="input searchThis" data-request="warehouse"
                       value="<?= $item->part_value ?? '' ?>" required/>

                <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount,
                                    SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.
                                     Or OTHER for any item '; ?>
                <label for="mounting-type">Mounting Type</label>
                <select name="mounting-type" id="mounting-type" class="input" data-title="<?= $t ?>" required>
                    <?php foreach (MOUNTING_TYPE as $type): ?>
                        <option value="<?= $type ?>" <?= isset($item->mounting_type) && $item->mounting_type == $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="manufacture-part-number">Manufacture P/N <b class="text-danger">*</b></label>
                <input type="text" placeholder="MF P/N"
                       name="manufacture-part-number" id="manufacture-part-number" class="input searchThis" data-request="warehouse"
                       value="<?= $item->manufacture_pn ?? '' ?>" required/>
                <label for="manufacturer">Manufacturer</label>
                <input type="text" placeholder="MF"
                       name="manufacturer" id="manufacturer" class="input searchThis" data-request="manufacturer"
                       value="<?= $item->manufacturer ?? '' ?>"/>
                <label for="footprint">Footprint</label>
                <input type="text" placeholder="F/P"
                       name="footprint" id="footprint" class="input"
                       value="<?= $item->footprint ?? '' ?>"/>
                <label for="minimun-quantity">Min QTY <b class="text-danger">*</b></label>
                <input type="number" placeholder="Min QTY"
                       name="minimun-quantity" id="minimun-quantity" class="input"
                       value="<?= $item->min_qty ?? '' ?>" required/>
                <label for="description">Description</label>
                <input type="text" placeholder="Desc"
                       name="description" id="description" class="input"
                       value="<?= $item->description ?? '' ?>"/>
                <label for="notes">Notes</label>
                <input type="text" placeholder="Note"
                       name="notes" id="notes" class="input"
                       value="<?= $item->notes ?? '' ?>"/>
                <label for="datasheet">Datasheet</label>
                <input type="text" placeholder="DataSheet"
                       name="datasheet" id="datasheet" class="input"
                       value="<?= $item->datasheet ?? '' ?>"/>
                <label for="shelf-life">Shelf Life <b class="text-danger">*</b></label>
                <input type="text" placeholder="Shelf life"
                       name="shelf-life" id="shelf-life" class="input"
                       value="<?= $item->shelf_life ?? '' ?>" required/>
                <label for="storage-class">Storage Class <b class="text-danger">*</b></label>
                <input type="text" placeholder="Storage class"
                       name="storage-class" id="storage-class" class="input"
                       value="<?= $item->class_number ?? '' ?>" required/>
                <label for="storage-state">Storage State <b class="text-danger">*</b></label>
                <select name="storage-state" id="storage-state" class="input" data-title="<?= $t ?>" required>
                    <?php foreach (STORAGE_STATUS as $val => $name): ?>
                        <option value="<?= $val ?>" <?= $val == 'shelf' ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <!--             warehouse data -->
                <?php $owner = isset($wh->owner) ? json_decode($wh->owner)->name : ''; ?>
                <label for="owner">Owner <b class="text-danger">*</b></label>
                <input type="text" placeholder="Owner"
                       name="owner" id="owner" class="input searchThis" data-request="owner"
                       value="<?= $owner ?>" required/>

                <?php $t = 'Name of the spare part in the NTI company or custom owner name.
                            It is important to choose the appropriate name for the correct numbering of the incoming product/spare part.
                            If this number is not available or if the spare part/product belongs to another customer, 
                            select the custom option and write new name by upper letters!!!';
                $query = "SELECT DISTINCT REGEXP_REPLACE(owner_pn, '[0-9]+$', '') AS unique_part_name FROM warehouse";
                ?>
                <label for="owner-pn-list">Owner P/N</label>
                <div class="input-group">
                    <select name="owner-pn-list" id="owner-pn-list" class="form-select" data-title="<?= $t ?>" required>
                        <?php foreach (NTI_PN as $val => $name): ?>
                            <?php //foreach (R::getCol($query) as $name): ?>
                            <option value="<?= $val ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hidden mt-2 input-group" id="custom-pn-box">
                        <label for="owner-pn-input" class="text-primary">Write custom P/N</label>
                        <input type="text" name="owner-pn-input" id="owner-pn-input" class="input" placeholder="Enter custom P/N"/>
                    </div>
                </div>

                <?php if ($pageMode != 'edit') { ?>
                    <label for="quantity">Quantity <b class="text-danger">*</b></label>
                    <input type="number" placeholder="QTY" name="quantity" id="quantity" class="input"
                           value="<?= $wh['quantity'] ?? 0 ?>" required/>
                <?php } ?>

                <label for="storage-box">Storage Box <b class="text-danger">*</b></label>
                <input type="number" placeholder="Storage box"
                       name="storage-box" id="storage-box" class="input"
                       value="<?= $wh->storage_box ?? '' ?>" required/>
                <label for="storage-shelf">Storage Shelf <b class="text-danger">*</b></label>
                <input type="text" placeholder="Storage shelf"
                       name="storage-shelf" id="storage-shelf" class="input"
                       value="<?= $wh->storage_shelf ?? '' ?>" required/>

                <!--             invoice - lot data -->
                <?php if ($pageMode != 'edit') { ?>
                    <label for="manufactured-date">Manufactured Date <b class="text-danger">*</b></label>
                    <input type="datetime-local" placeholder="MF date"
                           name="manufactured-date" id="manufactured-date" class="input"
                           value="<?= $wh->manufacture_date ?? date('Y-m-d H:i') ?>" required/>
                <?php } ?>
                <label for="part-lot">Lot</label>
                <input type="text" placeholder="Lot"
                       name="part-lot" id="part-lot" value="<?= $lot->lot ?? '' ?>" class="input"/>

                <?php if ($pageMode != 'edit') { ?>
                    <label for="consignment">Consignment document number <b class="text-danger">*</b></label>
                    <input type="text" placeholder="Delivery note"
                           name="consignment" id="consignment" value="<?= $lot['consignment'] ?>" class="input" required/>

                    <label for="delivery_note">Delivery note <b class="text-danger">*</b></label>
                    <input type="text" placeholder="Delivery note"
                           name="delivery_note" id="delivery_note" value="<?= $lot['delivery_note'] ?>" class="input"/>
                <?php } ?>

                <label for="supplier">Supplier</label>
                <?php $supplier = isset($lot->supplier) ? json_decode($lot->supplier)->name : ''; ?>
                <input type="text" placeholder="Supplier" class="input searchThis" data-request="supplier"
                       name="supplier" id="supplier" value="<?= $supplier ?>"/>

                <div class="mb-3">
                    <label for="warehouse-type">Warehouse Type <b class="text-danger">*</b></label>
                    <?php $t = 'Required warehouse type indicator: the default warehouse for the production line is defined!'; ?>
                    <select name="warehouse-type-id" id="warehouse-type" class="input" data-title="<?= $t ?>" required>
                        <?php foreach (R::findAll(WH_TYPES) as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= isset($wh->wh_types_id) && $wh->wh_types_id == $type['id'] ? 'selected' : '' ?>>
                                <?= $type['type_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
                <button type="submit" name="save-edited-item" class="btn btn-outline-warning input" id="save-btn" disabled>Save edited item</button>

                <input type="hidden" id="page_data" value="<?= $user['user_name'] . ',' . $user['id']; ?>">
            </form>
        </div>
    </div>
</div>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

/* FOOTER AND SCRIPTS */
PAGE_FOOTER($page, false);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Получаем элемент page_data
        const pageDataElem = dom.e('#page_data');
        // Проверяем, что элемент page_data существует на странице
        if (pageDataElem) {
            // Добавляем обработчик события keyup ко всему документу
            document.addEventListener('keyup', function () {
                // Проверяем, существует ли элемент user_data
                const userDataElem = dom.e('#user_data');
                const modalBtn = dom.e('#modal-btn-succes');
                if (userDataElem) {
                    // Копируем значение из page_data в user_data
                    userDataElem.value = pageDataElem.value;
                    modalBtn.disabled = false;
                }
            });
        }

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
                    let img = document.getElementById('item-image-preview');
                    img.src = URL.createObjectURL(blob);
                    img.classList.remove("hidden");
                    dom.e("#pasteArea").classList.add("hidden");
                }
            }
        });

        // creation links for searching elements
        dom.in("change", "#manufacture-part-number", function () {
            createSearchLinks(this.value);
        });

        // кнопки выбора фото пользователя и Обработчик обновления превью
        dom.doClick("#item-image-btn", "#item-image-file");
        dom.doPreviewFile("#item-image-file", "#item-image-preview");

        // Обработка клика по результату поиска запчасти
        dom.in("click", "#search-responce tr.part", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);

                // Устанавливаем полученные значения в поля ввода
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
                    // Устанавливаем значение и показываем блок
                    dom.e("#owner-pn-input").value = info.owner_part_name;
                    dom.show("#custom-pn-box");

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

                if (dom.e("#quantity"))
                    dom.e("#quantity").value = info.quantity;

                dom.e("#storage-box").value = info.storage_box;
                dom.e("#storage-shelf").value = info.storage_shelf;
                // dom.e("#manufactured-date").value = info.manufactured_date.replace(" ", "T");
                // dom.e("#part-lot").value = info.part_lot;
                // dom.e("#invoice").value = info.invoice;
                dom.e("#supplier").value = info.supplier_name;
                dom.e("#supplier-id").value = info.supplier_id;
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
                dom.e("#owner-id").value = info.clientID; // Устанавливаем ID клиента
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

        // выборка фоток из БД которые существуют
        const args = {method: "POST", url: "get_data", headers: null};
        dom.makeRequest("#db-image-btn", "click", "data-request", args, function (error, result, _) {
            if (error) {
                console.error('Error during fetch:', error);
                return;
            }

            // вывод информации в модальное окно
            let modalTable = dom.e("#searchModal");
            if (modalTable) {
                dom.e("#search-responce").innerHTML = result;
                dom.show("#searchModal", "fast", true);
            }
        });

        // установка результата выбора фото из БД
        dom.in("click", "#search-responce td.image-path", function () {
            console.log(this.dataset)
            if (this.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = this.dataset.info;
                let img = dom.e("#item-image-preview");
                img.src = info;
                img.classList.remove('hidden');
                dom.hide("#pasteArea");
                dom.e("#item-image-path").value = info;
            }
            // Очищаем результаты поиска
            dom.hide("#searchModal");
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

        // выбор произвольного имени для номера запчасти
        // например имя которое дал клиент
        dom.in("change", "#owner-pn-list", function () {
            if (this.value === 'custom') {
                dom.show('#custom-pn-box');
                dom.e('#owner-pn-input').required = true;
            } else {
                dom.hide('#custom-pn-box');
                dom.e('#owner-pn-input').required = false;
            }
        });

        // Проверка localStorage для определения, было ли показано модальное окно
        if (!localStorage.getItem('popupDisplayed')) {
            showModal();
        }

        // проверка полей формы на заполенение
        const form = document.getElementById('item-form');
        const saveBtn = document.getElementById('save-btn');

        function checkForm() {
            const inputs = form.querySelectorAll('input[required], select[required]');
            let allFilled = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    allFilled = false;
                }
            });

            saveBtn.disabled = !allFilled;
        }

        form.addEventListener('input', checkForm);
        form.addEventListener('change', checkForm);

        checkForm(); // Проверить форму при загрузке страницы

        // Обработка клика по результату поиска для места хранения
        dom.in("click", "#storage-box", function () {
            // Отправляем POST-запрос на сервер
            $.post('', {'search-for-storage-box': $(this).val()}, function (data) {
                // При успешном получении ответа обновляем значение поля ввода
                dom.e('#storage-box').value = data;
                console.log(data);
            });
        });
    });

    // Функция создания ссылок на новые парт номера
    function createSearchLinks(val) {
        $("#search-item-goog").attr("href", "https://www.google.com/search?q=" + encodeURIComponent(val) + "&ie=UTF-8");
        $("#search-item-octo").attr("href", "https://octopart.com/search?q=" + encodeURIComponent(val) + "&currency=USD&specs=0");
    }

    // Функция для открытия новых вкладок для парт номеров
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

    // Функция для отображения модального окна при первом посещении
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
