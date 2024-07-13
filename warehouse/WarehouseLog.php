<?php
class WarehouseLog
{
    /**
     * Регистрация поступления товара на склад.
     * @param $itemData
     * @param $warehouseData
     * @param $invoiceData
     * @param $user
     * @return string[]
     */
    public static function registerNewArrival($itemData, $warehouseData, $invoiceData, $user): array
    {
        // $itemData
        //  ["id"], ["part_name"], ["part_value"], ["part_type"], ["footprint"], ["manufacturer"], ["manufacture_pn"],
        // ["min_qty"], ["shelf_life"], ["class_number"], ["datasheet"], ["description"], ["notes"], ["date_in"]
        // $warehouseData
        //  ["id"], ["items_id"], ["owner"]->"{"name":"NTI", "id":""}", ["owner_pn"], ["quantity"], ["storage_box"],
        // ["storage_shelf"], ["storage_state"], ["manufacture_date"], ["fifo"], ["date_in"]
        // $invoiceData
        //  ["id"], ["items_id"], ["quantity"], ["warehouses_id"], ["lot"], ["invoice"],
        // ["supplier"]=>"{"name":"Toshiba Electronic Devices & Storage","id":""}"
        // ["owner"]=> string(23) "{"name":"NTI", "id":""}", ["date_in"]

        // Должна записывать в лог:
        $log = R::dispense(WAREHOUSE_LOGS);
        $log->action = 'NEW ITEM CREATION'; // тип операции
        $log->date_in = date('Y-m-d H:i');
        // item
        $log->items_id = $itemData['id']; // идентификатор товара
        $log->items_data = json_encode($itemData, JSON_UNESCAPED_UNICODE);

        // warehouse
        $log->warehouse_id = $warehouseData['id']; // идентификатор документа (warehouse).
        $log->warehouse_data = json_encode($warehouseData, JSON_UNESCAPED_UNICODE);

        // invoice
        $log->invoice_id = $invoiceData['id']; // идентификатор документа (invoice).
        $log->invoice_data = json_encode($invoiceData['invoice'], JSON_UNESCAPED_UNICODE);

        $log->user_id = $user['id']; // идентификатор пользователя
        $log->user = $user['user_name'];
        R::store($log);
        return ['info' => 'Part was added successfully', 'color' => 'success'];
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
     * @param mixed $item_id Идентификатор товара.
     * @param mixed $quantity Количество списываемого товара.
     * @param mixed $from откуда пришел товар
     * @param mixed $to куда положили
     * @param mixed $supplier кто поставщик
     * @param mixed $invoice накладная на прибытие/списание
     * @param mixed $lot лот товара по складу
     * @param mixed $user Идентификатор пользователя, проводившего операцию.
     * @return string[]  значение для возврата
     * @throws \\RedBeanPHP\RedException\SQL  ошибка для БД
     */
    public static function registerWriteOff($log_data, $user): array
    {
        // Должна записывать в лог:
        $operation_type = (strpos($quantity, '-') !== false) ? 'WRITEOFF' : 'RECEIVING'; // тип операции
        $log = R::dispense(WAREHOUSE_LOGS);
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
        return ['info' => 'The write-off has been completed, the part quantity: ' . $quantity . ' pieces has been written off successfully', 'color' => 'success'];
    }

    /**
     * Регистрация перемещения товара внутри склада.
     * @param mixed $item_id
     * @param mixed $from
     * @param mixed $to
     * @param mixed $quantity
     * @param mixed $user
     * @return string[]
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function registerMovement($item_id, $from, $to, $quantity, $user): array
    {
        $item = R::findOne(WH_ITEMS, 'id = ?', [$item_id]);
        // Должна записывать в лог:
        $log = R::dispense(WAREHOUSE_LOGS);
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
        return ['info' => 'The write-off has been completed, the part quantity: ' . $quantity . ' pieces has been written off successfully', 'color' => 'success'];

    }

    public static function updatingSomeData($log_data, $user): array
    {
        // тут будем регистрировать и логровать разные операции
        // обновление данных товара
        //
        //$log_data['item_data_before'];
        //$log_data['item_data_after'];
        return [null];
    }
}