<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR]);
require 'warehouse/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'view_item';
$data = [];
$item = null;
// tab by default
$A_T = $_GET['tab'] ?? 'tab1'; // Active Tab

// updating information in to table warehouse, invoice, reserve, movement
if (isset($_POST['item_id']) && isset($_POST['table-name'])) {
    $args = WareHouse::updateRelatedTables($_POST, $user);
}

// формируем данные для вывода на страницу
if (isset($_GET['itemid'])) {
    // получаем товар
    $item = R::load(WH_ITEMS, _E($_GET['itemid']));
    // получаем информацию о приходах
    $lots = R::findAll(WH_INVOICE, 'items_id = ?', [$item->id]);
    // получаем информацию о складе
    $wh = R::findAll(WAREHOUSE, 'items_id = ?', [$item->id]);
    // получаем весь резерв на данный товар
    $wh_reserv = R::findAll(WH_RESERV, 'WHERE items_id = ?', [$item->id]);
    // получаем логирование по данному товару
    $logs = R::findAll(WH_LOGS, 'WHERE items_id = ?', [$item->id]);

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
            w.storage_shelf, w.storage_box, w.quantity, w.owner_pn, w.storage_state, w.wh_types_id
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

        .link-box {
            display: grid;
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
<?php
// NAVIGATION BAR
$navBarData['title'] = 'View Item ID: ' . $item->id;
$navBarData['active_btn'] = Y['STOCK'];
$navBarData['page_tab'] = $_GET['page'] ?? null;
$navBarData['record_id'] = $item->id;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid my-3 border-top border-bottom">
    <div class="row mt-2 mb-2">
        <div class="col-4">
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

        <div class="col-4 border-end">
            <?php
            // Функция создания ссылок на новые парт номера
            function linkCreation(array $mfn): string
            {
                $li = '';
                foreach ($mfn as $l) {
                    $li .= '<a href="https://www.google.com/search?q=' . $l . '&ie=UTF-8" target="_blank">Google <b>' . $l . '</b></a>';
                    $li .= '<a href="https://octopart.com/search?q=' . $l . '&currency=USD&specs=0" target="_blank">Octopart <b>' . $l . '</b></a>';
                }
                return $li;
            }

            $mfn = explode(',', $item['manufacture_pn']);
            if (!empty($mfn)) {
                $link = linkCreation($mfn);
            }
            ?>
            <div class="link-box">
                <h4>Links to items</h4>
                <?= $link ?>
            </div>

            <div class="mt-2">
                <label for="dsription">Description</label>
                <textarea id="dsription" readonly class="form-control"><?= $item['description'] ?></textarea>
            </div>
            <div class="mt-2">
                <label for="notes">Notice</label>
                <textarea id="notes" readonly class="form-control"><?= $item['notes'] ?></textarea>
            </div>
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

<h1 class="m-2">Additional information for this Item</h1>
<div class="container-fluid border-top pt-3">

    <!--  кнопки переключения между табами -->
    <ul class="nav nav-tabs" role="tablist">
        <!-- Таб 1 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab1') ? 'active' : '' ?>"
                    data-bs-target="#tab1" id="nav-link-1" type="button" role="tab">Warehouse Information
            </button>
        </li>
        <!-- Таб 2 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab2') ? 'active' : '' ?>"
                    data-bs-target="#tab2" id="nav-link-2" type="button" role="tab">Reserved item information
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
            <table class="p-3">
                <!-- header -->
                <thead>
                <tr>
                    <th>Owner P/N</th>
                    <th>Owner</th>
                    <th>Warehouse Type</th>
                    <th>Shelf</th>
                    <th>Box</th>
                    <th>Storage State</th>
                    <th>Mnf. Date</th>
                    <th>Expaire Date</th>
                    <th>Actual QTY</th>
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
                            <td data-name="table-name" class="hidden"><?= WAREHOUSE ?></td>
                            <td data-name="item_id" class="hidden"><?= $line['id']; ?></td>
                            <td data-name="owner_id" class="hidden"><?= json_decode($line['owner'])->id; ?></td>

                            <td data-name="owner_pn"><?= $line['owner_pn']; ?></td>
                            <td class="text-primary"><?= json_decode($line['owner'])->name; ?></td>
                            <td class="text-primary"><?= R::load(WH_TYPES, $line['wh_types_id'])->type_name; ?></td>
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
        </div>
        <!-- end tab 1 -->

        <!--  Контент Таба 2 -->
        <div class="tab-pane fade show <?= ($A_T == 'tab2') ? 'active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
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
                        <td data-name="table-name" class="hidden"><?= WH_RESERV ?></td>
                        <td data-name="item_id"><?= $row['id'] ?></td>
                        <td data-name="order-amount"><?= $row['order_amount'] ?></td>
                        <td data-name="date_in"><?= $row['date_in'] ?></td>
                        <td data-name="date_out"><?= $row['date_out'] ?></td>
                        <td data-name="prioritet"><?= $row['prioritet'] ?></td>
                        <td data-name="projectname"><?= $row['projectname'] ?></td>
                        <td data-name="revision"><?= $row['revision'] ?></td>
                        <td data-name="name"><?= $row['name'] ?></td>
                        <td data-name="priority"><?= $row['priority'] ?></td>
                        <td data-name="storage_shelf"><?= $row['storage_shelf'] ?></td>
                        <td data-name="storage_box"><?= $row['storage_box'] ?></td>
                        <td data-name="quantity"><?= $row['quantity'] ?></td>
                        <td data-name="owner_pn"><?= $row['owner_pn'] ?></td>
                        <td data-name="reserved_qty"><?= $row['reserved_qty'] ?></td>
                    </tr>
                <?php } ?>
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
                            <td data-name="table-name" class="hidden"><?= WH_INVOICE ?></td>
                            <td data-name="item_id" class="hidden"><?= $line['id']; ?></td>
                            <td data-name="supplier_id" class="hidden"><?= json_decode($line['supplier'])->id; ?></td>
                            <td data-name="owner_id" class="hidden"><?= json_decode($line['owner'])->id; ?></td>

                            <td data-name="lot"><?= $line['lot']; ?></td>
                            <td data-name="invoice"><?= $line['invoice']; ?></td>
                            <td data-name="supplier"><?= json_decode($line['supplier'])->name; ?></td>
                            <td data-name="owner"><?= json_decode($line['owner'])->name; ?></td>
                            <td data-name="quantity"><?= $line['quantity']; ?></td>
                            <td data-name="date_in"><?= $line['date_in']; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>

        </div>
        <!-- end tab 3 -->

        <!-- Контент Таба 4 no changes table data -->
        <div class="tab-pane fade show <?= ($A_T == 'tab4') ? 'active' : '' ?>" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
            <table class="p-3">
                <!-- header -->
                <thead>
                <tr>
                    <th>Item Id</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Date In</th>
                </tr>
                </thead>
                <!-- table -->
                <tbody>
                <?php
                // fixme сделать переход при клике на строку в просмотр запчасти но с данными только по этому инвойсу
                if (!empty($logs)) {
                    foreach ($logs as $n => $line) {
                        echo '<tr  class="text-primary">';
                        echo '<td>' . $line['items_id'] . '</td>';
                        echo '<td>' . $line['action'] . '</td>';
                        echo '<td>' . $line['user_name'] . '</td>';
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

    <button id="saveChanges" type="button" class="btn btn-success mt-5" style="display: none">Save Changes</button>

    <!-- editing table rows form -->
    <form id="hiddenForm" style="display: none;" action="" method="post">
        <!-- the fields was added automaticaly -->
    </form>
</div>

<!-- Модальное окно -->
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

<?php
// если пользователю разрешено редактировать данные таблиц warehouse, invoice, reserve, movements
//  присвоение данного статуса требует повторной авторизации пользователя!!!
if ($user['can_change_data']) {
    echo '<div id="isUserCanChangeData" class="hidden">approved</div>';
}

// FOOTER
footer($page);
// SCRIPTS
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const changedRows = new Set();

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

            // Удаляем атрибут id со всех таблиц
            document.querySelectorAll('.tab-pane table').forEach(table => {
                table.removeAttribute('id');
            });

            // Добавляем атрибут id к таблице в активном табе
            if (tabId !== "#tab4") {
                let targetTable = targetTab.querySelector('table');
                let check_user = dom.e("#isUserCanChangeData");
                if (targetTable && check_user) {
                    targetTable.setAttribute('id', 'items-table');
                    // Вызываем функцию для добавления слушателя событий
                    addListenerAfterIdChange();
                }
            }
        });

        // добавляем событие клик на кнопку сохранить данные при изменениях в таблицах
        dom.in("click", "#saveChanges", function () {
            const confirmation = confirm("Are you sure you want to change the data? Changes can have irreversible consequences and lead to problems.");

            if (confirmation) {
                let form = dom.e('#hiddenForm');

                // Очистка предыдущих полей формы
                form.innerHTML = '';

                changedRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    cells.forEach((cell) => {
                        const dataName = cell.getAttribute('data-name');
                        if (dataName) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = dataName;
                            input.value = cell.textContent;
                            form.appendChild(input);
                        }
                    });
                });

                // Отправка формы
                //form.submit();
            } else {
                // Пользователь нажал отмена, форма не отправляется
                alert("Changes will not be saved!");
                dom.hide("#saveChanges");
            }
        });

        // двойное нажатие на строке в таблице склада позволяет редактировать данные в этом поле
        // после редактирования можно сохранить данные в БД с помощью кнопки сохранить
        function addListenerAfterIdChange() {
            const table = document.getElementById('items-table');
            table.addEventListener('dblclick', function (e) {
                const target = e.target;
                if (target.tagName === 'TD') {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = target.textContent;
                    input.style.width = target.clientWidth + 'px'; // Размер как у ячейки
                    input.addEventListener('blur', function () {
                        target.textContent = this.value;
                        dom.show("#saveChanges"); // Активировать кнопку при изменении
                        changedRows.add(target.closest('tr')); // Добавляем строку в набор изменённых строк
                    });
                    input.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                            this.blur();
                        }
                    });
                    target.textContent = '';
                    target.appendChild(input);
                    input.focus();
                }
            });
        }

        // Проверка localStorage для определения, было ли показано модальное окно
        if (!localStorage.getItem('popupDisplayed')) {
            showModal();
        }
    });

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