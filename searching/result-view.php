<?php
/**
 * функция вывода поиска на странице добавления клиента
 *
 * @param $result
 * @param $col
 * @param $mySearchString
 * @return void
 */
function viewCustomer($result, $col, $mySearchString)
{
    if ($result && !empty($mySearchString)) { ?>
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
                'priority' => _empty($item[$col[3]], 0),
                'clientID' => $item['id'],
                'headpay' => _empty($item['head_pay'], 0),
                'phone' => _empty($item['phone'], 0),
                'email' => _empty($item['email'], 0)
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
    } else {
        echo 'EMPTY';
    }
}

/**
 * ФУНКЦИЯ ВЫВОДА ДАННЫХ ДЛЯ ПОЛЕЙ SUPPLIER/MANUFACTURER
 *
 * @param $result
 * @param $request
 * @param $mySearchString
 * @return void
 */
function viewSupplier($result, $request, $mySearchString): void
{
    if ($result && !empty($mySearchString)) { ?>
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
    } else {
        if (!empty($mySearchString)) {
            // ДОБАВЛЕНИЕ НА ЛЕТУ ПОСТАВЩИКА-ПРОИЗВОДИТЕЛЯ
            ?>
            <thead>
            <tr>
                <th><h2>Form adding a new supplier/manufacturer</h2></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form action="/counterparties/suppliers.php" method="post" id="supplier_form">
                        <input type="hidden" name="user-data" value="" id="user_data">
                        <input type="hidden" name="request" value="<?= $request ?>">

                        <div class="mb-2">
                            <label for="supplier-name">Supplier/Manufacturer Name <b class="text-danger">*</b></label>
                            <input type="text" placeholder="Supplier Name" name="supplier-name" id="supplier-name" class="input"
                                   value="<?= set_value('supplier-name', $mySearchString); ?>" required/>
                        </div>

                        <div class="row mb-2">
                            <div class="col-8 pe-3">
                                <label for="sup_type">Choose Supplier Type <b class="text-danger">*</b></label>
                                <select name="sup_type" id="sup_type" class="input" required>
                                    <option value="Manufacturer">Manufacturer</option>
                                    <option value="Distributors">Distributors</option>
                                </select>
                            </div>

                            <div class="col-4">
                                <label for="rating">Rating <b class="text-danger">*</b></label>
                                <input type="number" placeholder="Rating" class="input" name="rating" id="rating"
                                       value="<?= set_value('rating'); ?>" required/>
                            </div>
                        </div>

                        <label for="description">Description <b class="text-danger">*</b></label>
                        <textarea placeholder="Description" name="description" id="description" class="input" required><?= set_value('description'); ?></textarea>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-success input" id="modal-btn-succes" disabled>Save Changes</button>
                        </div>
                    </form>
                </td>
            </tr>
            </tbody>
            <?php
        } else {
            echo 'EMPTY';
        }
    }
}

/**
 * @param $result
 * @param $col
 * @param $mySearchString
 * @return void
 * функция вывода результатов поиска на странице добавления заказа
 * форма поиска по проектам
 */
function viewLineOfUnit($result, $col, $mySearchString)
{
    //i ВЫВОД РЕЗУЛЬТАТОВ ПОИСКА В МОДАЛЬНОМ ОКНЕ
    if ($result && !empty($mySearchString)) { ?>
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
    } else {
        echo 'EMPTY';
    }
}

/**
 * @param $result
 * @param $user
 * @return void
 * функция вывода результата поиска на странице пректов
 * форма глобального поиска
 */
