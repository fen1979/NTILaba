<?php

class WarehouseLog
{
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

    /**
     * Регистрация поступления товара на склад.
     *
     * @param string $item_id Идентификатор товара.
     * @param int $quantity Количество поступившего товара.
     * @param mixed $supplier Источник поступления (поставщик).
     * @param mixed $invoice Идентификатор документа (накладная).
     * @param mixed $lot Идентификатор вдетали (номер).
     * @param mixed $user Идентификатор пользователя, проводившего операцию.
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function registerArrival($log_data, $user): array
    {
        // Должна записывать в лог:
        $log = R::dispense(WAREHOUSE_LOGS);
        $log->action = $log_data['operation']; // тип операции
        $log->date_in = date('Y-m-d H:i'); // дату и время
        $log->items_id = $log_data['item_id']; // идентификатор товара
        $log->items_data = $log_data['item_data']; // товар
        $log->quantity = $log_data['quantity']; // количество товара
        $log->supplier_id = $log_data['supplier_id']; // id (поставщик).
        $log->supplier = $log_data['supplier']; // источник (поставщик).
        $log->invoice = $log_data['invoice']; // идентификатор документа (накладная).
        $log->lot_id = $log_data['lot_id']; // идентификатор запчасти на складе LOT:id.
        $log->lot_data = $log_data['lot_data']; // accessori table line full stack
        $log->lot = $log_data['lot']; // идентификатор запчасти на складе LOT:num.
        $log->owner = $log_data['owner']; // customer ppart owner
        $log->owner_pn = $log_data['owner_pn']; // part name from customer
        $log->user = $user['user_name']; // идентификатор пользователя
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