<?php
require 'WarehouseLog.php';

class WareHouse
{
    /* ============================ PROTECTED METHODS =============================== */
    /**
     * CHECKING POST DATA FOR AN SCAM DATA
     * @param $post
     * @return array
     */
    private static function checkPostDataAndConvertToArray($post): array
    {
        $postDataArray = [];
        foreach ($post as $key => $item) {
            if (is_array($item)) {
                $postDataArray[$key] = self::checkPostDataAndConvertToArray($item);
            } else {
                $postDataArray[$key] = _E($item);
            }
        }
        return $postDataArray;
    }

    /**
     * CONVERTING AND SAVING IMAGES FOR ITEMS
     * @param $file
     * @param $partName
     * @return array
     */
    private static function convertAndSaveImageForItem($file, $partName): array
    {
        // Преобразование всех символов в нижний регистр и замена пробелов, тире и нежелательных символов
        $partName = preg_replace(['/[^a-z0-9 \-_]/', '/[ \-]+/'], ['', '_'], strtolower($partName));
        $uploadDir = TEMP_FOLDER;
        $toSave = 0;
        /* формируем путь к папке для изображения */
        $outputFile = STOCK_FOLDER . "$partName.webp";
        $tmp_name = $file['tmp_name'];
        $uploadedFile = $uploadDir . basename($file['name']);
        $imageFileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

        if ($imageFileType != 'webp') {
            $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
        } else {
            $uploadSuccess = move_uploaded_file($tmp_name, $outputFile);
            $args[] = ['color' => 'success', 'info' => 'Image saved successfully'];
            $toSave = 1;
        }
        /* when file uploaded then converting to webp format if need */
        if ($uploadSuccess && !$toSave) {
            try {
                $convert = Converter::convertToWebP($uploadedFile, $outputFile);
                if ($convert) {
                    array_map('unlink', glob("$uploadDir*.*"));
                    $toSave = 1;
                    $args[] = ['color' => 'success', 'info' => 'Image saved successfully'];
                } else {
                    $args[] = ['color' => 'danger', 'info' => 'Conversion error, image format not supported!'];
                }
            } catch (Exception $e) {
                $args[] = ['color' => 'danger', 'info' => print($e)];
            }

            if ($toSave) {
                $args['file-path'] = $outputFile;
            }
        } else {
            if ($toSave) {
                $args['file-path'] = $outputFile;
            } else {
                $args[] = ['color' => 'danger', 'info' => 'Error! image uploading file!'];
            }
        }

        return $args;
    }

    /**
     * CONVERTING AND SAVING DRAG AND DROPT IMAGE
     * @param $imageData
     * @param $partName
     * @return string[]
     */
    private static function convertAndSavePastedImageForItem($imageData, $partName): array
    {
        $partName = preg_replace(['/[^a-z0-9 \-_]/', '/[ \-]+/'], ['', '_'], strtolower($partName));
        // Убираем префикс data:image/...;base64, если он есть
        if (strpos($imageData, "base64,") !== false) {
            list($typePart, $imageData) = explode('base64,', $imageData);
            // Получаем тип изображения из строки data URI
            preg_match("/^data:image\/(\w+);/", $typePart, $matches);
            $imageType = $matches[1] ?? null;
        } else {
            $imageType = null; // Тип не определён
        }

        $imageData = base64_decode($imageData);
        // Определяем путь сохранения в зависимости от типа изображения
        if ($imageType !== 'webp') {
            // Если изображение не webp, сохраняем во временную папку для дальнейшей конвертации
            $filePathRaw = TEMP_FOLDER . 'image.' . $imageType; // Используем расширение полученное из типа
            // Сохраняем изображение
            file_put_contents($filePathRaw, $imageData);
            // Путь для сохранения конвертированного изображения
            $filePathWebp = STOCK_FOLDER . $partName . ".webp";
            // Вызываем метод конвертации
            $res = Converter::convertToWebp($filePathRaw, $filePathWebp);
            array_map('unlink', glob(TEMP_FOLDER . "*.*"));
        } else {
            // Если изображение уже в webp, формируем путь для сохранения
            $filePathWebp = STOCK_FOLDER . $partName . ".webp";
            // Сохраняем изображение
            file_put_contents($filePathWebp, $imageData);
        }

        return ['file-path' => $filePathWebp];
    }

