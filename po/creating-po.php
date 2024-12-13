<?php
// check and return user if logged in
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'pioi';

// check for not in use boxes in storage
// called from ajax metod by clicking on storage box field
if (isset($_POST['search-for-storage-box'])) {
    exit(WareHouse::getEmptyBoxForItem($_POST));
}

/* СОЗДАЕМ НОВЫЙ ПРОЕКТ И ЗАКАЗ НА ОСНОВЕ ДАННЫХ И СОХРАНЯЕМ В БД */
if (isset($_POST['pioi']) && isset($_POST['projectName'])) {

    if (isset($_POST['addCustomer']) && empty($_POST['customerId'])) {
        // создаем нового пользователя если нет в БД
        try {
            $args = CPC::CustomerInformation($_GET, $_POST, $user);
        } catch (\RedBeanPHP\RedException\SQL $e) {
            _flashMessage($e->getMessage(), 'danger');
        }
        $_POST['customerId'] = $args['customer_id'];
    }

    // создаем новый проект заглушку
    $args = Project::createNewProject($_POST, $user, $_FILES);
    // получаем данные для создания заказа заглушки
    $project = R::load(PROJECTS, $args['id']);
    $client = R::load(CLIENTS, $args['customerId']);
    // создаем заказ заглушку что бы не забыть
    $args = Orders::createOrder($user, $client, $project, $_POST);
    if ($args[0]) {
        // переходим на странице деталей заказа
        // redirectTo("order/preview?orid=$args[1]");
        // переходим на страницу добавления приходных данных
        redirectTo("po-replenishment?orid=$args[1]");
    }
}
?>
<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
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

        #po-form th:last-child, #po-form td:last-child {
            text-align: right;
            padding-right: 0;
        }

        th, td {
            text-align: left;
            padding: 5px;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }

        #po-form td {
            width: 50%;
        }

        .border-bottom {
            border-bottom: 3px solid #000000 !important;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'P.O. Creation', 'user' => $user, 'page_name' => $page]); ?>

<h3 class="mt-3 mb-3 text-center">Draft Project and Order Record</h3>

<div class="container mt-5 mb-5 px-3 py-3 rounded" style="background: beige;">

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" id="create-pioi">
        <input type="hidden" id="customerId" name="customerId" value="">
        <table id="po-form">
            <tbody>
            <tr>
                <!--i CUSTOMER NAME  -->
                <td>
                    <label for="customerName" class="form-label">Customer Name <b class="text-danger">*</b> <i class="bi bi-search"></i></label>
                    <input type="text" class="form-control searchThis" id="customerName" name="customerName"
                           value="<?= set_value('customerName'); ?>" data-request="customer" required>
                </td>
                <td class="fs-4">
                    <input type="checkbox" class="form-check-input" id="addCustomer" name="addCustomer" value="1">
                    <label for="addCustomer" class="form-check-label">Add New Customer</label>
                </td>
            </tr>

            <!--i CUSTOMER PHONE AND EMAIL -->
            <tr>
                <td>
                    <label for="phone" class="form-label">Phone Number <b class="text-danger">*</b></label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="<?= set_value('phone'); ?>" required>
                </td>
                <td>
                    <label for="email" class="form-label">Contact Email <b class="text-danger">*</b></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= set_value('email'); ?>" required>
                </td>
            </tr>

            <!--i CUSTOMER PRIORITY AND HEAD PAY -->
            <tr>
                <td>
                    <label for="priorityMakat" class="form-label">Priority makat</label>
                    <input type="text" name="priorityMakat" value="<?= set_value('priorityMakat'); ?>"
                           class="form-control" id="priorityMakat">
                </td>
                <td>
                    <label for="headPay" class="form-label">Head Pay</label>
                    <input type="text" name="headPay" value="<?= set_value('headPay'); ?>"
                           id="headPay" class="form-control">
                </td>
            </tr>

            <!--i PROJECT NAME, INCOMING DATE, REVISION -->
            <tr>
                <td>
                    <label for="pn" class="form-label" id="project_label"><i class="bi bi-search"></i> Project Name <b class="text-danger">*</b></label>
                    <input type="text" name="projectName" value="<?= set_value('projectName'); ?>"
                           class="searchThis form-control" id="projectName" data-request="project" required>
                </td>
                <td>
                    <label for="projectRevision" class="form-label">Project Version <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="projectRevision" name="projectRevision" required
                           value="<?= set_value('projectRevision'); ?>">
                </td>
            </tr>

            <!--i PROJECT FILES -->
            <tr class="border-bottom">
                <td>
                    <button type="button" class="btn btn-outline-primary form-control" id="pickFile"
                            data-who="file">Upload Project Drawing (PDF Only)
                    </button>
                    <input type="file" name="dockFile" id="pdf_file" accept=".pdf" hidden/>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-primary form-control " id="projects_files_btn">
                        <?php $t = 'Warning! All files must be outside the folders, 
                    saving the folder is possible only in archived form, 
                    the file size cannot exceed 300MB in total! 
                    All types of files are allowed for uploading, 
                    you can download or view files after uploading and saving the project.'; ?>
                        <i class="bi bi-info-circle" data-title="<?= $t; ?>"></i>
                        <span id="pick_files_text">Upload Additional files</span>
                    </button>
                    <input type="file" name="projects_files[]" id="projects_files" accept="*/*" value="" multiple hidden>
                </td>
            </tr>

            <!--i PROJECT TYPE AND SERIALISATION REQUREMENTS -->
            <tr class="fs-3 border-bottom">
                <td>
                    <div class="form-switch">
                        <input class="form-check-input track-change" type="checkbox" id="project_type" name="project_type" value="1">
                        <label class="form-check-label" for="project_type">
                            Project type SMT <br> surface mount assembly line.
                        </label>
                    </div>
                </td>
                <td>
                    <div class="form-switch">
                        <input class="form-check-input track-change" type="checkbox" id="serial-required" name="serial-required" value="1">
                        <label class="form-check-label" for="serial-required">
                            Each unit in this project must be serialized with a mandatory serial number.
                        </label>
                    </div>
                </td>
            </tr>

            <!--I ORDER AMOUNT AND FAI QTY -->
            <tr>
                <td>
                    <label for="fai_qty" class="form-label">FAI Qty</label>
                    <input type="number" class="form-control track-change" id="fai_qty" name="fai_qty" value="<?= set_value('fai_qty', '3') ?>" min="0">
                </td>
                <td>
                    <label for="orderAmount" class="form-label">Order Amount <b class="text-danger">*</b></label>
                    <input type="number" class="form-control track-change" id="orderAmount" name="orderAmount"
                           value="<?= set_value('orderAmount', '10') ?>" min="1" required>
                </td>
            </tr>

            <!--I STORAGE PLACE FOR THIS ORDER-PROJECT -->
            <tr>
                <td>
                    <label for="storageBox" class="form-label">Storage Box</label>
                    <input type="number" class="form-control" id="storageBox" name="storageBox" min="1"
                           value="<?= set_value('storageBox', 1); ?>" placeholder="Click here for new number">
                </td>
                <td>
                    <label for="storageShelf" class="form-label">Storage Shelf/Place </label>
                    <input type="text" class="form-control track-change" id="storageShelf" name="storageShelf"
                           value="<?= set_value('storageShelf'); ?>" placeholder="Write your shelf here">
                </td>
            </tr>

            <!--I DATE AND TIME IN AND OUT -->
            <tr>
                <td>
                    <label for="date_in" class="form-label">Application date</label>
                    <input type="datetime-local" class="form-control track-change" id="date_in" name="date_in"
                           value="<?= date('Y-m-d H:i'); ?>">
                </td>
                <td>
                    <label for="date_out" class="form-label">Delivery time</label>
                    <input type="datetime-local" class="form-control track-change" id="date_out" name="date_out"
                           value="<?= date('Y-m-d H:i'); ?>">
                </td>
            </tr>

            <!-- i выбор работников для заказа -->
            <tr>
                <td>
                    <label for="orderWorkers" class="form-label">Workers to Order <b class="text-danger">*</b></label>
                    <div class="dropdown" id="workers">
                        <input type="text" name="orderWorkers" id="orderWorkers" class="form-control" placeholder="Choose the workers"
                               data-bs-toggle="dropdown" aria-expanded="false" readonly required value="<?= $order['workers'] ?? ''; ?>">
                        <ul class="dropdown-menu ps-4 ajeco-bg-aqua  w-100 fs-5" aria-labelledby="orderWorkers">
                            <?php
                            $allUsers = R::find(USERS);
                            foreach ($allUsers as $key => $u) {
                                if ($u['id'] != '1') { ?>
                                    <li class="form-check dropdown-item">
                                        <input type="checkbox" id="u-<?= $key; ?>" value="<?= $u['user_name']; ?>" class="form-check-input">
                                        <label class="form-check-label w-100" for="u-<?= $key; ?>"><?= $u['user_name']; ?></label>
                                    </li>
                                <?php }
                            } ?>
                        </ul>
                    </div>
                </td>

                <td>
                    <!-- переведено на ... -->
                    <label for="forwardTo" class="form-label">Forward To <b class="text-danger">*</b></label>
                    <div class="dropdown" id="forwarded">
                        <input type="text" name="forwardedTo" id="forwardTo" class="form-control" placeholder="Forward To"
                               data-bs-toggle="dropdown" aria-expanded="false" value="<?= $order['forwarded_to'] ?? '' ?>" readonly required>
                        <ul class="dropdown-menu ajeco-bg-aqua  w-100" aria-labelledby="forwardTo">
                            <?php foreach ($allUsers as $u) {
                                if ($u['id'] != '1') { ?>
                                    <li class="dropdown-item"><?= $u['user_name']; ?></li>
                                <?php }
                            } ?>
                        </ul>
                    </div>
                </td>
            </tr>

            <!--I PRIORITET AND STATUS FOR THIS ORDER -->
            <tr>
                <td>
                    <?php $t = 'To improve effectiveness, keep your mind clear.'; ?>
                    <label for="prioritet" class="form-label"><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> &nbsp;Prioritet</label>
                    <select class="form-control success" name="prioritet" id="prioritet">
                        <option value="LOW">LOW</option>
                        <option value="MEDIUM">MEDIUM</option>
                        <option value="HIGH">HIGH</option>
                        <option value="DO FIRST">DO FIRST</option>
                    </select>
                </td>

                <td>
                    <label for="order-status" class="form-label">Order Status</label>
                    <select class="form-control" name="order-status" id="order-status">
                        <?php
                        // если заказ на паузе то выводим только один статус для разблокировки
                        foreach (SR::getAllResourcesInGroup('status') as $key => $status) {
                            if ($key != '-1') {
                                echo _if($key == 'st-333',
                                    '<option value="' . $key . '" selected>' . SR::getResourceValue('status', $key) . '</option>',
                                    '<option value="' . $key . '">' . SR::getResourceValue('status', $key) . '</option>');
                                ?>
                            <?php }
                        } ?>
                    </select>
                </td>
            </tr>

            <!--I OTHER INFORMATION -->
            <tr>
                <td colspan="2">
                    <label for="purchaseOrder" class="form-label">Purchase Order</label>
                    <input type="text" class="form-control track-change" id="purchaseOrder" name="purchaseOrder"
                           value="<?= set_value('purchaseOrder', '0'); ?>">
                </td>
            </tr>

            <!--i ADDITIONAL INFORMATIONS -->
            <tr>
                <td colspan="2">
                    <div class="mb-3">
                        <label for="ai" class="form-label">Additional information</label>
                        <textarea class="form-control" id="ai" name="extra"></textarea>
                    </div>
                </td>
            </tr>

            <!--i CREATE PROJECT BUTTONS -->
            <tr>
                <td colspan="2">
                    <button type="submit" class="btn btn-outline-success form-control" id="create-po-btn" name="pioi">
                        Create P.O. data
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>

<!-- prompt text for serialization switch -->
<span class="hidden" id="prompt-text">Please note:
    By selecting this option, you are responsible for manually entering the serial number in the designated input fields for this operation.
    Failure to do so may result in discrepancies and potential issues with your project.
    Proceed with caution and ensure all serial numbers are correctly assigned and documented.
</span>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

// FOOTER & SCRIPTS
PAGE_FOOTER($page, false); ?>

<!-- project scripts-->
<script src="/public/js/pioi.js"></script>
</body>
</html>