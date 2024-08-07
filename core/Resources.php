<?php
class_alias('Resources', 'SR');

/* ==================================================== PROJECT CONSTANTS ==================================================== */
const BASE_URL = 'https://nti.icu/'; // path to site root catalog (index.php)

$lang = substr($_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 2);
define("LANG", $lang);

/*
 *  USER ROLE CONSTANTS
 * */
const ROLE_SUPERVISOR = 'supervisor'; // supervisor
const ROLE_SUPERADMIN = 'super_admin'; // super admin
const ROLE_ADMIN = 'admin'; // creator/editor
const ROLE_WORKER = 'worker'; // reader only
const ROLE = [ROLE_SUPERVISOR => 'Creator', ROLE_SUPERADMIN => 'Super Admin (All privileges)',
    ROLE_ADMIN => 'Admin (Creator/Editor)', ROLE_WORKER => 'Worker (Reader only)'];

/*
 *  CONSTANTS FOR PROJECT USE
 * */
const ARCHIVATED = 0;
const PROJECTS_FOLDER = 'storage/projects/';
const STOCK_FOLDER = 'storage/warehouse/';
const ORDERS_FOLDER = 'storage/orders/';
const TOOLS_FOLDER = 'storage/tools/';
const TEMP_FOLDER = 'public/temporary/';
const PDF_FOLDER = 'pdfOut/';
const WIKI_FOLDER = 'storage/wiki/';
/** default user registration settings for preview order table, other settings user can set in managment area */
const DEFAULT_SETTINGS = ['table_name' => 'orders', 'setup' => '["id", "status", "project_name", "order_amount", "customer_name", "order_progress"]'];
const SHARE_LINK_ROUTE = BASE_URL . 'shared-project?shared='; // the path to share link

/*
 *  ACTIVE LINKS FOR NAV BUTTONS
 * */
const Y = ['ORDER' => 0, 'PROJECT' => 1, 'N_ORDER' => 2, 'N_PROJECT' => 3, 'CLIENT' => 4, 'STOCK' => 5, 'LOG' => 6, 'WIKI' => 7, 'E_ORDER' => 8, 'SETTINGS'=>9];
const STORAGE_STATUS = ['smt' => 'In SMT Line', 'shelf' => 'On Shelf', 'work' => 'In Work'];


//I DATABASE TABLES NAMES CONSTANTS
const ORDERS = 'orders';
const PROJECTS = 'projects';
const PROJECT_STEPS = 'projectsteps';
const PROJECT_BOM = 'projectbom';
const CLIENTS = 'customers';
const USERS = 'users'; // users table
const SUPPLIERS = 'suppliers';

const WH_ITEMS = 'whitems'; // перечень товарной базы
const WH_INVOICE = 'whinvoice'; // товарный склад, приход/рарсход
const WAREHOUSE = 'warehouse'; // динамический склад, кол-во и прочее
const WH_LOGS = 'whlogs'; // логи склада и всех его операций
const WH_RESERV = 'whreserv'; // созданный резерв запчастей для заказов
const WH_TYPES = 'whtypes'; // СПИСОК ИМЕН И ТИПОВ СКЛАДОВ
const WH_ORDERED_ITEMS = 'whordereditems'; // СПИСОК ЗАКАЗАННЫХ ЗАПЧАСТЕЙ
const ASSY_PROGRESS = 'assyprogress'; // состояние работы над заказом
const TOOLS = 'tools'; // таблица инструмента компании
const ROUTE_ACTION = 'routeaction'; // рут карта список
const HISTORY = 'history'; // история изменений в сборочных шагах
const ORDER_CHATS = 'orderchats'; // сообщения внутри заказа
//const USER_CHATS = 'userchats'; // TODO user global chats
const HASHES = 'hashdump'; // временная таблица изменений в БД и коллекция уникальных генераций
const SETTINGS = 'settings'; // таблица настроек пользователя
const SMT_LINE = 'smtline';
const LOGS = 'logs'; // логи сайта и всех его операций
const UNDO_TABLE = 'deletedrecords'; // таблица временно хранящая данные удаленных записей склада

