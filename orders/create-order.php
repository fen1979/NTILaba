<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
// Инициализируем менеджер заказов
$orderManager = new OrderManager($user);
// Обрабатываем GET-запросы для редактирования заказа
$orderManager->handleGetRequest(RequestData::getInstance());
// Проверка свободных мест на складе
$orderManager->checkForStorageBoxes($_POST);
// Создание нового заказа
$orderManager->createOrder($_POST);
// Обновление информации о заказе
$orderManager->updateOrder($_POST);
// Создание заказа из списка проектов
$orderManager->createOrderFromProject($_GET);
// Обновление существующего заказа
$orderManager->updateExistingOrder($_POST, $_GET);
// Получаем данные для страницы
$pageData = $orderManager->getPageData();
// Используем данные для отображения на странице
$titleText = $pageData['titleText'];
$btnSubmit = $pageData['btnSubmit'];
$order = $pageData['order'];
$project = $pageData['project'];
$client = $pageData['client'];
$page = $pageData['page'];
$result = $pageData['result'];
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

        /*=====================*/
        /* CSS */
        .input-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0 8px;
            background-color: #fff;
        }

        .input-container input {
            border: none;
            flex: 1;
            min-width: 150px;
            padding: 6px 12px;
            font-size: 1rem;
            background-color: transparent;
        }

        .input-container input:focus {
            outline: none;
            box-shadow: none;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            margin-right: 8px;
        }

        .tag {
            background-color: #e9ecef;
            border-radius: 0.375rem;
            padding: 5px 10px;
            margin: 4px 0;
            margin-right: 4px;
            display: flex;
            align-items: center;
        }

        .tag .remove-tag {
            margin-left: 8px;
            cursor: pointer;
            font-weight: bold;
            color: #6c757d;
        }

        .tag .remove-tag::after {
            content: '×';
        }

        .input-container input::placeholder {
            color: #6c757d;
        }

    </style>
</head>
<body>

<?php
// NAVIGATION BAR
NavBarContent(['title' => $titleText, 'active_btn' => Y['N_ORDER'], 'user' => $user, 'page_name' => $page]); ?>

