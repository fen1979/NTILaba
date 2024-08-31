<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'warehouse/WareHouse.php';
$page = 'replenishment';
$item = null;

// check for not in use boxes in storage
// called from ajax metod by clicking on storage box field
if (isset($_POST['search-for-storage-box'])) {
    exit(WareHouse::getEmptyBoxForItem($_POST));
}

// save new arrival data to DB
if (isset($_POST['save-new-arrival']) && !empty($_POST['item_id'])) {
    try {
        $args = WareHouse::ReplenishInventory($_POST, $user);
        if (!empty($args['action']) && $args['action'] == 'success') {
            redirectTo('wh/the_item?item_id=' . $_POST['item_id']);
            exit($args);
        }
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Error: ' . $e->getMessage(), 'danger');
    }

}

if (isset($_GET['item_id'])) {
    $item = R::load(WH_ITEMS, _E($_GET['item_id']));
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
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent([
    'title' => 'Replenishment of Warehouse',
    'page_tab' => $_GET['page'] ?? null,
    'record_id' => $item->id ?? null,
    'user' => $user,
    'page_name' => $page]); ?>

<div class="container-fluid">
    <div class="row mt-2 mb-2">
        <div class="col-6">
            <?php
            // ADD NEW ARRIVAL FOR ITEM IN TO DB
            if ($item) { ?>
                <form action="" method="post" autocomplete="off" id="item-form">
                    <input type="hidden" name="item_id" value="<?= $item->id ?>">
                    <input type="hidden" name="supplier-id" id="supplier-id"/>
                    <input type="hidden" name="owner-id" id="owner-id"/>

                    <label for="owner">Owner</label>
                    <input type="text" placeholder="Owner"
                           name="owner" id="owner" class="input searchThis" data-request="owner"
                           value="<?= set_value('owner'); ?>" required/>

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
                                <option value="<?= $val ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hidden mt-2 input-group" id="custom-pn-box">
                            <label for="owner-pn-input" class="text-primary">Write custom P/N</label>
                            <input type="text" name="owner-pn-input" id="owner-pn-input" class="input" placeholder="Enter custom P/N"/>
                        </div>
                    </div>

                    <label for="quantity">Quantity</label>
                    <input type="number" placeholder="QTY"
                           name="quantity" id="quantity" class="input"
                           value="<?= set_value('quantity'); ?>" required/>

                    <label for="consignment">Consignment document number</label>
                    <input type="text" placeholder="consignment"
                           name="consignment" id="consignment" value="<?= set_value('consignment'); ?>" class="input" required/>

                    <label for="delivery_note">Delivery note</label>
                    <input type="text" placeholder="Delivery note [optional]"
                           name="delivery_note" id="delivery_note" value="<?= set_value('delivery_note'); ?>" class="input"/>

                    <label for="supplier">Supplier</label>
                    <input type="text" placeholder="Supplier" class="input searchThis" data-request="supplier"
                           name="supplier" id="supplier" value="<?= set_value('supplier'); ?>"/>
                    <label for="storage-box">Storage Box</label>
                    <input type="number" placeholder="Storage box"
                           name="storage-box" id="storage-box" class="input"
                           value="<?= set_value('storage-box'); ?>" required/>
                    <label for="storage-shelf">Storage Shelf</label>
                    <input type="text" placeholder="Storage shelf"
                           name="storage-shelf" id="storage-shelf" class="input"
                           value="<?= set_value('storage-shelf'); ?>" required/>
                    <label for="storage-state">Storage State</label>
                    <?php $t = 'Indicator of the working location of this part-device.'; ?>
                    <select name="storage-state" id="storage-state" class="input" data-title="<?= $t ?>" required>
                        <?php foreach (STORAGE_STATUS as $val => $name): ?>
                            <option value="<?= $val ?>" <?= $val == 'shelf' ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="manufactured-date">Manufactured Date</label>
                    <input type="datetime-local" placeholder="MF date"
                           name="manufactured-date" id="manufactured-date" class="input"
                           value="<?= set_value('manufactured-date', date('Y-m-d H:i')); ?>" required/>
                    <label for="part-lot">Lot</label>
                    <input type="text" placeholder="Lot"
                           name="part-lot" id="part-lot" value="<?= set_value('part-lot'); ?>" class="input"/>
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
                    <button type="submit" name="save-new-arrival" id="save-btn" class="btn btn-outline-success input" disabled>Save new amount</button>

                    <input type="hidden" id="page_data" value="<?= $user['user_name'] . ',' . $user['id']; ?>">
                </form>
            <?php } ?>
        </div>
        <div class="col-2 p-5">
            <p>Part name: <b><?= $item['part_name'] ?></b></p>
            <p>Part Value: <b><?= $item['part_value'] ?></b></p>
            <p>Mounting Type: <b><?= $item['mounting_type'] ?></b></p>
            <p>Manufacture P/N: <b><?= $item['manufacture_pn'] ?></b></p>
            <p>Manufacturer: <b><?= $item['manufacturer'] ?></b></p>
            <p>Shelf life: <b><?= $item['shelf_life'] ?></b> month</p>
            <p>Storage Class: <b><?= $item['class_number'] ?></b></p>
            <!--            <p>Storage State: <b>--><?php //= $item['storage_state'] ?><!--</b></p>-->
            <p>Footprint: <b><?= $item['footprint'] ?></b></p>
        </div>
        <!--i IMAGE CONTAINER-->
        <div class="col-4">
            <div class="m-2">
                <!-- part image -->
                <img class="rounded add-img-style" id="item-image-preview" alt="Item image"
                     src="<?= !empty($item['item_image']) ? "/{$item['item_image']}" : '/public/images/goods.jpg' ?>">
            </div>
        </div>
    </div>
</div>

<?php
// MODAL FOR SEARCH RESPONCE ANSWER
SearchResponceModalDialog($page, 'search-responce');

// FOOTER and SCRIPTS
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

        // обработка заполнения формы на странице
        if (dom.e("#item-form"))
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
</script>
</body>
</html>
