<?php
/**
 * @param $result
 * @param $col
 * @return void
 * функция вывода поиска на странице добавления клиента
 */
function viewCustomer($result, $col)
{ ?>
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Priority</th>
        <th>Contact</th>
        <th>Info</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($result as $item) {
        // Подготавливаем данные для атрибута data-info
        $infoData = json_encode([
            'name' => $item[$col[0]],
            'contact' => $item[$col[1]],
            'info' => $item[$col[2]],
            'priority' => $item[$col[3]],
            'clientID' => $item['id'],
            'headpay' => $item['head_pay']
        ]); ?>

        <tr class="customer item-list" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            <td><?= $item['id']; ?></td>
            <td><?= $item[$col[0]]; ?></td>
            <td><?= $item[$col[3]]; ?></td>
            <td><?= $item[$col[1]]; ?></td>
            <td><?= $item[$col[2]]; ?></td>
        </tr>
    <?php } ?>
    </tbody>
    <?php
}

/**
 * ФУНКЦИЯ ВЫВОДА ДАННЫХ ДЛЯ ПОЛЕЙ SUPPLIER/MANUFACTURER
 * @param $result
 * @param $request
 * @return void
 */
function viewSupplier($result, $request)
{ ?>
    <thead>
    <tr>
        <th>Name</th>
        <th>Who</th>
        <th>Info</th>
        <th>Rating</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($result as $sup) {
        $infoData = json_encode([
            'is_request' => $request,
            'supplier_id' => $sup['id'],
            'supplier_name' => $sup['name']
        ]); ?>
        <tr class="supplier item-list" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            <td><?= $sup['name']; ?></td>
            <td><?= $sup['sup_type']; ?></td>
            <td><?= $sup['description']; ?></td>
            <td><?= $sup['rating']; ?></td>
        </tr>
    <?php } ?>
    </tbody>
    <?php
}

/**
 * @param $result
 * @param $col
 * @return void
 * функция вывода результатов поиска на странице добавления заказа
 * форма поиска по проектам
 */
function viewLineProject($result, $col)
{ ?>
    <thead>
    <tr>
        <th>Id</th>
        <th>Version</th>
        <th>Name</th>
        <th>Customer</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($result as $item) {
        // Подготавливаем данные для атрибута data-info
        $infoData = json_encode([
            'name' => $item[$col[0]],
            'client' => $item[$col[1]],
            'revision' => $item[$col[2]],
            'projectID' => $item['id'],
        ]); ?>
        <tr class="project item-list" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
            <td><?= $item['id']; ?></td>
            <td><?= $item[$col[2]]; ?></td>
            <td><?= $item[$col[0]]; ?></td>
            <td><?= $item[$col[1]]; ?></td>
        </tr>
    <?php } ?>
    </tbody>
    <?php
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
    if ($result) {
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
    } else { ?>
        <h1>No result by your search!</h1>
        <?php
    }
}

/**
 * вывод результата поиска на странице логов
 * @param $result
 * @return void
 */
function viewLogs($result)
{
    foreach ($result as $log) {
        ?>
        <tr class="item-list">
            <td class="text-primary"><?= $log['user']; ?></td>
            <td class="text-primary"><?= $log['action']; ?></td>
            <td class="text-primary"><?= $log['object_type']; ?></td>
            <td><?= $log['details']; ?></td>
            <td class="text-primary"><?= $log['date']; ?></td>
        </tr>
    <?php }
}

/**
 * ФУНКЦИЯ ВЫВОДА РЕЗУЛЬТАТА ДЛЯ СТРАНИЦЫ СКЛАД, ДОБАВЛЕНИЕ ДЕТАЛИ, БОМ ПРОЕКТА
 * @param $result
 * @param $searchString
 * @param $request
 * @param $user
 * @return void
 */
