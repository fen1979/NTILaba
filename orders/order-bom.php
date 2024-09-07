<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');

/*
 * произвести поиск по складу согласно запчастям, сопоставить полученные значения с нуждами заказа по проекту,
 * вывести доступное количество и количество нужное для проекта/заказа, обозначить недостачу по запчастям
 * в таблице должно быть поле добавления полученноо количества от клиента по запчастям для проекта,
 * при добавлении количества после сохранения добавить данное количество на склад,
 * если запчасть в наличии и не предоставлялась дополнительно клиентом то оставить исходное значение,
 * после начала работ над заказом отнять получившееся количество запчастей по проекту от количества на складе и сохранить склад.
 *
 *
 * полученные запчасти для любого проекта добавляются отдельно на склад через форму добавления товара!
 * после создания проекта в проекте создается ВОМ для возможности формировать заказы и вести учет на складе,
 * товар можно добавлять на склад в любое время,
 * сделать так что бы при выборке заказов на странице заказов проверялся статус запчастей и при наличии всех частей
 * появлялась возможность менять статусы заказов, по умолчанию после создания заказа при отсутствии з/ч статус "ожидает з/ч"
 *
 *
 * продумать вариант вывода таблицы материалов нужных для проекта с внесенными данными исходя из проектного БОМА
 * с нужным количеством при данном заказе, продумать вывод если проект не создан еще а заказ с зп уже пришел
 * сделать крос проверку если такие запчасти есть в бд то вывести наличие даннных зп к этому заказу
 * если зп не внесену на склад то предоставить поле для внесения количества согласно макату присланного от клиента
 * если макат не совпадает но зп внесена в бд ??? продумать
 * при сохранении вывести таблицу итогов е если все зп в наличии то перевести заказ в статус готов к работе
 * если зп нет или не хватает перревести заказ в статус ожидает запчастей
 *
 * */
$page = 'order_bom';
$order = $project = $projectBom = $client = $settings = null;
$viewButtons = $emptyProjectBom = false;
/* выборка данных из БД для вывода пользователю */
if (isset($_GET['orid']) && isset($_GET['pid'])) {
    $order = R::load(ORDERS, _E($_GET['orid']));
    $project = R::load(PROJECTS, _E($_GET['pid']));
    $projectBom = R::findAll(PROJECT_BOM, 'projects_id = ?', [_E($_GET['pid'])]);
    $client = R::load(CLIENTS, _E($order->customers_id));
    // приходим из /order-details
    if (isset($_GET['tab'])) {
        $viewButtons = true;
    }

    /* настройки вывода от пользователя */
    if ($user) {
        foreach ($user['ownSettingsList'] as $item) {
            if (isset($item['table_name']) && $item['table_name'] == PROJECT_BOM) {
                $settings = json_decode($item['setup']);
                break;
            }
        }
    }
    // if bom empty or not created yet
    $emptyProjectBom = empty($projectBom) ?? true;
}

// TODO
/* установка разрешения собрать заказ частично */
/* дбавить алгоритм работы с частично заказанными заказами */
/* после окончания данного этапа отправить в статус ждет запчастей!!! */
if (isset($_POST['approved-for-work'])) {
    $order = R::load(ORDERS, _E($_POST['approved-for-work']));
    $order->pre_assy = 1; // Partial Assembly Allowed
    R::store($order);
    redirectTo("order/preview?orid={$_GET['orid']}&tab=tab1");
}

/* добавка части к ВОМ и составление таблицы материалов которые есть для ЗАКАЗ НАРЯДА */

// если запчасти нет то добавляем ее через меню прихода новой запчасти
// если запчасть есть то добавляем новый приход на склад с сопутствующими документами
// изменить данные для работы с существующими запчастями
if (isset($_POST['import_qty']) && isset($_POST['item_id'])) {
    $item_id = _E($_POST['item_id']);
    try {
        if (WareHouse::updateQuantityForItem($_POST, $user)) {
            $consignment = "consignment={$_POST['consignment']}";
            $item_id = "item-id={$_POST['item_id']}";
            $qty = "qty={$_POST['import_qty']}";
            $backLink = "orid={$_GET['orid']}&pid={$_GET['pid']}";
            redirectTo("arrivals?new-item&$consignment&$item_id&$qty&$backLink");
        }
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Something is gone wrong! ' . $e->getMessage(), 'danger');
    }
}

