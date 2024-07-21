<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'warehouse/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'in_out_item';
$item = null;

// save new arrival data to DB
if (isset($_POST['save-new-arrival']) && !empty($_POST['item_id'])) {
    $args = WareHouse::ReplenishInventory($_POST, $user);
    if (!empty($args['action']) && $args['action'] == 'success') {
        header('Location: wh/the_item?item_id=' . $_POST['item_id']);
        exit($args);
    }
}

if (isset($_GET['arrival']) && isset($_GET['item_id'])) {
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
            width: 50%;
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
$navBarData['title'] = $pageMode;
$navBarData['page_tab'] = $_GET['page'] ?? null;
$navBarData['record_id'] = $item->id ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="container-fluid">
    <?php
    // ADD NEW ARRIVAL FOR ITEM IN TO DB
    if ($item) { ?>
        <form action="" method="post" autocomplete="off" id="item-form">
            <input type="hidden" name="item_id" value="<?= $item->item_id ?>">
            <input type="hidden" name="supplier-id" id="supplier-id"/>
            <input type="hidden" name="owner-id" id="owner-id"/>

            <label for="owner">Owner</label>
            <input type="text" placeholder="Owner"
                   name="owner" id="owner" class="input searchThis" data-request="owner"
                   value="<?= set_value('owner'); ?>" required/>
            <?php $t = 'Name of the spare part in the NTI company.
                            It is important to choose the appropriate name for the correct numbering of the incoming product/spare part.
                            If this number is not available or if the spare part/product belongs to another customer, select the Other option'; ?>
            <label for="owner-part-key">NTI P/N</label>
            <select name="owner-part-key" id="owner-part-key" class="input" data-title="<?= $t ?>" required>
                <?php foreach (NTI_PN as $val => $name): ?>
                    <option value="<?= $val ?>"><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <label for="owner-part-name">Owner P/N</label>
            <input type="text" placeholder="Owner P/N"
                   name="owner-part-name" id="owner-part-name" class="input" data-request="warehouse searchThis"
                   value="<?= set_value('owner-part-name'); ?>"/>
            <label for="quantity">Quantity</label>
            <input type="number" placeholder="QTY"
                   name="quantity" id="quantity" class="input"
                   value="<?= set_value('quantity'); ?>" required/>
            <label for="invoice">Invoice</label>
            <input type="text" placeholder="Invoice"
                   name="invoice" id="invoice" value="<?= set_value('invoice', 'base flooding'); ?>" class="input" required/>
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

        </form>
    <?php } ?>
</div>
<?php
// MODAL FOR SEARCH RESPONCE ANSWER
SearchResponceModalDialog($page, 'search-responce');
// FOOTER
footer($page);
// SCRIPTS
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
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
    });
</script>
</body>
</html>


<!--/*
* создать запись в логах
* списать нужное количество из БД
* создать запись перемещения с указанием кто отпустил , кому и когда с количеством и прочими данными
* если списали под проект то указать проект и/или заказ
* если списани под заказ то в заказе если был резерв то удалить списанное из резерва
* если был резерв для другого заказа то обновить данные по резерву исключив кол-во списаное под
* сторонние нужды. сделать документы по перемещению или то то подобное
* запси о этом событии будут выведены в полях детали при входе в детали ЗЧ
* Item Movements information вкладка для отображения всех перемещений деталей
* */

/*
* сделать списание запчасти вэтом файле и приход запчасти которая уже внесена в БД
* при списании дать возможность выбрать из какого прихода нужно списать
*
*/-->
