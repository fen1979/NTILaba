<?php
// this if uses from ajax request it need to be first on page!!!!
if (isset($_POST['suggest'])) {
    include_once 'Orders.php';
    exit((Orders::makeXLSXfileAndSave($_POST['suggest'], null)) ? 'success' : 'error');
}

// rest page functions and includes
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean', null, 'order');
include_once 'orders/Orders.php';

/* получение пользователя из сессии */
$role = $user['app_role'];
$page = 'order_details';

// tab by default
$A_T = $_GET['tab'] ?? 'tab1'; // Active Tab
/* получение ID заказа */
$orderid = $_GET['orid'];

/* set list users and status to order */
if (isset($_POST['set-order-status']) || isset($_POST['set-order-user'])) {
    Orders::setStatusOrUserInOrder($user, $orderid, $_POST);
}

/* editing or deleteng message from chat */
if (isset($_POST['editChatMessage']) || isset($_POST['deleteChatMessage'])) {
    Orders::editOrDeleteMessage($_POST, $user, $orderid);
    $A_T = 'tab7';
}

/* save message to chat from users or system */
if (isset($_POST['save-message']) && isset($_POST['messageText'])) {
    /* checking if user uploaded good file */
    if (!empty($_FILES['chatFile']['name'][0])) {
        if (!Orders::getFileExtension($_FILES['chatFile'])) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('File format is wrong! Only jpg, png, webp, mp4, pdf, csv, xls, xlsx, doc, txt, zip, rar, 7z 
             files is approved!', 'danger');
        } else {
            if (Orders::checkSizeOfFile($_FILES['chatFile'])) {
                Orders::saveChatMessage($orderid, $user, $_POST, $_FILES['chatFile']);
            } else {
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('File wery big! try another file.', 'danger');
            }
        }
    } else {
        Orders::saveChatMessage($orderid, $user, $_POST);
    }
    $A_T = 'tab7';
}

// ORDER DETAILS INIT
$projectid = $order = $customer = null;
$project = $stepsData = $projectBom = null;
$orderChat = $chatLastMsg = $assy_in_progress = null;
$amount = 0;

/* getting all needed information for all tabs */
if (isset($_POST['orid']) || isset($orderid)) {
    $order = R::load(ORDERS, $orderid);
    $projectid = $order['projects_id'];
    $amount = $order['order_amount'];
    $reserve = R::count(WH_RESERV, 'WHERE order_uid = ?', [$order->id]); // find if exist reserved BOM items
    $customer = R::load(CLIENTS, $order->customers_id);
    $project = R::load(PROJECTS, $projectid);

    // tab 6 steps old
    $stepsData = R::findAll(PROJECT_STEPS, 'projects_id = ? ORDER BY step ASC', [$projectid]);
    // tab 6 steps new
    $unit_staps = R::find(PROJECT_STEPS, "projects_id LIKE ? ORDER BY CAST(step AS UNSIGNED) ASC", [$projectid]);

    $projectBom = R::findAll(PROJECT_BOM, 'projects_id = ?', [$projectid]);
    $orderChat = R::findAll(ORDER_CHATS, 'orders_id = ? ORDER BY time_in ASC', [$orderid]);
    $chatLastMsg = R::findOne(ORDER_CHATS, 'orders_id = ? ORDER BY time_in DESC', [$orderid]);
    $assy_in_progress = R::findOne(ASSY_PROGRESS, 'orders_id = ? AND users_id = ? AND workend = ?', [$orderid, $user['id'], '0']);
}

////  order flow switch CREATE ORDER WORK FLOW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $actionKey = ''; // Переменная для хранения активного ключа

// Перечисляем возможные ключи операций
    $keys = [
        'order-progress-init',
        'backToWork',
        'take-a-step-to-work',
        'next_step',
        'back_to_previos',
        'skip_this_step',
        'forward_step_to_user',
        'validate_step',
        'repite_order',
        'set_on_pause',
        'complete_order',
        'smt_component'
    ];

// Определяем, какой ключ присутствует в $_POST
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $actionKey = $key;
            break;
        }
    }

