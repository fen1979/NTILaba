<?php
/**
 * @param $result
 * @param $col
 * @return void
 * функция вывода поиска на странице добавления клиента
 */
function viewCustomer($result, $col)
{
    foreach ($result as $item) {
        // Подготавливаем данные для атрибута data-info
        $infoData = json_encode([
            'name' => $item[$col[0]],
            'contact' => $item[$col[1]],
            'info' => $item[$col[2]],
            'priority' => $item[$col[3]],
            'clientID' => $item['id'],
            'headpay' => $item['head_pay']
        ]);
        ?>
        <p class="customer rounded border-bottom" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            ID: <span class="text-info me-2"><?= $item['id']; ?></span>
            Name: <span class="text-info me-2"><?= $item[$col[0]]; ?></span>
            Priority: <span class="text-info me-2"><?= $item[$col[3]]; ?></span>
            Contact: <span class="text-info me-2"><?= $item[$col[1]]; ?></span>
            Info: <span class="text-info me-2"><?= $item[$col[2]]; ?></span>
        </p>
        <?php
    }
}

/**
 * @param $result
 * @param $col
 * @return void
 * функция вывода результатов поиска на странице добавления заказа
 * форма поиска по проектам
 */
function viewLineProject($result, $col)
{
    foreach ($result as $item) {
        // Подготавливаем данные для атрибута data-info
        $infoData = json_encode([
            'name' => $item[$col[0]],
            'client' => $item[$col[1]],
            'revision' => $item[$col[2]],
            'projectID' => $item['id'],
        ]);
        ?>
        <p class="project rounded border-bottom" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            ID: <span class="text-danger me-2"><?= $item['id']; ?></span>
            Vers: <span class="text-info me-2"><?= $item[$col[2]]; ?></span>
            Name: <span class="text-info me-2"><?= $item[$col[0]]; ?></span>&nbsp;
            Customer: <span class="text-info me-2"><?= $item[$col[1]]; ?></span>&nbsp;
        </p>
        <?php
    }
}

/**
 * @param $result
 * @return void
 * функция вывода результата поиска на странице создания ВОМ заказа
 */
function viewParts($result)
{
    foreach ($result as $item) {
        // Подготавливаем данные для атрибута data-info
        $infoData = json_encode([
            'partName' => "{$item['part_name']}, {$item['part_value']}, {$item['footprint']}",
            'MFpartName' => $item['manufacture_pn'],
            'ownerPartName' => $item['owner_pn'],
            'lineID' => $item['id']]);
        ?>
        <p class="parts rounded border-bottom" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            <?= $item['part_name']; ?>: <span class="text-primary me-2"><?= "{$item['part_value']}, {$item['footprint']}"; ?></span>
            Manufacture: <span class="text-primary me-2"><?= $item['manufacture_pn']; ?></span>
            Owner P/N: <span class="text-primary me-2"><?= $item['owner_pn']; ?></span>
            Exp date: <span class="text-primary me-2"><?= $item['exp_date']; ?></span>
            Amount: <span class="text-primary me-2"><?= $item['actual_qty']; ?></span>
        </p>
        <?php
    }
}

/**
 * @param $result
 * @param $user
 * @return void
 * функция вывода результата поиска на странице пректов
 * форма глобального поиска
 */
