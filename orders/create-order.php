<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();
include_once 'Orders.php';

// check for not in use boxws in storage
if (isset($_POST['search-for-storage-box'])) {
    // Получаем текущий номер из запроса
    $currentNumber = _E($_POST['search-for-storage-box']);
    // Ищем следующий доступный номер, где in_use = 0, есть индексация по колонке box
    $nextBox = R::findOne(WH_SLOTS, 'box > ? AND in_use = 0 ORDER BY box ASC', [$currentNumber]);

    // Если следующего номера нет (мы достигли конца списка), начинаем сначала
    if (!$nextBox) {
        $nextBox = R::findOne(WH_SLOTS, 'in_use = 0 ORDER BY box ASC');
    }

    // Возвращаем номер следующего доступного элемента
    if ($nextBox) {
        echo $nextBox->box;
    } else {
        // Если не найдено ни одного доступного номера
        echo "No available boxes found";
    }
    exit();
}

$user = $_SESSION['userBean'];
$page = 'new_order';
$titleText = 'Order Creation';
$project = $order = $client = null;
$btnSubmit['text'] = 'Create new order';
$btnSubmit['name'] = 'createOrder';

/* creating new order */
if (isset($_POST['createOrder'])) {
    $project = R::load(PROJECTS, _E($_POST['project_id']));
    $client = R::load(CLIENTS, _E($_POST['customer_id']));

    $_SESSION['info'] = $result = Orders::createOrder($user, $client, $project, $_POST);

    if ($result[0]) {
        $orderId = $result[1];
        header("Location: /check_bom?orid=$orderId&pid=$project->id");
        exit();
    } else {
        $result = ['color' => 'danger', 'info' => 'Can not write log information^ '];
    }
}

/* TODO updating order information ?????? разобратся где оно берется! да я забыл поэтому и записываю */
if (isset($_POST['editOrder'])) {
    $project = R::load(PROJECTS, _E($_POST['project_id']));
    $client = R::load(CLIENTS, _E($_POST['customer_id']));
    $order = R::load(ORDERS, _E($_POST['order_id']));

    $_SESSION['info'] = $res = Orders::updateOrder($user, _E($_POST['order_id']), $_POST, $project, $client);
    if ($res) {
        header("Location: /check_bom?orid=$orderId&pid=$project->id");
        exit();
    }
}

// create new order from project list page
if (isset($_GET['pid']) && isset($_GET['nord'])) {
    $project = R::load(PROJECTS, _E($_GET['pid']));
    $client = R::findOne(CLIENTS, 'name = ?', [$project->customername]);
}

// edit order from order view page
if (isset($_GET['edit-order']) && isset($_GET['pid']) && isset($_GET['orid'])) {
    $order = R::load(ORDERS, _E($_GET['orid']));
    $project = R::load(PROJECTS, $order->projects_id);
    $client = R::load(CLIENTS, $order->customers_id);
    $btnSubmit['text'] = 'Update this order';
    $btnSubmit['name'] = 'updateOrder';
    $titleText = 'Editing Order';
    $page = 'edit_order';
}

// call update order function
if (isset($_POST['updateOrder']) && !empty($_POST['order-id'])) {
    $result = Orders::updateOrder($user, _E($_POST['order-id']), $_POST, true, true);
    $_SESSION['info'] = $result;
    header("Location: /edit-order?edit-order&orid={$_GET['orid']}&pid={$result['pid']}");
    exit();
}

// title for navbar
$title = ['title' => $titleText, 'app_role' => $user['app_role']];
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
    </style>
</head>
<body>

<?php
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($result ?? null);
?>
<!-- NAVIGATION BAR -->
<?php NavBarContent($page, $title, null, Y['N_ORDER']); ?>

