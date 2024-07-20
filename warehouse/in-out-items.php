<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'wh');
require 'warehouse/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'in_out_item';
$item = null;
if (isset($_GET['writeoff']) && isset($_GET['item_id'])) {
    $pageMode = 'Write-Off';
    $item = R::load(WH_ITEMS, _E($_GET['item_id']));
    $wh = R::findAll(WAREHOUSE, 'quantity > 0 AND items_id = ?', [$item->id]);
}

if (isset($_GET['arrival']) && isset($_GET['item_id'])) {
    $pageMode = 'Arrival';
    $item = R::load(WH_ITEMS, _E($_GET['item_id']));
}

// save new arrival data to DB
if (isset($_POST['save-new-arrival']) && !empty($_POST['item_id'])) {
    $item_id = _E($_POST['item_id']);
    $item = R::load(WH_ITEMS, $item_id);

    // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ СКЛАД
    $warehouse = R::dispense(WAREHOUSE);
    $warehouse->items_id = $item_id;
    // создаем json обьект для дальнейшего использования
    $owner_data = '{"name":"' . $post['owner'] . '", "id":"' . ($post['owner-id'] ?? '') . '"}';
    $warehouse->owner = $owner_data; // this part owner

    $owner_pn = '';
    if (!empty($post['owner-part-name'])) {
        $owner_pn = $post['owner-part-name'];
    } else {
        $res = self::GetNtiPartNumberForItem($post['owner-part-key']);
        if (!empty($res))
            $owner_pn = $res['key'] . ($res['number'] + 1);
    }
    $warehouse->owner_pn = $owner_pn;

    // полученное кол-во нового товара
    $warehouse->quantity = $post['quantity'];
    $warehouse->storage_box = $post['storage-box'];
    $warehouse->storage_shelf = $post['storage-shelf'];
    $warehouse->storage_state = $post['storage-state'];
    $mf_date = $warehouse->manufacture_date = str_replace('T', ' ', $post['manufactured-date']);
    // Создание срока годности для товара
    $datetime = new DateTime($mf_date);
    $datetime->add(new DateInterval("P{$sl_mo}M"));
    $warehouse->fifo = $datetime->format('Y-m-d H:i');
    $warehouse->date_in = date('Y-m-d H:i');
    $warehouse_id = R::store($warehouse);


    // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ ИНВОЙСОВ
    $invoice = R::dispense(WH_INVOICE);
    $invoice->items_id = $item_id;
    $invoice->quantity = $post['quantity']; // полученное кол-во товара в этой накладной
    $invoice->warehouses_id = $warehouse_id;
    $lot = $invoice->lot = !empty($post['part-lot']) ? $post['part-lot'] : 'N:' . date('m/Y') . ':TI~' . $item_id;
    $invoice->invoice = $post['invoice']; // this airrval invoice
    $invoice->supplier = '{"name":"' . ($post['supplier'] ?? '') . '","id":"' . ($post['supplier-id'] ?? '') . '"}'; // this airrval suplplier
    $invoice->owner = $owner_data; // this part owner
    $invoice->date_in = date('Y-m-d H:i');
    $invoice_id = R::store($invoice);

    // ЗАПИСЫВАЕМ В ЛОГ ОПЕРАЦИЮ И ДАННЫЕ ТАБЛИЦ
    return WareHouseLog::registerNewArrival($item->export(), $warehouse->export(), $invoice->export(), $user);
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
<!-- NAVIGATION BAR -->
<?php
$title = ['title' => $pageMode, 'app_role' => $user['app_role']];
NavBarContent($page, $title, $item->id ?? null, Y['STOCK']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="container-fluid">
    <?php if ($pageMode == 'Write-Off') { ?>
        <table class="p-3" id="write-off-data">
            <!-- header -->
            <thead>
            <tr>
                <th>Owner P/N</th>
                <th>Owner</th>
                <th>Shelf</th>
                <th>Box</th>
                <th>Storage State</th>
                <th>Mnf. Date</th>
                <th>Expaire Date</th>
                <th>Arrival QTY</th>
                <th>Date In</th>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            // сделать переход при клике на строку в просмотр запчасти но с данными только по этому инвойсу
            if (!empty($wh)) {
                foreach ($wh as $line) { ?>
                    <tr class="item-list" data-line="<?= $line ?>">
                        <td class="hidden" data-name="item-id"><?= $line['id']; ?></td>
                        <td data-name="owner_pn"><?= $line['owner_pn']; ?></td>
                        <td><?= json_decode($line['owner'])->name; ?></td>
                        <td data-name="storage_shelf"><?= $line['storage_shelf']; ?></td>
                        <td data-name="storage_box"><?= $line['storage_box']; ?></td>
                        <td data-name="storage_state"><?= $line['storage_state']; ?></td>
                        <td data-name="manufacture_date"><?= $line['manufacture_date']; ?></td>
                        <td data-name="fifo"><?= $line['fifo']; ?></td>
                        <td data-name="quantity"><?= $line['quantity']; ?></td>
                        <td data-name="date_in"><?= $line['date_in']; ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
    <?php }

    // ADD NEW ARRIVAL FOR ITEM IN TO DB
    if ($pageMode == 'Arrival') { ?>
        <form action="" method="post" autocomplete="off" id="item-form">
            <input type="hidden" id="item_id" value="<?= $_GET['item_id'] ?>">
            <input type="hidden" name="supplier-id" id="supplier-id"/>
            <input type="hidden" name="owner-id" id="owner-id"/>

            <label for="owner">Owner</label>
            <input type="text" placeholder="Owner"
                   name="owner" id="owner" class="input searchThis" data-request="owner"
                   value="<?= set_value('owner'); ?>" required/>
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
