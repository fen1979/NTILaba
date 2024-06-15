<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();

$user = $_SESSION['userBean'];
$page = 'customers';
$client = null;
$saveButtonText = 'Save New Customer';

/* creation new customer from creation new order page */
if (isset($_GET['routed-from']) || isset($_GET['search'])) {
    $saveButtonText = 'Save and Back to ' . $_GET['routed-from'];
}

// create or update and save new customer
function backDataToRoutedPage(array $get, array $args, $urlData): void
{
    // routed from create project page
    if (isset($get['routed-from'])) {
        $_SESSION['info'] = $args;
        $url = 'customer_name=' . urlencode($urlData->name) . '&priority=' . urlencode($urlData->priority) .
            '&customer_id=' . urlencode($urlData->id);
        switch ($get['routed-from']) {
            // back to project creation page with parameters
            case 'create-project':
                header("Location: new_project?$url");
                break;
            // back to order creation page with parameters
            case 'create-order':
                header("Location: new_order?$url");
                break;
        }
        exit();
    }
}

if (isset($_POST['createCstomer'])) {
    $extraPhones = ['phone_1' => _E($_POST['extraPhone_1']), 'phone_2' => _E($_POST['extraPhone_2'])];
    $extraContact = ['contact_1' => _E($_POST['extraContact_1']), 'contact_2' => _E($_POST['extraContact_2'])];
    $extraEmail = ['email_1' => _E($_POST['extraEmail_1']), 'email_2' => _E($_POST['extraEmail_2'])];

    $name = _E($_POST['customerName']);
    $priority = _E($_POST['priorityMakat']);
    $c = null;
    // Проверяем, состоит ли строка только из чисел
    if (isset($_POST['cuid']) && ctype_digit($_POST['cuid'])) {
        $c = R::load(CLIENTS, $_POST['cuid']);
    } else {
        $c = R::dispense(CLIENTS);
    }

    $c->name = $name;
    $c->head_pay = _E($_POST['headPay']);
    $c->priority = $priority;
    $c->address = _E($_POST['address']);
    $c->phone = _E($_POST['phone']);
    $c->contact = _E($_POST['contact']);
    $c->email = _E($_POST['email']);
    $c->extra_phone = json_encode($extraPhones ?? ['']);
    $c->extra_contact = json_encode($extraContact ?? ['']);
    $c->extra_email = json_encode($extraEmail ?? ['']);
    $c->information = _E($_POST['information']);
    $c->date_in = _E($_POST['dateIn']);
    $id = R::store($c);

    $args = ['color' => 'success', 'info' => 'Customer Saved successfully!'];

    // if routed from order or project pages back data to page
    $urlData['id'] = $id;
    $urlData['name'] = $name;
    $urlData['priority'] = $priority;
    backDataToRoutedPage($_GET, $args, $urlData);

    // if customer was edited
    if (isset($_POST['cuid'])) {
        $args['info'] = 'Customer Edited successfully!';
    }
}

// get data for customer editing
if (isset($_POST['edit-customer']) && isset($_POST['cuid'])) {
    $client = R::load(CLIENTS, _E($_POST['cuid']));
}

