<?php

// контент первого таба (TAB1) на странице выводится как функция для удобства редактирования кода
function getOrderInformationHTML($orderid, $order, $customer, $project, $projectBom, $assy_in_progress, $chatLastMsg, $amount)
{
    ?>
    <!-- TAB HEADER -->
    <!-- кол-во, имя проекта, смена статуса, выбор работника for admins -->
    <div class="row border-bottom" style="margin: 0">
        <div class="col-3">
            <h4 class="mb-3 ps-3 pt-2"><?= $project['projectname']; ?> &nbsp;
                <b class="text-warning">QTY: &nbsp;</b><?= $amount; ?>
            </h4>
        </div>

        <!--  изменение статуса заказа -->
        <?php if (isUserRole(ROLE_ADMIN)) { ?>
            <div class="col-2">
                <form action="" method="POST" class="form" id="setStatus">
                    <input type="hidden" name="set-order-status" readonly>
                    <input type="hidden" name="order_id" value="<?= $orderid; ?>">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle fs-5 bg-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Set Order Status
                        </button>
                        <ul class="dropdown-menu ps-2 pe-2">
                            <?php
                            // если заказ на паузе то выводим только один статус для разблокировки
                            if (isset($order->status) && $order->status != 'st-6') {
                                foreach (L::STATUS() as $key => $status) {
                                    if ($key != -1) {
                                        $checked = ($key == $order->status) ? 'checked' : '';
                                        ?>
                                        <div class="radio-item">
                                            <input type="radio" name="status" id="in-<?= $key; ?>" value="<?= $key; ?>" <?= $checked; ?> class="me-2">
                                            <label for="in-<?= $key; ?>"> <?= L::STATUS((string)$key); ?></label>
                                        </div>
                                        <?php
                                    }
                                }
                            } else {
                                // статус в работу для разблокировки заказа
                                $key = 'st-8';
                                ?>
                                <div class="radio-item">
                                    <input type="radio" name="status" id="in-<?= $key; ?>" value="<?= $key; ?>" class="me-2">
                                    <label for="in-<?= $key; ?>"> <?= L::STATUS($key); ?></label>
                                </div>
                                <?php
                            } ?>
                            <li>
                                <button class="btn btn-outline-success form-control" type="submit">Save Changes</button>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>
        <?php } ?>

        <!--  установка работника для проекта -->
        <?php if (isUserRole(ROLE_ADMIN)) { ?>
            <div class="col-2">
                <form action="" method="POST" class="form">
                    <input type="hidden" name="set-order-user">
                    <input type="hidden" name="order_id" value="<?= $orderid; ?>">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle fs-5 bg-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Add or Remove User
                        </button>
                        <ul class="dropdown-menu" style="padding: 5%;">
                            <?php
                            $allUsers = R::find(USERS);
                            $s = explode(',', $order['workers']);
                            foreach ($allUsers as $key => $u) {
                                if ($u['id'] != '1') {
                                    $checked = (in_array($u['user_name'], $s)) ? 'checked' : '';
                                    ?>
                                    <li class="form-check dropdown-item">
                                        <input type="checkbox" id="u-<?= $key; ?>" name="users[]" value="<?= $u['user_name']; ?>" <?= $checked; ?>
                                               class="form-check-input">&nbsp;
                                        <label for="u-<?= $key; ?>" class="form-check-label"><?= $u['user_name']; ?></label>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                            <li>
                                <button type="submit" class="btn btn-outline-success form-control">Save Changes</button>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>
        <?php } ?>
        <!--  местный вывод информации -->
        <div class="col p-2">
            <h5>For: <small class="text-primary"><?= $order['workers']; ?></small></h5>
        </div>
    </div>

    <!--   TAB BODY -->
    <!-- информация о клиенте, статусе заказа в целом, доп информация -->
    <div class="row mt-3" style="margin: 0">
        <!--  Order Information LEFT SIDE -->
        <div class="col-3 p-2">
            <div class="mb-3">
                <h5>Order Information</h5>
                <?php $pri = $order['prioritet'] ?? 'MEDIUM'; ?>
                <p class="<?= strtolower($pri); ?> fs-5 ps-2 rounded">Prioritet: <?= $pri; ?></p>
                <p class="fs-5 ps-2">
                    Storage Shelf/Box:&nbsp;
                    <?= ($order['storage_shelf'] ?? 'A0') . '/' . ($order['storage_box'] ?? '1'); ?>
                </p>
                <p class="fs-5 ps-2">Date In: <?= $order['date_in']; ?></p>
                <p class="fs-5 ps-2">Extra: <?= $order['extra']; ?></p>
                <?php if ($order['serial_required'] == 1): ?>
                    <p class="fs-5 ps-2 danger rounded text-white">Each unit required Serial Number</p>
                <?php endif; ?>
            </div>

            <!--  Chat Log -->
            <?php if (!empty($chatLastMsg['message'])) { ?>
                <h5 class="ps-2">Chat Log</h5>
                <div class="mb-3 border p-2 rounded">
                    <p><?= $chatLastMsg['message']; ?></p>
                    <div>
                        <small><?= $chatLastMsg['date_in'] ?? ''; ?></small>
                        <small><?= $chatLastMsg['user_name'] ?? ''; ?></small>
                    </div>
                </div>
            <?php } ?>

            <!--  BUTTONS FOR DOWNLOAD BOM AND PRINT ROUTECARD -->
            <?php if (isUserRole(ROLE_ADMIN)) { ?>
                <div class="mt-3">

                    <?php
                    $url = BASE_URL . "{$project->projectdir}docs/order_bom_for_{$project->projectname}_.xlsx";
                    $path = "{$project->projectdir}docs/order_bom_for_{$project->projectname}_.xlsx";
                    $d = (is_file($path)) ? '' : 'hidden';
                    ?>
                    <button type="button" id="download_bom" class="btn btn-outline-dark form-control mt-2" value="<?= $order->id; ?>">
                        Create file BOM <i class="bi bi-filetype-xlsx"></i>
                    </button>

                    <a role="button" href="<?= $url; ?>" download class="btn btn-outline-info <?= $d; ?> form-control mt-2" id="download_link">
                        File ready click for download <i class="bi bi-cloud-download-fill"></i>
                    </a>

                    <?php $url = "/route_card?pid=$project->id&orid=$orderid"; ?>
                    <a role="button" class="btn btn-outline-warning form-control mt-2" target="_blank" href="<?= $url; ?>">
                        Route Card PDF <i class="bi bi-diagram-3"></i>
                    </a>

                    <a role="button" href="/assy_flow_pdf?pid=<?= $project->id ?>" target="_blank" class="btn btn-outline-sunset form-control mt-2">
                        Assembly steps PDF <i class="bi bi-bar-chart-steps"></i>
                    </a>
                </div>
            <?php } ?>
        </div>

        <?php
        // information about order for admins use only all statuses MIDDLE SIDE
        if (isUserRole(ROLE_ADMIN)) { ?>
            <div class="col-3 border-start sunset">
                <!-- Customer Information -->
                <div class="mb-3">
                    <h5>Customer Information</h5>
                    <p>Name: <?= $customer['name']; ?></p>
                    <p>Address: <?= $customer['address']; ?></p>
                    <p>Phone: <?= $customer['phone']; ?></p>
                    <p>Contact: <?= $customer['contact']; ?></p>
                    <p>Information: <?= $customer['information']; ?></p>
                    <p>Extra: <?= $customer['extra']; ?></p>
                </div>

                <!-- Order Information -->
                <div class="mb-3">
                    <h5>Order Information</h5>
                    <p>Date In: <?= $order['date_in']; ?></p>
                    <p>Purchase Order: <?= $order['purchase_order']; ?></p>
                    <p>Client Priority: <?= $order['client_priority']; ?></p>
                    <p>First Quantity: <?= $order['first_qty']; ?></p>
                    <p>Extra: <?= $order['extra']; ?></p>
                </div>
            </div>
            <?php
        }
        ?>
        <!--  information for worker use RIGHT SIDE -->
        <div class="col border-start">
            <!-- statuses of order -->
            <div class="mb-3">
                <h4 class="<?= L::STATUS($order['status'], 1); ?> p-2 rounded mb-2">
                    Order Status:
                    <small>
                        <?= L::STATUS($order['status']); ?>
                    </small>
                </h4>

                <h4 class="info p-2 rounded">
                    Progress Status:
                    <small>
                        <?= Orders::getOrderProgress($order); ?>
                    </small>
                </h4>
            </div>
            <?php
            /*  output for status st-1 (Approved for work) */
            // проверка полноценности наличия запчастей
            $isBomComplite = Orders::isBomComplite($projectBom, $order);
            if ($order->status == 'st-1' && $isBomComplite) {

                // проверка возможнонго количества для сборки при частичном наличии запчастей
                // выводится при разрешенной частичной сборке
                if ($order['pre_assy']) {
                    $act_qty = Orders::getActualAmountForPartialOrderCollection($projectBom, $order);
                    echo '<h4 class="warning text-danger p-2 rounded blinking">Partial order collection!!! &nbsp;&nbsp;&nbsp; Amount for build: ' .
                        $act_qty . '</h4>';
                }

                // вывод чек листа для работника после которого начинается выполнение заказа
                ?>
                <h4>
                    Before you start this order pass check list
                    &nbsp;
                    <i class="bi bi-check2-square text-danger hidden" id="check-all-once">Check All</i>
                </h4>
                <form action="" method="post" class="form p-3">
                    <?php foreach (CHECK_BOX as $ix => $check) : ?>
                        <div class="form-check">
                            <input id="ck-<?= $ix; ?>" type="checkbox" class="form-check-input workflow">
                            <label for="ck-<?= $ix; ?>" class="form-check-label"><?= $check; ?></label>
                        </div>
                    <?php endforeach; ?>

                    <?php if (isset($order->serial_required) && $order->serial_required != 0): ?>
                        <div class="mt-2 mb-2">
                            <label for="serial-required">First serial number for unit</label>
                            <input type="text" id="serial-required" name="serial_required" class="form-control"
                                   placeholder="Write here start serial number" required>
                        </div>
                    <?php endif; ?>
                    <!-- айди заказа на всякий случай -->
                    <input type="hidden" name="orid" value="<?= $order['id']; ?>">
                    <!-- переключатель событий -->
                    <input type="hidden" name="order-state" value="<?= $order['id']; ?>">
                    <button type="submit" id="order-progress-init" name="order-progress-init" class="btn btn-success form-control mt-3">Get to Work</button>
                </form>

                <?php
                // если компонентов для сборки не достаточно или нет совсем
                // предоставляем возможность редактирования БОМа заказа
            } elseif ($order->status == 'st-1') {
                $url = "/check_bom?orid=$order->id&pid=$project->id&tab=tab1";
                ?>
                <h4 class="p-2">
                    The spare parts for this order were either not fully assembled or incorrectly supplied!
                    Please check the BOM and the availability of spare parts before starting to work on the order.
                    Replenish any missing items in the warehouse.
                    Once the components for the order are fully accounted for, you will be able to commence work on the order.
                    If the order was only partially placed but allowed for assembly despite not having a complete set of spare parts,
                    indicate this in the order's BOM.
                </h4>
                <?php if (isUserRole(ROLE_ADMIN)) { ?>
                    <a href="<?= $url; ?>" role="button" class="btn btn-outline-dark">Edit BOM for this Order</a>
                    <?php
                }
            }

            /*  output for status st-8 (order in work) */
            if ($order->status == 'st-8' && $assy_in_progress) { ?>
                <h4>Order in work, get back to yours last step.</h4>
                <p>Last step number: <b><?= $assy_in_progress->current_step ?? 'none'; ?></b></p>
                <p>Sart working on this step: <b><?= $assy_in_progress->workstart ?? 'none'; ?></b></p>

                <!--  кнопка перехода к последнему шагу по сборке для данного работника -->
                <form action="" method="post">
                    <input type="hidden" name="assyid" value="<?= $assy_in_progress->id; ?>">
                    <button type="submit" id="backToWork" name="backToWork" class="btn btn-success form-control mt-3"
                            value="<?= $assy_in_progress->current_stepid ?? null; ?>">
                        Continue
                    </button>
                </form>
            <?php } elseif ($order->status == 'st-8') { ?>
                <h3>To continue assembling the project for this order, you need to go to the "Project Steps" tab and select the required step to work on!</h3>
            <?php } ?>
        </div>
    </div>
<?php }