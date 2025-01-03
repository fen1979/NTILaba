<?php
define('STATUS', SR::getAllResourcesInGroup('status'));

class Orders
{
    /* i============================ PROTECTED METHODS =============================== */
    private static function makeFolderInStorage(string $unic): string
    {
        /* Создаем папку проекта*/
        $orderDir = ORDERS_FOLDER . $unic . '/';
        if (!is_dir($orderDir))
            mkdir($orderDir);
        return $unic;
    }

    /* i============================ ORDERS ACTIONS =============================== */

    /**
     * ORDER FILTERS BY USER & STATUS
     * @param $post
     * @param $user
     * @return mixed|\RedBeanPHP\OODBBean
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function changeFilters($post, $user): mixed
    {
        /* resetup filters by user and status */
        if (isset($post['filter-by-status'])) {
            $u = R::load(USERS, $user['id']);
            $u->filterby_status = implode(',', $post['status'] ?? ['']);
            $_SESSION['userBean'] = R::load(USERS, R::store($u));
            _flashMessage('Filter by status changed');
        }

        /* filter by user updating */
        if (isset($post['filter-by-user'])) {
            $u = R::load(USERS, $user['id']);
            $u->filterby_user = implode(',', $post['users'] ?? ['all']);
            $_SESSION['userBean'] = R::load(USERS, R::store($u));
            _flashMessage('Filter by User changed');
        }

