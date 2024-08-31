<?php
require 'WareHouseLog.php';

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
            _flashMessage('Image saved successfully');
            $toSave = 1;
        }
        /* when file uploaded then converting to webp format if need */
        if ($uploadSuccess && !$toSave) {
            try {
                $convert = Converter::convertToWebP($uploadedFile, $outputFile);
                if ($convert) {
                    array_map('unlink', glob("$uploadDir*.*"));
                    $toSave = 1;
                    _flashMessage('Image saved successfully');
                } else {
                    _flashMessage('Conversion error, image format not supported!', 'danger');
                }
            } catch (Exception $e) {
                _flashMessage(print($e), 'danger');
            }

            if ($toSave) {
                $args['file-path'] = $outputFile;
            }
        } else {
            if ($toSave) {
                $args['file-path'] = $outputFile;
            } else {
                _flashMessage('Error! image uploading file!', 'danger');
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
            Converter::convertToWebp($filePathRaw, $filePathWebp);
            // удаляем временный файл
            array_map('unlink', glob(TEMP_FOLDER . "*.*"));
        } else {
            // Если изображение уже в webp, формируем путь для сохранения
            $filePathWebp = STOCK_FOLDER . $partName . ".webp";
            // Сохраняем изображение
            file_put_contents($filePathWebp, $imageData);
        }

        return ['file-path' => $filePathWebp];
    }

    /**
     * Футкция учета названий данных клиентом (NTI)
     * работает только для наших запчастей
     * @param $key
     * @return array
     */
    private static function GetNtiPartNumberForItem($key): array
    {
        // Составление SQL-запроса
        $sql = "SELECT MAX(CAST(SUBSTRING(owner_pn, LENGTH(:key) + 1) AS UNSIGNED)) AS max_number FROM warehouse WHERE owner_pn LIKE :keyPattern";
        // Выполнение запроса и получение результата
        $maxNumber = R::getCell($sql, [':key' => $key, ':keyPattern' => $key . '%']);
        // Вывод результата
        if (!empty($maxNumber))
            return ["key" => $key, "number" => $maxNumber];
        else
            return [null];
    }
    /* ============================ PUBLIC METHODS =============================== */
    /**
     * CERATE NEW ITEM IN STORAGE
     * @param $post
     * @param $user
     * @return string[]
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function CreateNewWarehouseItem($post, $user): array
    {
        // ПРОВЕРЯЕМ ЕСЛИ ФОТО КОПИРОВАНО С САЙТА
        $imageExist = false;
        $imageData = null;
        if (!empty($_POST['imageData']) && strpos($_POST['imageData'], 'data:image') === 0) {
            $imageExist = true;
            $imageData = $post['imageData'];
        }

        // ПРОВЕРЯЕМ БЕЗОПАСНОСТЬ ДАННЫЧ
        $post = self::checkPostDataAndConvertToArray($post);
        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦЕ ТОВАРОВ
        $item = R::dispense(WH_ITEMS);

        $item->part_name = $post['part-name'];
        $item->part_value = $post['part-value'];
        $item->mounting_type = $post['mounting-type'];
        $item->footprint = $post['footprint'] ?? '';
        $item->manufacturer = $post['manufacturer'] ?? 'Not Added Yet';
        $item->manufacture_pn = $post['manufacture-part-number'];
        // нужна для обозначения нехватки товара
        $item->min_qty = !empty($post['minimun-quantity']) ? $post['minimun-quantity'] : floor((int)$post['quantity'] * 0.10);
        $sl_mo = $item->shelf_life = $post['shelf-life'] ?? 12;
        $item->class_number = $post['storage-class'];
        $item->datasheet = $post['datasheet'] ?? 'Not Added Yet';
        $item->description = $post['description'] ?? 'Not Added Yet';
        $item->notes = $post['notes'] ?? 'Not Added Yet';
        $item->date_in = date('Y-m-d H:i');

        // если фото было выбрано физически
        if (!empty($_FILES['item-image']['name'][0])) {
            $result = self::convertAndSaveImageForItem($_FILES['item-image'], $post['manufacture-part-number']); // return url path for image or null
            $item->item_image = $result['file-path'] ?? null;
        }
        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['manufacture-part-number']);
            $item->item_image = $result['file-path'] ?? null;
        }

        //если фото было выбрано из существующих на сервере
        if (!empty($post['image-path'])) {
            $item->item_image = $post['image-path'];
        }
        // store new item
        $item_id = R::store($item);


        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ СКЛАД
        $warehouse = R::dispense(WAREHOUSE);
        $warehouse->wh_types_id = $post['warehouse-type-id']; // расположение склада физичеки
        $warehouse->items_id = $item_id;
        // создаем json обьект для дальнейшего использования
        $owner_data = '{"name":"' . $post['owner'] . '", "id":"' . ($post['owner-id'] ?? '') . '"}';
        $warehouse->owner = $owner_data; // this part owner

        $owner_pn = '';
        // если был выбран один из списка
        if (!empty($post['owner-pn-list']) && $post['owner-pn-list'] != 'custom') {
            $res = self::GetNtiPartNumberForItem($post['owner-pn-list']);
            if (!empty($res))
                $owner_pn = $res['key'] . ($res['number'] + 1);
        } else {
            // если был внесен новый или клиентский номер
            $owner_pn = $post['owner-pn-input'];

        }
        // сохраняем имя детали в БД
        $warehouse->owner_pn = $owner_pn;

        // полученное кол-во нового товара
        $warehouse->quantity = $post['quantity'];

        if (!empty($warehouse->storage_box) && $warehouse->storage_box != $post['storage-box']) {
            // отменяем предыдущий ящик
            SR::updateResourceDetail('stock', $warehouse->storage_box, '0', true);
        }

        // назначаем новый ящик
        SR::updateResourceDetail('stock', $post['storage-box'], '1');
        // сохраняем новое значение
        $warehouse->storage_box = $post['storage-box'];
        $warehouse->storage_shelf = $post['storage-shelf'];

        $warehouse->storage_state = $post['storage-state'];
        $mf_date = $warehouse->manufacture_date = str_replace('T', ' ', $post['manufactured-date']);
        // Создание срока годности для товара
        $datetime = new DateTime($mf_date);
        $datetime->add(new DateInterval("P{$sl_mo}M"));
        $warehouse->fifo = $datetime->format('Y-m-d H:i');
        $warehouse->date_in = $item->date_in;
        $warehouse_id = R::store($warehouse);


        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ ИНВОЙСОВ
        $invoice = R::dispense(WH_DELIVERY);
        $invoice->items_id = $item_id;
        $invoice->quantity = $post['quantity']; // полученное кол-во товара в этой накладной
        $invoice->warehouses_id = $warehouse_id;
        $lot = $invoice->lot = !empty($post['part-lot']) ? $post['part-lot'] : 'N:' . date('m/Y') . ':TI~' . $item_id;
        $invoice->consignment = $post['consignment']; // this airrval consignment document number
        $invoice->delivery_note = $post['delivery_note']; // this airrval delivery_note
        $invoice->supplier = '{"name":"' . ($post['supplier'] ?? '') . '","id":"' . ($post['supplier-id'] ?? '') . '"}'; // this airrval suplplier
        $invoice->owner = $owner_data; // this part owner
        $invoice->date_in = $item->date_in;
        $invoice_id = R::store($invoice);

        // ЗАПИСЫВАЕМ В ЛОГ ОПЕРАЦИЮ И ДАННЫЕ ТАБЛИЦ
        return WareHouseLog::registerNewArrival($item->export(), $warehouse->export(), $invoice->export(), $user);
    }

    /**
     * FUNCTION UPDATING ITEM DATA AND INVOICE-LOT DATA
     * @param $post
     * @param $user
     * @return void
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function UpdateNomenclatureItem($post, $user)
    {
        // fixme доработать данную функцию на предмет сохранения изменений в других таблицах!
        $needToDelete = $imageExist = false;
        $imageData = null;
        if (!empty($_POST['imageData']) && strpos($_POST['imageData'], 'data:image') === 0) {
            //if (!empty($_POST['imageData']) && str_starts_with($_POST['imageData'], 'data:image')) { // PHP 8.0 >>
            $imageExist = true;
            $imageData = $post['imageData'];
        }

        $post = self::checkPostDataAndConvertToArray($post);
        $item = R::load(WH_ITEMS, $post['item_id']);
        // Преобразование объекта в массив до изменений и в JSON-строку
        $itemDataBefore = json_encode($item->export(), JSON_UNESCAPED_UNICODE);
        // берем путь к старому фото
        $oldPhotoPath = $item->item_image;

        $item->part_name = $post['part-name'];
        $item->part_value = $post['part-value'];
        $item->mounting_type = $post['mounting-type'];
        $item->footprint = $post['footprint'] ?? '';
        $item->manufacturer = $post['manufacturer'] ?? 'Not Added Yet';
        $item->manufacture_pn = $post['manufacture-part-number'];
        // нужна для обозначения нехватки товара

        $item->min_qty = !empty($post['minimun-quantity']) ? $post['minimun-quantity'] : 1;
        $sl_mo = $item->shelf_life = $post['shelf-life'] ?? 12;
        $item->class_number = $post['storage-class'] ?? 1;
        $item->datasheet = $post['datasheet'] ?? 'Not Added Yet';
        $item->description = $post['description'] ?? 'Not Added Yet';
        $item->notes = $post['notes'] ?? 'Not Added Yet';
        $item->date_in = date('Y-m-d H:i'); //i add changed data to page

        // если фото было выбрано физически
        if (!empty($_FILES['item-image']['name'][0])) {
            $result = self::convertAndSaveImageForItem($_FILES['item-image'], $post['manufacture-part-number']);
            $item->item_image = $result['file-path'] ?? null;
            $needToDelete = true;
        }
        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['manufacture-part-number']);
            $item->item_image = $result['file-path'] ?? null;
            $needToDelete = true;
        }
        //если фото было выбрано из существующих на сервере
        if (!empty($post['image-path'])) {
            $item->item_image = $post['image-path'];
        }
        // update item
        $item_id = R::store($item);

        //i РАБОТАЕМ С ТАБЛИЦЕЙ СКЛАДОВ ИЛИ СКЛАДА ИЗ ПОСЛЕДНЕГО
        //обновление местоположения склада если его изменили
        // fixme сделать лог для изменений 
        $warehouse = R::load(WAREHOUSE, $post['wh_id']);
        if (!empty($warehouse->storage_box) && $warehouse->storage_box != $post['storage-box']) {
            // отменяем предыдущий ящик
            SR::updateResourceDetail('stock', $warehouse->storage_box, '0', true);
        }

        // назначаем новый ящик
        SR::updateResourceDetail('stock', $post['storage-box'], '1');
        // сохраняем новое значение
        $warehouse->storage_box = $post['storage-box'];
        $warehouse->storage_shelf = $post['storage-shelf'];
        $warehouse->storage_state = $post['storage-state'];

        if ($warehouse->wh_types_id != $post['warehouse-type-id']) {
            // $itemDataBefore['wh_type_id'] = $warehouse->wh_types_id;
            // расположение склада физичеки
            $warehouse->wh_types_id = $post['warehouse-type-id'];
            // $itemDataAfter['wh_type_id'] = $post['warehouse-type-id'];
        }
        R::store($warehouse);


        // проверяем если ранее было добавлено фото и удаляем старое если оно есть/было
        if (!empty($oldPhotoPath) && is_file($oldPhotoPath) && $needToDelete) {
            unlink($oldPhotoPath);
        }

        // Преобразование объекта в массив и в JSON-строку
        $itemDataAfter = json_encode($item->export(), JSON_UNESCAPED_UNICODE);

        /* writing warehouse log */
        // Объединение данных в один массив
        $logData = ['item_data_before' => json_decode($itemDataBefore, true),
            'item_data_after' => json_decode($itemDataAfter, true)];
        WareHouseLog::updatingSomeData($item_id, $logData, $user);
    }

    /**
     * ADD NEW ARRIVALS FOR ONE ITEM EXISTED IN NOMENCLATURE TABLE
     * @param $post
     * @param $user
     * @return array
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function ReplenishInventory($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);

        $item_id = $post['item_id'];
        $item = R::load(WH_ITEMS, $item_id);

        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ СКЛАД
        $warehouse = R::dispense(WAREHOUSE);
        $warehouse->items_id = $item_id;
        $warehouse->wh_types_id = $post['warehouse-type-id']; // расположение склада физичеки
        // создаем json обьект для дальнейшего использования
        $owner_data = '{"name":"' . $post['owner'] . '", "id":"' . ($post['owner-id'] ?? '') . '"}';
        $warehouse->owner = $owner_data; // this part owner

        $owner_pn = null;
        // если был выбран один из списка
        if (!empty($post['owner-pn-list']) && $post['owner-pn-list'] != 'custom') {
            $res = self::GetNtiPartNumberForItem($post['owner-pn-list']);
            if (!empty($res))
                $owner_pn = $res['key'] . ($res['number'] + 1);
        } else {
            // если был внесен новый или клиентский номер
            $owner_pn = $post['owner-pn-input'];

        }
        // сохраняем имя детали в БД
        $warehouse->owner_pn = $owner_pn;

        // полученное кол-во нового товара
        $warehouse->quantity = $post['quantity'];

        if (!empty($warehouse->storage_box) && $warehouse->storage_box != $post['storage-box']) {
            // отменяем предыдущий ящик
            SR::updateResourceDetail('stock', $warehouse->storage_box, '0', true);
        }

        // назначаем новый ящик
        SR::updateResourceDetail('stock', $post['storage-box'], '1');
        // сохраняем новое значение
        $warehouse->storage_box = $post['storage-box'];
        $warehouse->storage_shelf = $post['storage-shelf'];
        $warehouse->storage_state = $post['storage-state'];
        $mf_date = $warehouse->manufacture_date = str_replace('T', ' ', $post['manufactured-date']);

        // Создание срока годности для товара
        try {
            $datetime = new DateTime($mf_date);
            $datetime->add(new DateInterval("P{$item->shelf_life}M"));
            $warehouse->fifo = $datetime->format('Y-m-d H:i');
            $warehouse->date_in = date('Y-m-d H:i');
        } catch (Exception $e) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Error ' . $e->getMessage(), 'danger');
        }

        $warehouse_id = R::store($warehouse);


        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ ИНВОЙСОВ
        $delivery = R::dispense(WH_DELIVERY);
        $delivery->items_id = $item_id;
        $delivery->quantity = $post['quantity']; // полученное кол-во товара в этой накладной
        $delivery->warehouses_id = $warehouse_id;
        $lot = $delivery->lot = !empty($post['part-lot']) ? $post['part-lot'] : 'N:' . date('m/Y') . ':TI~' . $item_id;
        $delivery->consignment = $post['consignment']; // this airrval consignment document number
        $delivery->delivery_note = $post['delivery_note']; // this airrval delivery note
        $delivery->supplier = '{"name":"' . ($post['supplier'] ?? '') . '","id":"' . ($post['supplier-id'] ?? '') . '"}'; // this airrval suplplier
        $delivery->owner = $owner_data; // this part owner
        $delivery->date_in = date('Y-m-d H:i');
        $delivery_id = R::store($delivery);

        // ЗАПИСЫВАЕМ В ЛОГ ОПЕРАЦИЮ И ДАННЫЕ ТАБЛИЦ
        $args = WareHouseLog::registerNewArrival($item->export(), $warehouse->export(), $delivery->export(), $user, 'NEW ITEM ARRIVAL');
        $args['action'] = 'success';
        return $args;
    }


    /**
     * Updates related tables by comparing the provided data with existing data in the database.
     * If the data differs, it updates the database with the new values.
     *
     * @param array $post Data from the POST request, containing table name, item ID, and fields to update.
     * @param array $user Information about the current user making the request.
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function updateRelatedTables(array $post, array $user)
    {
        // Convert POST data to array if necessary
        $post = self::checkPostDataAndConvertToArray($post);

        // Extract table name and item ID from the POST data
        $tableName = $post['table-name'];
        $itemId = $post['item_id'];

        // Load the existing record from the database
        $item = R::load($tableName, $itemId);

        // Array to hold log data for before and after changes
        $logData = ['item_data_before' => [], 'item_data_after' => []];

        // Iterate over POST data to compare and update fields
        foreach ($post as $name => $value) {
            if (in_array($name, ['table-name', 'item_id'])) {
                continue; // Skip table name and item ID fields
            }

            if ($name == 'supplier' || $name == 'owner') {
                $existingValue = json_decode($item->$name, true);
                $existingId = $existingValue['id'] ?? '';
                $existingName = $existingValue['name'] ?? '';
                $newId = $post["{$name}_id"] ?? '';
                $newName = $post[$name] ?? '';

                if ($existingId !== $newId || $existingName !== $newName) {
                    // Update field in the database record
                    $item->$name = json_encode(['name' => $newName, 'id' => $newId]);

                    // Log the changes
                    $logData['item_data_before'][$name] = $existingValue;
                    $logData['item_data_after'][$name] = ['name' => $newName, 'id' => $newId];
                }
            } else {
                // Normalize strings by removing non-alphanumeric characters
                $existingValue = preg_replace('/[^a-zA-Z0-9]/', '', $item->$name);
                $newValue = preg_replace('/[^a-zA-Z0-9]/', '', $value);

                // Compare existing value with new value
                if ($existingValue !== $newValue) {
                    // Log the changes
                    $logData['item_data_before'][$name] = $item->$name;
                    $logData['item_data_after'][$name] = $value;

                    // Update field in the database record
                    $item->$name = $value;
                }
            }
        }

        // Save the updated record to the database if there were any changes
        if (!empty($logData['item_data_after'])) {
            R::store($item);
        }

        // write the log data for further processing or auditing
        WareHouseLog::updatingSomeData($itemId, $logData, $user);
    }


    /*i ============================ FOR ORDER MATERIALS ACTIONS =============================== */
    /**
     * ADD ITEM QTY TO STORAGE ITEM FROM ORDER-BOM
     * @param $postData
     * @param $user
     * @return bool
     */
    // fixme ПЕРЕДЕЛАТЬ ЗАПОЛНЕНИЕ БОМА ДЛЯ ЗАКАЗА
    public static function updateQuantityForItem($postData, $user): bool
    {
        /** что то сделать для правильной работы кейса
         * тут надо добавить новое поступление если запчасть есть в БД
         * создаем документ на складе заносим новые данные и кол во
         * а если нет то перейти к созданию новой запчасти
         *
         * пока что провто переходим к созданию новой детали
         */


//        $post = self::checkPostDataAndConvertToArray($postData);
//        $projectBomItem = R::load(PROJECT_BOM, $postData['item_id']);
//        $project = R::load(PROJECTS, $projectBomItem->projects_id);
//        $owner_pn = $projectBomItem->owner_pn;
//
//        $search = !empty($owner_pn) ? trim($owner_pn) : null;
//        $item_id = !empty($projectBomItem['item_id']) ? $projectBomItem['item_id'] : null;
//        if ($search != null) {
//            $stock = R::findOne(WAREHOUSE, 'owner_pn = ? OR items_id', [$search, $item_id]);
//            if ($stock) {
//                $am = $stock->quantity;
//                $stock->quantity = $am + (float)$postData['import_qty'];
//                $res = false;
//            } else {
//                $res = true;
//            }
//
//            $bo = R::store($stock);
//            _flashMessage('QTY for Item №' . $bo . ' successfully updated');
//
//            // TODO log for warehouse logs
//            /* [     LOGS FOR THIS ACTION     ] */
//            $details = "New QTY for Item №: $bo, in Project: $project->projectname, was added";
//            /* сохранение логов если успешно то переходим к БОМ */
//            if (!logAction($user['user_name'], 'ITEM_CHANGED', OBJECT_TYPE[6], $details)) {
//                _flashMessage('Log creation failed.', 'danger');
//            }
//        } else {

//        }
        return true;
    }

    /**
     * Получает фактическое количество для указанного товара и владельца.
     *
     * Эта функция проверяет наличие записей в таблице склада, которые соответствуют указанным `owner_id` и `item_id`.
     * Затем она суммирует все значения поля `quantity`, которые не равны нулю. Если записи найдены, но все значения
     * поля `quantity` равны нулю, функция возвращает 0. Если записи не найдены, функция возвращает `null`.
     *
     * @param string $owner_id Идентификатор владельца.
     * @param string $item_id Идентификатор товара.
     * @return float|null Суммарное количество товара или `null`, если записи не найдены.
     */
    public static function GetActualQtyForItem(string $owner_id, string $item_id)
    {
        // Проверяем, что $owner_id и $item_id не пусты
        $ownerId = (!empty($owner_id)) ? trim($owner_id) : null;
        $itemId = (!empty($item_id)) ? trim($item_id) : null;

        // Проводим запрос в БД только если $ownerId и $itemId не пусты
        if ($ownerId !== null && $itemId !== null) {
            $query = 'SELECT quantity FROM ' . WAREHOUSE . ' WHERE JSON_UNQUOTE(JSON_EXTRACT(owner, "$.id")) = ? AND items_id = ?';
            $results = R::getAll($query, [$ownerId, $itemId]);

            // Если записи найдены, суммируем количество
            if ($results) {
                $totalQuantity = 0;
                foreach ($results as $row) {
                    $totalQuantity += $row['quantity'];
                }

                // Если все количества равны нулю, вернуть 0
                if ($totalQuantity == 0) {
                    return 0.0;
                }

                return $totalQuantity;
            } else {
                // Если ничего не найдено
                return null;
            }
        } else {
            // Если $ownerId или $itemId пусты, не выполняем запрос
            return null;
        }
    }