/**
 * ORDER - 0
 *
 * ORDER_BOM - 1 ? fix this ?
 *
 * ORDER_CHAT - 2
 *
 * PROJECT - 3
 *
 * PROJECT_STEP - 4
 *
 * PROJECT_BOM - 5
 *
 * WAREHOUSE - 6
 *
 * SETTINGS - 7
 *
 * ROUTE_ACTION - 8
 *
 * TOOLS - 9
 *
 * COLUMNS - 10
 *
 * USER - 11
 *
 * ADMINKA - 12
 *
 * CUSTOMERS - 13
 *
 * fixme расширить и продумат как перенести в БД
 */
const OBJECT_TYPE = ['ORDER', 'ORDER_BOM', 'ORDER_CHAT',
    'PROJECT', 'PROJECT_STEP', 'PROJECT_BOM',
    'WAREHOUSE', 'SETTINGS', 'ROUTE_ACTION',
    'TOOLS', 'COLUMNS', 'USER', 'ADMINKA', 'CUSTOMERS'];

/**
 * fixme перенести в БД сделать пополняемой
 * СПИСОК ВАРИАНТОВ ВИДОВ УСТАНОВКИ ДЕТАЛЕЙ
 */
const MOUNTING_TYPE = ["SMT", "TH", "CM", "PM", "SOLDER", "CRIMP", "LM", "OTHER"];

/**
 * fixme перенести в БД и сделать расширяемым обьектом
 * СПИСОК НАЗВАНИЙ ПАРТ НОМЕРОВ ДЛЯ NTI
 */
const NTI_PN = ['NCAP' => 'Capacitor', 'NRES' => 'Resistor', 'NDIO' => 'Diode', 'NIC' => 'Micro Chip', 'NTR' => 'Transistor',
    'NCR' => 'Oscilator', 'NFU' => 'Fuse', 'NFB' => 'Ferrite bead', 'NCON' => 'Connector', 'NIND' => 'Inductor', 'NPIN' => 'Pins',
    'NW' => 'Wires', 'NTUBE' => 'Shrink Tube', 'custom' => 'Custom'];

/* ==================================================== PROJECT RESOURCES ==================================================== */

class Resources
{
    // Data Base table name
    private const RESOURCES = 'resources';
    private static ?string $groupName = null;

    /**
     * Set the group name
     *
     * SR::setGroupName('group1');
     * @param string $group
     * @return void
     */
    public static function setGroupName(string $group)
    {
        self::$groupName = $group;
    }

    /**
     * Create table first init process
     *
     * SR::createTable(); // создать таблицу при первом запуске
     * @param $table
     * @return void
     */
    public static function createTable()
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
     * @param int $status
     * @return bool
     */
    public static function addResource($group, $key, $value, $detail = '0'): bool
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
                $_SESSION['info'] = ['info' => 'Resource added successfuly', 'color' => 'success'];
                return true;
            }
        } catch (Exception $exception) {
            $_SESSION['info'] = ['info' => $exception->getMessage(), 'color' => 'danger'];
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
                $data->value = $value;
                $data->detail = $detail;
                R::store($data);
                $_SESSION['info'] = ['info' => 'Resource updated successfuly', 'color' => 'success'];
                return true;
            }
        } catch (Exception $exception) {
            $_SESSION['info'] = ['info' => $exception->getMessage(), 'color' => 'danger'];
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
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function updateResourceDetail($group, $key, $detail)
    {
        $data = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        if ($data) {
            $data->detail = $detail;
            R::store($data);
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
    public static function deleteResource($group, $key)
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
    public static function deleteAllResourcesInGroup($group)
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
     * @return string
     */
    public static function getResourceValue($group, $key): string
    {
        $group = ($key == 'date_in') ? 'global' : $group;
        //$groupName = self::$groupName ?: $group;
        $o = R::findOne(self::RESOURCES, 'group_name = ? AND key_name = ?', [$group, $key]);
        if ($o)
            return $o->value;
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
     * Get all records in groups
     *
     * SR::getAllResourcesInGroup('group1'); // example
     *
     * поддерживает установку группы через
     * setGroupName(string $group)
     * @param null $group
     * @param bool $object
     * @param bool $ordered
     * @return array
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
}