// Используем switch для обработки найденного ключа
    switch ($actionKey) {
        case 'order-progress-init':
        case 'repite_order':
            $action = 'initiation';
            break;

        case 'backToWork':
        case 'take-a-step-to-work':
        case 'next_step':
        case 'back_to_previos':
        case 'skip_this_step':
        case 'forward_step_to_user':
        case 'validate_step':
        case 'smt_component':
            $action = 'continue';
            break;

        case 'set_on_pause':
            if ($_POST['set_on_pause'] == $orderid) {
                // worker set order assembling to pause by some reasons
                // переводим заказ в статус st-6 Order on Pause
                // оставляем все шаги и данные такими же не меняя ничего
                // возвращаем пользователя на страницу заказов
                // вернуть заказ в работу может только Администратор
                // возврат в работу должен быть в статус st-8 !!!!
                $data['status'] = 'st-6';
                $data['set-order-status'] = '1';
                Orders::setStatusOrUserInOrder($user, $orderid, $data);
                // reload page
                redirectTo('order');
            }
            break;

        case 'complete_order':
            if ($_POST['complete_order'] == $orderid) {
                // worker compliting the order
                // переводим заказ в статус st-4 Waiting Inspection
                // возвращаем пользователя на страницу заказов
                $data['status'] = 'st-4';
                $data['set-order-status'] = '1';
                Orders::setStatusOrUserInOrder($user, $orderid, $data);
                // reload page
                redirectTo('order');
            }
            break;

        default:
            break;
    }

    $tab = $A_T;
    if (!empty($action))
        $args = Orders::OrderAssemblyProcess($order, $project, $stepsData, $user, $_POST, $action);

    // дальнейший код
    if (!empty($args['tab'])) {
        $tab = $args['tab']; // переход на страницу для действий
        $sid = !empty($args['step_id']) ? '&#sid-' . $args['step_id'] : ''; // возврат к последнему шагу

        // вывод информации об ошибках и успехах
        redirectTo("order/preview?orid=$orderid&tab=tab$tab" . $sid, $args);
    }
}

// reserving bom for order
if (isset($_POST['do-reserve-bom'])) {
    Orders::ReserveBomForOrder($user, $_GET, $projectBom, $reserve);
    $reserve = $reserve == 0 ? 1 : $reserve;
}

// unreserving bom for order
if (isset($_POST['do-unreserve-bom'])) {
    Orders::UnReserveBomForOrder($user, $_GET, $projectBom);
    $reserve = 0;
}
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page); ?>
    <style>
        #nav-items .nav-item {
            border: 1px solid #0d6efd;
            border-radius: 4px;
            margin-right: 1px;
        }
    </style>
</head>
<body>
<form action="" id="routing" method="post" class="hidden"></form>