//    public static function GetActualQtyForItem(string $owner_id, string $item_id)
//    {
//        // Проверяем, что $owner_id и $item_id не пусты
//        $ownerId = (!empty($owner_id)) ? trim($owner_id) : null;
//        $itemId = (!empty($item_id)) ? trim($item_id) : null;
//
//        // Проводим запрос в БД только если $ownerId и $itemId не пусты
//        if ($ownerId !== null && $itemId !== null) {
//            $query = 'SELECT quantity FROM ' . WAREHOUSE . ' WHERE JSON_EXTRACT(owner, "$.id") = ? AND items_id = ?';
//            $results = R::getAll($query, [$ownerId, $itemId]);
//            // Если записи найдены, суммируем количество
//            if ($results) {
//                $totalQuantity = 0;
//                foreach ($results as $row) {
//                    $totalQuantity += $row['quantity'];
//                }
//
//                // Если все количества равны нулю, вернуть 0
//                if ($totalQuantity == 0) {
//                    return 0.0;
//                }
//
//                return $totalQuantity;
//            } else {
//                // Если ничего не найдено
//                return null;
//            }
//        } else {
//            // Если $ownerId или $itemId пусты, не выполняем запрос
//            return null;
//        }
//    }


    /**
     * SEARCH AND RETURN ONE ITEM FOR PROJECT BOM TAB
     * function for finding component by several entries for SMT line assembling mode
     * @param $part_number
     * @param $owner_pn
     * @param null $item_id
     * @return mixed
     */
    public static function GetOneItemFromWarehouse($part_number, $owner_pn, $item_id = null)
    {
        $item = null;
        if (!empty($item_id)) {
            $item = R::findOne(WAREHOUSE, 'items_id = ?', [$item_id]);
        } else {
            // Очистка входных данных
            $part_number = !empty($part_number) ? $part_number : null;
            $owner_pn = !empty($owner_pn) ? $owner_pn : null;
            if ($part_number || $owner_pn) {
                // SQL-запрос для поиска по двум таблицам
                $sql = "SELECT wi.*, w.* FROM whitems wi LEFT JOIN warehouse w ON wi.id = w.items_id
            WHERE wi.manufacture_pn LIKE ? OR w.owner_pn LIKE ? LIMIT 1";
                // Выполнение запроса и получение результата
                $result = R::getRow($sql, ["%$part_number%", "%$owner_pn%"]);
                $item = $result;
            }
        }
        return $item;
    }

    /**
     * CHECK IN DB IF ITEM EXIST FOR ADD USE FILE
     * @param $data
     * @param bool $isPost
     * @return array
     */
    public static function CheckDuplicates($data, bool $isPost = true): array
    {
        // Создаем шаблоны для поиска с учетом любых разделителей и местоположения
        if ($isPost) {
            // Поля из файла для заполнения БД
            $part_value = '%' . $data['part-value'] . '%';
            $manufacture_pn = '%' . $data['manufacture-part-name'] . '%';
            $owner_pn = '%' . $data['owner-part-name'] . '%';
        } else {
            // поля из таблицы БД при переборе результата
            $part_value = '%' . $data['part_value'] . '%';
            $manufacture_pn = '%' . $data['manufacture_pn'] . '%';
            $owner_pn = '%' . $data['owner_pn'] . '%';
        }
        $invoice = '%' . $data['consignment'] . '%';

        // Имена таблиц для запроса
        $wh_item = WH_ITEMS;
        $wh_delivery = WH_DELIVERY;
        $warehouse = WAREHOUSE;

        // SQL-запрос для поиска полного совпадения
        $sqlFullMatch = "SELECT w.* FROM $warehouse w JOIN $wh_item wi ON wi.id = w.items_id
        JOIN $wh_delivery win ON win.id = w.invoice_id WHERE wi.part_value LIKE ? 
        AND wi.manufacture_pn LIKE ? AND w.owner_pn LIKE ? AND win.consignment LIKE ?";

        // Выполнение запроса и получение результата
        $fullMatch = R::getRow($sqlFullMatch, [$part_value, $manufacture_pn, $owner_pn, $invoice]);

        // Если найдено полное совпадение
        if ($fullMatch) {
            return [false];
        }

        // SQL-запрос для поиска частичного совпадения (без учета consignment)
        $sqlPartialMatch = "SELECT w.* FROM $warehouse w JOIN $wh_item wi ON wi.id = w.items_id
        WHERE wi.part_value LIKE ? AND wi.manufacture_pn LIKE ? AND w.owner_pn LIKE ?";

        // Выполнение запроса и получение результата
        $partialMatch = R::getRow($sqlPartialMatch, [$part_value, $manufacture_pn, $owner_pn]);

        // Если найдено частичное совпадение
        if ($partialMatch) {
            return ['exist', $partialMatch['id']];
        }

        // Если ничего не найдено в БД
        return [true];
    }

    // функция возвращает данные из таблицы склада warehouse
    //$wh_item = findClosestShelfLifeItem($item);
