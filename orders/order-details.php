<?php
// this if uses from ajax request it need to be first on page!!!!
if (isset($_POST['suggest'])) {
    include_once 'Orders.php';
    exit((Orders::makeXLSXfileAndSave($_POST['suggest'], null)) ? 'success' : 'error');
}

// rest page functions and includes
isset($_SESSION['userBean']) or header("Location: /order") and exit();
include_once 'orders/Orders.php';

/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$role = $user['app_role'];
$page = 'order_details';
// tab by default
$A_T = $_GET['tab'] ?? 'tab1'; // Active Tab
/* получение ID заказа */
$orderid = $_GET['orid'];

/* set list users and status to order */
if (isset($_POST['set-order-status']) || isset($_POST['set-order-user'])) {
    $args = Orders::setStatusOrUserInOrder($user, $orderid, $_POST);
}

/* editing or deleteng message from chat */
if (isset($_POST['editChatMessage']) || isset($_POST['deleteChatMessage'])) {
    $args = Orders::editOrDeleteMessage($_POST, $user, $orderid);
    $A_T = 'tab7';
}

/* save message to chat from users or system */
if (isset($_POST['save-message']) && isset($_POST['messageText'])) {
    /* checking if user uploaded good file */
    if (!empty($_FILES['chatFile']['name'][0])) {
        if (!Orders::getFileExtension($_FILES['chatFile'])) {
            $args = ['color' => 'danger', 'info' => 'File format is wrong! Only jpg, png, webp, mp4, pdf, csv, xls, xlsx, doc, txt, zip, rar, 7z 
             files is approved!'];
        } else {
            if (Orders::checkSizeOfFile($_FILES['chatFile'])) {
                $args = Orders::saveChatMessage($orderid, $user, $_POST, $_FILES['chatFile']);
            } else {
                $args = ['color' => 'danger', 'info' => 'File wery big! try another file.'];
            }
        }
    } else {
        $args = Orders::saveChatMessage($orderid, $user, $_POST);
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
    $customer = R::load(CLIENTS, $order->customers_id);
    $project = R::load(PROJECTS, $projectid);
    $stepsData = R::findAll(PROJECT_STEPS, 'projects_id = ? ORDER BY step ASC', [$projectid]);
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
                $_SESSION['info'] = Orders::setStatusOrUserInOrder($user, $orderid, $data);
                // reload page
                header("Location: /order");
                exit();
            }
            break;

        case 'complete_order':
            if ($_POST['complete_order'] == $orderid) {
                // worker compliting the order
                // переводим заказ в статус st-4 Waiting Inspection
                // возвращаем пользователя на страницу заказов
                $data['status'] = 'st-4';
                $data['set-order-status'] = '1';
                $_SESSION['info'] = Orders::setStatusOrUserInOrder($user, $orderid, $data);
                // reload page
                header("Location: /order");
                exit();
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

        $_SESSION['info'] = $args['errors']; // вывод информации об ошибках и успехах
        header("Location: /order/preview?orid=$orderid&tab=tab$tab" . $sid);
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
</head>
<body>

<?php
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="container-fluid p-2" style="height: 95vh;">
    <!--  заголовок окна -->
    <div class="header-line mb-4">
        <h4>
            Project Name: <small><?= $project['projectname']; ?></small>
            &nbsp;&nbsp;
            <b class="text-danger">Amount: </b>
            <?= $amount; ?>
        </h4>
        <?php LANGUAGE_BUTTONS(); ?>
        <!-- Кнопка Закрыть окно -->
        <button type="submit" id="closeButton" class="btn btn-danger">Back to Orders</button>
    </div>

    <!--  кнопки переключения между табами -->
    <ul class="nav nav-tabs" role="tablist">
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
                    data-bs-target="#tab8" id="nav-link-8" type="button" role="tab">Instructions for assembling the project
            </button>
        </li>
    </ul>

    <!-- ----------------------- Контент Табов ------------------------------ -->
    <div class="tab-content" id="myTabContent">

        <!--  Контент Таба 1 order information -->
        <div class="tab-pane fade show <?= ($A_T == 'tab1') ? 'active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
            <?php
            include_once 'order-info.php';
            getOrderInformationHTML($orderid, $order, $customer, $project, $projectBom, $assy_in_progress, $chatLastMsg, $amount);
            ?>
        </div>
        <!-- end tab 1 -->

        <!--  Контент Таба 2 order bom -->
        <div class="tab-pane fade show <?= ($A_T == 'tab2') ? 'active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
            <?php if ($projectBom) { ?>
                <table class="p-3">
                    <!-- header -->
                    <thead>
                    <tr>
                        <?php
                        if ($settings = getUserSettings($user, PROJECT_BOM)) {
                            foreach ($settings as $item) {
                                echo '<th>' . L::TABLES(PROJECT_BOM, $item) . '</th>';
                            } ?>
                            <th>Shelf / Box</th>
                            <th>Aqtual QTY</th>
                            <?php
                        } else {
                            ?>
                            <th>
                                Your view settings for this table isn`t exist yet
                                <a role="button" href="/setup" class="btn btn-outline-info">Edit Columns view settings</a>
                            </th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <!-- table -->
                    <tbody>
                    <?php
                    foreach ($projectBom as $line) {
                        $color = (($line['amount'] * $order['order_amount']) <= WareHouse::GetActualQtyForItem($line['customerid'], $line['item_id'])) ?
                            'success' : 'danger';
                        ?>
                        <tr class="item-list <?= $color; ?>">
                            <?php
                            if ($settings) {
                                foreach ($settings as $item) {
                                    if ($item == 'amount') {
                                        $it = $line[$item] * $order['order_amount'];
                                    } else {
                                        $it = $line[$item];
                                    }
                                    ?>
                                    <td><?= $it; ?></td>
                                    <?php
                                }
                            }
                            $storage = WareHouse::GetOneItemFromWarehouse($line['manufacture_pn'], $line['owner_pn']);
                            $shelf = $storage['storage_shelf'] ?? 'N/A';
                            $box = $storage['storage_box'] ?? 'N/A';
                            ?>
                            <td><?= $shelf . ' / ' . $box; ?></td>
                            <td><?= $storage['quantity'] ?? '0'; ?></td>
                        </tr>
                        <?php
                    } ?>
                    </tbody>
                </table>

                <?php
            } else {
                $_SESSION['projectid'] = $project->id;
                ?>
                <div class="align-middle mt-3">
                    <h3>Information on the available parts to create this project has not yet to be entered!</h3>
                    <br>
                    <?php $url = "check_part_list?mode=orderbom&back-id=$order->id&pid=$project->id"; ?>
                    <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary">
                        Do you want to enter information?
                    </button>
                </div>
            <?php } ?>
        </div>
        <!-- end tab 2 -->

        <!--  Контент Таба 3 tool -->
        <div class="tab-pane fade show <?= ($A_T == 'tab3') ? 'active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <?php if (!empty($project['tools']) && $project['tools'] != 'NC') { ?>
                <table class="p-3">
                    <!-- header -->
                    <thead>
                    <tr>
                        <?php
                        /* настройки вывода от пользователя */
                        if ($settings = getUserSettings($user, TOOLS)) {
                            foreach ($settings as $item) {
                                echo '<th>' . L::TABLES(TOOLS, $item) . '</th>';
                            }
                        } else {
                            ?>
                            <th>
                                Your view settings for this table isn`t exist yet
                                <a role="button" href="/setup" class="btn btn-outline-info">Edit Columns view settings</a>
                            </th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <!-- table -->
                    <tbody>
                    <?php
                    $toolsId = explode(',', $project['tools']);
                    foreach ($toolsId as $id) {
                        $row = R::load(TOOLS, $id);
                        echo '<tr class="item-list">';
                        if ($settings) {
                            foreach ($settings as $item) {
                                if ($item != 'image') {
                                    echo '<td>' . $row[$item] . '</td>';
                                } else {
                                    echo '<td>' .
                                        '<img src="/' . $row['image'] . '" alt="Tool Image Preview" width="180px" >' .
                                        '</td>';
                                }
                            }
                        }
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <!-- notice for creation tools table for project -->
                <div class="row mt-3">
                    <div class="col-12">
                        <h3>No tool has to be selected for this project yet.</h3>
                        <br>
                        <?php $vurl = "new_project?mode=editmode&pid={$project['id']}&back-id={$_GET['orid']}"; ?>
                        <button type="button" value="<?= $vurl; ?>" class="url btn btn-outline-primary">
                            Do you want to select tools?
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>
        <!-- end tab 3 -->

        <!-- Контент Таба 4 project docs -->
        <div class="tab-pane fade show <?= ($A_T == 'tab4') ? 'active' : '' ?>" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
            <div class="row mt-3" style="margin: 0">
                <div class="col-8">
                    <?php
                    $d = 'disabled';
                    if (!empty($project['projectdocs']) && strpos($project['projectdocs'], '.pdf') !== false) {
                        $d = '';
                        ?>
                        <iframe id="pdf-docs" width="100%" height="340%" src="/<?= $project['projectdocs']; ?>"></iframe>
                    <?php } else { ?>
                        <img src="/<?= getProjectFrontPicture($projectid, 'docs'); ?>" alt="<?= $orderid; ?>"
                             class="img-fluid rounded" style="width: 100%;">
                    <?php } ?>
                </div>

                <div class="col-4" style="border-left:solid black 1px;">
                    <div class="mb-3">
                        <h3 class="mb-3 ps-2">Additional Information</h3>
                        <p class="ps-2"> <?= $project['extra']; ?></p>
                        <p class="ps-2"><?= 'Project Revision: ' . $project['revision']; ?></p>
                        <p class="ps-2 text-primary"><?= 'Created in: ' . $project['date_in']; ?></p>
                    </div>
                    <div class="mb-3">
                        <a role="button" href="<?= BASE_URL . $project->projectdocs; ?>" target="_blanks" class="btn btn-outline-info <?= $d; ?>">
                            Open Document
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end tab 4 -->

        <!-- Контент Таба 5 project Part list (BOM) -->
        <div class="tab-pane fade show <?= ($A_T == 'tab5') ? 'active' : '' ?>" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
            <?php if ($projectBom) { ?>
                <table class="p-3">
                    <!-- header -->
                    <thead>
                    <tr>
                        <?php
                        if ($settings = getUserSettings($user, PROJECT_BOM)) {
                            foreach ($settings as $item) {
                                echo '<th>' . L::TABLES(PROJECT_BOM, $item) . '</th>';
                            }
                        } else {
                            ?>
                            <th>
                                Your view settings for this table isn`t exist yet
                                <a role="button" href="/setup" class="btn btn-outline-info">Edit Columns view settings</a>
                            </th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <!-- table -->
                    <tbody>
                    <?php
                    foreach ($projectBom as $line) {
                        echo '<tr class="item-list">';
                        if ($settings) {
                            foreach ($settings as $item) {
                                echo '<td>' . $line[$item] . '</td>';
                            }
                        }
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            } else {
                $_SESSION['projectid'] = $project->id;
                ?>
                <div class="align-middle row mt-3">
                    <div class="col-12">
                        <h3>Information on the available parts to create this project has not yet to be entered!</h3>
                        <br>
                        <?php $url = "check_part_list?mode=editmode&back-id=$order->id&pid=$project->id"; ?>
                        <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary">
                            Do you want to enter information?
                        </button>
                    </div>
                </div>
                <?php
            } ?>
        </div>
        <!-- end tab 5 -->

        <!-- Контент для Таба 6 project steps -->
        <div class="tab-pane fade show <?= ($A_T == 'tab6') ? 'active' : '' ?>" id="tab6" role="tabpanel" aria-labelledby="tab6-tab">
            <div class="step-box mt-3">
                <?php
                if ($stepsData) {
                    $stepCount = 0;
                    /* выводим все шаги для просмотра и выбора в работу */
                    foreach ($stepsData as $step) {
                        // проверяем если шаг был завершен то не выводим его
                        if (!Orders::isStepComplite($order->status, $step['id'])) {
                            $stepCount++;
                            echo ($step['validation']) ? '<p class="text-white bg-danger">' . $step['validation'] . '</p>' : '';
                            ?>
                            <div class="row row-side" id="sid-<?= $step['step']; ?>" style="margin: 0">
                                <div class="col-5">

                                    <?php
                                    // на случай если в проекте нет шагов с фото или видео
                                    if (!empty($step['image'])) { ?>
                                        <img class="step-image" src="/<?= $step['image']; ?>" alt="Hello asshole">
                                        <?php
                                    } else {
                                        echo '<h3>' . $step['routeaction'] . '</h3>';
                                    }

                                    if ($step['video'] != 'none') { ?>
                                        <video src="<?= $step['video']; ?>" controls width="100%" height="auto">
                                            Your browser not support video
                                        </video>
                                    <?php } ?>
                                </div>

                                <div class="col-7 info-side">
                                    <h5 class="mb-3">Step Number: <?= $step['step']; ?></h5>
                                    <p class="text-primary"><?= $step['description']; ?></p>
                                    <pre class="warning rounded border p-2">
WARNING!
Before you start this step, read the rules for transitioning between steps!
1) Execute step:
After completing the step completely.
IMPORTANT!
Click on the "step completed" button
This will prevent the possibility of taking a step into work by mistake!
2) Partial execution:
In case of partial or serial execution of the order.
IMPORTANT!
After completing the step, click on the “next step” button.
This button will appear if a serial number is included in the order!
3) Transferring a step to another worker:
If you need to transfer a step to another worker.
IMPORTANT!
Select an employee from the list and click the “transfer step” button.
This action will open up the opportunity for another worker to choose a step to work on!
4) Step verification by administrator:
If this step is verified, the “request step verification” button will be presented on the page.
IMPORTANT!
Click on this button after making the first copy of the product in your order!
If serial numbering is set, the action is performed for all copies of the product at this step!
5) Stop order fulfillment:
In a situation where a stop is required while executing an order.
IMPORTANT!
Press the "order to pause" button
Next, in the dialog that opens, you need to write the reason for stopping the order in any language
and click the “ok” button to complete the operation.
                                </pre>
                                    <?php
                                    // если пользователь взял в работу один шаг то отключаем возможность взять другой в работу
                                    if (!$assy_in_progress && $order->status == 'st-8') { ?>
                                        <form action="" method="post">
                                            <?php $assy_work_flow = R::findOne(ASSY_PROGRESS, 'current_stepid = ?', [$step['id']]); ?>
                                            <input type="hidden" name="assyid" value="<?= $assy_work_flow->id; ?>">
                                            <input type="hidden" name="stepid" value="<?= $step['id']; ?>">
                                            <button type="submit" class="btn btn-outline-primary" name="take-a-step-to-work">
                                                Take a step to work
                                            </button>
                                        </form>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php }
                    }
                    // завершение заказа или повтор если требуется серийный номер или поштучное изготовление
                    if ($stepCount == 0) {
                        ?>
                        <div class="mb-3 mt-3 p3 text-center">
                            <h3>All project steps have been completed, complete the order or repeat all steps.</h3>
                            <form action="" method="post">
                                <button type="submit" name="complete_order" value="<?= $order->id; ?>" class="btn btn-outline-dark">
                                    Order assembly complete, move on to the next order?
                                </button>

                                <h4>For orders where a serial number is required.</h4>
                                <input type="text" name="serial_number_for_assy_flow" class="form-control" placeholder="Write next serial number">
                                <button type="submit" name="repite_order" value="<?= $order->id; ?>" class="btn btn-outline-dark">
                                    Repeat the assembly procedure step by step for the new serial number
                                </button>
                            </form>
                        </div>
                        <?php
                    }

                    // если нет шагов по сборке, выводим предложение добавить шаги в проекты
                } else { ?>
                    <div class="mb-3">
                        <h4>It seems there are no assembly instructions for this project yet. Would you like to add assembly instructions to this project?</h4>
                        <a role="button" href="/add_step?pid=<?= $project->id; ?>" target="_blank" class="btn btn-outline-info">
                            Add Project steps
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
        <!-- end tab 6 -->

        <!-- Контент для Таба 7 order chat -->
        <div class="tab-pane fade show <?= ($A_T == 'tab7') ? 'active' : '' ?>" id="tab7" role="tabpanel" aria-labelledby="tab7-tab">
            <div class="row" style="margin: 0">
                <!-- chat messages window -->
                <div class="col-9">
                    <div id="chatWindow" style="overflow: scroll; height: 65vh;">
                        <?php
                        $ext_arr = ['zip', 'rar', '7z'];
                        foreach ($orderChat as $msg) {
                            $edited = ($msg->edited) ? 'darker' : '';
                            $f_disp = !empty($f = $msg['file_path']);
                            $if_disp = !empty($if = $msg['image_file_path']);
                            $vf_disp = !empty($vf = $msg['video_file_path']);
                            $af_disp = !empty($af = $msg['audio_file_path']);
                            /* сообщение с файлом видео или картинкой */
                            if ($f) {
                                $f_ext = strtolower(pathinfo(basename($f), PATHINFO_EXTENSION));
                                $icon_style = (in_array($f_ext, $ext_arr)) ? "file-zip" : "filetype-$f_ext";
                            } ?>
                            <div class="container <?= $edited; ?>">
                                <span><?= $msg['user_name']; ?></span>

                                <?php if ($if_disp): ?>
                                    <img src="/<?= $if; ?>" alt="Avatar" class="right">
                                <?php endif; ?>

                                <?php if ($vf_disp): ?>
                                    <video controls class="right">
                                        <source src='/<?= $vf; ?>' type='video/mp4'>
                                        <!--Your browser does not support the video tag.-->
                                        Go to hell video not sponsored by me!
                                    </video>
                                <?php endif; ?>

                                <?php if ($af_disp): ?>
                                    <audio controls class="right">
                                        <source src='/<?= $af; ?>' type='audio/m4a'>
                                        <source src='/<?= $af; ?>' type='audio/mp3'>
                                        <!--Your browser does not support the audio element.-->
                                        Go to hell audio not sponsored by me!
                                    </audio>
                                <?php endif;

                                if ($f_disp) {
                                    if ($f_ext != 'pdf') { ?>
                                        <a href="/<?= $f; ?>" class="btn btn-primary right" download>
                                            <i class="bi bi-<?= $icon_style; ?>"></i> Download
                                        </a>
                                    <?php } else { ?>
                                        <a href="/<?= $f; ?>" class="btn btn-primary right" target="_blank">
                                            <i class="bi bi-<?= $icon_style; ?>"></i> Preview
                                        </a>
                                    <?php }
                                } ?>
                                <p data-msgid="<?= $msg->id; ?>" class="msg-url"> <?= $msg['message']; ?></p>
                                <span class="time-left"><?= $msg['date_in']; ?></span>
                                <?php if ($f_disp) { ?>
                                    <span class="time-left"><?= basename($f); ?></span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="gear-container" id="gear-container">
                        <i class="bi bi-gear-wide-connected gear" id="gear1"></i>
                        <i class="bi bi-gear-wide-connected gear" id="gear2"></i>
                    </div>
                    <form action="" method="post" class="mt-4" enctype="multipart/form-data">
                        <div class="d-flex">
                            <?php $t = 'A message written here can be edited or deleted only within 15 minutes from the moment it was saved!'; ?>
                            <textarea name="messageText" id="chatTextArea" class="form-control" style="flex-grow: 1; max-width: 75%;" rows="3"
                                      placeholder="<?= $t; ?>" required></textarea>

                            <div class="d-flex flex-row justify-content-between">
                                <input type="file" name="chatFile" class="hidden" id="fileToTake">
                                <!-- write and send audio message -->
                                <button id="recordButton" type="button" class="btn btn-outline-primary ms-2 btn-rounded">
                                    <i class="bi bi-mic-fill fs-3"></i>
                                </button>
                                <!-- add file to message -->
                                <button type="button" id="getFileContent" class="btn btn-outline-primary ms-2 btn-rounded">
                                    <i class="bi bi-folder-plus fs-2"></i>
                                </button>
                                <!-- send message btn -->
                                <button type="submit" name="save-message" id="chat-send-button" class="btn btn-outline-info ms-2 btn-rounded">
                                    <i class="bi bi-send fs-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- chat rules notice and file name preview -->
                <div class="col-3 border-start">
                    <i class="bi bi-info-circle text-info"></i>
                    <p class="chat-notes">
                        <b>NOTICE</b>
                        <br>
                        <b class="info">Audio Recording:</b> <i class="bi bi-mic-fill info"></i>
                        <br>
                        Press and hold the record button to create a voice message.
                        <br>
                        After recording the audio, make sure to write a note on the recording.
                        <br>
                        After sending the message, please be patient! Saving recordings and files takes time, which depends on your Internet connection.
                        <br>
                        <b class="info">File Uploading:</b>
                        <br>
                        Only jpg, png, webp, mp4, pdf, csv, xls, xlsx, doc, txt, zip, rar, 7z can be uploaded!
                        <br>
                        Max size 300MB!!!
                        <br>
                        <b class="info">Message Edit/Delete:</b>
                        <br>
                        <?= $t; ?>
                    </p>
                    <br>
                    <br>
                    <!-- file name preview -->
                    <div id="fileNamePreviewContainer" class="p-2 hidden warning rounded">
                        <h3>Choosen file:</h3>
                        <p class="text-primary" id="fileNamePreview"></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- end tab 7 -->

        <!-- Контент для Таба 8 ORDER WORK FLOW -->
        <div class="tab-pane fade show <?= ($A_T == 'tab8') ? 'active' : '' ?>" id="tab8" role="tabpanel" aria-labelledby="tab8-tab">
            <?php
            require_once 'assembly-type.php';
            if ($project->project_type == 0) {
                standardAssemblyProjectType($order, $stepsData, $assy_in_progress, $amount);
            }
            if ($project->project_type == 1) {
                smtAssemblyProjectType($order, $amount, $projectBom);
            }
            ?>
        </div>
        <!-- end tab 8 -->
    </div>
</div>

<form action="" id="routing" method="post" class="hidden"></form>

<!-- EDIT OR DELETE CHAT MESSAGE MODAL DIALOG -->
<div id="chatModalDialog" class="chatModalDialog">
    <!-- back button to edit-project or home -->
    <?php $url = 'order?orid=' . $_GET['orid']; ?>
    <form action="" method="post" class="form">
        <textarea name="chatMessage" id="chatMessage" rows="14" class="form-control p-3"></textarea>
        <div class="button-box">
            <button type="submit" name="editChatMessage" class="actionButtons btn btn-warning">Edit</button>
            <button type="submit" name="deleteChatMessage" class="actionButtons btn btn-danger">Delete</button>
            <button type="button" class="btn btn-outline-danger" id="chatModalDialogClose"><i class="bi bi-x-lg"></i></button>
        </div>
    </form>
</div>

<!-- WINDOW JAVASCRIPT -->
<?php ScriptContent($page); ?>
<script type="application/javascript" src="/public/js/order-details.js"></script>
</body>
</html>