<?php /** @noinspection PhpUnused */

class ResourceController
{
    // Data Base table name
    private const string RESOURCES = 'resources';
    private static ?string $groupName = null;

    /**
     * Set the group name
     *
     * SR::setGroupName('group1');
     * @param string $group
     * @return void
     */
    public static function setGroupName(string $group): void
    {
        self::$groupName = $group;
    }

    /**
     * Create table first init process
     *
     * SR::createTable(); // создать таблицу при первом запуске
     * @return void
     */
    public static function createTable(): void
    {
        R::exec('CREATE TABLE IF NOT EXISTS ' . self::RESOURCES . ' (id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(255) NOT NULL, key_name VARCHAR(255) NOT NULL, value TEXT NOT NULL,
        UNIQUE KEY unique_key (group_name, key_name))');
    }

    /**
     * Add resource to DB
     *
     * SR::addResource('group1', 'key1', 'value1', 'status1'); // добавляем новую запись сттатус указан как 0 по умолчанию
     * @param $group
     * @param $key
     * @param $value
     * @param string $detail
     * @return bool
     */
    public static function addResource($group, $key, $value, string $detail = '0'): bool
    {
        try {
            // Проверяем, существует ли запись
            $existingData = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
            if (!$existingData) {
                // Если записи нет, создаем новую
                $data = R::dispense(self::RESOURCES);
                $data->group_name = $group;
                $data->key_name = $key;
                $data->value = $value ?? '';
                $data->detail = $detail;
                R::store($data);
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('Resource added successfuly');
                return true;
            }
        } catch (Exception $exception) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage($exception->getMessage(), 'danger');
        }
        return false;
    }

    /**
     * Update resource value and detail
     *
     * SR::updateResource('group1', 'key1', 'new_value', 'new_detail); // изменение записи: детали и значение
     * @param $group
     * @param $key
     * @param $value
     * @param $detail
     * @return bool
     */
    public static function updateResource($group, $key, $value, $detail): bool
    {
        try {
            $data = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
            if ($data) {
                $data->key_name = $key;
                $data->value = $value;
                $data->detail = $detail;
                R::store($data);
                _flashMessage('Resource updated successfuly');
                return true;
            }
        } catch (Exception $exception) {
            _flashMessage($exception->getMessage(), 'danger');
        }
        return false;
    }

    /**
     * update resource detail only
     *
     * SR::updateResourceDetail('group1', 'key1', 'new_detail'); // обновление деталей
     * @param $group
     * @param $key
     * @param $detail
     * @param bool $check
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function updateResourceDetail($group, $key, $detail, bool $check = false): void
    {
        $data = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        if ($data && !$check) {
            $data->detail = $detail;
            R::store($data);
        } else {
            if ($data->detail == '0') {
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('THIS BOX IS NOT EMPTY!', 'danger');
            }

            if ($data->detail == '1') {
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('THIS BOX IS NOT EMPTY!', 'danger');
            }
        }
    }

    /**
     * Delete record by key
     *
     * SR::deleteResource('group1', 'key1'); // удаление записи по связке группа-ключь
     * @param $group
     * @param $key
     * @return void
     */
    public static function deleteResource($group, $key): void
    {
        $data = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        if ($data) {
            R::trash($data);
        }
    }

    /**
     * Delete all records in group
     *
     * SR::deleteAllResourcesInGroup('group1'); // удаление всех записей по групповому признаку
     * @param $group
     * @return void
     */
    public static function deleteAllResourcesInGroup($group): void
    {
        $data = R::find(self::RESOURCES, 'group_name = ?', [$group]);
        R::trashAll($data);
    }

    /********************************************* GETTERS ***************************************************/
    /**
     * Get record by group & key
     *
     * SR::getResource('group1', 'key1'); // вывод записи по связке группа-ключь
     * @param $group
     * @param $key
     * @return \RedBeanPHP\OODBBean|NULL
     */
    public static function getResource($group, $key): ?\RedBeanPHP\OODBBean
    {
        return R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
    }

    /**
     * Get value by key
     *
     * SR::getResourceValue('group1', 'key1');
     * @param $group
     * @param $key
     * @param bool $detail
     * @return string
     */
    public static function getResourceValue($group, $key, bool $detail = false): mixed
    {
        $group = ($key == 'date_in') ? 'global' : $group;
        //$groupName = self::$groupName ?: $group;
        $o = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        if ($o)
            return !$detail ? $o->value : [$o->value, $o->detail];
        else
            return '';
    }

    /**
     * Get status by key
     *
     * SR::getResourceDetail('group1', 'key1');
     * @param $group
     * @param $key
     * @return string
     */
    public static function getResourceDetail($group, $key): string
    {
        $o = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        return $o->detail;
    }


    /**
     * Get all details by group name
     *
     * SR::getAllResourceDetailsInGroup('group1');
     * @param $group
     * @return array
     */
    public static function getAllResourceDetailsInGroup($group): array
    {
        $result = [];
        $o = self::getAllResourcesInGroup($group, true);
        foreach ($o as $item) {
            if ($item['value'] == 'in_use') {
                $result[$item['key_name']] = $item['detail'];
            }
        }
        return $result;
    }

    /**
     * Get all records in groups
     *
     * SR::getAllResourcesInGroup('group1'); // example
     *
     * поддерживает установку группы через
     * setGroupName(string $group)
     * @param null $group
     * @param bool $object
     * @param bool $ordered
     * @return array|null
     */
    public static function getAllResourcesInGroup($group = null, bool $object = false, bool $ordered = false): ?array
    {
        $groupName = self::$groupName ?: $group;
        if ($object) {
            $query = ($ordered) ? 'ORDER BY id' : '';
            return R::find(self::RESOURCES, 'group_name = ? ' . $query, [$groupName]);
        } else {
            $records = R::find(self::RESOURCES, 'group_name = ?', [$groupName]);
            $result = [];
            if ($records) {
                foreach ($records as $record) {
                    $result[$record['key_name']] = $record['value'];
                }

                return $result;
            } else {
                return null;
            }
        }
    }

    /**
     * Get all records
     *
     * SR::getAllResources(); // вывод вссех записей из БД
     *
     * @return array
     */
    public static function getAllResources(): array
    {
        return R::findAll(self::RESOURCES, 'ORDER BY group_name');
    }

    /**
     * Переопределяет все значения в БД в поле ДЕТАЛИ для конкретной группы
     * если передано значение то переопределение всех записей в БД будет приведено к данному значению
     * если значение не передано то будет установлено значение по умолчанию "0"
     * @param $group_name
     * @param string $default
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function clearAllDetailsInGroup($group_name, string $default = ''): void
    {
        $res = self::getAllResourcesInGroup($group_name);
        foreach ($res as $re) {
            $re['detail'] = _empty($default, '0');
            R::store($re);
        }
    }

    /**
     * Переопределяет все значения в БД в поле ЗНАЧЕНИЕ для конкретной группы
     * если передано значение то переопределение всех записей в БД будет приведено к данному значению
     * если значение не передано то будет установлено значение по умолчанию "null"
     * @param $group_name
     * @param string $default
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function clearAllValuesInGroup($group_name, string $default = ''): void
    {
        $res = self::getAllResourcesInGroup($group_name);
        foreach ($res as $re) {
            $re['value'] = _empty($default, '0');
            R::store($re);
        }
    }
}