<div class="container-fluid p-2" style="height: 95vh;">
    <!--  заголовок окна -->
    <div class="header-line mb-4" style="border-bottom: 2px solid black">
        <!-- Кнопка Закрыть окно -->
        <button type="button" class="btn btn-danger closeButton">Back to Orders</button>

        <h4>
            Order ID: <small><?= $order->id ?></small>
            &nbsp;&nbsp;
            Unit: <small><?= $project['projectname']; ?></small>
            &nbsp;&nbsp;
            <b class="text-danger">Amount: </b>
            <?= $amount; ?>
        </h4>
        <?php LANGUAGE_BUTTONS(); ?>
        <!-- Кнопка Закрыть окно -->
        <button type="button" class="btn btn-danger closeButton">Back to Orders</button>
    </div>

    <!--  кнопки переключения между табами -->
    <ul class="nav nav-tabs" role="tablist" id="nav-items">
        <!-- Таб 1 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab1') ? 'active' : '' ?>"
                    data-bs-target="#tab1" id="nav-link-1" type="button" role="tab">Order Info
            </button>
        </li>
        <!-- Таб 2 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab2') ? 'active' : '' ?>"
                    data-bs-target="#tab2" id="nav-link-2" type="button" role="tab">Order BOM
            </button>
        </li>
        <!-- Таб 3 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab3') ? 'active' : '' ?>"
                    data-bs-target="#tab3" id="nav-link-3" type="button" role="tab">Tools
            </button>
        </li>
        <!-- Таб 4 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab4') ? 'active' : '' ?>"
                    data-bs-target="#tab4" id="nav-link-4" type="button" role="tab">Project Docs
            </button>
        </li>
        <!-- Таб 5 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab5') ? 'active' : '' ?>"
                    data-bs-target="#tab5" id="nav-link-5" type="button" role="tab">Project BOM
            </button>
        </li>
        <!-- Таб 6 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab6') ? 'active' : '' ?>"
                    data-bs-target="#tab6" id="nav-link-6" type="button" role="tab">Project Steps
            </button>
        </li>
        <!-- Таб 7 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab7') ? 'active' : '' ?>"
                    data-bs-target="#tab7" id="nav-link-7" type="button" role="tab">Chat Log
            </button>
        </li>
        <!-- Таб 8 -->
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($A_T == 'tab8') ? 'active' : '' ?>"
                    data-bs-target="#tab8" id="nav-link-8" type="button" role="tab">Instructions for assembling the unit
            </button>
        </li>
    </ul>

    <!-- ----------------------- Контент Табов ------------------------------ -->
    <div class="tab-content" id="myTabContent">
        <!--  Контент Таба 1 order information -->
        <div class="tab-pane fade show <?= ($A_T == 'tab1') ? 'active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
            <?php include_once 'work-flow/tab_1.php'; ?>
        </div>
        <!-- end tab 1 -->

        <!--  Контент Таба 2 order bom -->
        <div class="tab-pane fade show <?= ($A_T == 'tab2') ? 'active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
            <?php include_once 'work-flow/tab_2.php'; ?>
        </div>
        <!-- end tab 2 -->

        <!--  Контент Таба 3 tool -->
        <div class="tab-pane fade show <?= ($A_T == 'tab3') ? 'active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <?php include_once 'work-flow/tab_3.php'; ?>
        </div>
        <!-- end tab 3 -->

        <!-- Контент Таба 4 project docs -->
        <div class="tab-pane fade show <?= ($A_T == 'tab4') ? 'active' : '' ?>" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
            <?php include_once 'work-flow/tab_4.php'; ?>
        </div>
        <!-- end tab 4 -->

        <!-- Контент Таба 5 project Part list (BOM) -->
        <div class="tab-pane fade show <?= ($A_T == 'tab5') ? 'active' : '' ?>" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
            <?php include_once 'work-flow/tab_5.php'; ?>
        </div>
        <!-- end tab 5 -->

        <!-- Контент для Таба 6 project steps -->
        <div class="tab-pane fade show <?= ($A_T == 'tab6') ? 'active' : '' ?>" id="tab6" role="tabpanel" aria-labelledby="tab6-tab">
            <?php include_once 'work-flow/tab_6_steps.php'; ?>
        </div>
        <!-- end tab 6 -->

        <!-- Контент для Таба 7 order chat -->
        <div class="tab-pane fade show <?= ($A_T == 'tab7') ? 'active' : '' ?>" id="tab7" role="tabpanel" aria-labelledby="tab7-tab">
            <?php include_once 'work-flow/tab_7_chat.php'; ?>
        </div>
        <!-- end tab 7 -->

        <!-- Контент для Таба 8 ORDER WORK FLOW -->
        <div class="tab-pane fade show <?= ($A_T == 'tab8') ? 'active' : '' ?>" id="tab8" role="tabpanel" aria-labelledby="tab8-tab">
            <?php
            // STANDARD assembling flow
            if ($project->project_type == 0) {
                include_once 'work-flow/tab_8.php';
            }
            // SMT assembling flow
            if ($project->project_type == 1) {
                include_once 'work-flow/tab_8_smt.php';
            }
            ?>
        </div>
        <!-- end tab 8 -->
    </div>
</div>
<!-- EDIT OR DELETE CHAT MESSAGE MODAL DIALOG -->
<div id="chatModalDialog" class="chatModalDialog">
    <!-- back button to edit-project or home -->
    <?php $url = 'order?orid=' . $_GET['orid']; ?>
    <form action="" method="post" class="form">
        <label for="chatMessage" class="form-label">Message text</label>
        <textarea name="chatMessage" id="chatMessage" rows="14" class="form-control p-3"></textarea>
        <div class="button-box">
            <button type="submit" name="editChatMessage" class="actionButtons btn btn-warning">Edit</button>
            <button type="submit" name="deleteChatMessage" class="actionButtons btn btn-danger">Delete</button>
            <button type="button" class="btn btn-outline-danger" id="chatModalDialogClose"><i class="bi bi-x-lg"></i></button>
        </div>
    </form>
</div>

<!-- WINDOW JAVASCRIPT -->
<?php PAGE_FOOTER($page, false); ?>
<script type="application/javascript" src="/public/js/order-details.js"></script>
</body>
</html>