function viewFullProject($result, $user)
{
    foreach ($result as $value) {
        if ($value['archivation']) {
            $projectId = $value['id'];
            // получаем фото/много фоток
            $imgPath = getProjectFrontPicture($projectId, $user['preview'], true);
            $projectName = str_replace('_', ' ', $value['projectname']);
            $shareLink = SHARE_LINK_ROUTE . $value['sharelink'];
            $customerName = $value['customername'];
            $descInfo = $value['extra'];
            $startDate = $value['date_in'];
            $revision = $value['revision']; ?>
            <div class="col-md-4">

                <div class="card mb-4 shadow-sm">
                    <!--  Project Name and Share Link -->
                    <h5 class="card-title position-relative"><b class="text-primary">Name:</b> <?= $projectName; ?>
                        <span class="text-primary share-project position-absolute end-0 me-3" data-share-link="<?= $shareLink; ?>">
					<i class="bi bi-share-fill"></i>
				</span>
                    </h5>

                    <?php
                    //Project Documentation preview or Last step of project if Docs not exist
                    if ($user['preview'] == 'docs') {
                        if (!empty($value['projectdocs']) && strpos($value['projectdocs'], '.pdf') !== false) { ?>
                            <iframe src="<?= $value['projectdocs']; ?>"></iframe>
                            <a href="<?= $value['projectdocs']; ?>" target="_blank" class="mt-2 pdf-link">View Project Docs</a>
                        <?php } else { ?>
                            <img src="<?= $imgPath; ?>" alt="<?= $projectName; ?>" class="img-fluid">
                        <?php }
                    } ?>
                    <!-- photo gallery for project -->
                    <div class="photo-gallery">
                        <?php
                        if ($user['preview'] == 'image' && $imgPath) {
                            $firstImg = reset($imgPath); // Сброс указателя массива и получение первого элемента
                            foreach ($imgPath as $img) {
                                $display = ($img === $firstImg) ? '' : 'hidden';
                                ?>
                                <img src="<?= $img['image']; ?>" alt="<?= $projectName; ?>" class="img-fluid gallery-photo <?= $display; ?>">
                                <?php
                            }
                        }
                        ?>
                    </div>

                    <div class="card-body">
                        <!--  Customer or Company Name -->
                        <p class="card-text"><b class="text-primary">Customer:</b><br/> <?= $customerName; ?></p>

                        <!-- Some additional information about project, view if exist -->
                        <?php if (!empty($descInfo)) { ?>
                            <p class="card-text"><b class="text-primary">Additional Info:</b><br/><?= $descInfo; ?></p>
                        <?php } ?>

                        <!-- project created or updated Date and Revision -->
                        <p class="card-text text-primary"><?= $startDate; ?> &nbsp; <small class="text-danger">Rev:&nbsp;<?= $revision; ?></small></p>

                        <!--  action buttons group  -->
                        <div class="btn-group">
                            <a type="button" title="Preview" class="btn btn-outline-info anchor-btn" href="/edit_project?pid=<?= $projectId; ?>">
                                <i class="bi bi-eye"></i>
                            </a>

<!--                            --><?php //if (isUserRole(ROLE_ADMIN)) { ?>
<!--                                <a type="button" title="Take To Job" class="btn btn-outline-success" href="/new_order?pid=--><?php //= $projectId; ?><!--">-->
<!--                                    <i class="bi bi-tools"></i>-->
<!--                                </a>-->
<!--                                <button type="button" title="Archive" class="btn btn-outline-warning archive" data-projectid="--><?php //= $projectId; ?><!--">-->
<!--                                    <i class="bi bi-archive-fill"></i>-->
<!--                                </button>-->
<!--                                <button type="button" title="Delete" class="btn btn-outline-danger delete-button" data-projectid="--><?php //= $projectId; ?><!--">-->
<!--                                    <i class="bi bi-trash"></i>-->
<!--                                </button>-->
<!--                            --><?php //} ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

function getOrderProgress($order): string
{
    if ($order['order_progress'] != '0') {
        $res = '';
        $tmp = explode(',', $order['order_progress']);
        if (count($tmp)) {
            foreach ($tmp as $item) {
                $routAct = R::load(ROUTE_ACTION, $item);
                $res .= $routAct->actions . '<br>';
            }
        }
    }
    return $res ?? 'No Progress yet.';
}

/**
 * @param $result
 * @param $user
 * @return void
 * функция вывода результатов поиска на странице заказов
 * глобальная форма поиска
 */
function viewOrder($result, $user)
{
    if ($user) {
        foreach ($user['ownSettingsList'] as $item) {
            if (isset($item['table_name']) && $item['table_name'] == 'orders') {
                $settings = json_decode($item['setup']);
                break;
            }
        }
    }

    $byUser = explode(',', $user['filterby_user']);
    $orders_ids = $customer_name = $priority_id = '';
    foreach ($result as $order) {
        $progress = getOrderProgress($order);
        /* filter by user */
        $workers = explode(',', $order['workers']);
        if (!empty(array_intersect($workers, $byUser)) || $byUser[0] == 'all') {

            /* формируем приорити данные для заказов ID заказов через запятую добавляем в скрытую форму
            а при клике на кнопку дай приорити открываем новую страницу и формируем там таблицу типа эксель */
            $orders_ids .= $order['id'] . ',';
            $customer_name = $order['customer_name'];
            $priority_id = $order['client_priority'];
            ?>
            <tr class="align-middle order-row border-bottom">
            <?php
            if ($settings) {
                // creating table using user settings
                foreach ($settings as $k => $item) {
                    $click = ($k === 0 && (in_array($thisUser['user_name'], $workers) || isUserRole(ROLE_ADMIN))) ? ' onclick="getInfo(' . $order['id'] . ')"' : '';
                    if ($item == 'status') {
                        // status colorise bg and play text
                        $color = L::STATUS($order[$item], 1);
                        echo '<td class="border-end ' . $color . '"' . $click . '>' . L::STATUS($order[$item]) . '</td>';

                    } elseif ($item == 'prioritet') {
                        // prioritet colorise bg
                        $c = strtolower($order[$item]);
                        echo '<td class="border-end ' . $c . '"' . $click . '>' . $order[$item] . '</td>';

                    } elseif ($item == 'order_progress') {
                        // order progress preview
                        echo '<td class="border-end"' . $click . '>' . $progress . '</td>';

                    } else {
                        // regular tab cel
                        echo '<td class="border-end"' . $click . '>' . $order[$item] . '</td>';
                    }
                }
            }

            //i buttons for some actions like delete, edite, & edit BOM
            if (isUserRole(ROLE_ADMIN)) {
                ?>
                <td>
                    <?php if ($order['status'] == 'st-111'): ?>
                        <button type="button" value="<?= $order['id']; ?>" class="archive-order btn btn-outline-dark" data-title="Archivate Order">
                            <i class="bi bi-archive"></i>
                        </button>
                    <?php endif; ?>

                    <?php $url = "check_bom?orid={$order['id']}&pid={$order['projects_id']}"; ?>
                    <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary" data-title="Edit order BOM">
                        <i class="bi bi-card-list"></i>
                    </button>
                    <?php $url = "edit_order?orid={$order['id']}&pid={$order['projects_id']}"; ?>
                    <button type="button" value="<?= $url; ?>" class="url btn btn-outline-warning" data-title="Edit Order">
                        <i class="bi bi-pencil"></i>
                    </button>

                </td>
                </tr>
                <?php
            }
        } // if(filter-by-user)
    } // end foreach()

    // форма вывода данных для таблицы приорити
    ?>
    <tr class="hidden">
        <td>
            <form action="/priority-out" method="post" target="_blank" id="priority-form" class="hidden">
                <input type="hidden" name="order-ids" value="<?= $orders_ids; ?>">
                <input type="hidden" name="customer-name" value="<?= $customer_name; ?>">
                <input type="hidden" name="priority-id" value="<?= $priority_id; ?>">
            </form>
        </td>
    </tr>
    <?php
}

/**
 * вывод результата поиска на странице логов
 *
 * @param $result
 * @return void
 */
function viewLogs($result)
{
    foreach ($result as $log) {
        ?>
        <tr>
            <th class="text-primary" scope="row"><?= $log['user']; ?></th>
            <th class="text-primary"><?= $log['action']; ?></th>
            <th class="text-primary"><?= $log['object_type']; ?></th>
            <td><?= $log['details']; ?></td>
            <th class="text-primary"><?= $log['date']; ?></th>
        </tr>
    <?php }
}

/**
 * функция вывода результатов поиска на странице склада
 * @param $result
 * @param $searchString
 * @param $request
 * @param $user
 * @return void
 */
function viewStorageItems($result, $searchString, $request, $user)
{
    if ($result) {
        $settings = getUserSettings($user, WH_NOMENCLATURE);

        foreach ($result as $item) {
            $infoData = json_encode([
                'partName' => $item['part_name'],
                'partValue' => $item['part_value'],
                'footprint' => $item['footprint'],
                'part-type' => $item['part_type'],
                'MFpartName' => $item['manufacture_pn'],
                'manufacturer' => $item['manufacturer'],
                'ownerPartName' => $item['owner_pn'],
                'amount' => $item['actual_qty'],
                'minQTY' => $item['min_qty'],
                'storShelf' => $item['storage_shelf'],
                'storBox' => $item['storage_box'],
                'storState' => $item['storage_state'],
                'storageClass' => $item['class_number'],
                'datasheet' => $item['datasheet'],
                'description' => $item['description'],
                'notes' => $item['notes'],
                'manufacturedDate' => $item['manufacture_date'],
                'shelfLife' => $item['shelf_life'],
                'invoice' => $item['invoice'],
                'lot' => $item['lots'],
                'owner' => $item['owner']
            ]);
            if ($request == 'warehouse') {
                // вывод результатов поиска на страницу просмотра элемента
                ?>
                <p class="part border-bottom p-2" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
                    Part Name: <span class="text-info me-2"><?= $item['part_name']; ?></span>
                    Manufacture P/N: <span class="text-info me-2"><?= $item['manufacture_pn']; ?></span>
                    Value: <span class="text-info me-2"><?= $item['part_value']; ?></span>
                    Owner: <span class="text-info me-2"><?= $item['owner']; ?></span>
                    Owner P/N: <span class="text-info me-2"><?= $item['owner_pn']; ?></span>
                    Exp date: <span class="text-info me-2"><?= $item['exp_date']; ?></span>
                    Amount: <span class="text-info me-2"><?= $item['actual_qty']; ?></span>
                    Storage State: <span class="text-info me-2"><?= $item['storage_state']; ?></span>
                </p>
                <?php
            } else {
                // вывод результата поиска на страницу просмотра всей БД
                $color = '';
                if ((int)$item['actual_qty'] <= (int)$item['min_qty']) {
                    $color = 'danger';
                } elseif ((int)$item['actual_qty'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                    $color = 'warning';
                }
                ?>
                <!-- это полный набор переменных выводимых из БД -->
                <tr class="<?= $color; ?>" data-id="<?= $item['id']; ?>" id="row-<?= $item['id']; ?>">
                    <td><?= $item['id']; ?></td>
                    <?php
                    // выводим таблицу согласно настройкам пользователя
                    foreach ($settings as $set) {
                        if ($set == 'item_image') { ?>
                            <td>
                                <?php $img_href = ($item['part_type'] == 'SMT') ? '/public/images/smt.webp' : '/public/images/pna_en.webp' ?>
                                <img src="<?= $item['item_image'] ?? $img_href; ?>" alt="goods" width="100" height="auto">
                            </td>
                        <?php } elseif ($set == 'datasheet') {
                            ?>
                            <td><a type="button" class="btn btn-outline-info" href="<?= $item['datasheet'] ?> " target="_blank">Open Datasheet</a></td>
                            <?php
                        } else {
                            echo '<td>' . $item[$set] . '</td>';
                        }
                    }
                    ?>
                </tr>
                <?php
            }
        }
    } else {
        if ($request != 'warehouse') {
            ?>
            <h4 class="py-3 px-3">Ooops seams this item not exist yet.</h4>
            <a href="/warehouse/the_item?newitem=<?= $searchString; ?>" role="button" class="m-3 p-3 btn btn-outline-secondary">
                Do you want to create this Item?
            </a>
            <?php
        }
    }
}

/**
 * searching in PROJECT BOM page
 * @param $result
 * @return void
 */
function viewPartsForBOM($result)
{
    if ($result): foreach ($result as $item):

        $infoData = json_encode([
            'partName' => $item['part_name'],
            'partValue' => $item['part_value'],
            'footprint' => $item['footprint'],
            'partType' => $item['part_type'],
            'manufacturer' => $item['manufacturer'],
            'MFpartName' => $item['manufacture_pn'],
            'ownerPartName' => $item['owner_pn'],
            'description' => !empty($item['extra']) ? $item['extra'] : ' '
        ]);

        ?>
        <tr class="item-list" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            <td><?= $item['id']; ?></td>
            <td><?= $item['part_name']; ?></td>
            <td><?= $item['part_value']; ?></td>
            <td><?= $item['part_type']; ?></td>
            <td><?= $item['footprint']; ?></td>
            <td><?= $item['manufacturer']; ?></td>
            <td><?= $item['manufacture_pn']; ?></td>
            <td><?= $item['owner_pn']; ?></td>
            <td><?= !empty($item['extra']) ? $item['extra'] : 'N'; ?></td>
            <td><?= 'N' ?></td>
            <td><?= $item['actual_qty']; ?></td>
        </tr>
    <?php endforeach; endif;
}