<?php
/*!
 * здесь собран код вывода для работы над проектами в Заказе
 * вкладка номер 8 (tab8)
 * функция:
 * i standardSaaemblyProjectType()
 * выводит шаги для работы над всеми проектами которые не являются СМТ сборкой
 * так же включая сборку ТН компонентов в платы
 * функция:
 * i smtAssemblyProjectType()
 * выводит код для работы над проектами которые будут собраны с помощью СМТ машины и ТН пайки компонентов
 * для данного вида сборки предусмотрен другой вид инструкций которые прописаны заранее
 * для ТН сборки будут представленны шаги с фотографиями правильности расположения компонентов
 * если такая опция будет нужна (доступна) в процессе сборки
 */

// STANDARD PROJECT ASSEMBLY TYPE
function standardAssemblyProjectType($order, $stepsData, $assy_in_progress, $amount): void
{ ?>
    <div class="step-box mt-3">
        <?php if ($order->status == 'st-8' && $assy_in_progress) {

            foreach ($stepsData as $step) {
                /* если данный шаг равен записаному в заказе то выводим его */
                if ($step['id'] == $assy_in_progress->current_stepid) {
                    echo ($step['validation']) ? '<p class="text-white bg-danger">' . $step['validation'] . '</p>' : '';
                    ?>
                    <div class="row">
                        <div class="col-8">
                            <img class="step-image rounded shrincable" src="/<?= $step['image']; ?>" alt="Hello asshole">
                            <?php if ($step['video'] != 'none') { ?>
                                <video src="<?= $step['video']; ?>" controls width="100%" height="auto">
                                    Your browser not support video
                                </video>
                            <?php } ?>
                        </div>

                        <div class="col-4 border-start">
                            <!-- step description -->
                            <div class="mb-5 border p-2">
                                <h5 class="mb-3">Step Number: <?= $step['step']; ?></h5>
                                <small>Assembly instructions</small>
                                <p class="text-primary"><?= $step['description']; ?></p>
                                <small>Rout Action</small>
                                <p class="text-primary"><?= $step['routaction'] ?? 'Route Action not available'; ?></p>
                            </div>

                            <form action="" method="post">
                                <!-- step actions form -->
                                <div class="border-top mb-2"></div>

                                <div class="mb-2">
                                    <?php $t = 'The number of parts produced for a given order according to the project step. 
                                            This field is required for any operation! If there are no manufactured parts, set the value to ZERO!
                                            Order Required Amount writen by default!'; ?>
                                    <label for="qty_done_for_step" class="form-label">
                                        <i class="bi bi-info-circle text-info fs-4" data-title="<?= $t; ?>"></i>&nbsp;
                                        QTY for this step complited &nbsp;
                                        <b class="text-danger">*</b>

                                    </label>
                                    <input type="hidden" name="assy_step_id" value="<?= $assy_in_progress->id; ?>">
                                    <input type="number" name="qty_done" id="qty_done_for_step" required class="form-control" value="<?= $amount; ?>">
                                </div>

                                <div class="border-top mb-5"></div>

                                <!-- button next step ang back to previos step -->
                                <div class="mb-2 border rounded p-2">
                                    <small>
                                        After completing work on this step, click on the <b>“Step completed, go to next”</b> button.
                                        If an error occurred and the next button was pressed!
                                        but the work on the step was not completed!
                                        Click on the <b>"Oops, my mistake! Go back"</b> button to return to the previous step.
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <?php
                                    if ($step['validation'] == 0) {
                                        /* кнопка перехода на следующий шаг */
                                        $goToNext = $step['id'] + 1;
                                        ?>
                                        <button type="submit" name="next_step" value="<?= $goToNext; ?>" class="url btn btn-outline-success">
                                            Step completed, go to next
                                        </button>
                                        <?php
                                    } else {
                                        /* кнопка вызова проверяющего */
                                        ?>
                                        <button type="submit" name="validate_step" value="<?= $step['id']; ?>" class="url btn btn-outline-danger">
                                            Click to call an inspector
                                        </button>
                                        <?php
                                    }

                                    /* кнопка возврата к предыдущему шагу если что */
                                    if ($step['step'] != 1) {
                                        $goBack = $step['id'] - 1; ?>
                                        <button type="submit" name="back_to_previos" value="<?= $goBack; ?>" class="url btn btn-outline-warning">
                                            Oops, my mistake! Go back
                                        </button>
                                    <?php } ?>
                                </div>

                                <!-- если над проектом работают более одного человека то выводим кнопку пропустить шаг -->
                                <?php
                                $workers = explode(',', $order->workers);
                                if (count($workers) > 1) {
                                    ?>
                                    <div class="mb-2 border rounded p-2">
                                        <small>
                                            <b class="text-warning">NOTICE!</b><br>
                                            If you are not the only person working on this project and did not complete this step yourself,
                                            please click the <b>“Skip this Step”</b> button.
                                            If you accidentally skipped a step, click the <b>"Oops, my mistake! Go back"</b>
                                            button to return to the previous step.
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <?php $skipStep = $step['id']; ?>
                                        <button type="submit" name="skip_this_step" value="<?= $skipStep; ?>" class="url btn btn-outline-info">
                                            Skip this Step
                                        </button>
                                    </div>

                                    <div class="mb-2 border rounded p-2">
                                        <small>
                                            <b class="text-warning">NOTICE!</b><br>
                                            If for some reason the order needs to be paused,
                                            you will need to indicate the reason why the order was put on hold.
                                            For this action, click on the <b>“Set Order On Pause”</b> button.
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <button type="submit" name="set_on_pause" value="<?= $order->id; ?>" class="url btn btn-outline-danger">
                                            Set Order On Pause
                                        </button>
                                    </div>
                                <?php } ?>
                            </form>

                            <!-- forward step to user -->
                            <form action="" method="POST" id="forward_step">
                                <div class="mb-2 border rounded p-2">
                                    <small>
                                        <b class="text-danger">WARNING!</b><br>
                                        Transferring a step to another employee will entail a number of changes in
                                        the documentation for the execution of order steps!
                                        Your progress will be deleted, to select a person, click on <b>“Forward Step To User”</b> button.
                                    </small>
                                </div>

                                <input type="hidden" name="step_id" value="<?= $step['id']; ?>">
                                <input type="hidden" name="assy_step_id" value="<?= $assy_in_progress->id; ?>">
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle fs-5 btn-outline-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Forward Step To User
                                    </button>
                                    <ul class="dropdown-menu ps-2 pe-2">
                                        <?php
                                        $allUsers = R::find(USERS);
                                        foreach ($allUsers as $key => $u) {
                                            if ($u['id'] != '1') {
                                                ?>
                                                <div class="radio-item">
                                                    <input type="radio" name="workers" id="in-<?= $key; ?>" value="<?= $u['id']; ?>" class="me-2">
                                                    <label for="in-<?= $key; ?>" style="width: 75%;"> <?= $u['user_name']; ?></label>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                        <li>
                                            <button class="btn btn-outline-success form-control" type="submit" name="forward_step_to_user">
                                                Forward step
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php

                    break;
                } // if step = assy flow step now
            } // foreach

        } elseif ($order->status == 'st-8') {
            // if order status = st-8
            ?>
            <h3>
                To continue assembling the project for this order,
                you need to go to the "Project Steps" tab and select the required step to work on!
            </h3>
        <?php } else echo 'Order assembly table'; ?>
    </div>
    <?php
}

// SMT PROJECT ASSEMBLY TYPE (tab8)
function smtAssemblyProjectType($order, $amount, $projectBom): void
{
    if ($order->status == 'st-8') {
        // Получаем массив всех состояний фидеров $feederStates - это массив, где каждый элемент соответствует feeder_state для каждого ID от 1 до 86
        $query = "SELECT feeder_state FROM " . SMT_LINE . " WHERE id BETWEEN 1 AND 86";
        $feederStates = R::getCol($query);

        foreach ($projectBom as $item) {
            if ($item['item_in_work'] == '0') {
                $feeder_amount = 86; // feeders counter
                $feeders = 66; // feeders slots
                $sticks = 5; // stick slots
                $trays = 15; // tray positions
                ?>
                <div class="row border-bottom" style="margin: 1rem 0 0 0">
                    <div class="col-md-6 border-end fs-5">
                        <h4 class="border-bottom mb-2">Component Information</h4>
                        <p>
                            <strong>Manufacture P/N:</strong>
                            <span id="part_number_ref"><?= empty($item['manufacture_pn']) ? 'N/A' : $item['manufacture_pn']; ?></span>
                        </p>
                        <?php if (!empty($item['part_name'])) { ?>
                            <p><strong>Part Name:</strong> <span><?= $item['part_name']; ?></span></p>
                        <?php } ?>
                        <p><strong>Part Value:</strong> <span><?= $item['part_value']; ?></span></p>
                        <p><strong>Mounting Type:</strong> <span><?= $item['mounting_type'] ?? ''; ?></span></p>
                        <p><strong>Footprint:</strong> <span><?= $item['footprint']; ?></span></p>
                        <p><strong>Note:</strong> <span><?= $item['notes']; ?></span></p>
                        <?php
                        // component custom part number if exist
                        $pn = !empty($item['owner_pn'] && $item['owner_pn'] != 'NTI') ? $item['owner_pn'] : '';
                        ?>
                        <p><strong>Owner P/N:</strong> <span><?= $pn; ?></span></p>

                        <?php
                        // trying to find component in our DB
                        $it = WareHouse::GetOneItemFromWarehouse($item['manufacture_pn'], $item['owner_pn'], $item['items_id']);
                        if ($it) {
                            // if component exist then writing where is stored
                            $stor = $it->storage_shelf ?? 'N/A' . ' / ' . $it->storage_box ?? 'N/A';
                            ?>
                            <div class="warning p-2 rounded">
                                <p><strong>Storage place:</strong> <span><?= $stor; ?></span></p>
                                <p><strong>Storage Status:</strong> <span><?= $it->storage_state ?? 'Uncnown'; ?></span></p>
                            </div>
                        <?php } ?>

                        <p><strong>Description:</strong> <span><?= $item['description']; ?></span></p>
                    </div>

                    <div class="col-md-6">
                        <form action="" method="post">

                            <input type="hidden" name="item_id" value="<?= $item['id']; ?>">
                            <input type="hidden" name="needed_amount" value="<?= $item['amount'] * $amount; ?>">
                            <input type="hidden" name="order_id" value="<?= $order->id; ?>">
                            <input type="hidden" name="storage_state" value="<?= STORAGE_STATUS['smt']; ?>">
                            <input type="hidden" name="warehouse_id" value="<?= $it->id ?? ''; ?>">

                            <div class="mb-3">
                                <label for="feeder" class="form-label">Feeder for component</label>
                                <input type="number" id="feederInput" placeholder="Write feeder number" class="form-control large-input mb-3">
                                <div id="error-message" class="text-danger" style="display: none;"></div>

                                <select class="form-select large-input" id="feeder" name="feeder" data-counter="<?= $feeder_amount; ?>" required>
                                    <?php
                                    for ($i = 1; $i <= $feeder_amount; $i++) {
                                        list($d, $c) = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? ['disabled', ''] : ['', $i];
                                        if ($i <= $feeders)
                                            echo "<option value='$c' $d>Feeder $i</option>";
                                        elseif ($i <= ($feeders + $sticks))
                                            echo "<option value='$c' $d>Stick $i</option>";
                                        elseif ($i <= ($feeders + $sticks + $trays))
                                            echo "<option value='$c' $d>Tray $i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="part_number" class="form-label">Manufacture Part Number <b class="text-danger">*</b></i></label>
                                <input type="text" class="form-control large-input" placeholder="Scan Manufacture number from reel"
                                       name="part_number" id="part_number" required>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary large-button" id="submit-btn" name="smt_component" disabled>
                                    Confirm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                break;
            }
        }
        ?>
        <div class="wrapper-box">
            <div class="wrapper">
                <div class="column">
                    <?php
                    for ($i = 1; $i <= 33; $i++) {
                        $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success';
                        ?>
                        <div class="cell <?= $c ?>" id="cell-<?= $i ?>"><?= $i ?></div>
                    <?php } ?>
                </div>
                <div class="c_wrapper">
                    <div class="c_center">
                        <!-- Генерация рядов в центральной колонке -->
                        <?php for ($i = 67; $i <= 71; $i++) {
                            $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success'; ?>
                            <div class="cell <?= $c ?>" id="cell-<?= $i ?>">Stick <?= $i ?></div>
                        <?php } ?>
                    </div>
                    <div class="c_center">
                        <!-- Генерация рядов в центральной колонке -->
                        <?php for ($i = 72; $i <= 76; $i++) {
                            $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success';
                            ?>
                            <div class="cell <?= $c ?>" id="cell-<?= $i ?>">Tray <?= $i ?></div>
                        <?php } ?>
                    </div>
                    <div class="c_center">
                        <!-- Генерация рядов в центральной колонке -->
                        <?php for ($i = 77; $i <= 81; $i++) {
                            $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success';
                            ?>
                            <div class="cell <?= $c ?>" id="cell-<?= $i ?>">Tray <?= $i ?></div>
                        <?php } ?>
                    </div>
                    <div class="c_center">
                        <!-- Генерация рядов в центральной колонке -->
                        <?php for ($i = 82; $i <= 86; $i++) {
                            $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success';
                            ?>
                            <div class="cell <?= $c ?>" id="cell-<?= $i ?>">Tray <?= $i ?></div>
                        <?php } ?>
                    </div>
                </div>
                <div class="column">
                    <?php
                    for ($i = 34; $i <= 66; $i++) {
                        $c = (!empty($feederStates) && $feederStates[$i - 1] == 1) ? 'warning' : 'success';
                        ?>
                        <div class="cell <?= $c ?>" id="cell-<?= $i ?>"><?= $i ?></div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<h3 class="mt-3">The status of this order does not allow you to start working on the order!</h3>';
    }
}