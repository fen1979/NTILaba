<?php

/*  ФУНКЦИИ ОТКАТА ПРИ УДАЛЕНИИ ОБЫЧНЫХ ТАБЛИЦ В БАЗЕ ДАННЫХ
 * ОТКАТ РАБОТАЕТ ТОЛЬКО ДЛЯ ПОЛЕДНЕГО СОВЕРШЕННОГО ДЕЙСТВИЯ
 * переделать в глобальное что то позже для всех видов таблиц
 * и добавить возможность востанавливать данные в большом обьеме после удаления
 * */

class Undo
{
    /**
     * FUNCTION TEMPORARY STORE DELETED RECORD
     * @param $tableName
     * @param $recordId
     * @return int  Temp Record ID
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function StoreDeletedRecord($tableName, $recordId): int
    {
        $record = R::load($tableName, $recordId);

        if ($record->id) {
            // Сериализуем данные записи
            $serializedData = serialize($record->export());

            // Создаем запись в таблице удаленных записей
            $deletedRecord = R::dispense(UNDO_TABLE);
            $deletedRecord->table_name = $tableName;
            $deletedRecord->record_id = $recordId;
            $deletedRecord->data = $serializedData;
            return R::store($deletedRecord);
        }
        return 0;
    }

    /**
     * FUNCTION RESTORE DELETED RECORD
     * @param $deletedRecordId
     * @return void
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function RestoreDeletedRecord($deletedRecordId): void
    {
        $deletedRecord = R::load(UNDO_TABLE, $deletedRecordId);

        if ($deletedRecord->id) {
            // Десериализуем данные
            $data = unserialize($deletedRecord->data);
            // Восстанавливаем запись в исходной таблице
            $restoredRecord = R::dispense($deletedRecord->table_name);
            foreach ($data as $key => $value) {
                if ($key != 'id')
                    $restoredRecord->$key = $value;
            }

            // Сохраняем восстановленную запись
            R::store($restoredRecord);

            // Удаляем запись из временной таблицы
            R::trash($deletedRecord);
            //отчищаем всю таблицу целиком
            //R::wipe(UNDO_TABLE);
        }
    }
}