function viewFullUnit($result, $user)
{
    if ($_SESSION['preview_mode']) {
        //i ВЫВОД ТАБЛИЧНОГО ВИДА ПРОЕКТА ПРИ ПОИСКЕ
        $settings = getUserSettings($user, PROJECTS);
        ?>
        <table class="p-3" id="project-table">
            <!-- header -->
            <thead>
            <tr style="white-space: nowrap">
                <?= CreateTableHeaderUsingUserSettings($settings, 'project-table', PROJECTS, '<th>Share Project</th>') ?>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            foreach ($result as $value) {
                $shareLink = SHARE_LINK_ROUTE . $value['sharelink'];
                $projectId = $value['id'];
                ?>
                <tr class="item-list" data-id="<?= $projectId; ?>">
                    <?php
                    if ($settings) {
                        foreach ($settings as $item => $_) {
                            echo '<td>' . $value[$item] . '</td>';
                        }
                    }
                    ?>
                    <td>
                        <button type="button" class=" w-100 btn btn-sm btn-outline-diliny share-project" data-share-link="<?= $shareLink; ?>">
                            <i class="bi bi-share-fill"></i>
                        </button>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } else {
        //i ВЫВОД ПОЛНОГО ВИДА ПРОЕКТА ПРИ ПОИСКЕ
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
                $revision = $value['revision'];
                ?>

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
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
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
                    $settings = json_decode($item['setup'], true);
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
                    $k = 0;
                    foreach ($settings as $item => $_) {
                        $click = ($k === 0 && (in_array($user['user_name'], $workers) ||
                                isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR]))) ? ' onclick="getInfo(' . $order['id'] . ')"' : '';
                        if ($item == 'status') {
                            // status colorise bg and play text
                            $color = SR::getResourceDetail('status', $order[$item]);
                            echo '<td class="border-end ' . $color . '"' . $click . '>' . SR::getResourceValue('status', $order[$item]) . '</td>';

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
                        $k++;
                    }
                }

                //i buttons for some actions like delete, edite, & edit BOM
                if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) {
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
                <form action="/priority-out" method="get" target="_blank" id="priority-form" class="hidden">
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
 * ФУНКЦИЯ ВЫВОДА РЕЗУЛЬТАТА ДЛЯ СТРАНИЦЫ СКЛАД, ДОБАВЛЕНИЕ ДЕТАЛИ, БОМ ПРОЕКТА
 * @param $result
 * @param $searchString
 * @param $request
 * @param $user
 * @return void
 */
function viewStorageItems($result, $searchString, $request, $user): void
{
    if ($result && !empty($searchString)) {
        // ВЫВОД ТАБЛИЦЫ ПРИ ПОИСКЕ НА ГЛАВНОЙ СТРАНИЦЕ СКЛАДА
        if ($request == 'wh_nav') {
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
                <tr class="<?= $color; ?>" data-id="<?= $item['items_id']; ?>" id="row-<?= $item['items_id']; ?>">
                    <!--                    <td>--><?php //= $item['id']; ?><!--</td>-->
                    <td><?= $item['type_name']; ?></td>
                    <?php
                    // выводим таблицу согласно настройкам пользователя
                    foreach ($settings as $set => $_) {
                        if ($set == 'item_image') { ?>
                            <td>
                                <?php $img_href = ($item['mounting_type'] == 'SMT') ? '/public/images/smt.webp' : '/public/images/pna_en.webp' ?>
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
                    'item_id' => $item['id'],
                    'part_name' => $item['part_name'],
                    'part_value' => $item['part_value'],
                    'mounting_type' => $item['mounting_type'],
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
                    'storage_state' => $item['storage_state']
                    // consignment table fields
                    // 'manufactured_date' => $item['manufacture_date'],
                    // 'part_lot' => $item['lots'],
                    // 'delivery_note' => $item['delivery_note'], // was consignment
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
        } else {
            echo "EMPTY";
        }
    }
}

/**
 * ПОИСК ЭЛЕМЕНТА НА СКЛАДЕ ПРИ ЗАПОЛНЕНИИ PROJECT BOM
 * ВЫВОД ДАННЫХ В ТАБЛИЦУ НА СТРАНИЦЕ
 * @param $result
 * @return void
 */
function viewPartsForUnitBOM($result): void
{
    if ($result): foreach ($result as $item):

        $infoData = json_encode([
            'owner_id' => $item['owner'],
            'item_id' => $item['id'],
            'partName' => $item['part_name'],
            'partValue' => $item['part_value'],
            'footprint' => $item['footprint'],
            'mountingType' => $item['mounting_type'],
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
            <td><?= $item['mounting_type']; ?></td>
            <td><?= $item['footprint']; ?></td>
            <td><?= $item['manufacturer']; ?></td>
            <td><?= $item['manufacture_pn']; ?></td>
            <td><?= $item['owner_pn'] ?? ''; ?></td>
            <td><?= !empty($item['description']) ? $item['description'] : 'not filled in'; ?></td>
            <td><?= !empty($item['notes']) ? $item['notes'] : 'not filled in'; ?></td>
            <td><?= $item['quantity']; ?></td>
        </tr>
    <?php endforeach; endif;
}

/**
 * @param $itemImages
 * @return void
 */
function itemImagesForChoose($itemImages): string
{
    // собираем все пути в таблицу по 3 штуки в ряду и возвращаем для вывода на страницу
    // ширина изображения установлена в 200рх
    $outRes = '<thead><tr><th colspan="3">Click on image for choose</th></tr></thead>';
    $outRes .= '<tbody>';
    $count = 0;
    foreach ($itemImages as $path) {
        if ($count % 3 === 0) {
            if ($count > 0) {
                $outRes .= '</tr>';
            }
            $outRes .= '<tr>';
        }
        $outRes .= '<td data-info="' . $path . '" class="image-path"><img src="' . $path . '" alt="image" style="width: 200px" data-info="' . $path . '"></td>';
        $count++;
    }
    // Закрываем последний ряд, если он был открыт
    if ($count % 3 !== 0) {
        $outRes .= str_repeat('<td></td>', 3 - $count % 3);
        $outRes .= '</tr>';
    }
    $outRes .= '</tbody>';
    return $outRes;
}

/**
 * вывод результата поиска на странице общих логов
 * @param $result
 * @return void
 */
function viewLogs($result): void
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
 * вывод результата поиска на странице ресурсов для сайта
 * @param $result
 * @return void
 */
function viewResources($result): void
{
    if ($result) {
        foreach ($result as $reso) {
            $infoData = json_encode([
                'group_name' => $reso['group_name'],
                'key_name' => $reso['key_name'],
                'value' => $reso['value'],
                'detail' => $reso['detail']]);
            ?>
            <tr class="item-list" data-info="<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>">
                <td><?= $reso['group_name']; ?></td>
                <td><?= $reso['key_name']; ?></td>
                <td><?= $reso['value']; ?></td>
                <td><?= $reso['detail']; ?></td>
            </tr>
        <?php }
    } else {
        echo 'EMPTY';
    }
}

/**
 * Отображает логи склада в виде HTML-таблицы с использованием данных из базы данных.
 * Функция поддерживает отображение данных до и после изменений, данных из инвойса и данных из таблицы склада.
 * @param array $result
 * @return void
 */
function viewWarehouseLogs(array $result): void
{
    /**
     * Проверяет, является ли строка валидным JSON
     * @param string $data
     * @return bool
     */
    $isValidJson = function (string $data): bool {
        json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    };

    if ($result) {
        foreach ($result as $log) { ?>
            <tr class="item-list accordion-toggle">
                <td><?= $log['action'] ?></td>
                <td><?= $log['user_name'] ?></td>
                <td><?= $log['items_id'] ?></td>
                <td><?= $log['date_in'] ?></td>
            </tr>

            <tr class="accordion-content">
                <?php
                // Инициализация переменных
                $wh_data = $invoiceData = $itemDataBefore = $itemDataAfter = [];

                // Проверка наличия данных
                if (!empty($log['warehouse_data']) && !empty($log['invoice_data']) && $isValidJson($log['invoice_data'])) {
                    $wh_data = json_decode($log['warehouse_data'], true);
                    $invoiceData = json_decode($log['invoice_data'], true);
                }

                if (!empty($log['items_data'])) {
                    // Преобразование JSON-строки обратно в массив
                    $item = json_decode($log['items_data'], true);

                    if (!empty($item['item_data_before']) && !empty($item['item_data_after'])) {
                        // Доступ к данным до и после изменений
                        $itemDataBefore = $item['item_data_before'];
                        $itemDataAfter = $item['item_data_after'];
                    }
                }

                // Отображение данных до и после изменений
                if (!empty($itemDataBefore) || !empty($itemDataAfter)) {
                    echo '<td><p>Item Data Before Change</p>';
                    foreach ($itemDataBefore as $key => $value) {
                        echo "<p>$key --> $value</p>";
                    }
                    echo '</td>';

                    echo '<td><p>Item Data After Change</p>';
                    foreach ($itemDataAfter as $key => $value) {
                        echo "<p>$key --> $value</p>";
                    }
                    echo '</td>';
                } elseif (!empty($item)) {
                    echo '<td colspan="2">';
                    foreach ($item as $key => $value) {
                        echo "<p>$key --> $value</p>";
                    }
                    echo '</td>';
                }

                // Отображение данных инвойса
                echo '<td><p>Invoice Table Data</p>';
                if (!empty($invoiceData)) {
                    foreach ($invoiceData as $key => $value) {
                        echo "<p>$key --> $value</p>";
                    }
                } else {
                    echo '<p>Invoice: ' . $log['invoice_data'] . '</p>';
                }
                echo '</td>';

                // Отображение данных склада
                echo '<td><p>Warehouse Table Data</p>';
                if (!empty($wh_data)) {
                    foreach ($wh_data as $key => $value) {
                        echo "<p>$key --> $value</p>";
                    }
                }
                echo '</td>';
                ?>
            </tr>
        <?php }
    } else {
        echo '<h2>Unable to find data by search!</h2>';
    }
}

function viewToolsTable($result, $mySearchString)
{
    if ($result) { ?>
        <h2>Search result for Tools</h2>
        <table>
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Model</th>
                <th scope="col">Type</th>
                <th scope="col">Location</th>
                <th scope="col">Calibration</th>
                <th scope="col">SN:</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $row) {
                $infoData = json_encode([
                    'group_name' => $row['id'],
                    'key_name' => $row['manufacturer_name'],
                    'value' => $row['device_type']]);  ?>
                <tr class="item-list" data-info="<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>">
                    <td class="border-end"><?= $row['manufacturer_name']; ?></td>
                    <td class="border-end"><?= $row['device_model']; ?></td>
                    <td class="border-end"><?= $row['device_type']; ?></td>
                    <td class="border-end"><?= $row['calibration']; ?></td>
                    <td class="border-end"><?= $row['serial_num']; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    } else {
        echo 'EMPTY';
    }
}