        /* filter by client updating */
        if (isset($post['filter-by-client'])) {
            $u = R::load(USERS, $user['id']);
            $u->filterby_client = _E($post['clients'] ?? '');
            $_SESSION['userBean'] = R::load(USERS, R::store($u));
            _flashMessage('Filter by Customer changed');
        }
        return $_SESSION['userBean'];
    }

    /**
     * GET ORDERS BY FILTERS CHOOSEN BY USER
     * this function work and for searching orders
     * @param $status
     * @param $customerName
     * @param $pagination
     * @return array
     */
    public static function getOrdersByFilters($status, $customerName, $pagination): array
    {
        // Базовая часть запроса
        $query = "SELECT * FROM orders WHERE 1=1";
        $params = [];
        // Флаг для проверки, были ли применены фильтры
        $filterApplied = false;

        // Добавляем условия по статусу, если они указаны
        if (!empty($status) && $status != '-1') {
            $filterApplied = true; // Фильтр применен
            $stat = explode(',', $status);
            $query .= " AND status IN (" . R::genSlots($stat) . ")";
            $params = array_merge($params, $stat);
        }

        // Добавляем условие по имени клиента, если оно указано
        if (!empty($customerName)) {
            $filterApplied = true; // Фильтр применен
            $query .= " AND customer_name LIKE ?";
            $params[] = $customerName;
        }

        // Проверяем, был ли применен хотя бы один фильтр
        if (!$filterApplied && $status != '-1') {
            // Если фильтры не были применены, возвращаем пустой массив
            return [];
        }

        // Проверяем, выбран ли фильтрв ВСЕ
        if (!$filterApplied && $status == '-1') {
            // Если фильтры были применены, возвращаем 'orders'
            return R::findAll(ORDERS, 'ORDER BY id ASC ' . $pagination);
        }
        $query .= " $pagination";
        // Выполняем запрос
        $orders = R::getAll($query, $params);

        // Преобразуем результат в объекты 'orders'
        return R::convertToBeans(ORDERS, $orders);
    }

    /**
     * CHANGING USERS IN ORDER AND STATUSES
     * @param $user
     * @param $order_id
     * @param $post
     * @throws /\RedBeanPHP\RedException\SQL
     */
    public static function setStatusOrUserInOrder($user, $order_id, $post): void
    {
        $post = checkDataAndConvertToArray($post);
        $order = R::load(ORDERS, $order_id);
        // ОБНОВЛЕНИЕ СТАТУСА ЗАКАЗА И РАБОТНИКОВ ЗАКАЗА
        if (isset($post['set-order-user'])) {
            $oldUsers = $order->workers;
            $newUsers = $order->workers = implode(',', $post['users'] ?? ['all']);
            $log_details = $newUsers . ', This users was added to order production, ( OLD Users - ' . $oldUsers . ' )';
            $args['messageText'] = 'Workers changed: ' . $newUsers;
            $log_action = 'USER_CHANGED';
            _flashMessage('Users changed successfully');
        } else {
            $order->status = $post['status'];
            $log_details = 'Order status hase be changed to ' . SR::getResourceValue('status', $post['status']);
            _flashMessage('Status changed successfully');
            $args['messageText'] = 'Transferred to status: ' . SR::getResourceValue('status', $post['status']);
            $log_action = 'STATUS_CHANGED';
        }
        $args['readonly'] = 1;
        R::store($order);
        self::saveChatMessage($order_id, $user, $args);

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[0], $log_details)) {
            _flashMessage('Log creation failed.', 'danger');
        }
    }

    /**
     * ORDER PROGRESS WORK FLOW COLLECTOR
     * @param /\RedBeanPHP\OODBBean $order
     * @return string
     */
    public static function getOrderProgress($order): string
    {
        if ($order->order_progress != '0') {
            $res = '';
            $tmp = explode(',', $order->order_progress);
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
     * CREATING NEW ORDER
     * @param $user
     * @param $client
     * @param $project
     * @param $post
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createOrder($user, $client, $project, $post): array
    {
        $post = checkDataAndConvertToArray($post);

        $order = R::dispense(ORDERS);

        $c_name = $order->customer_name = $client['name']; // имя клиента
        $order->client_priority = $client['priority']; // номер клиента в приорити
        $order->purchase_order = $post['purchaseOrder']; // номер клиента
        $p_name = $order->project_name = $project['projectname']; // имя проекта для работы
        $order->project_revision = $project['revision']; // версия проекта
        $amount = $order->order_amount = $post['orderAmount']; // полное количество к выполнению
        $order->fai_qty = $post['fai_qty'] ?? 0; // тестовое количество для проверки
        $order->forwarded_to = $post['forwardedTo']; // на кого переведен заказ (создание, заполнение, проверка и тп)
        $order->workers = $post['orderWorkers']; // кто будет выполнять заказ
        $order->extra = $post['extra']; // дополнительная игформация по заказу
        $order->serial_required = $post['serial-required'] ?? 0; // требуется сериализация всей партии

        /* main order status */

        $order->status = $post['order-status']; // статус заказа по умолчанию st-0
        $order->order_progress = 0; // записываем id рут акта шага над которым работаем сейчас

        // LOW, MEDIUM, HIGH, DO FIRST
        $order->prioritet = $post['prioritet']; // приоритет выполнения заказа
        $shelf = $order->storage_shelf = $post['storageShelf'] ?? 'A0'; // место хранения коробки с запчастями к заказу

        if (ctype_digit($post['storageBox'])) {
            // обновляем значение в нумерации указывая что данный номер занят
            SR::updateResourceDetail('order_kit', $post['storageBox'], '1');
        }

        // присваиваем новый номер коробки для запчастей к заказу
        $box = $order->storage_box = $post['storageBox']; // номер коробки для запчастей к заказу

        $unic_name = strtolower($p_name) . '_' . date('Ymd_is'); // project name/date/
        $order->order_folder = self::makeFolderInStorage($unic_name); // папка заказа для хранения информации
        $order->date_in = str_replace('T', ' ', $post['date_in']); // дата создания заказа
        $order->date_out = str_replace('T', ' ', $post['date_out']); // дата выдачи заказа
        $order->subtraction = 0; // нужна для понимания что заказ в работе и списание больше не нужно если вдруг что

        /* привязка к таблицам проекта и клиента
         для поиска заказов по проекту или клиенту */
        $order->projects_id = $project['id'];
        $order->customers_id = $client['id'];

        $orderId = R::store($order);

        /* записываем в чат сообщение о создании заказа и установки ему параметров по умолчанию */
        $firstQty = (!empty($post['fai_qty'])) ? "<b class='text-danger'>First test batch: {$post['fai_qty']} pieces.</b><br>" : '';
        $msg = 'Order Status: ' . SR::getResourceValue('status', 'st-0') . ',<br>'
            . 'Order Prioritet: ' . $post['prioritet'] . ',<br>'
            . 'Order Workers: ' . $post['orderWorkers'] . ',<br>'
            . 'Forwarded to: ' . $post['forwardedTo'] . ',<br>'
            . 'Delivery Date: ' . $post['date_out'] . ',<br>'
            . $firstQty
            . 'Storage Shelf: ' . $shelf . ' / '
            . 'Box: ' . $box;

        self::saveChatMessage($orderId, $user, ['messageText' => $msg, 'readonly' => 1]);

        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Order N: $orderId, Customer: $c_name, Amount: $amount, Project: $p_name";
        /* сохранение логов если успешно то переходим к БОМ */
        if (logAction($user['user_name'], 'ORDER_CREATED', OBJECT_TYPE[0], $details)) {
            return [true, $orderId];
        }
        return [false, null];
    }

    /**
     *  UPDATING EXISTING ORDER
     * @param $user
     * @param $order_id
     * @param $post
     * @param null $project
     * @param null $client
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function updateOrderInformation($user, $order_id, $post, $project = null, $client = null): array
    {
        if ($project && $client) {
            $post = checkDataAndConvertToArray($post);
            $order = R::load(ORDERS, $order_id);
            $msg = '';
            // ИЗМЕНЕНИЕ ИННФОРМАЦИИ В ЗАКАЗЕ
            if ($order->forwarded_to != $post['forwardedTo']) {
                $order->forwarded_to = $post['forwardedTo']; // на кого переведен заказ (создание, заполнение, проверка и тп)
                // message to order log
                $msg .= "Forwarded to: {$post['forwardedTo']} <br>";
            }

            if ($order->workers != $post['orderWorkers']) {
                $order->workers = $post['orderWorkers']; // кто будет выполнять заказ
                // message to order log
                $msg .= "Order workers changed to: {$post['orderWorkers']} <br>";
            }

            // LOW, MEDIUM, HIGH, DO FIRST
            if ($order->prioritet != $post['prioritet']) {
                // message to order log
                $msg .= "<b class='text-danger'>Order Prioritet changed FROM: $order->prioritet -> TO: {$post['prioritet']}</b><br>";
                // приоритет выполнения заказа
                $order->prioritet = $post['prioritet'];
            }

            // ORDER STATUS FOR UPDATING
            if ($order->status != $post['order-status']) {
                // message to order log
                $msg .= "<b class='text-danger'>Order Status changed FROM: $order->status -> TO: {$post['order-status']}</b><br>";
                // приоритет выполнения заказа
                $order->status = $post['order-status'];
            }

            if ($order->customer_name != $post['customerName']) {
                $c_name = $order->customer_name = $post['customerName']; // имя клиента
                $order->customers_id = $post['customer_id'];
                // message to order log
                $msg .= "Customer name was change to: $c_name <br>";
            }

            $order->client_priority = $post['priority']; // номер клиента в приорити
            $order->purchase_order = $post['purchaseOrder']; // номер клиента head-pay

            if ($order->project_name != $post['projectName']) {
                $p_name = $order->project_name = $post['projectName']; // имя проекта для работы
                $order->project_revision = $post['projectRevision']; // версия проекта
                $order->projects_id = $post['project_id'];
                // message to order log
                $msg .= "Project name changed to: $p_name<br>";
            }

            $amount = $order->order_amount;
            $order->order_amount = $post['orderAmount']; // полное количество к выполнению
            $order->fai_qty = $post['fai_qty'] ?? 1; // тестовое количество для проверки
            $order->extra = $post['extra']; // дополнительная игформация по заказу

            $order->serial_required = $post['serial-required'] ?? 0; // требуется сериализация всей партии

            $shelf = $order->storage_shelf;
            $order->storage_shelf = $post['storageShelf'] ?? 'A0'; // место хранения коробки с запчастями к заказу

            // если коробка была изменена поизводим обновления в БД
            if (!empty($order->storage_box) && $order->storage_box != $post['storageBox']) {
                $box = $order->storage_box;

                if (ctype_digit($box)) {
                    // возвращаем коробку обратно в систему если коробка была изменена
                    SR::updateResourceDetail('order_kit', $box, '0');
                }

                if (ctype_digit($post['storageBox'])) {
                    // обновляем значение в нумерации указывая что данный номер занят
                    SR::updateResourceDetail('order_kit', $post['storageBox'], '1');
                }

                // присваиваем новый номер коробки для запчастей к заказу
                $order->storage_box = $post['storageBox'];

                // message to order log
                $msg .= "<b class='text-primary'>Storage box changet FROM: $box -> TO: {$post['storageBox']}</b><br>";
            }

            $order->date_in = str_replace('T', ' ', $post['date_in']); // дата создания заказа
            $order->date_out = str_replace('T', ' ', $post['date_out']); // дата отдачи заказа
            $orderId = R::store($order);

            // updating other DB tables if needed
            // and forming the message for log and chat
            $data = explode(',', $post['changed-fields']);
            foreach ($data as $field) {
                switch ($field) {
                    case 'priority':
                        $msg .= "Priority number changed to: {$post['priority']}<br>";
                        R::exec("UPDATE customers SET priority = ? WHERE id = ?", [$post['priority'], $post['customer_id']]);
                        break;
                    case 'date_in':
                        $msg .= "Application date changed to: {$post['date_in']}<br>";
                        break;
                    case 'date_out':
                        $msg .= "Delivery date changed to: {$post['date_out']}<br>";
                        break;
                    case 'head_pay':
                        $msg .= "Head pay changed to: {$post['purchaseOrder']}<br>";
                        R::exec("UPDATE customers SET head_pay = ? WHERE id = ?", [$post['purchaseOrder'], $post['customer_id']]);
                        break;
                    case 'shelf':
                        $msg .= "<b class='text-primary'>Storage shelf cnahged FROM: $shelf -> TO: {$post['storageShelf']}</b><br>";
                        break;
                    case 'extra':
                        $msg .= "Additional information was changed: {$post['extra']}<br>";
                        break;
                    case 'se_ed':
                        $msg .= ($post['serial-required'] == 1) ?
                            '<b class="danger text-white p-2">Each unit in this project must be serialized</b><br>' :
                            '<b class="warning p-2">Serialization for each unit in this project has been canceled</b><br>';
                        break;
                    case 'fai_qty':
                        if (!empty($post['fai_qty']) && $post['fai_qty'] != '0')
                            $msg .= "<b class='text-danger'>First test batch: {$post['fai_qty']} pieces.</b><br>";
                        else
                            $msg .= 'No first test batch is required.';
                        break;
                    case 'qty':
                        $msg .= "<b class='text-danger'>Order amount changed FROM: $amount -> TO: {$post['orderAmount']} </b><br>";
                        break;
                }
            }

            /* записываем в чат сообщение о изменениях в заказе */
            self::saveChatMessage($orderId, $user, ['messageText' => $msg, 'readonly' => 1]);

            // display message for user
            $res['pid'] = $post['project_id'];
            _flashMessage('Order details canged successfully');

            $details = "Order N: $orderId, Customer: $c_name, Amount: $amount, Project: $p_name";

            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], 'EDITING', OBJECT_TYPE[0], $details)) {
                _flashMessage('Log creation failed.', 'danger');
            }
        }
        return $res ?? [null];
    }

    /**
     * ARCHIVATION OR DEARCHIVATION ORDER
     * @param $post
     * @param $user
     * @return array
     * @throws /\RedBeanPHP\RedException\SQL
     */
    public static function archiveOrExtractOrder($post, $user): array
    {
        $password = _E($post['password']);
        $orderID = _E($post['idForUse']);
        $log_details = '';
        $res = [];
        /* check password */
        if (checkPassword($password)) {
            $order = R::load(ORDERS, $orderID);

            /* extract order ftom archive */
            if (isset($post['archivation']) && $order->status == 'st-222') {
                $order->status = 'st-111';
                R::store($order);
                _flashMessage('Order extracte from archive successfully');
                /* log details */
                $log_details = 'Order ID: ' . $orderID . ', Extracted from archive';
                /* write readonly message to order chat */
                $msg = ['readonly' => 1, 'messageText' => 'Order dearchivated<br>Transferred to status: Complited.'];
                self::saveChatMessage($orderID, $user, $msg);
            }

            /* putorder to archive */
            if (isset($post['archivation']) && $order->status == 'st-111') {
                $order->status = 'st-222';
                R::store($order);
                _flashMessage('Order added to archive successfully');
                /* log details */
                $log_details = 'Order was added to archive, Order ID: ' . $orderID;
                /* write readonly message to order chat */
                $msg = ['readonly' => 1, 'messageText' => 'Order archivated<br>Transferred to status: Archivated.'];
                self::saveChatMessage($orderID, $user, $msg);
            }

            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], 'ARCHIVATION', OBJECT_TYPE[0], $log_details)) {
                _flashMessage('Log creation failed.', 'danger');
            }
        } else {
            _flashMessage('Incorrect password writed!', 'danger');
        }

        return $res;
    }

    /**
     * i работает только через запрос аджакс сохраняет файл в папку докс проекта
     * make XLSX and save file
     * $pathToSave hase to be full/path/to/folder/plus_name.xlsx
     * or  full/path/to/folder/plus_name - without extension
     * @param $order_id
     * @param $pathToSave
     * @return bool
     */
    public static function makeXLSXfileAndSave($order_id, $pathToSave): bool
    {
        $titles = SR::getAllResourcesInGroup(PROJECT_BOM); // 12 titles
        $order = R::load(ORDERS, $order_id);
        $data = R::findAll(PROJECT_BOM, "projects_id = ?", [$order->projects_id]);
        $orderBOM[] = $titles;

        foreach ($data as $item) {
            $tmpArr = [];
            foreach ($titles as $key => $val) {
                if ($key == 'amount') {
                    $tmpArr[] = (int)($item[$key] * $order['order_amount']);
                } else {
                    $tmpArr[] = $item[$key] != null ? $item[$key] : 'N/A';
                }
            }
            $orderBOM[] = $tmpArr;
        }

        $project_name = $order->project_name;
        $pathToSave = ($pathToSave != null) ? $pathToSave : 'storage/projects/' . $project_name . '/docs/order_bom_for_' . $project_name . '_.xlsx';
        $path = (!str_contains($pathToSave, '.xlsx')) ? $pathToSave . '.xlsx' : $pathToSave;
        $xlsx = XLSXGeneration::fromArray($orderBOM);
        return $xlsx->saveAs($path);
    }

    /* i============================ ORDER CHAT ACTIONS =============================== */
    private static function getFileType($fileExt): string
    {
        $fileType = [
            'image' => ['jpg', 'png', 'webp', 'jpeg'],
            'video' => ['mp4'],
            'audio' => ['mp3', 'm4a', 'wav'],
            'document' => ['pdf', 'csv', 'xls', 'xlsx', 'doc', 'txt', 'zip', 'rar', '7z']
        ];
        foreach ($fileType as $type => $ext) {
            if (in_array($fileExt, $ext)) {
                return $type;
            }
        }
        return 'unknown'; // или значение по умолчанию, если статус не найден
    }

    /**
     * i checks file extension, returns true or false or file type
     * i ['jpg', 'png', 'webp', 'mp4', 'mp3', 'm4a', 'wav', 'pdf', 'csv', 'xls', 'xlsx', 'doc', 'txt', 'zip', 'rar', '7z']
     * @param $file
     * @param null $info
     * @return bool|string[]
     */
    public static function getFileExtension($file, $info = null): array|bool
    {
        $extArray = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mp3', 'm4a', 'wav', 'pdf', 'csv', 'xls', 'xlsx', 'doc', 'txt', 'zip', 'rar', '7z'];

        if (!empty($file['name'][0])) {
            $name = basename($file['name']);
            $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($fileExtension, $extArray))
                return true;
        } else {
            if ($info)
                return $extArray;
        }
        return false;
    }

    /**
     * CHECK FILE SIZE BEFORE SAVING
     * @param array $file
     * @return bool
     */
    public static function checkSizeOfFile(array $file): bool
    {
        $maxSize = 300 * 1024 * 1024; // 300MB

        if ($file['size'] > $maxSize) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *  SAVING FILES FOR ORDER CHAT
     * @param array $file
     * @param $order
     * @param $user
     * @return array
     */
    public static function saveFileToOrderFolder(array $file, $order, $user): array
    {
        $thisOrderFolder = $order->order_folder;
        $log_action = $details = '';
        $res = [null];
        /* если фай вообще существует  */
        if (!empty($file['name'][0])) {
            $uniqueID = unicum();
            $fileNameToSave = ORDERS_FOLDER . "$thisOrderFolder/$uniqueID";
            $tmp_name = $file['tmp_name'];
            $uploadToTemp = TEMP_FOLDER . basename($file['name']);
            /* если фай будет не цветным */
            $docsFileToSave = ORDERS_FOLDER . "$thisOrderFolder/" . basename($file['name']);
            $fileExtension = strtolower(pathinfo($uploadToTemp, PATHINFO_EXTENSION));
            /* проверяем какой тип файла получен */
            $fileType = self::getFileType($fileExtension);

            /*  если была загружена картинка */
            if ($fileType == 'image') {
                /*  если картинка уже в нужном фомате то просто сохраняем с новыи именем и записываем в БД */
                if ($fileExtension == 'webp') {
                    $uploadSuccess = move_uploaded_file($tmp_name, $fileNameToSave . '.webp');
                    if ($uploadSuccess) {
                        $res['image'] = $fileNameToSave . '.webp';
                        $log_action = 'IMAGE_SAVED';
                        $details = "Order N: $order->id, Was saved image file to Chat, File Placed: " . $res['image'];
                        logAction($user['user_name'], $log_action, OBJECT_TYPE[2], $details);
                        return $res;
                    }
                    _flashMessage('Error! image uploading file!');
                } else {
                    /*  если картинка требует конвертации сохраняем в ТЕМП папку */
                    if (move_uploaded_file($tmp_name, $uploadToTemp)) {
                        /*  конвертируем загруженный файл, перемещаем в папку заказа и удаляем оригинал */
                        try {
                            if (Converter::convertToWebP($uploadToTemp, $fileNameToSave . '.webp')) {
                                array_map('unlink', glob(TEMP_FOLDER . "*.*"));
                                $res['image'] = $fileNameToSave . '.webp';
                                $log_action = 'IMAGE_SAVED';
                                $details = "Order N: $order->id, Was saved video file to Chat, File Placed: " . $res['image'];
                            } else {
                                _flashMessage('Conversion error, image format not supported!');
                            }
                        } catch (Exception $e) {
                            _flashMessage(print($e), 'danger');
                        }
                    } else {
                        _flashMessage('Error! image uploading file!s', 'danger');
                    }
                }
            }

            /*  если был загружен видео файл конвертируем его и сохраняем в папку заказа */
            if ($fileType == 'video') {
                // Проверяем, что файл был загружен через HTTP POST
                if (is_uploaded_file($tmp_name)) {
                    // Перемещаем файл в целевую директорию
                    if (move_uploaded_file($tmp_name, $uploadToTemp)) {
                        _flashMessage("File uploaded successfully: " . $uploadToTemp);
                        // Проверяем, является ли файл видео MP4 с кодеком H.264
                        if (Converter::isMp4H264($uploadToTemp)) {
                            // Файл уже в нужном формате, переименуем и переместим его
                            rename($uploadToTemp, $fileNameToSave . '.mp4');
                        } else {
                            // Файл не в формате MP4 H.264, конвертируем его
                            Converter::convertToMp4H264($uploadToTemp, $fileNameToSave . '.mp4');
                            // Удаление исходного файла, если необходимо
                            array_map('unlink', glob(TEMP_FOLDER . "*.*"));
                        }
                        $res['video'] = $fileNameToSave . '.mp4';
                        $log_action = 'VIDEO_SAVED';
                        $details = "Order N: $order->id, Was saved video file to Chat, File Placed: " . $res['video'];
                    } else {
                        _flashMessage('Error! uploading video file!<br>', 'danger');
                    }
                } else {
                    _flashMessage('Notice: Video file not exist!<br>', 'danger');
                }
            }

            if ($fileType == 'audio') {
                if (move_uploaded_file($tmp_name, $uploadToTemp)) {
                    $outputFile = $fileNameToSave . '.m4a'; // Укажите путь к выходному аудиофайлу

                    $command = "ffmpeg -i " . escapeshellarg($uploadToTemp) . " -c:a aac -b:a 128k " . escapeshellarg($outputFile);
                    exec($command, $output, $returnVar);

                    if ($returnVar === 0) {
                        $res['audio'] = $fileNameToSave . '.m4a'; // ААС формат на выходе
                        _flashMessage('File oploaded successfully!');
                        // Удаление исходного файла
                        array_map('unlink', glob(TEMP_FOLDER . "*.*"));
                        $log_action = 'AUDIO_SAVED';
                        $details = "Order N: $order->id, Was saved audio to Chat, File Placed: " . $fileNameToSave;
                    } else {
                        _flashMessage('Error while file upload!', 'danger');
                    }
                }
            }

            /*  если был загружен какой то файл на скачивание то просто сохраняем его без изменений */
            if ($fileType == 'document') {
                $uploadSuccess = move_uploaded_file($tmp_name, $docsFileToSave);
                if ($uploadSuccess) {
                    $res['document'] = $docsFileToSave;
                    $log_action = 'FILE_SAVED';
                    $details = "Order N: $order->id, Was saved file to Chat, File Placed: " . $docsFileToSave;
                } else {
                    _flashMessage('Error! uploading file!', 'danger');
                }
            }


            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[2], $details)) {
                _flashMessage('Error! log creation!', 'danger');
            }
            return $res;
        } // end is file exist at ol
        return [null];
    }

    /**
     *  make and save new message to ORDERS chats
     * @param $order_id
     * @param $user
     * @param $post
     * @param $files
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function saveChatMessage($order_id, $user, $post, $files = null): void
    {
        $result = [null];
        $order = R::load(ORDERS, $order_id);

        /* saving files if exist any */
        if ($files) {
            $result = Orders:: saveFileToOrderFolder($files, $order, $user);
        }

        /* saving the message to DB */
        $chat = R::dispense(ORDER_CHATS);
        $chat->message = _E($post['messageText']);
        $chat->user_name = $user['user_name'];
        $chat->edited = 0;
        $chat->readonly = _E($post['readonly'] ?? 0); // if (1) than message can't be deleted or edited
        $chat->date_in = date('Y-m-d H:i');
        $chat->time_in = time();
        /* if was file than save path to DB */
        if (!empty($result)) {
            $chat->file_path = $result['document'] ?? null;
            $chat->image_file_path = $result['image'] ?? null;
            $chat->video_file_path = $result['video'] ?? null;
            $chat->audio_file_path = $result['audio'] ?? null;
        }

        R::store($chat);

        /* saving relatives to order */
        $order->ownChatList[] = $chat;
        R::store($order);

        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Message saved successfully');

        /* [     LOGS FOR THIS ACTION     ] */
        $details = "MSG in Order №:$order_id, MSG: " . _E($post['messageText']);
        /* сохранение логов если успешно то переходим к БОМ */
        if (!logAction($user['user_name'], 'NEW_MESSAGE', OBJECT_TYPE[2], $details)) {
            _flashMessage('The log not created all actions be canceled.', 'danger');
        }
    }

    /**
     * edit or delete message in to ORDERS chats
     * @param $post
     * @param $user
     * @param $order_id
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function editOrDeleteMessage($post, $user, $order_id): void
    {
        $log_details = $log_action = null;
        if (isset($post['editChatMessage'])) {
            $msg = R::load(ORDER_CHATS, _E($post['editChatMessage']));
            $currentTime = time();
            // Проверяем, не прошло ли более 15 минут и кто меняет сообщение
            if ((15 * 60) > ($currentTime - $msg->time_in) && $user['user_name'] == $msg['user_name'] && !$msg->readonly) {
                /* write stepsData to log */
                $log_action = 'MSG_EDITED';
                $log_details = "MSG for Order №:$order_id was edited, OLD-MSG: $msg->message <br>NEW-MSG:" . _E($post['chatMessage']);

                $msg->message = _E($post['chatMessage']);
                $msg->edited = 1;
                R::store($msg);

                // message collector (text/ color/ auto_hide = true)
                _flashMessage('Message edited successfully');
            } else {
                /* если кто то другой пытается поменять сообщение */
                if ($user['user_name'] != $msg['user_name'])
                    _flashMessage('The message cannot be edited by another user! contact the administrator.', 'warning');
                elseif ($msg->readonly)
                    _flashMessage('The message read only.', 'warning');
                else
                    _flashMessage('The message cannot be edited, the time limit has passed, contact the administrator.', 'warning');
            }
        }

        if (isset($post['deleteChatMessage'])) {
            $msg = R::load(ORDER_CHATS, _E($post['deleteChatMessage']));
            $currentTime = time();
            // Проверяем, не прошло ли более 15 минут и кто меняет сообщение
            if ((15 * 60) > ($currentTime - $msg->time_in) && $user['user_name'] == $msg['user_name'] && !$msg->readonly) {
                /* erasing files if exist any */
                if (!empty($msg['file_path'])) {
                    unlink($msg['file_path']);
                }
                if (!empty($msg['image_file_path'])) {
                    unlink($msg['image_file_path']);
                }
                if (!empty($msg['video_file_path'])) {
                    unlink($msg['video_file_path']);
                }
                if (!empty($msg['audio_file_path'])) {
                    unlink($msg['audio_file_path']);
                }
                $log_action = 'MSG_DELETED';
                $log_details = "MSG for Order №:$order_id was deleted, MSG: " . $msg->message;
                R::trash($msg);
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('Message deleted successfully');
            } else {
                /* если кто то другой пытается поменять сообщение */
                if ($user['user_name'] != $msg['user_name'])
                    _flashMessage('The message cannot be deleted by another user! contact the administrator.', 'warning');
                elseif ($msg->readonly)
                    _flashMessage('The message read only.', 'warning');
                else
                    _flashMessage('The message cannot be deleted, the time limit has passed, contact the administrator.', 'warning');
            }
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if ($log_action != null && $log_details != null) {
            if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[2], $log_details)) {
                _flashMessage('The log not created all actions be canceled.', 'danger');
            }
        }
    }

    /* i============================ ORDER WORK FLOW ACTIONS =============================== */

    /**
     * CHECK IN STORAGE IF ANY ITEM ISN'T EXIST
     * OR LOW AMOUNT RANGE
     * @param $projectBom
     * @param $order
     * @return bool
     */
    public static function isBomComplite($projectBom, $order): bool
    {

        // Проверяем, пустой ли массив projectBom
        if (empty($projectBom)) return false;
        // если разрешено собирать с частично полученными З/Ч
        // то значение будет 1, при стандартном раскладе значение 0
        if ((int)$order['pre_assy'] == 0) {
            foreach ($projectBom as $item) {
                $inShelf = WareHouse::GetActualQtyForItem($item['customerid'], $item['item_id'] ?? '');
                if (!$inShelf || ($item['amount'] * $order['order_amount']) > $inShelf)
                    return false;
            }
            return true;
        } else
            return false;
    }

    /**
     * ACTUAL AMOUNT FOR PARTIAL ORDER COLLECTION BUILD
     * @param  $projectBom
     * @param  $order
     * @return int
     */
    public static function getActualAmountForPartialOrderCollection($projectBom, $order): int
    {
        $minProductionAmount = PHP_INT_MAX; // Инициализируем максимально возможным числом

        foreach ($projectBom as $item) {
            $inShelf = WareHouse::GetActualQtyForItem($item['customerid'], $item['item_id'] ?? '');

            // Если какой-то компонент отсутствует на складе, возвращаем 0
            if (!$inShelf || $inShelf <= 0) {
                return 0;
            }

            // Вычисляем, сколько единиц продукции можно собрать на основании текущего компонента и его количества в заказе
            $needs = $item['amount'] * $order['order_amount']; // Требуемое количество данного компонента для заказа
            $possibleAmount = intdiv($inShelf, $needs);

            // Обновляем минимальное количество, если текущее меньше предыдущего
            if ($possibleAmount < $minProductionAmount) {
                $minProductionAmount = $possibleAmount;
            }
        }

        // Если мы прошли весь список без возврата 0, возвращаем минимально возможное количество продукции
        return $minProductionAmount == PHP_INT_MAX ? 0 : $minProductionAmount;
    }

    /**
     * i CHECKING STOCK IF ITEMS QTY AND SUBSTRACTING FOR NEEDS
     * @param $project_id
     * @param $order
     * @param $storage_space
     * @param $user
     * @return bool
     */
    private static function checkStockAndSubtract($project_id, $order, $storage_space, $user): bool
    {
        if ($order->subtraction == 0) {
            $no_reserved = true;
            $reserv = R::findAll(WH_RESERV, 'order_uid = ?', [$order['id']]); // получаем резерв
            if ($reserv) {  // если резерв существует
                try {
                    R::begin(); // Начинаем транзакцию
                    // делаем списание и удаляем резерв
                    foreach ($reserv as $item) {
                        $qty = WareHouse::GetActualQtyForItem($item['client_uid'], $item['items_id'] ?? '');
                        // отнимаем от склада то что зарезервировано и сохраняем остаток
                        // test message
                        _flashMessage($remainder = $qty - $item['reserved_qty'], 'dark');
                        // обходим масив и обновляем данные на складе
                        $wh_item = R::load(WAREHOUSE, $item['wh_uid']);
                        _flashMessage($wh_item->owner, 'warning');
                        //R::exec("UPDATE warehouse SET quantity = ? WHERE id = ?", [$remainder, $item['wh_uid']]);
                        //$args[] = WareHouseLog::registerMovement($item['items_id'], $item['client_uid'], $storage_space, $item['reserved_qty'], $user);
                    }

                    // удаляем резервированные данные
                    R::trashAll($reserv);
                    $no_reserved = false;
                    // Завершаем транзакцию если все проверки прошли успешно
                    R::commit();
                } catch (Exception $e) {
                    R::rollback(); // Откатываем транзакцию в случае исключения
                    _flashMessage('An error occurred: ' . $e->getMessage(), 'danger', false);
                }

                // произвести списание только если все позиции находятся в наличии на складе и имеют какое то количество в наличии
                _flashMessage('The parts for the production of this order were successfully written off from the warehouse. '
                    . 'A complete list of required parts can be found in the project BOM.');
                return true;
            }

            if ($no_reserved) { // на случай если резервирования не было получаем БОМ проекта
                $order_amount = $order->order_amount;
                $projectBom = R::findAll(PROJECT_BOM, 'projects_id = ?', [$project_id]);
                if ($projectBom) {
                    $bom = array();
                    // обходим БОМ
                    foreach ($projectBom as $item) {
                        // требуемое кол-во
                        $needed = $item['amount'] * $order_amount;
                        // получаем актуальное кол-во по запчасти/товару
                        $qty = WareHouse::GetActualQtyForItem($item['customerid'], $item['item_id'] ?? '');
                        // получаем запчасть/товар
                        $wh_item = WareHouse::GetOneItemFromWarehouse($item['manufacture_pn'], $item['owner_pn'], $item['item_id'] ?? '');
                        if (!empty($qty)) {
                            if ($qty >= $needed) {
                                // если больше чем нужно отнять нужное и сохранить остатки(записать в лог склада)
                                $bom[] = [
                                    'id' => $wh_item->id,
                                    'sub' => ($wh_item->quantity - $needed),
                                    'from' => $wh_item->storage_shelf . '/' . $wh_item->storage_box
                                ];

                            } else {
                                // если меньше чем нужно но стоит разрешение на сборку то отнять то количество которое есть (записать в лог склада)
                                if ($order->pre_assy && $wh_item->quantity > 0) {
                                    $bom[] = ['id' => $wh_item->id, 'sub' => $wh_item->quantity];
                                }
                            }
                        } else {
                            // при отсутствии записи хотя бы об одном товаре или количество равно 0 : прервать операцию!
                            _flashMessage('One or more parts are out of stock! The number of parts may not be sufficient to produce a complete order! '
                                . 'Contact the administrator to clarify the issue. '
                                . 'The operation was aborted!', 'danger', false);
                            return false;
                        }
                    }

                    // обновляем данные на складе
                    try {
                        R::begin(); // Начинаем транзакцию
                        // обходим созданный массив склада и обновляем количество компонентов на складе
                        foreach ($bom as $item) {
                            //i Обновляем поле status для каждого ID в массиве
                            R::exec("UPDATE warehouse SET quantity = ? WHERE id = ?", [$item['sub'], $item['id']]);
                            WareHouseLog::registerMovement($item['id'], $item['from'], $storage_space, $item['sub'], $user);
                        }
                        // Завершаем транзакцию если все проверки прошли успешно
                        R::commit();
                    } catch (Exception $e) {
                        R::rollback(); // Откатываем транзакцию в случае исключения
                        _flashMessage('An error occurred: ' . $e->getMessage(), 'danger', false);
                    }

                    // произвести списание только если все позиции находятся в наличии на складе и имеют какое то количество в наличии
                    _flashMessage('The parts for the production of this order were successfully written off from the warehouse. '
                        . 'A complete list of required parts can be found in the project BOM.');
                    return true;
                }
            }
        }

        // если ранее было списание то сразу отправляем возврат и оповещение
        _flashMessage('The parts were written off from the warehouse earlier, '
            . 'perhaps something went wrong, the operation was stopped, reload the page and try again!', 'danger', false);
        return false;
    }

    /**
     * ORDER CREATION PROCESS/PROGRESS
     * @param $order
     * @param $project
     * @param $steps
     * @param $user
     * @param $post
     * @param $action
     * @return array
     */
    public static function OrderAssemblyProcess($order, $project, $steps, $user, $post, $action): array
    {

        $res = ['tab' => '2', 'step_id' => '', 'errors' => ''];
        $post = checkDataAndConvertToArray($post);
        switch ($action) {
            case 'initiation':
                $res = self::orderProgressInit($order, $project, $user, $steps);
                break;

            case 'continue':
                $res = self::updateOrderProgress($order, $project, $user, $post);
                break;
        }

        return $res;
    }

    /**
     * the function checking if this step isn`t complite yet or status good for get to work
     * @param $order_status
     * @param $step_id
     * @return string|null
     */
    public static function isStepComplite($order_status, $step_id): ?string
    {
        // i статусы при которых можно взять один из шагов в работу задел на будущее
        $state = ['st-8'];
        if (in_array($order_status, $state, true)) {
            $assy = R::findOne(ASSY_PROGRESS, 'current_stepid = ?', [$step_id]);
            if ($assy) {
                return $assy->workend != '0';
            }
            return false;
        }
        return false;
    }

    /**
     * in this function we checking if any user work witch this order with me
     * and if yes then need to write route actions in proggres for all workers now
     * else change proggres for one action
     * @param $order_id
     * @param $user_id
     * @return bool
     */
    private static function isOrderMultiUsers($order_id, $user_id): bool
    {
        $assy = R::findAll(ASSY_PROGRESS, 'orders_id = ?', [$order_id]);
        foreach ($assy as $a) {
            if ($a['users_id'] != $user_id && $a['workend'] == '0' && $a['workstart'] != '0') {
                return true;
            }
        }
        return false;
    }

    /**
     * function creation all assembly steps for order at once
     * @param $order
     * @param $project
     * @param $user
     * @param $steps
     * @return array
     * @throws /\RedBeanPHP\RedException\SQL
     */
    private static function orderProgressInit($order, $project, $user, $steps): array
    {
        // получаем место хранения и перевода запчастей
        $storage_space = $order->storage_shelf . '/' . $order->storage_box;
        // проверяем если все запчасти в наличии и перемещаем к заказу если проект не СМТ
        if ($order->subtraction == 0 && $project->project_type == 0) {
            // списываем запчасти со склада
            $stockCheck = self::checkStockAndSubtract($project->id, $order, $storage_space, $user);
        } elseif ($project->project_type == 1) {
            // переходим к инициализации процесса для СМТ
            $res[] = self::smtAssemblingOrderInitiation($order, $project, $user, $steps);
            // если одной из запчастей нет то выводим ошибку и отменяем все операции
            $res['tab'] = '6';
            return $res;
        }

        // если операция прошла успшно то создаем запись в заказе
        if ($stockCheck) {
            // устанавливает вкладку 6 для дальнейшего выбора сборочного шага в работу
            $res['tab'] = '6';

            // сохраняем в БД данные для заказа
            $ws = $order->date_start = date('Y-m-d H:i'); // работа над заказом началась
            $order->date_end = '0'; // работа над заказом завершилась
            $order->status = 'st-8'; // статус  "order in work"
            $order->order_progress = 0; // до выбора шага заказ еще не в работе
            $order->subtraction = 1; // пишем что списание произошло со склада
            $orid = R::store($order); // сохраняем обновленные данные

            /* [     LOGS FOR THIS ACTION     ] */
            $s_c = count($steps);
            $log_details = "Work started on Project: name-{$project['projectname']}, ID: $project->id<br>";
            $log_details .= "Step count: $s_c, creation date: $project->date_in, Creator: $project->creator<br>";
            $log_details .= "Executor Name: $project->executor, Order started time: $ws<br>";
            $log_details .= "Spare parts for the order were written off from the warehouse";

            $msg = "Work on this order was started by: {$user['user_name']}<br>";
            $msg .= "Spare parts for the order were written off from the warehouse";
            // пишем в чат заказа о данной операции
            self::saveChatMessage($orid, $user, ['messageText' => $msg, 'readonly' => 1]);

            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Creation Started Successfully');

            /*   LOG ACTIONS   */
            if (!logAction($user['user_name'], 'WORK_STARTED', OBJECT_TYPE[0], $log_details)) {
                _flashMessage('The log not created all actions be canceled.', 'danger');
            }

        } else {
            // усли одной из запчастей нет то выводим ошибку и отменяем все операции
            $res['tab'] = '1';
        }
        return $res;
    }

    /**
     * function updating work flow actions for order flow assembling
     * @param $order
     * @param $project
     * @param $user
     * @param $post
     * @return array|string[]
     * @throws /\RedBeanPHP\RedException\SQL
     */
    private static function updateOrderProgress($order, $project, $user, $post): array
    {
        // go to choose step for work or go to assemble the line
        $res['tab'] = ($project->project_type == 1) ? '8' : '6';
        $orid = $order->id;

        // when was choosen step for work
        // добавляем данные для выбранного шага
        if (isset($post['take-a-step-to-work'])) {
            $step = R::load(PROJECT_STEPS, $post['stepid']);
            $res['tab'] = '8'; // go to work with
            //$res['step_id'] = $step->step; // page anchor id for back to needed step

            // начало работы над шагом
            $assy_flow = R::dispense(ASSY_PROGRESS);
            $ws = $assy_flow->workstart = date('Y-m-d H:i'); // step assembling started
            $assy_flow->workend = '0'; // step complited
            $assy_flow->qty_done = 0; // при завершении работы над шагом уазываем количество сделанного

            // если шаг требует проверки запоняем эти поля при выборе шага
            if (!empty($step->validation)) {
                $assy_flow->check_timer = 0; // время старта запроса (раб нажал на проверку)
                $assy_flow->validtime = '0'; // время ожидания запроса на момент проверки
                $assy_flow->approved_by = '0'; // who is checked step and approved
                $assy_flow->second_check = 0; // if second check needed (устанавливается при проверке)
            }

            // заполняем параметры шага для работы и вывода пользователю при выборе шага
            $assy_flow->current_step = $step->step; // step in work for this worker
            $assy_flow->current_stepid = $step->id; // step in work id for this worker
            $assy_flow->validation = $step->validation ?? 0; // step in work for one worker

            // если заказ серийный и надо собирать поштучно с добавлением серийника
            if ($order->serial_required == 1) {
                if (!empty($post['serial_required'])) {
                    $assy_flow->serial_number = $post['serial_required'];
                } elseif (!empty($post['serial_number_for_assy_flow'])) {
                    $assy_flow->serial_number = $post['serial_number_for_assy_flow'];
                } else {
                    $assy_flow->serial_number = '0';
                }
            }

            // данные для заполнения рут карты для данного работника
            $data['route_id'] = $step->routeid;
            $data['route_act'] = $step->routeaction ?? 'NA';
            $data['worker_id'] = $user['id'];
            $data['worker_name'] = $user['user_name'];
            $data['start_time'] = $ws;
            $data['end_time'] = 'NA';
            $assy_flow->route_card_body = json_encode($data);

            // привязки для поиска в дальнейшем
            $assy_flow->orders_id = $order->id; // привязка шага к заказу
            $assy_flow->users_id = $user['id']; // привязка шага к работнику

            // сохраняем настройки шага в работу
            R::store($assy_flow);

            // обновляем в БД данные для прогресса заказа
            // если над заказом работают 2 и более человек и добавить рут акт в прогресс если работает 1 чел то просто обновить прогресс
            if ($order->order_progress != '0' && self::isOrderMultiUsers($order->id, $user['id'])) {
                $temp = explode(', ', $order->order_progress);
                $temp[] = $step->routeid;
                $routes = implode(', ', $temp);
            } else {
                $routes = $data->route_id;
            }

            if (!empty($routes))
                R::exec("UPDATE orders SET order_progress = ? WHERE id = ?", [$routes, $order->id]);


            /* [     LOGS FOR THIS ACTION     ] */
            $log_details = "Work started on Project: name-{$project['projectname']}, ID: $project->id<br>";
            $log_details .= "Step number: $step->step, taken date: $ws, Worker: {$user['user_name']}<br>";
            $log_details .= "Order ID: $orid<br>";

            $msg = "Step number $step->step was taken by employee: {$user['user_name']}";
            self::saveChatMessage($orid, $user, ['messageText' => $msg, 'readonly' => 1]);
            _flashMessage($msg);

            /*   LOG ACTIONS   */
            if (!logAction($user['user_name'], 'ASSEMBLING', OBJECT_TYPE[0], $log_details)) {
                _flashMessage('The log not created all actions be canceled.', 'danger');
            }
            return $res;
        }

        // when worker back to work
        // возвращаемся в точку где закончили рабочий процесс
        if (isset($post['backToWork'])) {
            $res['tab'] = '8'; // go to work with
            return $res;
        }

        // worker pressed on next step button
        // сохраняем данные и переходим на страницу выбора шагов
        if (isset($post['next_step'])) {
            $res['tab'] = '6'; // go to work with

            // таблица состояний заказа и его шагов
            $assy_flow = R::load(ASSY_PROGRESS, $post['assy_step_id']);
            // время завершение работы над шагом
            $we = $assy_flow->workend = date('Y-m-d H:i'); // step complited
            $assy_flow->qty_done = $post['qty_done']; // при завершении работы над шагом уазываем количество сделанного

            // данные для заполнения рут карты i level one
            $data = json_decode($assy_flow->route_card_body);
            $data->end_time = $we;
            $assy_flow->route_card_body = json_encode($data);

            // сохраняем начальные настройки до выбора шага в работу
            R::store($assy_flow);

            // после сохранения всех данных удаляем данный шаг из прогресса
            $routeid = $data->route_id;
            if ($order->order_progress != '0' && self::isOrderMultiUsers($order->id, $user['id'])) {
                $temp = explode(',', $order->order_progress);
                $temp_2 = array_filter($temp, function ($value) use ($routeid) {
                    return trim($value) != $routeid;
                });
                $routes = !empty($temp_2) ? implode(',', $temp_2) : '0';
            } else {
                $routes = '0';
            }

            // обновляем в БД данные для заказа
            R::exec("UPDATE orders SET order_progress = ? WHERE id = ?", [$routes, $order->id]);

            /* [     LOGS FOR THIS ACTION     ] */
            // page anchor id for back to needed step
            $res['step_id'] = $st_num = $assy_flow->current_step;
            $log_details = "Work on Project: name-{$project['projectname']}, ID: $project->id<br>";
            $log_details .= "Step number: $st_num, complite, date: $we, Worker: {$user['user_name']}<br>";

            $msg = "Step number $st_num was complited by employee: {$user['user_name']}";
            self::saveChatMessage($orid, $user, ['messageText' => $msg, 'readonly' => 1]);
            _flashMessage($msg);

            /*   LOG ACTIONS   */
            if (!logAction($user['user_name'], 'ASSEMBLING', OBJECT_TYPE[0], $log_details)) {
                _flashMessage('The log not created all actions be canceled.', 'danger');
            }
            return $res;
        }

        // worker pressed back to previosly worked step button
        // ???? пока что
        if (isset($post['back_to_previos'])) {
            //$res['tab'] = '8';
            //$post['back_to_previos']; // prev step id

            _flashMessage('Временно не понятно что именно надо делать при таком варианте');
        }

        // worker pressed on skip this step button
        // отменяем все изменения сделанные в выбранном шаге
        if (isset($post['skip_this_step'])) {
            // сбрасываем все значения шага на нулевые для вывода его в список шагов
            $assy_flow = R::load(ASSY_PROGRESS, $post['assy_step_id']);
            $assy_flow->workstart = '0'; // step assembling started

            $data = json_decode($assy_flow->route_card_body);
            $routeid = $data->route_id;
            $data->worker_id = 'NA';
            $data->worker_name = 'NA';
            $data->start_time = "NA";
            $data->end_time = 'NA';
            $assy_flow->route_card_body = json_encode($data);
            // привязка шага к работнику
            $assy_flow->users_id = null;

            // сохраняем начальные настройки до выбора шага в работу
            R::store($assy_flow);

            // если над заказом работают 2 и более человек проверяем последнего и удаляем рут акт из прогресса
            if ($order->order_progress != '0' && self::isOrderMultiUsers($order->id, $user['id'])) {
                $temp = explode(',', $order->order_progress);
                $temp_2 = array_filter($temp, function ($value) use ($routeid) {
                    return trim($value) != $routeid;
                });
                $routes = !empty($temp_2) ? implode(',', $temp_2) : '0';
            } else {
                $routes = '0';
            }

            // обновляем в БД данные для заказа
            R::exec("UPDATE orders SET order_progress = ? WHERE id = ?", [$routes, $order->id]);

            /* [     LOGS FOR THIS ACTION     ] */
            $ws = date('Y-m-d H:i');

            $log_details = "Work on Project: name-$project->projectname, ID: $project->id<br>";
            $log_details .= "Step number: $assy_flow->current_step, skipped date: $ws, Worker: {$user['user_name']}<br>";
            $log_details .= "Order ID: $orid<br>";

            $msg = "Step number $assy_flow->current_step was skipped by employee: {$user['user_name']}";
            self::saveChatMessage($orid, $user, ['messageText' => $msg, 'readonly' => 1]);
            _flashMessage($msg);

            /*   LOG ACTIONS   */
            if (!logAction($user['user_name'], 'ASSEMBLING', OBJECT_TYPE[0], $log_details)) {
                _flashMessage('The log not created all actions be canceled.', 'danger');
            }
            return $res;
        }

        // worker forwarded this step to another person
        // создать копию асси шага под новым пользователем а для этого написать что не справился
        if (isset($post['forward_step_to_user'])) {
            $res['tab'] = 1;
            //$post['step_id']; // step id
            //$post['workers']; // user id
            _flashMessage('return to work function кнопка forward');
            return $res;
        }

        // worker proceed validation procedure
        if (isset($post['validate_step'])) {
            $res['tab'] = '8'; // go to choose step for work if continue
            // i устанавливаем статус st-5   Waiting for Step Validation
            //$post['validate_step']; // step id
            //$post['qty_done_for_step']; // assy id
            _flashMessage('return to work function кнопка validate_step');
            return $res;
        }

        if (isset($post['smt_component'])) {
            $res['tab'] = '8'; // continue adding components for  SMT flow
            // обновляем статус компонента в БОМ проекта (сбросить в 0 при статусе ЗАВЕРШЕНО)
            R::exec("UPDATE projectbom SET item_in_work = ? WHERE id = ?", [1, $post['item_id']]);

            if (!empty($post['warehouse_id'])) {
                // обновляем поле место хранения для компонента на складе (будет сброшено при возврате компонента на склад)
                R::exec("UPDATE warehouse SET storage_state = ? WHERE id = ?", [$post['storage_state'], $post['warehouse_id']]);
                // получаем актуальное количество деталей
                $qty = R::getCell("SELECT actual_qty FROM warehouse WHERE id = ?", [$post['warehouse_id']]);
                // обновляем БД новым количеством за минусом нужного для заказа
                R::exec("UPDATE warehouse SET actual_qty = ? WHERE id = ?", [$qty - (int)$post['needed_amount'], $post['warehouse_id']]);

                // пишем в лог перемещение детали и их количество
                WareHouseLog::registerMovement($post['warehouse_id'], 'On Shelf', 'In P&P', $post['needed_amount'], $user);
            }

            // обновляем статус фидера для машины (сбросить в 0 при возврате компонента на склад или изменении позиции)
            R::exec("UPDATE smtline SET feeder_state = ? WHERE id = ?", [1, $post['feeder']]);

            //$post['order_id']; // order id
            _flashMessage('return to work function кнопка smt_component');
            return $res;
        }// end of smt component

        _flashMessage('No Action was choosed but some how you get this message', 'warning', false);
        return $res;
    }

    /**
     * function initiation for SMT assembling flow
     * @param $order
     * @param $project
     * @param $user
     * @param $steps
     * @return array
     * @throws \\RedBeanPHP\RedException\SQL
     */
    private static function smtAssemblingOrderInitiation($order, $project, $user, $steps): array
    {
        // сохраняем в БД данные для заказа
        $ws = $order->date_start = date('Y-m-d H:i'); // работа над заказом началась
        $order->date_end = '0'; // работа над заказом завершилась
        $order->status = 'st-8'; // статус  "order in work"
        $order->order_progress = 0; // до выбора шага заказ еще не в работе
        // списание будет происходить постепенно по мере заполнения СМТ машины
        $order->subtraction = 1; // если БОМ полный то типа списываем со склада
        $orid = R::store($order); // сохраняем обновленные данные

        /* [     LOGS FOR THIS ACTION     ] */
        $s_c = count($steps);
        $log_details = "Work started on Project: name-{$project['projectname']}, ID: $project->id<br>";
        $log_details .= "Step count: $s_c, creation date: $project->date_in, Creator: $project->creator<br>";
        $log_details .= "Executor Name: $project->executor, Order started time: $ws<br>";
        $log_details .= "Spare parts for the order were written off from the warehouse";

        $msg = "Work on this order was started by: {$user['user_name']}<br>";
        $msg .= "Spare parts for the order were written off from the warehouse";
        self::saveChatMessage($orid, $user, ['messageText' => $msg, 'readonly' => 1]);

        _flashMessage('Creation Started Successfully');

        /*   LOG ACTIONS   */
        if (!logAction($user['user_name'], 'WORK_STARTED', OBJECT_TYPE[0], $log_details)) {
            _flashMessage('The log not created all actions be canceled.', 'danger');
        }
        return $res;
    }

    /**
     * Reserving BOM for project/order
     * @param $user
     * @param array $get
     * @param array|null $projectBom
     * @param $reserve
     */
    public static function ReserveBomForOrder($user, array $get, ?array $projectBom, $reserve): void
    {
        $order = R::load(ORDERS, _E($get['orid']));
        $reservations = [];

        // if BOM isn't reserved
        if ($reserve == 0 && $projectBom) {
            try {
                R::begin(); // Начинаем транзакцию
                foreach ($projectBom as $item) {
                    // Ищем записи в БД на данную деталь
                    $result = self::searchInItemsAndWarehouse($item['part_name'], $item['part_value'], $item['manufacture_pn'],
                        $item->customerid, $item['owner_pn']);

                    if (!empty($result['items']['id']) && !empty($result['warehouse'])) {
                        $length = (double)$item['length_mm'] ?? 0;
                        $qty = (int)$item['amount'];
                        $oqty = (int)$order['order_amount'];

                        $reserv = R::dispense(WH_RESERV);
                        $reserv->items_id = $result['items']['id'];
                        $reserv->wh_uid = $result['warehouse']['id'];
                        $reserv->order_uid = $order->id;
                        $reserv->project_uid = $order->projects_id;
                        $reserv->client_uid = $item->customerid;
                        $reserv->reserved_qty = empty($length) ? $qty * $oqty : (($qty * $length) / 1000) * $oqty;
                        $reservations[] = $reserv;
                    } else {
                        R::rollback(); // Откатываем транзакцию в случае ошибки
                        // message collector (text/ color/ auto_hide = true)
                        _flashMessage('Some Item not found in Stock, all operation is aborted', 'warning', false);
                    }
                }

                // Сохраняем все записи в конце, если все проверки прошли успешно
                R::storeAll($reservations);
                R::commit(); // Завершаем транзакцию
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('BOM for this order was reserved. To undo this action, press the unreserve button below the BOM table');
            } catch (Exception $e) {
                R::rollback(); // Откатываем транзакцию в случае исключения

                // message collector (text/ color/ auto_hide = true)
                _flashMessage('An error occurred: ' . $e->getMessage(), 'danger', false);
            }
        } else {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('No BOM to reserve or already reserved', 'warning');
        }
    }

    /**
     * Undo reserved items for project/order
     * @param $user
     * @param array $get
     * @param $reserve
     */
    public static function UnReserveBomForOrder($user, array $get, $reserve): void
    {
        $order = R::load(ORDERS, _E($get['orid']));
        $reserv = R::findAll(WH_RESERV, 'order_uid = ? AND project_uid = ?', [$order->id, $order->projects_id]);

        // Начинаем транзакцию
        R::begin();
        try {
            // when BOM is reserved
            if ($reserve > 0 && $reserv) {
                foreach ($reserv as $item) {
                    R::trash($item);
                }
            }
            // Фиксируем транзакцию
            R::commit();
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('BOM for this order was unreserved');
        } catch (Exception $e) {
            // Откатываем транзакцию в случае ошибки
            R::rollback();
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('An error occurred: ' . $e->getMessage(), 'danger', false);
        }
    }

    // функция поиска запчасти в БД при резервировании БОМА для заказа
    private static function searchInItemsAndWarehouse($partName, $partValue, $manufacturePn, $owner_id, $owner_pn): array
    {
        $pn = (empty($partName) || $partName == 0) ? null : $partName;
        $pv = (empty($partValue) || $partValue == 0) ? null : $partValue;
        $resultItems = R::findOne(WH_ITEMS, 'part_name = ? OR part_value = ? OR manufacture_pn = ?', [$pn, $pv, $manufacturePn]);

        $itId = ($resultItems && !empty($resultItems->id)) ? $resultItems->id : null;
        $opn = !empty($owner_pn) ? $owner_pn : null;
        $res = R::findOne(WAREHOUSE, 'items_id = ? OR owner_pn = ?', [$itId, $opn]);
        if ($res) {
            $resultWarehouse = $res;
        }

        return ['items' => $resultItems ?? [null], 'warehouse' => $resultWarehouse ?? [null]];
    }


} // окончание класса

/*
 * при работе с серийником продумать как это обработать
 * при формировании шагов прогресса продумать работу над одним шагом двум и более работникам
 * при переводе или добавлению работника надо придумать как это обработать и вывести для обзора
 * процедура проверки продумать как должно быть и обработать
 * решить если на каждый чих создавать асси фло и писать туда все или как сейчас
 * сразу на все шаги создаем асси и потом правим по мере прохождения ???
 * */