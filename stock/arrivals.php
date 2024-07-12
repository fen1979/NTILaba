<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'warehouse');
require 'WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'arrivals';
$pageMode = 'Add New Part';

// ДОБАВЛЕНИЕ НОВОЙ ЗАПЧАСТИ В БД
if (isset($_POST['save-new-item'])/* && $_POST['save-new-item'] == 'new'*/) {
    // ДОБАВЛЯЕМ ЗАПЧАСТЬ
    $args = WareHouse::CreateNewWarehouseItem($_POST, $user);

    // ЕСЛИ ДОБАВЛЕНИЕ ПРОИЗОШЛО ИЗ БОМА-ЗАКАЗА
    if (isset($_GET['orid']) && isset($_GET['pid'])) {
        header("Location: /check_bom?orid=" . _E($_GET['orid']) . "&pid=" . _E($_GET['pid']));
        exit();
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
<div class="container-fluid border-top">
    <div class="row">

        <div class="col-5">
            <!-- part image -->
            <div id="pasteArea" contenteditable="true" class="mb-4 border-bottom"></div>

            <img class="rounded add-img-style hidden" id="item-image-preview" alt="Item image"
                 src="/public/images/drop-here.png">
        </div>

        <div class="col-2">
            <!-- other information -->
            <div class="mb-3">
                <a role="button" id="search-item-goog" href="" class="btn btn-outline-warning input" target="_blank">
                    <i class="bi bi-google" data-title="Search Item on Google"></i>
                    &nbsp; Search Item on Google
                </a>
                <a role="button" id="search-item-octo" href="" class="btn btn-outline-info input" target="_blank">
                    <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                    &nbsp; Search Item on Octopart
                </a>
            </div>

            <div class="mb-3">
                <button type="button" id="item-image-btn" class="btn btn-outline-primary input">Take Item Picture</button>
            </div>
        </div>

        <div class="col-5">
            <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
                <!-- id for editing only -->
                <input type="hidden" name="item_id" value="<?= $item->id ?? ''; ?>">
                <!-- hidden data -->
                <input type="hidden" name="imageData" id="imageData">
                <input type="file" name="item-image" id="item-image-file" class="hidden">
                <input type="hidden" name="owner-id" id="owner-id"/>
                <input type="hidden" name="supplier-id" id="supplier-id"/>

                <!-- item data -->
                <input type="text" placeholder="Part name"
                       name="part-name" id="part-name" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('part-name'); ?>"/>
                <input type="text" placeholder="Part value"
                       name="part-value" id="part-value" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('part-value'); ?>" required/>

                <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount, 
                        SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.
                         No Case sensitive!!!'; ?>
                <select name="part-type" id="part-type" class="input" data-title="<?= $t ?>" required>
                    <?php foreach (ITEM_TYPES as $type): ?>
                        <option value="<?= $type ?>"><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" placeholder="MF P/N"
                       name="manufacture-part-number" id="manufacture-part-number" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('manufacture-part-number'); ?>" required/>
                <input type="text" placeholder="MF"
                       name="manufacturer" id="manufacturer" class="input searchThis" data-request="manufacturer"
                       value="<?= set_value('manufacturer'); ?>"/>
                <input type="text" placeholder="F/P"
                       name="footprint" id="footprint" class="input"
                       value="<?= set_value('footprint'); ?>"/>
                <input type="number" placeholder="Min QTY"
                       name="minimun-quantity" id="minimun-quantity" class="input"
                       value="<?= set_value('minimun-quantity'); ?>" required/>
                <input type="text" placeholder="Desc"
                       name="description" id="description" class="input"
                       value="<?= set_value('description'); ?>"/>
                <input type="text" placeholder="Note"
                       name="notes" id="notes" class="input"
                       value="<?= set_value('notes'); ?>"/>
                <input type="text" placeholder="DataSheet"
                       name="datasheet" id="datasheet" class="input"
                       value="<?= set_value('datasheet'); ?>"/>
                <input type="text" placeholder="Shelf life"
                       name="shelf-life" id="shelf-life" class="input"
                       value="<?= set_value('shelf-life'); ?>" required/>
                <input type="text" placeholder="Storage class"
                       name="storage-class" id="storage-class" class="input"
                       value="<?= set_value('storage-class'); ?>" required/>
                <input type="text" placeholder="Storage state"
                       name="storage-state" id="storage-state" class="input"
                       value="<?= set_value('storage-state'); ?>" required/>

                <!-- warehouse data -->
                <input type="text" placeholder="Owner"
                       name="owner" id="owner" class="input searchThis" data-request="owner"
                       value="<?= set_value('owner'); ?>" required/>
                <?php $t = 'Name of the spare part in the NTI company. 
                It is important to choose the appropriate name for the correct numbering of the incoming product/spare part. 
                If this number is not available or if the spare part/product belongs to another customer, select the "OTHERS" option'; ?>
                <select name="owner-part-key" id="owner-part-key" class="input" data-title="<?= $t ?>" required>
                    <?php
                    foreach (NTI_PN as $val => $name): ?>
                        <option value="<?= $val ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" placeholder="Owner P/N"
                       name="owner-part-name" id="owner-part-name" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('owner-part-name'); ?>"/>
                <input type="number" placeholder="QTY"
                       name="quantity" id="quantity" class="input"
                       value="<?= set_value('quantity'); ?>" required/>
                <input type="number" placeholder="Storage box"
                       name="storage-box" id="storage-box" class="input"
                       value="<?= set_value('storage-box'); ?>" required/>
                <input type="text" placeholder="Storage shelf"
                       name="storage-shelf" id="storage-shelf" class="input"
                       value="<?= set_value('storage-shelf'); ?>" required/>

                <!-- invoice - lot data -->
                <input type="datetime-local" placeholder="MF date"
                       name="manufactured-date" id="manufactured-date" class="input"
                       value="<?= set_value('manufactured-date', date('Y-m-d H:i')); ?>" required/>
                <input type="text" placeholder="Lot"
                       name="part-lot" id="part-lot" value="<?= set_value('part-lot'); ?>" class="input"/>
                <input type="text" placeholder="Invoice"
                       name="invoice" id="invoice" value="<?= set_value('invoice'); ?>" class="input" required/>
                <input type="text" placeholder="Supplier" class="input searchThis" data-request="supplier"
                       name="supplier" id="supplier" value="<?= set_value('supplier'); ?>"/>

                <div class="mb-3">
                    <label for="warehouse-type" class="form-label">Warehouse Type <b class="text-danger">*</b></label>
                    <?php $t = 'Required warehouse type indicator: the default warehouse for the production line is defined!'; ?>
                    <select name="warehouse-type" id="warehouse-type" class="input" data-title="<?= $t ?>" required>
                        <?php foreach (R::findAll(WH_TYPES) as $type): ?>
                            <option value="<?= $type['id'] ?>"><?= $type['type_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="save-new-item" class="btn btn-outline-success input">Save new item</button>
            </form>
        </div>
    </div>
</div>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

/* SCRIPTS */
ScriptContent('arrivals');
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
                dom.e("#part-type").value = info.part_type;
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
                dom.e("#owner-part-name").value = info.owner_part_name;
                dom.e("#quantity").value = info.quantity;
                dom.e("#storage-box").value = info.storage_box;
                dom.e("#storage-shelf").value = info.storage_shelf;
                // dom.e("#manufactured-date").value = info.manufactured_date.replace(" ", "T");
                // dom.e("#part-lot").value = info.part_lot;
                // dom.e("#invoice").value = info.invoice;
                // dom.e("#supplier").value = info.supplier;
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