function viewStorageItems($result, $searchString, $request, $user)
{
    if ($result) {
        // ВЫВОД ТАБЛИЦЫ ПРИ ПОИСКЕ НА ГЛАВНОЙ СТРАНИЦЕ СКЛАДА
        if ($request == 'warehouse_nav') {
            $settings = getUserSettings($user, WH_ITEMS);
            foreach ($result as $item) {
                if (!empty($item['owner'])) {
                    // get owner name from json data set
                    $wh = json_decode($item['owner'])->name;
                }

                // вывод результата поиска на страницу просмотра всей БД
                $color = '';
                if ((int)$item['quantity'] <= (int)$item['min_qty']) {
                    $color = 'danger';
                } elseif ((int)$item['quantity'] <= (int)$item['min_qty'] + ((int)$item['min_qty'] / 2)) {
                    $color = 'warning';
                }
                ?>
                <!-- это полный набор переменных выводимых из БД -->
                <tr class="<?= $color; ?>" data-id="<?= $item['id']; ?>" id="row-<?= $item['id']; ?>">
                    <!--                    <td>--><?php //= $item['id']; ?><!--</td>-->
                    <td><?= $item['type_name']; ?></td>
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
                            // output data from two tables warehouse and whitems
                            if ($set == 'owner' && !empty($wh)) {
                                // get owner name from json data set
                                $wr = $wh;
                            } else {
                                $wr = $item[$set] ?? '';
                            }
                            // print data to page
                            echo '<td>' . $wr . '</td>';
                        }
                    }
                    ?>
                </tr>
                <?php
            }
        }

        // ВЫВОД ТАБЛИЦЫ ПРИ ПОИСКЕ ПО value, name, mf P/N, owner P/N,
        if ($request == 'warehouse') {
            ?>
            <thead>
            <tr>
                <th>Value</th>
                <th>MF P/N</th>
                <th>Owner P/N</th>
                <th>QTY</th>
                <th>Exp Date</th>
                <th>Footprint</th>
                <th>Where</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $item) {
                if (!empty($item['owner'])) {
                    // get owner name from json data set
                    $wh = json_decode($item['owner'])->name;
                }
                $infoData = json_encode([
                    // item table fields
                    'part_name' => $item['part_name'],
                    'part_value' => $item['part_value'],
                    'part_type' => $item['part_type'],
                    'manufacture_part_number' => $item['manufacture_pn'],
                    'manufacturer' => $item['manufacturer'],
                    'footprint' => $item['footprint'],
                    'minimal_quantity' => $item['min_qty'],
                    'description' => $item['description'],
                    'notes' => $item['notes'],
                    'datasheet' => $item['datasheet'],
                    'shelf_life' => $item['shelf_life'],
                    'storage_class' => $item['class_number'],
                    'wh_type' => $item['type_name'],
                    // warehouse table fields
                    'owner' => $wh,
                    'owner_part_name' => $item['owner_pn'],
                    'quantity' => $item['quantity'],
                    'storage_box' => $item['storage_box'],
                    'storage_shelf' => $item['storage_shelf'],
                    'storage_state' => $item['storage_state'],
                    // invoice table fields
                    // 'manufactured_date' => $item['manufacture_date'],
                    // 'part_lot' => $item['lots'],
                    // 'invoice' => $item['invoice']б
                    // 'supplier' => $item['supplier']
                ]);
                ?>
                <tr class="part item-list" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
                    <td><?= $item['part_value']; ?></td>
                    <td><?= $item['manufacture_pn']; ?></td>
                    <td><?= $item['owner_pn']; ?></td>
                    <td><?= $item['quantity']; ?></td>
                    <td><?= $item['fifo']; ?></td>
                    <td><?= $item['footprint']; ?></td>
                    <!--                    <td>--><?php //= $item['storage_state']; ?><!--</td>-->
                    <td><?= $item['type_name']; ?></td>
                </tr>
            <?php } ?>
            </tbody>
            <?php
        }

        //  ВЫВОД ПРИГЛАШЕНИЯ ДОБАВИТЬ НОВЫЙ ТОВАР НА СКЛАД ПРИ ОТСУТСТВИИ РЕЗУЛЬТАТОВ ПОИСКА НА ГЛАВНОЙ СТРАНИЦЕ СКЛАДА
    } else {
        if ($request != 'warehouse') {
            ?>
            <h4 class="py-3 px-3">Ooops seams this item not exist yet.</h4>
            <a href="/wh/the_item?newitem=<?= $searchString; ?>" role="button" class="m-3 p-3 btn btn-outline-secondary">
                Do you want to create this Item?
            </a>
            <?php
        }
    }
}

/**
 * ПОИСК ЭЛЕМЕНТА НА СКЛАДЕ ПРИ ЗАПОЛНЕНИИ PROJECT BOM
 * ВЫВОД ДАННЫХ В ТАБЛИЦУ НА СТРАНИЦЕ
 * @param $result
 * @return void
 */
function viewPartsForProjectBOM($result)
{
    if ($result): foreach ($result as $item):

        $infoData = json_encode([
            'item_id' => $item['id'],
            'partName' => $item['part_name'],
            'partValue' => $item['part_value'],
            'footprint' => $item['footprint'],
            'partType' => $item['part_type'],
            'manufacturer' => $item['manufacturer'],
            'MFpartName' => $item['manufacture_pn'],
            'ownerPartName' => $item['owner_pn'],
            'notes' => !empty($item['notes']) ? $item['notes'] : ' ',
            'description' => !empty($item['description']) ? $item['description'] : ' '
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
            <td><?= !empty($item['description']) ? $item['description'] : 'N'; ?></td>
            <td><?= !empty($item['notes']) ? $item['notes'] : 'N'; ?></td>
            <td><?= $item['actual_qty']; ?></td>
        </tr>
    <?php endforeach; endif;
}