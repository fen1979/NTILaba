<?php
/*!
 * здесь собран код вывода для работы над проектами в Заказе
 * вкладка номер 8 (tab8)
 *
 * вывод код для работы над проектами которые будут собраны с помощью СМТ машины и ТН пайки компонентов
 * для данного вида сборки предусмотрен другой вид инструкций которые прописаны заранее
 * для ТН сборки будут представленны шаги с фотографиями правильности расположения компонентов
 * если такая опция будет нужна (доступна) в процессе сборки
 */


// SMT PROJECT ASSEMBLY TYPE (tab8)

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