    /* ============================ PUBLIC METHODS =============================== */
    /**
     * CERATE NEW ITEM IN STORAGE
     * @param $post
     * @param $user
     * @return string[]
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function createNewItem($post, $user): array
    {
        $imageExist = false;
        $imageData = null;
        if (!empty($_POST['imageData']) && strpos($_POST['imageData'], 'data:image') === 0) {
            $imageExist = true;
            $imageData = $post['imageData'];
        }

        $post = self::checkPostDataAndConvertToArray($post);
        $goods = R::dispense(WH_NOMENCLATURE);

        // если фото было выбрано физически
        if (!empty($_FILES['item-image']['name'][0])) {
            $result = self::convertAndSaveImageForItem($_FILES['item-image'], $post['manufacture-part-number']); // return url path for image or null
            $goods->item_image = $result['file-path'] ?? null;
            $res = $result;
        }

        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['manufacture-part-number']);
            $goods->item_image = $result['file-path'] ?? null;
            $res = $result;
        }

        // остальные данные для сохранения
        $goods->part_name = $post['part-name'];
        $goods->part_value = $post['part-value'];
        $goods->part_type = $post['part-type'];
        $goods->footprint = $post['footprint'];
        $goods->manufacturer = $post['manufacturer'];
        $goods->manufacture_pn = $post['manufacture-part-number'];
        $goods->actual_qty = $post['total-quantity'];
        $goods->min_qty = $post['minimal-quantity'];
        $sl_mo = $goods->shelf_life = $post['shelf-life'] ?? 12;
        $goods->class_number = $post['storage-class'];
        $goods->datasheet = $post['datasheet'];
        $goods->description = $post['description'];
        $goods->notes = $post['notes'];
        $goods->date_in = date('Y-m-d H:i');

        // store new item
        $item_id = R::store($goods);

        // creation accessory table for this arrival
        $accessory = R::dispense(WAREHOUSE);
        $accessory->items_id = $item_id;
        $lot = $accessory->lot = $post['part-lot'] ?? 'N:' . date('m/Y') . ':TI~' . $id;
        $accessory->invoice = $post['invoice'] ?? '';
        $accessory->supplier = $post['supplier'];
        $accessory->owner = $post['owner'];
        $accessory->owner_pn = $post['owner-part-name'];
        $accessory->quantity = $post['quantity']; // was amount
        $accessory->storage_box = $post['storage-box'];
        $accessory->storage_shelf = $post['storage-shelf'];
        $mf_date = $accessory->manufacture_date = str_replace('T', ' ', $post['manufactured-date']);
        // Создание объекта DateTime из строки
        $datetime = new DateTime($mf_date);
        // Добавление месяцев к дате
        $datetime->add(new DateInterval("P{$sl_mo}M"));
        // Преобразование даты обратно в строку
        $accessory->expaire_date = $datetime->format('Y-m-d H:i');
        $accessory->date_in = $goods->date_in;

        // Преобразование объекта в массив и в JSON-строку
        $accessoryData = json_encode($accessory->export(), JSON_UNESCAPED_UNICODE);
        $lot_id = R::store($accessory);

        /* writing stock log for this action */
        $log_data['item_id'] = $item_id; // id stored item for search in logs
        // Преобразование объекта в массив и в JSON-строку
        // item full data for save in to log
        $log_data['item_data'] = json_encode($goods->export(), JSON_UNESCAPED_UNICODE);
        $log_data['lot_id'] = $lot_id; // lot id for search in logs
        $log_data['lot'] = $lot; // lot number for preview in logs
        $log_data['lot_data'] = $accessoryData; // lot stored information
        $log_data['invoice'] = $post['invoice'] ?? ''; // invoice for view
        $log_data['supplier_id'] = $post['supplier-id']; // id for seach
        $log_data['supplier'] = $post['supplier']; // name for view
        $log_data['owner_pn'] = $post['owner-part-name']; // id for search
        $log_data['owner'] = $post['owner']; // name for view
        $log_data['quantity'] = $post['quantity']; // amount for view
        $log_data['operation'] = 'NEW ITEM CREATION';
        return WarehouseLog::registerArrival($log_data, $user);
    }

    /**
     * FUNCTION UPDATING ITEM DATA AND INVOICE-LOT DATA
     * @param $post
     * @param $user
     * @return string[]
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function updateNomenclatureItem($post, $user): array
    {
        $imageExist = false;
        $imageData = null;
        if (!empty($_POST['imageData']) && strpos($_POST['imageData'], 'data:image') === 0) {
            $imageExist = true;
            $imageData = $post['imageData'];
        }

        $post = self::checkPostDataAndConvertToArray($post);
        $goods = R::load(WH_NOMENCLATURE, $post['item_id']);

        // Преобразование объекта в массив до изменений и в JSON-строку
        $itemDataBefore = json_encode($goods->export(), JSON_UNESCAPED_UNICODE);

        // if image changed
        if (!empty($_FILES['item-image']['name'][0])) {
            $result = self::convertAndSaveImageForItem($_FILES['item-image'], $post['MFpartName']); // return url path for image or null
            $goods->item_image = $result['file-path'] ?? null;
            $res = $result;
        }

        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['MFpartName']);
            $goods->item_image = $result['file-path'] ?? null;
            $res = $result;
        }

        // обновление данных товара
        $goods->part_name = $post['partName'];
        $goods->part_value = $post['partValue'];
        $goods->part_type = $post['part-type'];
        $goods->footprint = $post['footprint'];
        $goods->manufacturer = $post['manufacturer'];
        $goods->manufacture_pn = $post['MFpartName'];
        $goods->actual_qty = $post['amount'];
        $goods->min_qty = $post['minQTY'];
        $sl_mo = $goods->shelf_life = $post['shelfLife'] ?? 12;
        $goods->class_number = $post['partClassNumber'];
        $goods->datasheet = $post['datasheet'];
        $goods->description = $post['description'];
        $goods->notes = $post['notes'];
        $goods->date_in = date('Y-m-d H:i');

        // Преобразование объекта в массив и в JSON-строку
        $itemDataAfter = json_encode($goods->export(), JSON_UNESCAPED_UNICODE);
        // store new item
        $item_id = R::store($goods);

        /* writing stock log */
        // пишем лог что поменялось и сохраняем две записи до и после изменений
        $log_data['item_data_before'] = $itemDataBefore; // item full data for save in to log
        $log_data['item_data_after'] = $itemDataAfter; // item full data for save in to log
        return WarehouseLog::updatingSomeData($log_data, $user);
    }

    /**
     * ADD NEW ARRIVALS FOR ONE ITEM EXISTED IN NOMENCLATURE TABLE
     * @param $post
     * @param $user
     * @return null[]
     */
    public static function replenishInventory($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        $goods = R::load(WH_NOMENCLATURE, $post['item_id']);
        // Преобразование объекта в массив и в JSON-строку
        $itemData = json_encode($goods->export(), JSON_UNESCAPED_UNICODE);

        // creation accessory table for this arrival
        $accessory = R::dispense(WAREHOUSE);
        $accessory->items_id = $goods->id;
        $lot = $accessory->lot = $post['partLot'] ?? 'N:' . date('m/Y') . ':TI~' . $goods->id;
        $accessory->invoice = $post['invoice'] ?? '';
        $accessory->supplier = $post['supplier'];
        $accessory->owner = $post['owner'];
        $accessory->owner_pn = $post['ownerPartName'];
        $accessory->quantity = $post['quantity']; // was amount
        $accessory->storage_box = $post['storBox'];
        $accessory->storage_shelf = $post['storShelf'];
        $mf_date = $accessory->manufacture_date = str_replace('T', ' ', $post['manufacturedDate']);
        // Создание объекта DateTime из строки
        $datetime = new DateTime($mf_date);
        // Добавление месяцев к дате
        $datetime->add(new DateInterval("P{$goods->shelf_life}M"));
        // Преобразование даты обратно в строку
        $accessory->expaire_date = $datetime->format('Y-m-d H:i');
        $accessory->date_in = date('Y-m-d H:i');

        // Преобразование объекта в массив и в JSON-строку
        $accessoryData = json_encode($accessory->export(), JSON_UNESCAPED_UNICODE);
        $lot_id = R::store($accessory);

        /* writing stock log for this action */
        $log_data['item_id'] = $goods->id; // id stored item for search in logs
        $log_data['item_data'] = $itemData; // item full data for save in to log
        $log_data['lot_id'] = $lot_id; // lot id for search in logs
        $log_data['lot'] = $lot; // lot number for preview in logs
        $log_data['lot_data'] = $accessoryData; // lot stored information
        $log_data['invoice'] = $post['invoice'] ?? ''; // invoice for view
        $log_data['supplier_id'] = $post['supplier_id']; // id for seach
        $log_data['supplier'] = $post['supplier']; // name for view
        $log_data['owner_pn'] = $post['ownerPartName']; // id for search
        $log_data['owner'] = $post['owner']; // name for view
        $log_data['quantity'] = $post['amount']; // amount for view
        $log_data['operation'] = 'ITEM ARRIVAL UPDATE';
        return WarehouseLog::registerArrival($log_data, $user);
    }

    /**
     * RESERVE ITEM FOR ORDER DECREACE ITEM QTY AND CHANGE STORAGE PLACE
     * @param $post
     * @param $user
     * @return null[]
     */
    public static function reserveItemForOrder($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        $goods = R::load(WH_NOMENCLATURE, $post['save-item']);
        $stored_qty = $goods->actual_qty;
        // если кол-во отличается от сохраненного и имеет знак минус то отнимаем
        if (strpos($post['amount'], '-') !== false)
            $goods->actual_qty = $stored_qty - (int)str_replace('-', '', $post['amount']);
        elseif ($post['amount'] != $stored_qty)
            $goods->actual_qty = $stored_qty + $post['amount'];
        // использовать это значение для расчета частичной сборки тоже
        $goods->min_qty = $post['minQTY'];

        $goods->storage_box = $post['storBox'];
        $goods->storage_shelf = $post['storShelf'];

        $goods->manufacture_date = str_replace('T', ' ', $post['manufacturedDate']);
        $goods->exp_date = str_replace('T', ' ', $post['expDate']);
        $goods->date_in = date('Y-m-d H:i');
        // мысль такая списание для заказа производить тут
        // брать самую первую или ближайшую деталь к дате просрочки
        // и из нее рать нужное кол-во если таая есть
        // если деталь одна то просто брать кол-во нужное
        // возвращаем массив с данными для заказа
        // какая полка, какой лот и прочее нужное для работы
        // отнимаем кол-во под заказ из кол-ва в лоте к которому привязываем заказ
        // отнимаем от общего кол-ва тоже сумму для заказа
        // проследить частичное выполнение заказа !!!!
        // пишем лог о перемещении детали в заказ и сохраняем нужную инфу

        $stor_place = $goods->storage_shelf . '/' . $goods->storage_box;
        /* writing stock log */
        //return WarehouseLog::registerWriteOff($log_data, $user);
        return WarehouseLog::registerWriteOff($id, $post['new-amount'], $supplier, $stor_place, $supplier, $invoice, $lot, $user);

    }

    /**
     * DELETE WAREHOUSE ITEM FROM DB
     * @param $itemId
     * @param $user
     * @return array
     */
    public static function putItemToArchive($itemId, $user): array
    {
        if (checkPassword($_POST['password'])) {
//            $g = R::load(STORAGE, $itemId);
//
//            $res[] = ['color' => 'success', 'info' => 'Item was deleted successfully!'];
//            $bomid = Undo::StoreDeletedRecord(STORAGE, $itemId);
//            $url = '<a href="/warehouse?undo=true&bomid=' . $bomid . '" class="btn btn-outline-dark fs-5">Undo Delete Item</a>';
//            $res[] = ['info' => $url, 'color' => 'dark'];
            $res[] = ['info' => 'TODO Archivation or deletion!!' . $itemId . $user['id'], 'color' => 'danger'];

//            R::trash($g);

//            $log_details = "Item was archived to warehouse archive";
//            /* [     LOGS FOR THIS ACTION     ] */
//            if (!logAction($user['user_name'], 'ITEM_ARCHIVED', OBJECT_TYPE[6], $log_details)) {
//                $res['info'] = 'The log not created all actions be canceled.';
//                $res['color'] = 'danger';
//            }
        } else {
            $res['color'] = 'danger';
            $res['info'] = 'Password wrong! try again.';
        }
        return $res;
    }

    /* ============================ FOR ORDER MATERIALS ACTIONS =============================== */
    /**
     * ADD ITEM QTY TO STORAGE ITEM FROM ORDER-BOM
     * @param $postData
     * @param $user
     * @return array
     * @throws /\RedBeanPHP\RedException\SQL
     */
    public static function updateQuantityForItem($postData, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($postData);
        $projectBomItem = R::load(PROJECT_BOM, $postData['item_id']);
        $project = R::load(PROJECTS, $projectBomItem->projects_id);
        $owner_pn = $projectBomItem->owner_pn;

        $search = !empty($owner_pn) ? trim($owner_pn) : null;
        if ($search != null) {
            $stock = R::findOne(WH_NOMENCLATURE, 'owner_pn = ?', [$search]);
            if ($stock) {
                $am = $stock->actual_qty;
                $stock->actual_qty = $am + (float)$postData['import_qty'];
                $res['args'] = false;
            } else {
                return ['args' => true];
            }

            $bo = R::store($stock);
            $res['info'] = 'QTY for Item №' . $bo . ' successfully updated';
            $res['color'] = 'success';

            // TODO log for warehouse logs
            /* [     LOGS FOR THIS ACTION     ] */
            $details = "New QTY for Item №: $bo, in Project: $project->projectname, was added";
            /* сохранение логов если успешно то переходим к БОМ */
            if (!logAction($user['user_name'], 'ITEM_CHANGED', OBJECT_TYPE[6], $details)) {
                $res['info'] = 'Log creation failed.';
                $res['color'] = 'danger';
            }
        } else {
            return ['args' => true];
        }
        return $res;
    }

    /**
     * получение актуального количества одной запчасти на складе
     * или при $getItem = true вывод одого товара целиком
     * @param $owner_pn
     * @param bool $getItem
     * @return array|float|mixed|true
     */
    public static function getActualQtyFromWarehouse($owner_pn, bool $getItem = false)
    {
        // Проверяем, что переменные $customer_pn или $owner_pn не пусты
        $search = (!empty($owner_pn)) ? trim($owner_pn) : null;

        // Проводим запрос в БД только если $search не пуст
        if ($search !== null) {
            $wh = R::findOne(WH_NOMENCLATURE, 'owner_pn = ?', [$search]);

            // Проверяем, что результат запроса действительно существует
            if ($wh) {
                if ($getItem) {
                    /* if need item from warehouse */
                    return $wh;
                }
                /* if need only actual item qty */
                return $wh->actual_qty ?? 0.0;
            } else {
                // Если ничего не найдено
                return null;
            }
        } else {
            // Если $search пуст, не выполняем запрос
            return null;
        }
    }

    /**
     * function for finding component by several entries for SMT line assembling mode
     * @param $part_number
     * @param $owner_pn
     * @return mixed
     */
    public static function findItemInWareHouseByManufacturePN($part_number, $owner_pn)
    {
        $part_number = !empty($part_number) ? $part_number : null;
        $owner_pn = !empty($owner_pn) ? $owner_pn : null;
        if ($part_number || $owner_pn)
            return R::findOne(WH_NOMENCLATURE, 'manufacture_pn LIKE ? OR owner_pn LIKE ?', [$part_number, $owner_pn]);
        else
            return null;
    }

    /**
     * CHECK IN DB IF ITEM EXIST
     * @param $rowData
     * @return array
     */
    public static function checkDuplicates($rowData): array
    {
        $part_value = $rowData['part_value'] ?? null;
        $manufacture_pn = $rowData['manufacture_pn'] ?? null;
        $owner_pn = $rowData['owner_pn'] ?? null;
        $invoice = $rowData['invoice'] ?? null;

        // Поиск полного совпадения
        $fullMatch = R::findOne(WH_NOMENCLATURE, 'manufacture_pn LIKE ? AND invoice LIKE ?', ['%' . $manufacture_pn . '%', '%' . $invoice . '%']);

        // Если найдено полное совпадение
        if ($fullMatch) {
            return [false];
        }

        // Поиск частичного совпадения (без учета invoice)
        $partialMatch = R::findOne(WH_NOMENCLATURE, 'part_value = ? AND owner_pn = ? AND manufacture_pn LIKE ?', [
            $part_value, $owner_pn, '%' . $manufacture_pn . '%'
        ]);

        // Если найдено частичное совпадение
        if ($partialMatch) {
            return ['exist', $partialMatch->id];
        }

        // Нет совпадений
        return [true];
    }


    //I BACK UP CODE
    public static function updateItemBACKUP_CODE($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        if ((!isset($post['item-id']) && !isset($post['new-invoice']) && !isset($post['new-amount']))) {
            $goods = R::load(WH_NOMENCLATURE, $post['save-item']);
            // остальные данные для сохранения
            $goods->part_name = $post['partName'];
            $goods->part_value = $post['partValue'];
            $goods->part_type = $post['part-type'];
            $goods->footprint = $post['footprint'];
            $goods->manufacturer = $post['manufacturer'];
            $goods->manufacture_pn = $post['MFpartName'];
            $goods->owner_pn = $post['ownerPartName'];
            // получаем сохраненное значение кол-ва деталей
            $stored_qty = $goods->actual_qty;

            $goods->min_qty = $post['minQTY'];
            $goods->storage_box = $post['storBox'];
            $goods->storage_shelf = $post['storShelf'];
            $goods->class_number = $post['partClassNumber'];
            $goods->datasheet = $post['datasheet'];
            $goods->owner = $post['owner'];
            $supplier = $goods->supplier = $post['supplier'];
            $goods->description = $post['description'];
            $goods->notes = $post['notes'];

            // добавляем инвойс если он есть
            $goods->invoice = $post['invoice'] ?? '';
            //  добавляем лот
            $goods->lots = implode(',', $lt);

            $goods->manufacture_date = str_replace('T', ' ', $post['manufacturedDate']);
            $goods->exp_date = str_replace('T', ' ', $post['expDate']);
            $goods->date_in = date('Y-m-d H:i');

            $id = R::store($goods);

            /* writing stock log */
            return WarehouseLog::registerWriteOff($id, $post['amount'], null, null, $supplier, $invoice, $lot, $user);

        }
        $stor_place = $goods->storage_shelf . '/' . $goods->storage_box;
        /* writing stock log */
        return WarehouseLog::registerWriteOff($id, $post['new-amount'], $supplier, $stor_place, $supplier, $invoice, $lot, $user);

    }
    // i ---------------------------------------------
}