<div class="container mt-4 px-3 py-3 rounded" style="background: aliceblue;">
    <?php

    //i ПРОВЕРЯЕМ ЕСЛИ ОЗАКАЗ БЫЛ СОХРАНЕН ИЛИ ТОЛЬКО ПРИШЛИ НА СТРАНИЦУ
    //если только пришли на страницу то выводим форму для заполнения заказа
    if ($result == null) { ?>
        <!-- форма добавления нового заказа -->
        <div class="row">
            <?php $t = 'To search, click on the field and start writing. Search fields are marked with a search sign'; ?>
            <div class="col-8"><h3><small><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> </small>&nbsp; <?= $titleText ?></h3></div>
            <?php if (!isset($_GET['edit-order'])): ?>
                <div class="col-4"><h3>Order ID: &nbsp; <?= R::getCell('SELECT MAX(id) FROM ' . ORDERS) + 1; ?></h3></div>
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
                    <div class="col-6 raw-label">
                        <label for="projectName" class="form-label"><i class="bi bi-search"></i>&nbsp;Project Name <b class="text-danger">*</b></label>
                    </div>

                    <div class="col-2 one-p">
                        <label for="project_id" class="form-label">Project ID</label>
                    </div>
                    <div class="col-2 one-p">
                        <label for="projectRevision" class="form-label">Project Version</label>
                    </div>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-6 raw-label">
                        <div id="project-input-container" class="input-container">
                            <div id="project-tags" class="tags"></div>
                            <input type="text" class="searchThis form-control" id="projectName" name="projectName" data-request="project" value="<?= set_value('projectName', $project->projectname ?? ''); ?>" autocomplete="off">
                            <input type="hidden" id="projects" name="projects" value="">
                        </div>
                    </div>

                    <div class="col-2 one-p">
                        <input type="text" class="form-control" id="project_id" name="project_id"
                               value="<?= set_value('project_id', $project->id ?? '0'); ?>" readonly>
                    </div>
                    <div class="col-2 one-p">
                        <input type="text" class="form-control" id="projectRevision" name="projectRevision" readonly
                               value="<?= set_value('projectRevision', $project->revision ?? '0'); ?>">
                    </div>
                    <div class="col-auto one-p">
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
                    <div class="col-2">
                        <label for="storageBox" class="form-label">Kit Storage Box <b class="text-danger">*</b></label>
                    </div>
                    <div class="col-2">
                        <label for="storageShelf" class="form-label">Kit Storage Shelf/Place </label>
                    </div>
                    <div class="col-2">
                        <label for="date_in" class="form-label">Application date <b class="text-danger">*</b></label>
                    </div>
                    <div class="col-2">
                        <label for="date_out" class="form-label">Delivery time <b class="text-danger">*</b></label>
                    </div>
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col-2">
                        <input type="number" class="form-control track-change" id="orderAmount" name="orderAmount"
                               value="<?= set_value('orderAmount', $order['order_amount'] ?? '10') ?>" min="1" required data-field-id="qty">
                    </div>
                    <div class="col-2">
                        <input type="number" class="form-control track-change" id="fai_qty" name="fai_qty"
                               value="<?= set_value('fai_qty', $order['fai_qty'] ?? '1') ?>" min="0" data-field-id="fai_qty">
                    </div>

                    <div class="col-2">
                        <input type="text" class="form-control" id="storageBox" name="storageBox"
                               value="<?= set_value('storageBox', $order['storage_box'] ?? '1'); ?>"
                               placeholder="Field for hand writing" required>
                    </div>

                    <div class="col-2">
                        <input type="text" class="form-control track-change" id="storageShelf" name="storageShelf"
                               value="<?= set_value('storageShelf', $order['storage_shelf'] ?? ''); ?>"
                               placeholder="Write your shelf here" data-field-id="shelf">
                    </div>

                    <div class="col-2">
                        <input type="datetime-local" class="form-control track-change" id="date_in" name="date_in"
                               value="<?= $order['date_in'] ?? date('Y-m-d H:i'); ?>" required data-field-id="date_in">
                    </div>

                    <div class="col-2">
                        <input type="datetime-local" class="form-control track-change" id="date_out" name="date_out"
                               value="<?= $order['date_out'] ?? date('Y-m-d H:i'); ?>" required data-field-id="date_out">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="row g-3 align-items-center">
                    <div class="col">
                        <label for="purchaseOrder" class="form-label">Purchase Order</label>
                    </div>
                    <div class="col">
                        <label for="orderWorkers" class="form-label">Workers to Order <b class="text-danger">*</b></label>
                    </div>
                    <div class="col">
                        <label for="forwardTo" class="form-label">Forward To <b class="text-danger">*</b></label>
                    </div>
                    <div class="col">
                        <?php $t = 'To improve effectiveness, keep your mind clear.'; ?>
                        <label for="prioritet" class="form-label"><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> &nbsp;Prioritet</label>
                    </div>
                    <div class="col">
                        <label for="order-status" class="form-label">Order Status</label>
                    </div>
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col">
                        <input type="text" class="form-control track-change" id="purchaseOrder" name="purchaseOrder"
                               value="<?= set_value('purchaseOrder', $client->head_pay ?? '0'); ?>" data-field-id="head_pay">
                    </div>

                    <div class="col">
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
                                            <input type="checkbox" id="u-<?= $key; ?>" value="<?= $u['user_name']; ?>"
                                                   class="form-check-input" <?= $checked ?>>
                                            <label class="form-check-label w-100" for="u-<?= $key; ?>"><?= $u['user_name']; ?></label>
                                        </li>
                                    <?php }
                                } ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col">
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

                    <div class="col">
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

                    <div class="col">
                        <select class="form-control" name="order-status">
                            <?php
                            // если заказ на паузе то выводим только один статус для разблокировки
                            foreach (SR::getAllResourcesInGroup('status') as $key => $status) {
                                if ($key != '-1') { ?>
                                    <option value="<?= $key ?>"> <?= SR::getResourceValue('status', $key); ?></option>
                                <?php }
                            } ?>
                        </select>
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
                <textarea class="form-control track-change" id="extra" name="extra"
                          data-field-id="extra"><?= set_value('extra', $order['extra'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary form-control mt-3 mb-2" id="createOrderFBtn" name="<?= $btnSubmit['name'] ?>">
                <?= $btnSubmit['text'] ?>
            </button>
        </form>

        <?php

        // i ПРОВЕРЯЕМ ЕСЛИ ЗАКАЗ БЫЛ СОХРАНЕН
        // если заказ был сохранен успешно то выводим модальное окно для переходов по желанию пользователя
        // действия: распечатать детали заказа
        // перейти к заполнению нового прихода запчастей/товаров
        // перейти к предварительному внесению полученного от клиента
    } elseif ($result[0]) { ?>
        <!-- модальное окно для перехода между страницами ВОМ и печать деталей-->
        <div class="modal" tabindex="-1" style="display: contents;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">The order number is: <?= $result[1] ?></h5>
                    </div>

                    <div class="modal-body">
                        <p class="p-2">Order for Project number: <?= $project->id ?? '' ?>,
                            <br>
                            Name: <?= $project->projectname ?? '' ?>,
                            <br>
                            was created successfully!
                            <br>
                            Do you want to fill out the receiving invoice?
                            <br>
                            Or print out general information about the order?</p>
                    </div>
                    <div class="modal-footer">
                        <a type="button" class="btn btn-outline-warning" href="<?= "/po-replenishment?orid=$orderId" ?>">Add Incoming Invoice</a>
                        <a type="button" class="btn btn-outline-info" href="<?= "/order_pdf?pid=$project->id&orid=$orderId" ?>">Print Order details</a>
                        <a type="button" class="btn btn-outline-primary" href="<?= "/check_bom?orid=$orderId&pid=$project->id" ?>">Fill Order BOM</a>
                    </div>
                </div>
            </div>
        </div>

    <?php } ?>
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
PAGE_FOOTER($page, false);
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
        // dom.in("click", "#search-responce tr.project", function () {
        //     if (this.parentElement.dataset.info) {
        //         // Извлекаем и парсим данные из атрибута data-info
        //         let info = JSON.parse(this.parentElement.dataset.info);
        //         // Устанавливаем полученные значения в поля ввода
        //         dom.e("#projectName").value = info.name; // Устанавливаем имя в поле ввода
        //         dom.e("#projectRevision").value = info.revision; // Устанавливаем ревизию в поле ввода
        //         dom.e("#project_id").value = info.projectID; // Устанавливаем id в скрытое поле
        //
        //         flushMultipleProjects(info);
        //         // Очищаем результаты поиска
        //         dom.hide("#searchModal");
        //     }
        // });
        // Обработка клика по результату поиска project
        dom.in("click", "#search-responce tr.project", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);

                // Создаем объект проекта
                const project = {
                    id: info.projectID,
                    name: info.name,
                    revision: info.revision
                };
                // Добавляем тег
                addProjectTag(project);
                // Очищаем поле ввода
                dom.e("#projectName").value = '';
                // Скрываем результаты поиска
                dom.hide("#searchModal");
                dom.e('#projectName').focus();
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

        //i определится в будущем пользуемся ли мы автоматом для установки коробок хранения???
        // Обработка клика по результату поиска для места хранения
        dom.in("click", "#storageBox", function () {
            // Отправляем POST-запрос на сервер
            console.log(this.value)
            $.post('', {'search-for-storage-box': this.value}, function (data) {
                // При успешном получении ответа обновляем значение поля ввода
                dom.e('#storageBox').value = data;
                console.log(data)
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

    // Массив для хранения выбранных проектов
    let selectedProjects = [];

    // Функция для обновления скрытого инпута
    function updateProjectsInput(info) {
        const projectsInput = dom.e('#projects');
        projectsInput.value = selectedProjects.map(project => project.id).join(',');

        if (selectedProjects.length === 1 && info) {
            dom.e("#projectRevision").value = info.revision; // Устанавливаем ревизию в поле ввода
            dom.e("#project_id").value = info.id; // Устанавливаем id в скрытое поле
        }
        if (selectedProjects.length === 2) {
            dom.toggleClass(".one-p", "hidden");
            dom.toggleClass(".col-6.raw-label","col-12" );
            // dom.e(".raw-label").classList.add("col-12");
        }
    }

    // Функция для добавления тега
    function addProjectTag(project) {
        const tagsContainer = dom.e('#project-tags');

        // Проверяем, есть ли проект уже в списке
        if (selectedProjects.some(p => p.id === project.id)) {
            return; // Проект уже добавлен
        }

        selectedProjects.push(project);
        updateProjectsInput(project);

        // Создаем тег
        const tag = document.createElement('div');
        tag.classList.add('tag');

        const tagText = document.createElement('span');
        tagText.textContent = project.name;

        const removeBtn = document.createElement('span');
        removeBtn.classList.add('remove-tag');
        removeBtn.addEventListener('click', function () {
            removeProjectTag(project.id, tag);
        });

        tag.appendChild(tagText);
        tag.appendChild(removeBtn);
        tagsContainer.appendChild(tag);
    }

    // Функция для удаления тега
    function removeProjectTag(projectId, tagElement) {
        // Удаляем из массива
        selectedProjects = selectedProjects.filter(p => p.id !== projectId);
        updateProjectsInput();
        // Удаляем тег из DOM
        tagElement.remove();
    }


</script>
</body>
</html>