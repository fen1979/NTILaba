<?php
isset($_SESSION['userBean']) && $_SESSION['userBean']['app_role'] == ROLE_ADMIN or header("Location: /") and exit();
require 'stock/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'view_item';
$pageMode = 'View Item';
$data = [];
$item = null;
// tab by default
$A_T = $_GET['tab'] ?? 'tab1'; // Active Tab

if (isset($_GET['itemid'])) {
    // получаем товар
    $item = R::load(WH_ITEMS, _E($_GET['itemid']));
    // получаем информацию о приходах
    $lots = R::findAll(WH_INVOICE, 'items_id = ?', [$item->id]);
    // получаем информацию о складе
    $wh = R::findAll(WAREHOUSE, 'items_id = ?', [$item->id]);
    // получаем весь резерв на данный товар
    $wh_reserv = R::findAll(WH_RESERV, 'WHERE items_id = ?', [$item->id]);

    foreach ($wh_reserv as $line) {
        $item_id = $line['items_id'];
        $order_id = $line['order_uid'];
        $project_id = $line['project_uid'];
        $client_id = $line['client_uid'];
        $reserved_qty = $line['reserved_qty'];

        // функция создания запроса в БД сборка общей таблицы резервирования
        $result = getDataForTable($item_id, $order_id, $project_id, $client_id, $reserved_qty);
        $data = array_merge($data, $result);
    }
}
function getDataForTable($itemId, $orderId, $projectId, $clientId, $reservedQty)
{
    // SQL-запрос с использованием JOIN
    $query = "
        SELECT 
            o.date_in, o.date_out, o.order_amount, o.prioritet, o.id,
            p.projectname, p.revision,
            c.name, c.priority,
            w.storage_shelf, w.storage_box, w.quantity, w.owner_pn
        FROM orders o
        JOIN projects p 
        JOIN customers c 
        JOIN warehouse w 
        WHERE 
            o.id = ?
            AND p.id = ?
            AND c.id = ?
            AND w.id = ?
    ";

    // Выполнение запроса и получение результатов
    $results = R::getAll($query, [$orderId, $projectId, $clientId, $itemId]);

    // Добавление reserved_qty к каждому результату
    foreach ($results as &$result) {
        $result['reserved_qty'] = $reservedQty;
    }

    return $results;
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

        /*I NAVIGATION TABS STYLES */
        .nav-tabs .nav-link:hover {
            background: #0d6efd;
            color: white;
        }

        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
            border-color: #dee2e6 #dee2e6 #fff;
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
<div class="container-fluid my-3 border-top border-bottom">
    <div class="row mt-2 mb-2">
        <div class="col-8 border-end">
            <p>Part name: <b><?= $item['part_name'] ?></b></p>
            <p>Part Value: <b><?= $item['part_value'] ?></b></p>
            <p>Part Type: <b><?= $item['part_type'] ?></b></p>
            <p>Manufacture P/N: <b><?= $item['manufacture_pn'] ?></b></p>
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

<div class="container-fluid border-top pt-3">

    <!--  кнопки переключения между табами -->
    <ul class="nav nav-tabs" role="tablist">
        <!-- Таб 1 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab1') ? 'active' : '' ?>"
                    data-bs-target="#tab1" id="nav-link-1" type="button" role="tab">Reserved item information
            </button>
        </li>
        <!-- Таб 2 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab2') ? 'active' : '' ?>"
                    data-bs-target="#tab2" id="nav-link-2" type="button" role="tab">Warehouse Information
            </button>
        </li>
        <!-- Таб 3 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab3') ? 'active' : '' ?>"
                    data-bs-target="#tab3" id="nav-link-3" type="button" role="tab">Invoices information
            </button>
        </li>
        <!-- Таб 4 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab4') ? 'active' : '' ?>"
                    data-bs-target="#tab4" id="nav-link-4" type="button" role="tab">Item Movements information
            </button>
        </li>
    </ul>

    <!-- ----------------------- Контент Табов ------------------------------ -->
    <div class="tab-content" id="myTabContent">

        <!--  Контент Таба 1 -->
        <div class="tab-pane fade show <?= ($A_T == 'tab1') ? 'active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Amount</th>
                    <th>Application Time</th>
                    <th>Delivery Date</th>
                    <th>Order Prioritet</th>

                    <th>Project Name</th>
                    <th>Version</th>

                    <th>Customer Name</th>
                    <th>Customer Priority</th>

                    <th>Storage Shelf</th>
                    <th>Storage Box</th>
                    <th>Quantity All</th>
                    <th>Owner PN</th>
                    <th>Reserved Quantity</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data as $row) { ?>
                    <tr class='item-list'>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['order_amount'] ?></td>
                        <td><?= $row['date_in'] ?></td>
                        <td><?= $row['date_out'] ?></td>
                        <td><?= $row['prioritet'] ?></td>
                        <td><?= $row['projectname'] ?></td>
                        <td><?= $row['revision'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['priority'] ?></td>
                        <td><?= $row['storage_shelf'] ?></td>
                        <td><?= $row['storage_box'] ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= $row['owner_pn'] ?></td>
                        <td><?= $row['reserved_qty'] ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

        </div>
        <!-- end tab 1 -->

        <!--  Контент Таба 2 -->
        <div class="tab-pane fade show <?= ($A_T == 'tab2') ? 'active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
            <table class="p-3">
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
                        <tr class="item-list">
                            <td><?= $line['owner_pn']; ?></td>
                            <td><?= json_decode($line['owner'])->name; ?></td>
                            <td><?= $line['storage_shelf']; ?></td>
                            <td><?= $line['storage_box']; ?></td>
                            <td><?= $line['storage_state']; ?></td>
                            <td><?= $line['manufacture_date']; ?></td>
                            <td><?= $line['fifo']; ?></td>
                            <td><?= $line['quantity']; ?></td>
                            <td><?= $line['date_in']; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <!-- end tab 2 -->

        <!--  Контент Таба 3 -->
        <div class="tab-pane fade show <?= ($A_T == 'tab3') ? 'active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <table class="p-3">
                <!-- header -->
                <thead>
                <tr>
                    <th>Lot ID</th>
                    <th>Invoice</th>
                    <th>Supplier</th>
                    <th>Owner</th>
                    <th>Arrival QTY</th>
                    <th>Date In</th>
                </tr>
                </thead>
                <!-- table -->
                <tbody>
                <?php
                // сделать переход при клике на строку в просмотр запчасти но с данными только по этому инвойсу
                if (!empty($lots)) {
                    foreach ($lots as $line) { ?>
                        <tr class="item-list">
                            <td><?= $line['lot']; ?></td>
                            <td><?= $line['invoice']; ?></td>
                            <td><?= json_decode($line['supplier'])->name; ?></td>
                            <td><?= json_decode($line['owner'])->name; ?></td>
                            <td><?= $line['quantity']; ?></td>
                            <td><?= $line['date_in']; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <!-- end tab 3 -->

        <!-- Контент Таба 4 -->
        <div class="tab-pane fade show <?= ($A_T == 'tab4') ? 'active' : '' ?>" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
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
        <!-- end tab 4 -->
    </div>
</div>

<?php
/* SCRIPTS */
ScriptContent('view_item');
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // script для переключения табов
        dom.in("click", ".nav-link", function () {
            // Получаем ID целевого таба
            let tabId = this.getAttribute("data-bs-target");
            let targetTab = dom.e(tabId);
            // получаем текущий URL страницы
            let newUrl = new URL(win.location);
            // добавляем номер таба в URL для перезагрузок
            newUrl.searchParams.set('tab', tabId.substring(1));
            // сохраняем этот URL в историю браузера
            history.pushState(null, '', newUrl);
            // Удаляем класс active со всех табов
            dom.removeClass('.nav-link', 'active');
            // Добавляем класс active к текущему табу
            dom.addClass(this, "active");
            // Убираем класс show и active со всех табов
            dom.removeClass('.tab-pane', 'show active');
            // Добавляем классы show и active к целевому табу
            dom.addClass(targetTab, "active show");
        });
    });
</script>
</body>
</html>