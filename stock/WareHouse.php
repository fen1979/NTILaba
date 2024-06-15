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
        $item->part_type = $post['part-type'];
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
            $res = $result;
        }
        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['manufacture-part-number']);
            $item->item_image = $result['file-path'] ?? null;
            $res = $result;
        }
        // store new item
        $item_id = R::store($item);


        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ СКЛАД
        $warehouse = R::dispense(WAREHOUSE);
        $warehouse->items_id = $item_id;
        // создаем json обьект для дальнейшего использования
        $owner_data = '{"name":"' . $post['owner'] . '", "id":"' . ($post['owner-id'] ?? '') . '"}';
        $warehouse->owner = $owner_data; // this part owner
        $owner_pn = '';
        if (!empty($post['owner-part-name'])) {
            $owner_pn = $post['owner-part-name'];
        } else {
            $res = self::GetNtiPartNumberForItem($post['owner-part-key']);
            if (!empty($res))
                $owner_pn = $res->key . ($res->number + 1);
        }
        $warehouse->owner_pn = $owner_pn;
        // полученное кол-во нового товара
        $warehouse->quantity = $post['quantity'];
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
        $invoice = R::dispense(WH_INVOICE);
        $invoice->items_id = $item_id;
        $invoice->quantity = $post['quantity']; // полученное кол-во товара в этой накладной
        $invoice->warehouses_id = $warehouse_id;
        $lot = $invoice->lot = !empty($post['part-lot']) ? $post['part-lot'] : 'N:' . date('m/Y') . ':TI~' . $item_id;
        $invoice->invoice = $post['invoice']; // this airrval invoice
        $invoice->supplier = '{"name":"' . ($post['supplier'] ?? '') . '","id":"' . ($post['supplier-id'] ?? '') . '"}'; // this airrval suplplier
        $invoice->owner = $owner_data; // this part owner
        $invoice->date_in = $item->date_in;
        $invoice_id = R::store($invoice);

        // ЗАПИСЫВАЕМ В ЛОГ ОПЕРАЦИЮ И ДАННЫЕ ТАБЛИЦ
        return WarehouseLog::registerNewArrival($item->export(), $warehouse->export(), $invoice->export(), $user->export());
    }

    /**
     * FUNCTION UPDATING ITEM DATA AND INVOICE-LOT DATA
     * @param $post
     * @param $user
     * @return string[]
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function UpdateNomenclatureItem($post, $user): array
    {
        $needToDelete = $imageExist = false;
        $imageData = null;
        if (!empty($_POST['imageData']) && strpos($_POST['imageData'], 'data:image') === 0) {
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
        $item->part_type = $post['part-type'];
        $item->footprint = $post['footprint'] ?? '';
        $item->manufacturer = $post['manufacturer'] ?? 'Not Added Yet';
        $item->manufacture_pn = $post['manufacture-part-number'];
        // нужна для обозначения нехватки товара
        $item->min_qty = !empty($post['minimun-quantity']) ? $post['minimun-quantity'] : floor((int)$post['quantity'] * 0.10);
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
            $res = $result;
            $needToDelete = true;
        }
        // если фото было где то скопированно а не выбрано физически
        if ($imageExist) {
            $result = self::convertAndSavePastedImageForItem($imageData, $post['manufacture-part-number']);
            $item->item_image = $result['file-path'] ?? null;
            $res = $result;
            $needToDelete = true;
        }
        // update item
        R::store($item);

        // проверяем если ранее было добавлено фото и удаляем старое если оно есть/было
        if (!empty($oldPhotoPath) && is_file($oldPhotoPath) && $needToDelete) {
            unlink($oldPhotoPath);
        }

        // Преобразование объекта в массив и в JSON-строку
        $itemDataAfter = json_encode($item->export(), JSON_UNESCAPED_UNICODE);
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
    public static function ReplenishInventory($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        $item = R::load(WH_ITEMS, $post['item_id']);

        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ СКЛАД
        $warehouse = R::dispense(WAREHOUSE);
        $warehouse->items_id = $item->id;
        // создаем json обьект для дальнейшего использования
        $owner_data = '{"name":"' . $post['owner'] . '", "id":"' . ($post['owner-id'] ?? '') . '"}';
        $warehouse->owner = $owner_data; // this part owner
        $owner_pn = '';
        if (!empty($post['owner-part-name'])) {
            $owner_pn = $post['owner-part-name'];
        } else {
            $res = self::GetNtiPartNumberForItem($post['owner-part-key']);
            if (!empty($res))
                $owner_pn = $res->key . ($res->number + 1);
        }
        $warehouse->owner_pn = $owner_pn;
        // полученное кол-во нового товара
        $warehouse->quantity = $post['quantity'];
        $warehouse->storage_box = $post['storage-box'];
        $warehouse->storage_shelf = $post['storage-shelf'];
        $warehouse->storage_state = $post['storage-state'];
        $mf_date = $warehouse->manufacture_date = str_replace('T', ' ', $post['manufactured-date']);
        // Создание срока годности для товара
        $datetime = new DateTime($mf_date);
        $datetime->add(new DateInterval("P{$item->shelf_life}M"));
        $warehouse->fifo = $datetime->format('Y-m-d H:i');
        $warehouse->date_in = date('Y-m-d H:i');
        $warehouse_id = R::store($warehouse);

        // СОЗДАЕМ ЗАПИСЬ В ТАБЛИЦУ ИНВОЙСОВ
        $invoice = R::dispense(WH_INVOICE);
        $invoice->items_id = $item->id;
        $invoice->quantity = $post['quantity']; // полученное кол-во товара в этой накладной
        $invoice->warehouses_id = $warehouse_id;
        $lot = $invoice->lot = !empty($post['part-lot']) ? $post['part-lot'] : 'N:' . date('m/Y') . ':TI~' . $item_id;
        $invoice->invoice = $post['invoice']; // this airrval invoice
        $invoice->supplier = '{"name":"' . ($post['supplier'] ?? '') . '","id":"' . ($post['supplier-id'] ?? '') . '"}'; // this airrval suplplier
        $invoice->owner = $owner_data; // this part owner
        $invoice->date_in = $item->date_in;
        $invoice_id = R::store($invoice);

        // ЗАПИСЫВАЕМ В ЛОГ ОПЕРАЦИЮ И ДАННЫЕ ТАБЛИЦ
        return WarehouseLog::registerNewArrival($item->export(), $warehouse->export(), $invoice->export(), $user->export());
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
        $goods = R::load(WH_ITEMS, $post['save-item']);
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
        // что то сделать для правильной работы кейса
        // тут надо добавить новое поступление если запчасть есть в БД
        // а если нет то перейти к созданию новой запчасти
        $post = self::checkPostDataAndConvertToArray($postData);
        $projectBomItem = R::load(PROJECT_BOM, $postData['item_id']);
        $project = R::load(PROJECTS, $projectBomItem->projects_id);
        $owner_pn = $projectBomItem->owner_pn;

        $search = !empty($owner_pn) ? trim($owner_pn) : null;
        if ($search != null) {
            $stock = R::findOne(WAREHOUSE, 'owner_pn = ?', [$search]);
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
     * @param $owner_id
     * @param $item_id
     * @return array|float|mixed|true
     */
    public static function GetActualQtyForItem($owner_id, $item_id)
    {
        // Проверяем, что $owner_id не пуст
        $ownerId = (!empty($owner_id)) ? trim($owner_id) : null;
        $itemId = (!empty($item_id)) ? trim($item_id) : null;

        // Проводим запрос в БД только если $search не пуст
        if ($ownerId !== null && $itemId !== null) {
            $wh = R::findOne(WAREHOUSE, 'owner LIKE ? AND items_id = ? LIMIT 1', ['%"' . $ownerId . '"%', $itemId]);

            // Проверяем, что результат запроса действительно существует
            if ($wh) {
                return $wh->quantity ?? 0.0;
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
     * SEARCH AND RETURN ONE ITEM FOR PROJECT BOM TAB
     * function for finding component by several entries for SMT line assembling mode
     * @param $part_number
     * @param $owner_pn
     * @return mixed
     */
    public static function GetOneItemFromWarehouse($part_number, $owner_pn)
    {
        // Очистка входных данных
        $part_number = !empty($part_number) ? $part_number : null;
        $owner_pn = !empty($owner_pn) ? $owner_pn : null;
        if ($part_number || $owner_pn) {
            // SQL-запрос для поиска по двум таблицам
            $sql = "SELECT wi.*, w.* FROM whitems wi LEFT JOIN warehouse w ON wi.id = w.items_id
            WHERE wi.manufacture_pn LIKE ? OR w.owner_pn LIKE ? LIMIT 1";
            // Выполнение запроса и получение результата
            $result = R::getRow($sql, ["%$part_number%", "%$owner_pn%"]);
            return !empty($result) ? $result : null;
        } else {
            return null;
        }
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
        $invoice = '%' . $data['invoice'] . '%';

        // Имена таблиц для запроса
        $wh_item = WH_ITEMS;
        $wh_invoice = WH_INVOICE;
        $warehouse = WAREHOUSE;

        // SQL-запрос для поиска полного совпадения
        $sqlFullMatch = "SELECT w.* FROM $warehouse w JOIN $wh_item wi ON wi.id = w.items_id
        JOIN $wh_invoice win ON win.id = w.invoice_id WHERE wi.part_value LIKE ? 
        AND wi.manufacture_pn LIKE ? AND w.owner_pn LIKE ? AND win.invoice LIKE ?";

        // Выполнение запроса и получение результата
        $fullMatch = R::getRow($sqlFullMatch, [$part_value, $manufacture_pn, $owner_pn, $invoice]);

        // Если найдено полное совпадение
        if ($fullMatch) {
            return [false];
        }

        // SQL-запрос для поиска частичного совпадения (без учета invoice)
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
    public static function findClosestShelfLifeItem($item): ?\RedBeanPHP\OODBBean
    {
        // Вычисление даты, от которой мы будем искать записи
        $x_day = strtotime("-{$item['shelf_life']} months");
        // Конвертация $x_day в формат даты для сравнения с полем fifo
        $x_day_date = date('Y-m-d H:i', $x_day);
        // Выполнение запроса и получение результата
        return R::findOne(WAREHOUSE, 'items_id = ? AND fifo > ? ORDER BY fifo ASC LIMIT 1', [$item['id'], $x_day_date]);
    }
}