//    public static function findClosestShelfLifeItem($item): mixed
//    {
//        // Вычисление даты, от которой мы будем искать записи
//        $x_day = strtotime("-{$item['shelf_life']} months");
//        // Конвертация $x_day в формат даты для сравнения с полем fifo
//        $x_day_date = date('Y-m-d H:i', $x_day);
//        // Выполнение запроса и получение результата
//        return R::findOne(WAREHOUSE, 'items_id = ? AND fifo > ? ORDER BY fifo ASC LIMIT 1', [$item['id'], $x_day_date]);
//    }

    /**
     * RESERVE ITEM FOR ORDER DECREACE ITEM QTY AND CHANGE STORAGE PLACE
     * @param $post
     * @param $user
     * @return null[]
     */
//    public static function reserveItemForOrder($post, $user): array
//    {
//        $post = self::checkPostDataAndConvertToArray($post);
//        $goods = R::load(WH_ITEMS, $post['save-item']);
//        $stored_qty = $goods->actual_qty;
//        // если кол-во отличается от сохраненного и имеет знак минус то отнимаем
//        if (strpos($post['amount'], '-') !== false)
//            $goods->actual_qty = $stored_qty - (int)str_replace('-', '', $post['amount']);
//        elseif ($post['amount'] != $stored_qty)
//            $goods->actual_qty = $stored_qty + $post['amount'];
//        // использовать это значение для расчета частичной сборки тоже
//        $goods->min_qty = $post['minQTY'];
//
//        $goods->storage_box = $post['storBox'];
//        $goods->storage_shelf = $post['storShelf'];
//
//        $goods->manufacture_date = str_replace('T', ' ', $post['manufacturedDate']);
//        $goods->exp_date = str_replace('T', ' ', $post['expDate']);
//        $goods->date_in = date('Y-m-d H:i');
//        // мысль такая списание для заказа производить тут
//        // брать самую первую или ближайшую деталь к дате просрочки
//        // и из нее рать нужное кол-во если таая есть
//        // если деталь одна то просто брать кол-во нужное
//        // возвращаем массив с данными для заказа
//        // какая полка, какой лот и прочее нужное для работы
//        // отнимаем кол-во под заказ из кол-ва в лоте к которому привязываем заказ
//        // отнимаем от общего кол-ва тоже сумму для заказа
//        // проследить частичное выполнение заказа !!!!
//        // пишем лог о перемещении детали в заказ и сохраняем нужную инфу
//
//        $stor_place = $goods->storage_shelf . '/' . $goods->storage_box;
//        /* writing warehouse log */
//        //return WareHouseLog::registerWriteOff($log_data, $user);
//        return WareHouseLog::registerWriteOff($id, $post['new-amount'], $supplier, $stor_place, $supplier, $invoice, $lot, $user);
//
//    }

