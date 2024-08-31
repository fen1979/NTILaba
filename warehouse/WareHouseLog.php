<?php
//    Пример записи в лог:
//
//    Тип операции: Поступление
//    Дата и время: 2024-03-16 14:30
//    Идентификаторы товара: SKU12345, "Ноутбук Model X"
//    Количество: Принято 10 шт., на складе после операции 50 шт.
//    Источник: Поставщик "Техника+".
//    Пользователь: Складской менеджер Иванов И.И.
//    Документы: Накладная № 45678 от 16.03.2024.
//    Дополнительные замечания: Товар требует дополнительной проверки качества.
/* получение пользователя из сессии */

class WareHouseLog
{
    /**
     * Регистрация поступления товара на склад.
     * @param $itemData
     * @param $warehouseData
     * @param $invoiceData
     * @param $user
     * @param null $action
     * @return string[]
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function registerNewArrival($itemData, $warehouseData, $invoiceData, $user, $action = null): array
    {
        // $itemData
        //  ["id"], ["part_name"], ["part_value"], ["mounting_type"], ["footprint"], ["manufacturer"], ["manufacture_pn"],
        // ["min_qty"], ["shelf_life"], ["class_number"], ["datasheet"], ["description"], ["notes"], ["date_in"]
        // $warehouseData
        //  ["id"], ["items_id"], ["owner"]->"{"name":"NTI", "id":""}", ["owner_pn"], ["quantity"], ["storage_box"],
        // ["storage_shelf"], ["storage_state"], ["manufacture_date"], ["fifo"], ["date_in"]
        // $invoiceData
        //  ["id"], ["items_id"], ["quantity"], ["warehouses_id"], ["lot"], ["consignment"],
        // ["supplier"]=>"{"name":"Toshiba Electronic Devices & Storage","id":""}"
        // ["owner"]=> string(23) "{"name":"NTI", "id":""}", ["date_in"]

        // Должна записывать в лог:
        $log = R::dispense(WH_LOGS);
        $log->action = $action ?? 'NEW ITEM CREATION'; // тип операции
        $log->date_in = date('Y-m-d H:i');
        // item
        $log->items_id = $itemData['id']; // идентификатор товара
        $log->items_data = json_encode($itemData, JSON_UNESCAPED_UNICODE);

        // warehouse
        $log->warehouse_id = $warehouseData['id']; // идентификатор документа (warehouse).
        $log->warehouse_data = json_encode($warehouseData, JSON_UNESCAPED_UNICODE);

        // consignment
        $log->invoice_id = $invoiceData['id']; // идентификатор документа (consignment).
        $log->invoice_data = json_encode($invoiceData, JSON_UNESCAPED_UNICODE);

        $log->user_id = $user['id']; // идентификатор пользователя
        $log->user_name = $user['user_name'] ?? '';
        R::store($log);
        _flashMessage('Part was added successfully');
        return ['item_id' => $itemData['id']];
    }

    /**
     * Регистрация списания товара со склада.
     *   Должна записывать в лог:
     *   - тип операции
     *   - дату и время
     *   - идентификатор товара
     *   - количество товара
     *   - причину списания
     *   - идентификатор пользователя
     * @param $logData
     * @param mixed $user Идентификатор пользователя, проводившего операцию.
     * @return void  значение для возврата
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function registerWriteOff($logData, $user)
    {
        // Должна записывать в лог:
        $operation_type = (strpos($quantity, '-') !== false) ? 'WRITEOFF' : 'RECEIVING'; // тип операции
        $log = R::dispense(WH_LOGS);
        $log->action = $operation_type; // тип операции
        $log->date_in = date('Y-m-d H:i'); // дату и время
        $log->items_id = $item_id; // идентификатор товара
        $log->quantity = $quantity; // количество товара
        $log->user = $user['user_name']; // идентификатор пользователя
        // not nessesary fields
        $log->from = $from ?? 'storage'; // место откуда перемещается товар
        $log->to = $to ?? 'client'; // место куда перемещается товар
        $log->supplier = $supplier ?? 'NTI'; // источник (поставщик).
        $log->invoice = $invoice ?? ''; // идентификатор документа (накладная).
        $log->lot = $lot ?? ''; // идентификатор запчасти на складе LOT:num.
        R::store($log);
        _flashMessage('The write-off has been completed, the part quantity: ' . $quantity . ' pieces has been written off successfully');
    }

    /**
     * Регистрация перемещения товара внутри склада.
     * @param mixed $item_id
     * @param mixed $from
     * @param mixed $to
     * @param mixed $quantity
     * @param mixed $user
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function registerMovement($item_id, $from, $to, $quantity, $user)
    {
        $item = R::findOne(WH_ITEMS, 'id = ?', [$item_id]);
        // Должна записывать в лог:
        $log = R::dispense(WH_LOGS);
        $log->action = 'MOVEMENT'; // тип операции
        $log->date_in = date('Y-m-d H:i'); // дату и время
        $log->items_id = $item_id; // идентификатор товара
        $log->quantity = $quantity; // количество товара было перемещено
        $log->user = $user['user_name']; // идентификатор пользователя
        // not nessesary fields
        $log->from = $from ?? 'storage'; // место откуда перемещается товар
        $log->to = $to ?? 'client'; // место куда перемещается товар
        $log->supplier = $item->supplier ?? ''; // источник (поставщик).
        $log->invoice = $item->invoice ?? ''; // идентификатор документа (накладная).
        $log->lot = $item->lot ?? ''; // идентификатор запчасти на складе LOT:num.
        R::store($log);

        _flashMessage('The write-off has been completed, the part quantity: ' . $quantity . ' pieces has been written off successfully');
    }

    /**
     * @param $item_id
     * @param $logData
     * @param $user
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function updatingSomeData($item_id, $logData, $user)
    {
        // Должна записать в лог:
        $log = R::dispense(WH_LOGS);
        $log->action = 'ITEM_UPDATED'; // тип операции
        $log->date_in = date('Y-m-d H:i'); // дату и время
        $log->user_id = $user['id']; // идентификатор пользователя
        $log->user_name = $user['user_name']; // идентификатор пользователя
        $log->items_id = $item_id; // идентификатор товара
        // Преобразование объединенного массива в JSON-строку
        $log->items_data = json_encode($logData, JSON_UNESCAPED_UNICODE);

        R::store($log);
        _flashMessage('Item was changed successfully');
        /*
         * вывод массива из БД на страницу
         *
         * // Преобразование JSON-строки обратно в массив
         * $logData = json_decode($log->items_data, true);
         *
         * // Доступ к данным до изменений
         * $itemDataBefore = $logData['item_data_before'];
         * // Доступ к данным после изменений
         * $itemDataAfter = $logData['item_data_after'];
         * */
    }

    /**
     * @param $user
     * @param $logData
     * @param $item_id
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function poAirrvalAction($user, $logData, $item_id)
    {
        // Должна записать в лог:
        $log = R::dispense(WH_LOGS);
        $log->action = 'PO_ITEM_ADDED'; // тип операции
        $log->date_in = date('Y-m-d H:i'); // дату и время
        $log->user_id = $user['id']; // идентификатор пользователя
        $log->user_name = $user['user_name']; // идентификатор пользователя
        $log->items_id = $item_id; // идентификатор товара
        $log->items_data = $logData;

        R::store($log);
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Item was added successfully');
    }
}