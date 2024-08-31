<?php if ($projectBom) { ?>
    <table class="p-3" id="order-bom-table">
        <!-- header -->
        <thead>
        <tr style="white-space: nowrap">
            <?php list($tHead, $settings) = CreateTableHeadByUserSettings(
                $user, 'order-bom-table', PROJECT_BOM, '<th>Shelf / Box</th><th>Aqtual QTY [PCS, M]</th>');
            echo $tHead;
            ?>
        </tr>
        </thead>
        <!-- table -->
        <tbody>
        <?php
        foreach ($projectBom as $line) {
            $actual_qty = WareHouse::GetActualQtyForItem($line['customerid'], $line['item_id'] ?? '');
            $length = (double)$line['length_mm'] ?? 0;
            $qty = (int)$line['amount'];
            $oqty = (int)$order['order_amount'];
            // length in meters
            $bom_qty = empty($length) ? $qty * $oqty : (($qty * $length) / 1000) * $oqty;
            $color = ($actual_qty >= $bom_qty) ? 'success' : 'danger';
            ?>
            <tr class="item-list <?= $color; ?>">
                <?php
                if ($settings) {
                    foreach ($settings as $item => $_) {
                        if ($item == 'amount') {
                            $it = $line[$item] * $order['order_amount'];
                        } elseif ($item == 'length_mm') {
                            $m = $line[$item] * $order['order_amount'] * $line['amount'] / 1000;
                            $it = !empty($m) ? "$m meter" : '---';
                        } else {
                            $it = $line[$item];
                        }
                        ?>
                        <td><?= $it; ?></td>
                        <?php
                    }
                }

                $storage = WareHouse::GetOneItemFromWarehouse($line['manufacture_pn'], $line['owner_pn'], $line['item_id']);
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

    <!-- form for reserve this bom for project -->
    <form action="" method="post" class="form mt-3">
        <label for="btn-reserve-bom">Reserve BOM items for this order</label>
        <br>
        <?php
        if ($reserve > 0) : ?>
            <button id="btn-unreserve-bom" name="do-unreserve-bom" class="btn btn-outline-success ">
                Undo Reserved BOM for this order
            </button>
        <?php else: ?>
            <button id="btn-reserve-bom" name="do-reserve-bom" class="btn btn-outline-success ">Do Reserve</button>
        <?php endif; ?>
    </form>
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