$settings = getUserSettings($user, PROJECT_BOM);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .container-fluid {
            padding: 0 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: .8em;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            background-color: #9ac2c3;
            color: #000000;
            padding: .4em;
            z-index: 100;
            top: 5%;
        }

        td {
            cursor: pointer;
            padding-left: 3px;
            text-align: center;
            vertical-align: middle;
            word-break: break-word; /* Обеспечивает перенос слов внутри ячейки */
        }

        th, td {
            border: 1px solid #ddd;
        }

        form {
            display: flex;
            gap: 5px; /* Расстояние между элементами формы */
            align-items: center;
        }

        input {
            height: 38px; /* Высота элементов формы */
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        input[type="text"] {
            flex-grow: 1; /* Поля ввода занимают доступное пространство */
        }
    </style>

</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['user' => $user, 'page_name' => $page]); ?>

<main class="container-fluid">
    <!-- PROJECT/ ORDER INFORMATION -->
    <div class="row p-3" style="background: #5f9ea0a1;">
        <?php $t = 'Page for checking spare parts sent by the client and/or in stock.'; ?>
        <div class="col-2"><h3><small><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i></small> &nbsp;BOM Inspection</h3></div>
        <div class="col-2">
            Project name: <span class="text-primary"><?= $order['project_name']; ?></span>
        </div>
        <div class="col-2">
            revision: <span class="text-primary"><?= $order['project_revision']; ?></span>
        </div>
        <div class="col-2">
            Client name: <span class="text-primary"><?= $order['customer_name']; ?></span>
        </div>
        <div class="col-2">
            Contact name: <span class="text-primary"><?= $client['contact']; ?></span>
        </div>
        <div class="col-2">
            <h3>Order ID: &nbsp;
                <span class="text-danger"> <?= $_GET['orid'] ?? ''; ?></span>
            </h3>
        </div>
    </div>
    <?php
    if ($viewButtons) {
        $url = "/order/preview?orid={$_GET['orid']}&tab=tab1";
        ?>
        <div class="row mt-2">
            <div class="col-auto">

                <div class="d-flex">
                    <?php if (!$emptyProjectBom): ?>
                        <form action="" method="post">
                            <button type="submit" class="btn btn-outline-warning me-2" name="approved-for-work" value="<?= $_GET['orid']; ?>">
                                Approve to work without spare parts
                            </button>
                        </form>
                    <?php endif; ?>
                    <a role="button" class="btn btn-outline-primary" href="<?= $url; ?>">Back to order details</a>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- ВЫВОД ДАННЫХ ПОСЛЕ СОХРАНЕНИЯ ЗАПЧАСТИ В БД -->
    <div class="container-fluid">
        <table id="itemTable">
            <!-- header -->
            <thead>
            <tr class="border-bottom info-1" style="white-space: nowrap">
                <?= CreateTableHeaderUsingUserSettings($settings, 'itemTable', PROJECT_BOM,
                    '<th>Actual QTY</th>' .
                    '<th>Consignment ID and Incoming QTY <b class="text-danger">Required Fields!!!</b></th>') ?>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            if ($projectBom && $settings) {
                foreach ($projectBom as $line) {
                    $required_qty = $line['amount'] * $order['order_amount'];
                    echo $actual_qty = WareHouse::GetActualQtyForItem($line['customerid'], $line['item_id'] ?? '');
                    if ($actual_qty > $required_qty) {
                        $color = 'success';
                    } elseif ($actual_qty < $required_qty) {
                        $color = 'danger';
                    } ?>
                    <tr class="<?= $color ?> border-bottom">
                        <?php foreach ($settings as $item => $_) {
                            echo "<td>$line[$item]</td>";
                        } ?>
                        <td><?= $actual_qty ?? 0; ?></td>
                        <td>
                            <form action="" method="post">
                                <input type="text" name="consignment" value="<?= set_value('consignment') ?>" placeholder="Write consignment ID" required>
                                <input type="number" name="import_qty" required placeholder="Write Quantity">
                                <button type="submit" class="btn btn-outline-primary" name="item_id" value="<?= $line['id']; ?>">Add</button>
                            </form>
                        </td>
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>
        <!-- if order or project BOM isnt exist yet -->
        <div class="align-middle row mt-3">
            <div class="col-12">
                <?php if (empty($projectBom)): ?>
                    <h3>Information on the available parts to create this project has not yet to be entered!</h3>
                <?php else: ?>
                    <h3>Want to add details? go to the page for adding details for this project: <?= $order['project_name']; ?></h3>
                <?php endif; ?>
                <br>
                <?php $url = "check_part_list?mode=editmode&back-id={$_GET['orid']}&pid={$_GET['pid']}"; ?>
                <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary">
                    Do you want to enter information?
                </button>
            </div>
        </div>
    </div>

</main>
<?php PAGE_FOOTER($page); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // скрываем столбцы в которых нет данных
        dom.hideEmptyColumnsInTable("#itemTable");
    });
</script>
</body>
</html>