//i================================================= staff code ==================

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
//            $url = '<a href="/wh?undo=true&bomid=' . $bomid . '" class="btn btn-outline-dark fs-5">Undo Delete Item</a>';
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
            _flashMessage('Password wrong! try again.', 'danger');
        }
        return $res;
    }

    /**
     *
     * @param $post
     * @param string $group_name
     * @return string
     */
    public static function getEmptyBoxForItem($post, string $group_name = ''): string
    {
        $group = _if(!empty($group_name), $group_name, 'stock');
        // Получаем текущий номер ключа из запроса
        $key = _E($post['search-for-storage-box']);

        // Получаем все записи в группе 'stock'
        $allResources = SR::getAllResourceDetailsInGroup($group);

        // Сортируем ключи по возрастанию
        ksort($allResources);

        // Инициализируем переменные
        $maxKey = 100; // Максимальное значение ключа
        $foundKey = null;

        // Проходим по записям начиная с указанного ключа
        for ($i = $key; $i <= $maxKey; $i++) {
            if (isset($allResources[$i]) && $allResources[$i] == '0') {
                $foundKey = $i;
                break;
            }
        }

        // Если ничего не найдено, начинаем поиск с начала списка до текущего ключа
        if ($foundKey === null) {
            for ($i = 1; $i < $key; $i++) {
                if (isset($allResources[$i]) && $allResources[$i] == '0') {
                    $foundKey = $i;
                    break;
                }
            }
        }

        // Если нашли подходящий ключ, возвращаем его, иначе возвращаем сообщение о том, что свободных мест нет
        return $foundKey !== null ? (string)$foundKey : "No available boxes found";
    }

    /**
     * incoming invoice level 1 check qty and set storage place
     * @param $post
     * @param $user
     * @param $order
     * @param $project
     * @return string
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function createNewReplenishmentList($post, $user, $order, $project): string
    {
        // Convert POST data to array if necessary
        $post = self::checkPostDataAndConvertToArray($post);

        $po_invoice = R::dispense(PO_AIRRVAL);
        if (!empty($order['id']) && !empty($project['id'])) {
            $po_invoice->orders_id = $order['id']; // связи таблиц
            $po_invoice->projects_id = $project['id']; // связи таблиц
        }

        $po_invoice->staging_id = $post['staging_id'] . '-' . date('Ymdhm');

        $po_invoice->owner_id = $post['owner_id']; // связи таблиц
        $po_invoice->owner_name = $post['owner']; // имя клиента

        $c_num = $po_invoice->consignment = $post['consignment']; // номер приходной накладной
        $po_invoice->for_whom = $post['for_whom']; // для какого заказа/проекта приход
        $po_invoice->date_in = $post['date_in']; // дата прихода

        // дополнительная информация
        $po_invoice->part_number = $post['makat']; // парт номер клиента указанный в накладной
        $po_invoice->manufacture_pn = $post['manufacture_pn'] ?? '';// парт номер производителя если есть
        $po_invoice->notes = $post['notes'] ?? ''; // доп информация о поставке
        $po_invoice->declared_qty = $post['declared_qty'];// заявленное кол-во в документе
        $po_invoice->actual_qty = $post['actual_qty'];// фактическое кол-во в наличии при получении товара
        $po_invoice->package_type = $post['package_type'] ?? '';// как была упакована или во что
        $po_invoice->storage_place = $post['storage_place'] ?? '';// куда фактически поставили на хранение
        $po_invoice->warehouse_type = $post['warehouse_type'];// тип помещения где поставили на хранение

        $po_invoice->defects = $post['defects'] ?? ''; // описание проблемы с товаром
        $po_invoice->user = '{"name":"' . $user['user_name'] . '" , "id":"' . $user['id'] . '" }'; // данные пользователя

        // сохраняем данные
        $id = R::store($po_invoice);

        // Объединение данных в один массив
        $logData = json_encode($po_invoice->export(), JSON_UNESCAPED_UNICODE);
        // пишем лог по складу и возвращаем ответ пользователю
        // message collector (text/ color/ auto_hide = true)
        WareHouseLog::poAirrvalAction($user, $logData, $id);
        return $c_num;
    }

    /**
     * make XMLS and save to order folder
     * @param $order_id
     * @return bool
     */
    public static function makeXLSXfileAndSave($order_id): bool
    {
        include_once 'libs/xlsxgen.php';

        $titles = SR::getAllResourcesInGroup(PO_AIRRVAL); // 12 titles
        $order = R::load(ORDERS, $order_id);
        $data = R::findAll(PO_AIRRVAL, "orders_id = ?", [$order->id]);
        $orderBOM[] = $titles;

        foreach ($data as $item) {
            $tmpArr = [];
            foreach ($titles as $key => $val) {
                if ($key == 'difference') {
                    list($_, $qty) = self::getQtyDifference($item['declared_qty'], $item['actual_qty']);
                    $tmpArr[] = $qty;
                } elseif ($key == 'user') {
                    $tmpArr[] = json_decode($item[$key], true)['name'];
                } else
                    $tmpArr[] = _empty($item[$key], 'N/A');
            }
            $orderBOM[] = $tmpArr;
        }

        $pathToSave = 'storage/orders/' . $order->order_folder . '/staging_' . $order->id . '_.xlsx';
        return XLSXGen::fromArray($orderBOM)->saveAs($pathToSave);
    }

    /**
     * RETURN DEFFERENCE OF 2 VALUES AND COLORS
     * > WARNING
     * < DANGER
     * = SUCCESS
     * @param $declared_qty
     * @param $actual_qty
     * @return array|null[]
     */
    public static function getQtyDifference($declared_qty, $actual_qty): array
    {
        // if declared is bigger than actual
        if ($declared_qty > $actual_qty) {
            return ['danger', ($declared_qty - $actual_qty)];
        }

        // if actual is bigger than declared
        if ($declared_qty < $actual_qty) {
            return ['warning', ($actual_qty - $declared_qty)];
        }

        // if qty the same
        if ($declared_qty == $actual_qty) {
            return ['success', 0];
        }
        return [null];
    }
}