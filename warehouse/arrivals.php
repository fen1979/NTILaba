<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'WareHouse.php';
$page = 'arrivals';
$bom_item = $consignment = $qty = $orid = $pid = null;
// check for not in use boxes in storage
// called from ajax metod by clicking on storage box field
if (isset($_POST['search-for-storage-box'])) {
    exit(WareHouse::getEmptyBoxForItem($_POST));
}

// ДОБАВЛЕНИЕ НОВОЙ ЗАПЧАСТИ В БД
if (isset($_POST['save-new-item'])) {
    // ДОБАВЛЯЕМ ЗАПЧАСТЬ
    $args = WareHouse::CreateNewWarehouseItem($_POST, $user);

    // ЕСЛИ ДОБАВЛЕНИЕ ПРОИЗОШЛО ИЗ БОМА-ЗАКАЗА
    if (isset($_GET['orid']) && isset($_GET['pid'])) {
        redirectTo("check_bom?orid=" . _E($_GET['orid']) . "&pid=" . _E($_GET['pid']));
    }

    // переходим на страницу вывода информации о добавленной ITEM
    if ($args && !empty($args['item_id'])) {
        $_SESSION['info'] = $args;
        redirectTo("wh/the_item?itemid=" . $args['item_id']);
    }
}

// добавление новой запчасти при заполнении ВОМ для заказа !!!
if (isset($_GET['consignment']) && isset($_GET['item-id']) && isset($_GET['qty']) && isset($_GET['orid']) && isset($_GET['pid'])) {
    // new-item&consignment=SH198349183&item-id=69&qty=22&orid=1013&pid=85
    // delivery_note
    $bom_item = R::load(PROJECT_BOM, _E($_GET['item-id']));
    $owner = R::load(CLIENTS, $bom_item->customerid);
    $consignment = _E($_GET['consignment']);
    $qty = _E($_GET['qty']);
    $orid = _E($_GET['orid']);
    $pid = _E($_GET['pid']);
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
<?php
// NAVIGATION BAR
NavBarContent([
    'title ' => 'Add New Part',
    'active_btn' => Y['STOCK'],
    'page_tab' => $_GET['page'] ?? null,
    'record_id' => $item->id ?? null,
    'user' => $user,
    'page_name' => $page]); ?>

<div class="container-fluid border-top">
    <div class="row">

        <div class="col-5">
            <!-- part image -->
            <div id="pasteArea" contenteditable="true" class="mb-4 border-bottom"></div>

            <img class="rounded add-img-style hidden" id="item-image-preview" alt="Item image"
                 src="/public/images/drop-here.png">
        </div>

        <div class="col-2 mt-2 pe-3">
            <!-- other information -->
            <div class="mb-3">
                <a role="button" id="search-item-goog" href="" class="btn btn-outline-gold input" target="_blank">
                    <i class="bi bi-google" data-title="Search Item on Google"></i>
                    &nbsp; Search Item on Google
                </a>
                <a role="button" id="search-item-octo" href="" class="btn btn-outline-gold input" target="_blank">
                    <i class="bi bi-snapchat" data-title="Search Item on Octopart"></i>
                    &nbsp; Search Item on Octopart
                </a>
            </div>

            <div class="mb-3">
                <button type="button" id="item-image-btn" class="btn btn-outline-primary input">Upload Item Picture</button>
            </div>
            <div class="mb-3">
                <button type="button" id="db-image-btn" class="btn btn-outline-info input" data-request="get-images">Choose Item Picture</button>
            </div>
        </div>

        <!-- item form -->
        <div class="col-5">
            <form action="" method="post" enctype="multipart/form-data" autocomplete="off" id="item-form">
                <!--             hidden data -->
                <input type="hidden" id="item_id" value="">
                <input type="hidden" name="imageData" id="imageData">
                <input type="file" name="item-image" id="item-image-file" class="hidden">
                <input type="hidden" name="image-path" id="item-image-path">
                <input type="hidden" name="owner-id" id="owner-id" value="<?= _empty($owner->id, '') ?>"/>
                <input type="hidden" name="supplier-id" id="supplier-id"/>

                <!--             item data -->
                <label for="part-name">Part Name</label>
                <input type="text" placeholder="Part name"
                       name="part-name" id="part-name" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('part-name', $bom_item->part_name ?? ''); ?>"/>
                <label for="part-value">Part Value</label>
                <input type="text" placeholder="Part value"
                       name="part-value" id="part-value" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('part-value', $bom_item->part_value ?? ''); ?>" required/>

                <?php $t = 'SMT = Surface mount, TH = Through holes, CM = Cable Mount, PM = Panel Mount,
                                    SOLDER = Soldering to wires, CRIMP = Crimping technic, LM = In line mount.
                                     Or OTHER for any item '; ?>
                <label for="mounting-type">Mounting Type</label>
                <select name="mounting-type" id="mounting-type" class="input" data-title="<?= $t ?>" required>
                    <?php foreach (MOUNTING_TYPE as $type): ?>
                        <option value="<?= $type ?>"><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="manufacture-part-number">Manufacture P/N</label>
                <input type="text" placeholder="MF P/N"
                       name="manufacture-part-number" id="manufacture-part-number" class="input searchThis" data-request="warehouse"
                       value="<?= set_value('manufacture-part-number', $bom_item->manufacture_pn ?? ''); ?>" required/>
                <label for="manufacturer">Manufacturer</label>
                <input type="text" placeholder="MF"
                       name="manufacturer" id="manufacturer" class="input searchThis" data-request="manufacturer"
                       value="<?= set_value('manufacturer', $bom_item->manufacturer ?? ''); ?>"/>
                <label for="footprint">Footprint</label>
                <input type="text" placeholder="F/P"
                       name="footprint" id="footprint" class="input"
                       value="<?= set_value('footprint', $bom_item->footprint ?? ''); ?>"/>
                <label for="minimun-quantity">Min QTY</label>
                <input type="number" placeholder="Min QTY"
                       name="minimun-quantity" id="minimun-quantity" class="input"
                       value="<?= set_value('minimun-quantity', 1); ?>" required/>
                <label for="description">Description</label>
                <input type="text" placeholder="Desc"
                       name="description" id="description" class="input"
                       value="<?= set_value('description', $bom_item->description ?? ''); ?>"/>
                <label for="notes">Notes</label>
                <input type="text" placeholder="Note"
                       name="notes" id="notes" class="input"
                       value="<?= set_value('notes', $bom_item->notes ?? ''); ?>"/>
                <label for="datasheet">Datasheet</label>
                <input type="text" placeholder="DataSheet"
                       name="datasheet" id="datasheet" class="input"
                       value="<?= set_value('datasheet'); ?>"/>
                <label for="shelf-life">Shelf Life</label>
                <input type="text" placeholder="Shelf life"
                       name="shelf-life" id="shelf-life" class="input"
                       value="<?= set_value('shelf-life', 12); ?>" required/>
                <label for="storage-class">Storage Class</label>
                <input type="text" placeholder="Storage class"
                       name="storage-class" id="storage-class" class="input"
                       value="<?= set_value('storage-class', 1); ?>" required/>

                <label for="storage-state">Storage State</label>
                <?php $t = 'Indicator of the working location of this part-device.'; ?>
                <select name="storage-state" id="storage-state" class="input" data-title="<?= $t ?>" required>
                    <?php
                    foreach (STORAGE_STATUS as $val => $name) {
                        $sel = _if($val == 'shelf', 'selected', _if(isset($bom_item) && $val = 'box', 'selected', ''));
                        ?>
                        <option value="<?= $val ?>" <?= $sel ?>><?= $name ?></option>
                    <?php } ?>
                </select>

                <!--  i           warehouse data -->
                <label for="owner">Owner</label>
                <input type="text" placeholder="Owner"
                       name="owner" id="owner" class="input searchThis" data-request="owner"
                       value="<?= set_value('owner', $owner->name ?? ''); ?>" required/>

                <?php $t = 'Name of the spare part in the NTI company or custom owner name.
                            It is important to choose the appropriate name for the correct numbering of the incoming product/spare part.
                            If this number is not available or if the spare part/product belongs to another customer, 
                            select the custom option and write new name by upper letters!!!';
                $query = "SELECT DISTINCT REGEXP_REPLACE(owner_pn, '[0-9]+$', '') AS unique_part_name FROM warehouse";
                ?>
                <label for="owner-pn-list">Owner P/N</label>
                <?php if (!$bom_item || _empty($bom_item->owner_pn, true)){ ?>
                <div class="input-group">
                    <select name="owner-pn-list" id="owner-pn-list" class="form-select" data-title="<?= $t ?>" required>
                        <?php foreach (NTI_PN as $val => $name): ?>
                            <option value="<?= $val ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hidden mt-2 input-group" id="custom-pn-box">
                        <label for="owner-pn-input" class="text-primary">Write custom P/N</label>
                        <input type="text" name="owner-pn-input" id="owner-pn-input" class="input" placeholder="Enter custom P/N"/>
                    </div>
                    <?php } else { ?>
                        <div class="mt-2 input-group" id="custom-pn-box">
                            <label for="owner-pn-input" class="text-primary">Write custom P/N</label>
                            <input type="text" name="owner-pn-input" id="owner-pn-input" class="input" value="<?= $bom_item->owner_pn ?>"/>
                        </div>
                    <?php } ?>
                </div>

                <label for="quantity">Quantity</label>
                <input type="number" placeholder="QTY"
                       name="quantity" id="quantity" class="input"
                       value="<?= set_value('quantity', $qty ?? ''); ?>" required/>

                <label for="storage-box">Storage Box</label>
                <input type="number" placeholder="Storage box"
                       name="storage-box" id="storage-box" class="input"
                       value="<?= set_value('storage-box'); ?>" required/>

                <label for="storage-shelf">Storage Shelf</label>
                <input type="text" placeholder="Storage shelf"
                       name="storage-shelf" id="storage-shelf" class="input"
                       value="<?= set_value('storage-shelf'); ?>" required/>

                <!-- i consignment =  invoice - lot data -->
                <label for="manufactured-date">Manufactured Date</label>
                <input type="datetime-local" placeholder="MF date"
                       name="manufactured-date" id="manufactured-date" class="input"
                       value="<?= set_value('manufactured-date', date('Y-m-d H:i')); ?>" required/>

                <label for="part-lot">Lot</label>
                <input type="text" placeholder="Lot"
                       name="part-lot" id="part-lot" value="<?= set_value('part-lot'); ?>" class="input"/>

                <label for="consignment">Consignment document number</label>
                <input type="text" placeholder="Consignment document number"
                       name="consignment" id="consignment" value="<?= set_value('consignment', $consignment ?? ''); ?>"
                       class="input" required/>

                <label for="delivery_note">Delivery Note</label>
                <?php $note = _if(isset($_GET['pid']), "For Project ID: $pid and Order ID: $orid", ''); ?>
                <input type="text" placeholder="Delivery Note optional"
                       name="delivery_note" id="delivery_note" value="<?= set_value('delivery_note', $note); ?>"
                       class="input"/>

                <label for="supplier">Supplier</label>
                <input type="text" placeholder="Supplier" class="input searchThis" data-request="supplier"
                       name="supplier" id="supplier" value="<?= set_value('supplier'); ?>"/>

                <div class="mb-3">
                    <label for="warehouse-type">Warehouse Type <b class="text-danger">*</b></label>
                    <?php $t = 'Required warehouse type indicator: the default warehouse for the production line is defined!'; ?>
                    <select name="warehouse-type-id" id="warehouse-type" class="input" data-title="<?= $t ?>" required>
                        <?php foreach (R::findAll(WH_TYPES) as $type): ?>
                            <option value="<?= $type['id'] ?>">
                                <?= $type['type_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="save-new-item" id="save-btn" class="btn btn-outline-success input" disabled>Save new item</button>

                <input type="hidden" id="page_data" value="<?= $user['user_name'] . ',' . $user['id']; ?>">
            </form>
        </div>
    </div>
</div>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

// FOOTER AND SCRIPTS
PAGE_FOOTER($page); ?>
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
        dom.doPreviewFile("#item-image-file", "#item-image-preview", function () {
            dom.hide("#pasteArea");
        });

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

        // обработка заполнения формы на странице
        dom.checkForm("item-form", "save-btn");

        // обработчик добавления поставщика/производителя в БД
        dom.requestOnFly("submit", "#supplier_form", function (response, error, _) {
            dom.hide("#loading");
            if (error) {
                console.error('Some Error:', error);
                return;
            }
            // if was added supplier
            if (response.request === "supplier") {
                dom.e("#supplier-id").value = response.supplier_id;
                dom.e("#supplier-name").value = response.supplier_name;
            }
            // if was added manufacturer
            if (response.request === "manufacturer") {
                dom.e("#manufacturer").value = response.supplier_name;
            }
            dom.hide("#searchModal");
            console.log(response);
        });

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
</script>
</body>
</html>