/* настройки вывода от пользователя */
if ($user) {
    $settings = getUserSettings($user, CLIENTS);
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
        .t-body:hover {
            background: #baecf6;
        }

        .t-body .col:first-child:hover {
            cursor: pointer;
            background: #dc3545;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
<?php
$title = ['title' => 'Customers', 'app_role' => $user['app_role']];
NavBarContent($page, $title, null, Y['CLIENT']);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid">
    <div class="row">
        <!-- CUSTOMER ADDING FORM -->
        <div class="col-4 rounded p-2 ms-2" style="background: antiquewhite;">
            <form id="createOrderForm" action="" method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="cuid" value="<?= $client['id'] ?? '0' ?>">
                <div class="mb-3">
                    <label for="customerName" class="form-label">Customer Name <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="customerName" name="customerName"
                           value="<?= set_value('customerName', $client['name'] ?? ''); ?>"
                           required>
                </div>

                <div class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-6">
                            <label for="priorityMakat" class="form-label">Priority <b class="text-danger">*</b></label>
                        </div>
                        <div class="col-6">
                            <label for="headPay" class="form-label">Head Pay <b class="text-danger">*</b></label>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-6">
                            <input type="text" class="form-control" id="priorityMakat" name="priorityMakat"
                                   value="<?= set_value('priorityMakat', $client['priority'] ?? '0'); ?>" required>
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control" id="headPay" name="headPay"
                                   value="<?= set_value('head_pay', $client['head_pay'] ?? '0'); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="address" name="address"
                           value="<?= set_value('address', $client['address'] ?? ''); ?>" required>
                </div>

                <!-- PHONES FIELDS -->
                <div class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-4">
                            <label for="phone" class="form-label">Phone Number <b class="text-danger">*</b></label>
                        </div>
                        <div class="col-4">
                            <label for="phone_1" class="form-label">Extra Phone</label>
                        </div>
                        <div class="col-4">
                            <label for="phone_2" class="form-label">Extra Phone</label>
                        </div>
                        <div class="col-4">
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= set_value('phone', $client['phone'] ?? ''); ?>" required>
                        </div>
                        <?php
                        // Преобразование строки JSON обратно в массив
                        if (!empty($client['extra_phone'])) {
                            $extraPhones = json_decode($client['extra_phone'], true);
                        }
                        ?>
                        <div class="col-4">
                            <input type="tel" class="form-control" id="phone_1" name="extraPhone_1"
                                   value="<?= set_value('extraPhone_1', $extraPhones['phone_1'] ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <input type="tel" class="form-control" id="phone_2" name="extraPhone_2"
                                   value="<?= set_value('extraPhone_2', $extraPhones['phone_2'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- CONTACT NAME FIELDS-->
                <div class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-4">
                            <label for="contact" class="form-label">Contact Name <b class="text-danger">*</b></label>
                        </div>
                        <div class="col-4">
                            <label for="contact_1" class="form-label">Extra Contact</label>
                        </div>
                        <div class="col-4">
                            <label for="contact_2" class="form-label">Extra Contact</label>
                        </div>
                        <div class="col-4">
                            <input type="text" class="form-control" id="contact" name="contact"
                                   value="<?= set_value('contact', $client['contact'] ?? ''); ?>" required>
                        </div>
                        <?php
                        // Преобразование строки JSON обратно в массив
                        if (!empty($client['extra_contact'])) {
                            $extraContact = json_decode($client['extra_contact'], true);
                        }
                        ?>
                        <div class="col-4">
                            <input type="text" class="form-control" id="contact_1" name="extraContact_1"
                                   value="<?= set_value('extraContact_1', $extraContact['contact_1'] ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <input type="text" class="form-control" id="contact_2" name="extraContact_2"
                                   value="<?= set_value('extraContact_2', $extraContact['contact_2'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- EMAILS FIELDS -->
                <div class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-4">
                            <label for="email" class="form-label">Contact Email <b class="text-danger">*</b></label>
                        </div>
                        <div class="col-4">
                            <label for="email_1" class="form-label">Extra Email</label>
                        </div>
                        <div class="col-4">
                            <label for="email_2" class="form-label">Extra Email</label>
                        </div>
                        <div class="col-4">
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= set_value('email', $client['email'] ?? ''); ?>" required>
                        </div>
                        <?php
                        // Преобразование строки JSON обратно в массив
                        if (!empty($client['extra_email'])) {
                            $extraContact = json_decode($client['extra_email'], true);
                        }
                        ?>
                        <div class="col-4">
                            <input type="email" class="form-control" id="email_1" name="extraEmail_1"
                                   value="<?= set_value('extraEmail_1', $extraContact['email_1'] ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <input type="email" class="form-control" id="email_2" name="extraEmail_2"
                                   value="<?= set_value('extraEmail_2', $extraContact['email_2'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <?php $t = 'Here write some additional information for Worker or any reasons.'; ?>
                    <label for="information" class="form-label">
                        <i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> &nbsp;
                        Additional Information
                    </label>
                    <textarea class="form-control" id="information" name="information"><?= set_value('information', $client['information'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Incoming Date</label>
                    <input type="datetime-local" class="form-control" id="date" name="dateIn" value="<?= $client['date_in'] ?? date('Y-m-d H:i'); ?>">
                </div>

                <button type="submit" class="btn btn-primary form-control mb-2 mt-3" name="createCstomer">
                    <?= $saveButtonText; ?>
                    <i class="bi bi-people-fill"></i>
                </button>
            </form>
        </div>

        <!-- CUSTOMERS TABLE VIEW -->
        <div class="col ms-2">
            <!-- header -->
            <div class="row secondary rounded p-2">
                <?php
                if ($settings) {
                    foreach ($settings as $item) {
                        echo '<div class="col">' . L::TABLES(CLIENTS, $item) . '</div>';
                    }
                } ?>
            </div>

            <!-- body -->
            <?php
            $cl = R::findAll(CLIENTS, 'ORDER BY name ASC');
            if ($cl) {
                foreach ($cl as $line) {
                    ?>
                    <div class="row t-body">
                        <?php

                        if ($settings) {
                            // creating table using user settings
                            foreach ($settings as $k => $item) {
                                $click = ($k === 0) ? 'onclick="changeClientInformation(' . $line['id'] . ')"' : '';
                                ?>
                                <div class="col p-2 border-bottom" <?= $click ?>>
                                    <?= $line[$item]; ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <form action="" method="post" id="tmp-form" class="hidden">
        <input type="hidden" name="cuid" id="cuid">
        <input type="hidden" name="edit-customer">
    </form>
</div>

<?php ScriptContent($page); ?>
<script>
    // document.addEventListener("DOMContentLoaded", function () {
    //     // Обработка клика по результату поиска клиента
    //     dom.in("click", "#search-responce tr.customer", function () {
    //         if (this.parentElement.dataset.info) {
    //             // Извлекаем и парсим данные из атрибута data-info
    //             let info = JSON.parse(this.parentElement.dataset.info);
    //             dom.e("#owner").value = info.name; // Устанавливаем имя клиента
    //             dom.e("#owner-id").value = info.clientID; // Устанавливаем ID клиента
    //             // Очищаем результаты поиска
    //             dom.hide("#searchModal);
    //         }
    //     });
    // }); //  конец реди док

    function changeClientInformation(id) {
        dom.e("#cuid").value = id;
        dom.e("#tmp-form").submit();
    }
</script>
</body>
</html>