<div class="container mt-4 px-3 py-3 rounded" style="background: aliceblue;">
    <div class="row">
        <?php $t = 'To search, click on the field and start writing. Search fields are marked with a search sign'; ?>
        <div class="col-8"><h3><small><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> </small>&nbsp; <?= $titleText ?></h3></div>
        <?php if (!isset($_GET['edit-order'])): ?>
            <div class="col-4"><h3>Order ID: &nbsp; <?= R::count(ORDERS) + 1; ?></h3></div>
        <?php else: ?>
            <div class="col-4"><h3>Order ID: &nbsp; <?= $order->id; ?></h3></div>
        <?php endif; ?>
    </div>

    <form id="createOrderForm" action="" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="order-id" value="<?= $order['id'] ?? ''; ?>">
        <input type="hidden" name="changed-fields" id="changedFields">
        <div class="mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-5">
                    <label for="clientName" class="form-label"><i class="bi bi-search"></i>&nbsp;Customer Name <b class="text-danger">*</b></label>
                </div>
                <div class="col-2">
                    <label for="customer_id" class="form-label">Customer ID</label>
                </div>
                <div class="col-3">
                    <label for="priorityMakat" class="form-label"><i class="bi bi-search"></i>&nbsp;Priority <b class="text-danger">*</b></label>
                </div>
                <div class="col-auto"></div>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-5">
                    <input type="text" class="searchThis form-control" id="clientName" name="customerName" data-request="customer"
                           value="<?= set_value('customerName', $client->name ?? ''); ?>" required>
                </div>
                <div class="col-2">
                    <input type="text" class="form-control" id="customer_id" name="customer_id"
                           value="<?= set_value('customer_id', $client->id ?? '0'); ?>" readonly>
                </div>
                <div class="col-3">
                    <input type="text" class="searchThis form-control track-change" id="priorityMakat" name="priority" data-request="priority"
                           value="<?= set_value('priority', $client->priority ?? '0'); ?>" required data-field-id="priority">
                </div>
                <div class="col-auto">
                    <button type="button" value="create_client?routed-from=create-order" class="url btn btn-outline-primary">Add Customer</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-6">
                    <label for="projectName" class="form-label"><i class="bi bi-search"></i>&nbsp;Project Name <b class="text-danger">*</b></label>
                </div>
                <div class="col-2">
                    <label for="project_id" class="form-label">Project ID</label>
                </div>
                <div class="col-2">
                    <label for="projectRevision" class="form-label">Project Version</label>
                </div>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-6">
                    <input type="text" class="searchThis form-control" id="projectName" name="projectName" data-request="project"
                           value="<?= set_value('projectName', $project->projectname ?? ''); ?>" required>
                </div>
                <div class="col-2">
                    <input type="text" class="form-control" id="project_id" name="project_id"
                           value="<?= set_value('project_id', $project->id ?? '0'); ?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" class="form-control" id="projectRevision" name="projectRevision" readonly
                           value="<?= set_value('projectRevision', $project->revision ?? '0'); ?>">
                </div>
                <div class="col-auto">
                    <button type="button" value="new_project?orders" class="url btn btn-outline-primary">Create Project</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-2">
                    <label for="orderAmount" class="form-label">Order Amount <b class="text-danger">*</b></label>
                </div>
                <div class="col-2">
                    <label for="fai_qty" class="form-label">FAI Qty</label>
                </div>
                <div class="col-3">
                    <label for="storageBox" class="form-label">Storage Box <b class="text-danger">*</b></label>
                </div>
                <div class="col-3">
                    <label for="storageShelf" class="form-label">Storage Shelf/Place </label>
                </div>
                <div class="col-2">
                    <label for="date_in" class="form-label">Order incoming date <b class="text-danger">*</b></label>
                </div>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-2">
                    <input type="number" class="form-control track-change" id="orderAmount" name="orderAmount"
                           value="<?= set_value('orderAmount', $order['order_amount'] ?? '10') ?>" min="1" required data-field-id="qty">
                </div>
                <div class="col-2">
                    <input type="number" class="form-control track-change" id="fai_qty" name="fai_qty"
                           value="<?= set_value('fai_qty', $order['fai_qty'] ?? '3') ?>" min="0" data-field-id="fai_qty">
                </div>

                <div class="col-3">
                    <input type="text" class="form-control" id="storageBox" name="storageBox"
                           value="<?= set_value('storageBox', $order['storage_box'] ?? ''); ?>"
                           placeholder="Click here for new number" required>
                </div>

                <div class="col-3">
                    <input type="text" class="form-control track-change" id="storageShelf" name="storageShelf"
                           value="<?= set_value('storageShelf', $order['storage_shelf'] ?? ''); ?>" placeholder="Write your shelf here" data-field-id="shelf">
                </div>

                <div class="col-2">
                    <input type="datetime-local" class="form-control track-change" id="date_in" name="date_in"
                           value="<?= $order['date_in'] ?? date('Y-m-d H:i'); ?>" required data-field-id="date">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-4">
                    <label for="purchaseOrder" class="form-label">Purchase Order</label>
                </div>
                <div class="col-3">
                    <label for="orderWorkers" class="form-label">Workers to Order <b class="text-danger">*</b></label>
                </div>
                <div class="col-3">
                    <label for="forwardTo" class="form-label">Forward To <b class="text-danger">*</b></label>
                </div>
                <div class="col-2">
                    <?php $t = 'To improve effectiveness, keep your mind clear.'; ?>
                    <label for="prioritet" class="form-label"><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> &nbsp;Prioritet</label>
                </div>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-4">
                    <input type="text" class="form-control track-change" id="purchaseOrder" name="purchaseOrder"
                           value="<?= set_value('purchaseOrder', $client->head_pay ?? '0'); ?>" data-field-id="head_pay">
                </div>

                <div class="col-3">
                    <!-- i выбор работников для заказа -->
                    <div class="dropdown" id="workers">
                        <input type="text" name="orderWorkers" id="orderWorkers" class="form-control" placeholder="Choose the workers"
                               data-bs-toggle="dropdown" aria-expanded="false" readonly required value="<?= $order['workers'] ?? ''; ?>">
                        <ul class="dropdown-menu ps-3 ajeco-bg-aqua  w-100" aria-labelledby="orderWorkers">
                            <?php $allUsers = R::find(USERS);
                            $workers = array();
                            if (!empty($order['workers'])) {
                                // Разделяем строку на массив и удаляем пробелы вокруг каждого элемента
                                $workers = array_map('trim', explode(',', $order['workers']));
                            }
                            foreach ($allUsers as $key => $u) {
                                if ($u['id'] != '1') {
                                    $checked = !empty($workers) && in_array($u['user_name'], $workers) ? 'checked' : '';
                                    ?>
                                    <li class="form-check dropdown-item">
                                        <input type="checkbox" id="u-<?= $key; ?>" value="<?= $u['user_name']; ?>" class="form-check-input" <?= $checked ?>>
                                        <label class="form-check-label w-100" for="u-<?= $key; ?>"><?= $u['user_name']; ?></label>
                                    </li>
                                <?php }
                            } ?>
                        </ul>
                    </div>
                </div>

                <div class="col-3">
                    <!-- переведено на ... -->
                    <div class="dropdown" id="forwarded">
                        <input type="text" name="forwardedTo" id="forwardTo" class="form-control" placeholder="Forward To"
                               data-bs-toggle="dropdown" aria-expanded="false" value="<?= $order['forwarded_to'] ?? '' ?>" readonly required>
                        <ul class="dropdown-menu ajeco-bg-aqua  w-100" aria-labelledby="forwardTo">
                            <?php foreach ($allUsers as $user) {
                                if ($user['user_name'] != 'amir-nti-laba') { ?>
                                    <li class="dropdown-item"><?= $user['user_name']; ?></li>
                                <?php }
                            } ?>
                        </ul>
                    </div>
                </div>

                <div class="col-2">
                    <?php
                    if (!empty($order['prioritet'])) {
                        $pri = ['DO FIRST' => 'danger', 'HIGH' => 'danger', 'MEDIUM' => 'warning', 'LOW' => 'success'];
                        echo '<select class="form-control ' . $pri[$order['prioritet']] . '" name="prioritet" id="prioritet">';
                        echo "<option value='{$order['prioritet']}'>{$order['prioritet']}</option>";
                        foreach ($pri as $p => $c) {
                            if ($p != $order['prioritet']) {
                                echo "<option value='$p'>$p</option>";
                            }
                        }
                        echo '</select>';
                    } else {
                        ?>
                        <select class="form-control success" name="prioritet" id="prioritet">
                            <option value="LOW">LOW</option>
                            <option value="MEDIUM">MEDIUM</option>
                            <option value="HIGH">HIGH</option>
                            <option value="DO FIRST">DO FIRST</option>
                        </select>
                    <?php } ?>
                </div>

            </div>
        </div>

        <div class="checkbox mb-3">
            <?php $serial = (!empty($order['serial_required']) && $order['serial_required'] == 1) ? 'checked' : ''; ?>
            <div class="form-check form-switch fs-3">
                <input class="form-check-input track-change" type="checkbox" id="serial-required" name="serial-required"
                       value="1" data-field-id="se_ed" <?= $serial; ?>>
                <label class="form-check-label" for="serial-required" style="font-size: large">
                    Each unit in this project must be serialized with a mandatory serial number.
                </label>
            </div>
        </div>

        <div class="mb-3">
            <?php $t = 'Here write some additional information for Worker or any reasons.'; ?>
            <label for="extra" class="form-label"><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> &nbsp; Additional Information</label>
            <textarea class="form-control track-change" id="extra" name="extra" data-field-id="extra"><?= set_value('extra', $order['extra'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary form-control mt-3 mb-2" id="createOrderFBtn" name="<?= $btnSubmit['name'] ?>">
            <?= $btnSubmit['text'] ?>
        </button>
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

// SCRIPTS
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Отключение автоматического закрытия выпадающих списков
        dom.in('click', "#workers", function (e) {
            e.stopPropagation();
        });

        // Обработка клика по результату поиска клиента
        dom.in("click", "#search-responce tr.customer", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                // Устанавливаем полученные значения в поля ввода
                dom.e("#clientName").value = info.name; // Устанавливаем имя клиента
                dom.e("#customer_id").value = info.clientID; // Устанавливаем ID клиента
                dom.e("#priorityMakat").value = info.priority; // Устанавливаем приоритет
                dom.e("#purchaseOrder").value = info.headpay; // Устанавливаем приоритет

                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        });

        // Обработка клика по результату поиска project
        dom.in("click", "#search-responce tr.project", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                // Устанавливаем полученные значения в поля ввода
                dom.e("#projectName").value = info.name; // Устанавливаем имя в поле ввода
                dom.e("#projectRevision").value = info.revision; // Устанавливаем ревизию в поле ввода
                dom.e("#project_id").value = info.projectID; // Устанавливаем id в скрытое поле

                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        }, "body");

        // скрываем ответ от сервера при клике на странице
        dom.in('click', "body", function (event) {
            const searchAnswer = dom.e("#searchAnswer");
            // Проверяем, что клик произошел вне элемента searchAnswer и что он видим
            if (!searchAnswer.contains(event.target) && getComputedStyle(searchAnswer).display !== 'none') {
                searchAnswer.style.display = 'none';
            }
        });

        // Обработчик события ввода для обновления состояния кнопки
        $('#createOrderForm input[required], #createOrderForm textarea[required]').on('input', function () {
            $('#createOrderFBtn').prop('disabled', !checkRequiredFields());
        });

        // Обработчик события клика на список работников по проекту
        $('#workers .form-check-input').on('change', function () {
            let selectedWorkers = [];
            // Перебираем все отмеченные чекбоксы
            $('#workers .form-check-input:checked').each(function () {
                // Добавляем имя работника в массив
                selectedWorkers.push($(this).val());
            });
            // Преобразуем массив в строку, разделяя имена запятой
            $('#orderWorkers').val(selectedWorkers.join(','));

            // Проверяем и обновляем состояние кнопки
            $('#createOrderFBtn').prop('disabled', !checkRequiredFields());
        });

        // Обработчик события клика на список на кого переведен проект сейчас
        $('#forwarded li').on('click', function () {
            // Устанавливаем значение выбранного элемента в input
            $('#forwardTo').val($(this).text());
            // Проверяем и обновляем состояние кнопки
            $('#createOrderFBtn').prop('disabled', !checkRequiredFields());
        });

        // Начальная проверка при загрузке страницы
        $('#createOrderFBtn').prop('disabled', !checkRequiredFields());


        /* prioritet for order */
        $(document).on("change", "#prioritet", function () {
            // Объект для сопоставления значений с классами
            const priorityClasses = {
                "DO FIRST": "danger",
                "HIGH": "danger",
                "MEDIUM": "warning",
                "LOW": "success"
            };

            // Получаем текущее значение и применяем соответствующий класс
            const selectedValue = $(this).val();
            const selectedClass = priorityClasses[selectedValue];

            // Сначала удаляем все классы, затем добавляем нужный
            $("#prioritet").removeClass("danger warning success").addClass(selectedClass).blur();

        });

        // Обработка клика по результату поиска для места хранения
        $(document).on("click", "#storageBox", function () {
            // Отправляем POST-запрос на сервер
            $.post('', {
                // Параметры, которые вы хотите отправить на сервер
                'search-for-storage-box': $(this).val()
            }, function (data) {
                // При успешном получении ответа обновляем значение поля ввода
                $('#storageBox').val(data);
            });
        });

        // отслеживание изменения в полях формы для работы в пхп над изменениями edit-order
        let changedFields = [];
        $('.track-change').on('change', function () {
            let fieldId = $(this).data('field-id');
            if ($.inArray(fieldId, changedFields) === -1) {
                changedFields.push(fieldId);
                $('#changedFields').val(changedFields.join(','));
            }
        });

        // чекбокс надобности серийного номера для проекта
        $('#serial-required').change(function () {
            // Check if the checkbox is checked
            if ($(this).is(':checked')) {
                // Show the prompt when checked
                let userResponse = confirm($("#prompt-text").text());
                if (!userResponse) {
                    // If user clicks 'Cancel', uncheck the checkbox
                    $(this).prop('checked', false);
                }
            }
        });

    });//  конец реди док

    // Функция для проверки заполненности всех обязательных полей
    function checkRequiredFields() {
        let isValid = true;
        // Проверка обычных обязательных полей
        $('#createOrderForm input[required], #createOrderForm textarea[required]').each(function () {
            if ($(this).val() === '') {
                isValid = false;
                return false;
            }
        });

        // Дополнительная проверка для readonly поля orderWorker
        if ($('#orderWorker').val() === '') {
            isValid = false;
        }
        return isValid;
    }
</script>
</body>
</html>