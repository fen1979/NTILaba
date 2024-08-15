<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
include_once 'orders/Orders.php';
$page = 'order';
$orderid = null;

/* resetup filters by user and status */
if (isset($_POST['filter-by-status']) || isset($_POST['filter-by-user']) || isset($_POST['filter-by-client'])) {
    $args = Orders::changeFilters($_POST, $_SESSION['userBean']);
}

/* получение пользователя из сессии после изменения фильтров */
$thisUser = $_SESSION['userBean'];
$role = $thisUser['app_role'];

/* PUT ORDER TO ARCHIVE */
if (isset($_POST['password']) && isset($_POST['idForUse'])) {
    $args = Orders::archiveOrExtractOrder($_POST, $thisUser);
}

/* настройки вывода от пользователя */
$settings = getUserSettings($thisUser, ORDERS);

// Параметры пагинации
//$conditions = ['query' => 'warehouses_id = ?', 'data' => $wh_type];
list($pagination, $paginationButtons) = PaginationForPages($_GET, $page, ORDERS);

/* вывод заказов после фильтрации по статусу заказа, работникам и клиенту */
$orderData = Orders::getOrdersByFilters($thisUser['filterby_status'], $thisUser['filterby_client'], $pagination);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
</head>
<body>
<?php
// NAVIGATION BAR
//$navBarData['title'] = '';
$navBarData['active_btn'] = Y['ORDER'];
//$navBarData['page_tab'] = $_GET['page'] ?? null;
//$navBarData['record_id'] = $item->id ?? null;
$navBarData['user'] = $thisUser;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="main-container">
    <main class="container-fluid content">
        <!-- ORDER FILTERS -->
        <div class="row mb-3 p-2 info-1">
            <?php
            $viewByUser = $viewByStatus = '';
            $devider = '&nbsp;<b class="text-danger">|</b>&nbsp;';
            ?>
            <!-- фильтр по статусу заказа -->
            <div class="col-1">
                <form action="" method="POST" class="form me-2" id="byStatus">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Statuses
                        </button>
                        <ul class="dropdown-menu p-2">
                            <?php
                            if (isset($thisUser['filterby_status'])) {
                                $s = explode(',', $thisUser['filterby_status']);
                                foreach (SR::getAllResourcesInGroup('status') as $key => $status) {
                                    $checked = '';
                                    if (in_array($key, $s)) {
                                        $checked = 'checked';
                                        $viewByStatus .= $status . $devider;
                                    }
                                    ?>
                                    <li class="form-check dropdown-item">
                                        <input type="checkbox" id="cb-<?= $key; ?>" name="status[]" value="<?= $key; ?>" <?= $checked; ?> class="form-check-input">
                                        <label class="form-check-label" for="cb-<?= $key; ?>"> <?= SR::getResourceValue('status', $key); ?></label>
                                    </li>
                                    <?php
                                }
                            } ?>
                            <li>
                                <button type="submit" name="filter-by-status" class="success filter-btn">set filter</button>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>

            <!-- фильтр по работнику компании -->
            <div class="col-1">
                <form action="" method="POST" class="form me-2" id="byUser">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Workers
                        </button>
                        <ul class="dropdown-menu p-2">
                            <?php
                            $s = explode(',', $thisUser['filterby_user']);
                            foreach (R::find(USERS) as $key => $u) {
                                if ($u['id'] != '1') {
                                    $checked = '';
                                    if (in_array($u['user_name'], $s)) {
                                        $checked = 'checked';
                                        $viewByUser .= $u['user_name'] . $devider;
                                    }
                                    ?>
                                    <li class="form-check dropdown-item">
                                        <input type="checkbox" id="u-<?= $key; ?>" name="users[]" value="<?= $u['user_name']; ?>" <?= $checked; ?>
                                               class="form-check-input">
                                        <label class="form-check-label" for="u-<?= $key; ?>"><?= $u['user_name']; ?></label>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                            <li>
                                <button type="submit" name="filter-by-user" class="success filter-btn">set filter</button>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>

            <!-- фильтр по клиенту компании -->
            <div class="col-1">
                <form action="" method="POST" class="form me-2" id="byClient">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Clients
                        </button>
                        <ul class="dropdown-menu p-2">
                            <?php
                            foreach (R::find(CLIENTS) as $cl) {
                                $checked = ($cl['name'] == $thisUser['filterby_client']) ? 'checked' : '';
                                ?>
                                <div class="radio-item">
                                    <label>
                                        <input type="radio" name="clients" value="<?= $cl['name']; ?>" <?= $checked; ?>>&nbsp;
                                        <?= $cl['name']; ?>
                                    </label>
                                </div>
                            <?php } ?>
                            <li>
                                <button type="submit" name="filter-by-client" class="success filter-btn">set filter</button>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>

            <!-- кнопки сброса фильтров и приорити -->
            <div class="col-2">
                <!-- сброс фильтров  -->
                <button id="resetFilters" class="btn btn-outline-warning me-2">
                    Reset
                    <i class="bi bi-filter"></i>
                </button>

                <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                    <!-- вывод таблицы данных xlsx для приорити по фильтрам -->
                    <button class="btn btn-outline-secondary" type="button" id="priority-out-data">
                        Priority
                        <i class="bi bi-filetype-xml"></i>
                    </button>
                <?php } ?>
            </div>

            <!-- Вывод данных по фильтрам -->
            <div class="col secondary text-white rounded">
                <div class="row">
                    <div class="col">
                        By Client
                    </div>
                    <div class="col">
                        By Worker
                    </div>
                    <div class="col">
                        By Status
                    </div>
                </div>

                <div class="row text-primary">
                    <!-- данные по фильтру "Клиенты"-->
                    <div class="col">
                        <?= ($thisUser['filterby_client'] != 'all') ? $thisUser['filterby_client'] : ''; ?>
                    </div>
                    <!-- данные по фильтру "работник компании" -->
                    <div class="col">
                        <small><?= $viewByUser ?? ''; ?></small>
                    </div>
                    <!-- данные по фильтру "статус" -->
                    <div class="col">
                        <small><?= $viewByStatus ?? ''; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ORDERS TABLE -->
        <div class="table-responsive">
            <table id="orders-table">
                <thead>
                <tr class="border-bottom info-1" style="white-space: nowrap">
                    <?= CreateTableHeaderUsingUserSettings($settings, 'orders-table', ORDERS, '<th scope="col">Actions</th>') ?>
                </tr>
                </thead>
                <tbody id="searchAnswer">
                <?php
                $byUser = explode(',', $thisUser['filterby_user']);
                $orders_ids = $customer_name = $priority_id = '';
                foreach ($orderData as $order) {
                    $workers = explode(',', $order['workers']);
                    $progress = Orders::getOrderProgress($order);
                    /* filter by user */
                    if (!empty(array_intersect($workers, $byUser)) || $byUser[0] == 'all') {

                        /* формируем приорити данные для заказов ID заказов через запятую добавляем в скрытую форму
                        а при клике на кнопку дай приорити открываем новую страницу и формируем там таблицу типа эксель */
                        $orders_ids .= $order['id'] . ',';
                        $customer_name = $order['customer_name'];
                        $priority_id = $order['client_priority'];
                        ?>
                        <tr class="align-middle order-row border-bottom">
                            <?php
                            if ($settings) {
                                // creating table using user settings
                                $k = 0;
                                foreach ($settings as $item => $_) {
                                    $click = ($k === 0 && (in_array($thisUser['user_name'], $workers) ||
                                            isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR]))) ? ' onclick="getInfo(' . $order['id'] . ')"' : '';
                                    if ($item == 'status') {
                                        // status colorise bg and play text
                                        $color = SR::getResourceDetail('status', $order[$item]);
                                        echo '<td class="border-end ' . $color . '"' . $click . '>' . SR::getResourceValue('status', $order[$item]) . '</td>';

                                    } elseif ($item == 'prioritet') {
                                        // prioritet colorise bg
                                        $c = strtolower($order[$item]);
                                        echo '<td class="border-end ' . $c . '"' . $click . '>' . $order[$item] . '</td>';

                                    } elseif ($item == 'order_progress') {
                                        // order progress preview
                                        echo '<td class="border-end"' . $click . '>' . $progress . '</td>';

                                    } else {
                                        // regular tab cel
                                        echo '<td class="border-end"' . $click . '>' . $order[$item] . '</td>';
                                    }
                                    $k++;
                                }
                            }

                            // i buttons for some actions like delete, edite, & edit BOM
                            if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                                <td>
                                    <?php
                                    if ($order['status'] == 'st-111' || $order['status'] == 'st-222') {
                                        $c = ($order['status'] == 'st-222') ? 'btn-outline-info' : 'btn-outline-dark';
                                        $t = ($order['status'] == 'st-222') ? 'Dearchivate Order' : 'Archivate Order';
                                        ?>
                                        <button type="button" data-orid="<?= $order['id']; ?>" class="archive-order btn <?= $c ?>" data-title="<?= $t ?>">
                                            <i class="bi bi-archive" data-orid="<?= $order['id']; ?>"></i>
                                        </button>
                                    <?php } ?>

                                    <?php $url = "check_bom?orid={$order['id']}&pid={$order['projects_id']}"; ?>
                                    <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary" data-title="Edit order BOM">
                                        <i class="bi bi-card-list"></i>
                                    </button>
                                    <?php $url = "edit-order?edit-order&orid={$order['id']}&pid={$order['projects_id']}"; ?>
                                    <button type="button" value="<?= $url; ?>" class="url btn btn-outline-warning" data-title="Edit Order">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                </td>
                            <?php } ?>
                        </tr>
                        <?php
                    } // if(filter-by-user)
                } //foreach(orders)

                // форма вывода данных для таблицы приорити
                ?>
                <tr class="hidden">
                    <td>
                        <form action="/priority-out" method="GET" target="_blank" id="priority-form" class="hidden">
                            <input type="hidden" name="order-ids" value="<?= $orders_ids; ?>">
                            <input type="hidden" name="customer-name" value="<?= $customer_name; ?>">
                            <input type="hidden" name="priority-id" value="<?= $priority_id; ?>">
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <?php
        /* POPUP CHAT WINDOW */
        ShowGroupChatPopup($page, $thisUser);
        ?>
    </main>

    <!-- pagination buttons -->
    <?= $paginationButtons; ?>

    <!--  модальное окно форма для архивирования заказа  -->
    <div class="modal show" id="archive_order" style="backdrop-filter: blur(15px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Заголовок модального окна -->
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Archivate Order</h5>
                    <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
                </div>

                <!-- Содержимое модального окна -->
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <input type="hidden" class="form-control" id="idForUse" name="idForUse">
                        </div>
                        <button type="submit" name="archivation" class="btn btn-primary">Archivate Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <?php footer($page); ?>
</div>

<!-- Hidden form to submit if changes are detected -->
<form id="has_changes" class="hidden" action="" method="post">
    <input type="hidden" name="reaction_to_changes_in_db" value="1">
    <input type="hidden" id="uid" name="uid" value="<?= $thisUser['id']; ?>">
    <?php
    if (isset($_POST['reaction_to_changes_in_db']) && $_POST['reaction_to_changes_in_db'] == '1')
        echo '<span id="play-song" class="hidden"></span>';

    if (!empty($thisUser['sound']) && $thisUser['sound'] != '0') {
        echo getNotificationSound($thisUser['sound']);
    }
    ?>
</form>

<!-- JAVASCRIPTS -->
<?php ScriptContent($page); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.onDBChangeListener('#play-song', '#notificationSound', "#uid");
    });
</script>
</body>
</html>