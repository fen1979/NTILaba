<?php

/* ==================================================== PROJECT CONSTANTS ==================================================== */
const BASE_URL = 'https://nti.icu/'; // path to site root catalog (index.php)
const SALT_PEPPER = 'w96qH3b3ijLiqFD';

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
const Y = ['ORDER' => 0, 'PROJECT' => 1, 'N_ORDER' => 2, 'N_PROJECT' => 3, 'CLIENT' => 4, 'STOCK' => 5,
    'LOG' => 6, 'WIKI' => 7, 'E_ORDER' => 8, 'SETTINGS' => 9, 'PO' => 10];
const STORAGE_STATUS = ['smt' => 'In SMT Line', 'shelf' => 'On Shelf', 'work' => 'In Work', 'box' => 'Is in the received package'];


//I DATABASE TABLES NAMES CONSTANTS
const ORDERS = 'orders';
const PROJECTS = 'projects';
const PROJECT_STEPS = 'projectsteps';
const PROJECT_BOM = 'projectbom';
const CLIENTS = 'customers';
const USERS = 'users'; // users table
const SUPPLIERS = 'suppliers';

const WH_ITEMS = 'whitems'; // перечень товарной базы
const WH_DELIVERY = 'whdelivery'; // товарный склад, приход/рарсход
const WAREHOUSE = 'warehouse'; // динамический склад, кол-во и прочее
const WH_LOGS = 'whlogs'; // логи склада и всех его операций
const WH_RESERV = 'whreserv'; // созданный резерв запчастей для заказов
const WH_TYPES = 'whtypes'; // СПИСОК ИМЕН И ТИПОВ СКЛАДОВ
const WH_ORDERED_ITEMS = 'whordereditems'; // СПИСОК ЗАКАЗАННЫХ ЗАПЧАСТЕЙ
const PO_AIRRVAL = 'whstaging'; // СПИСОК ВРЕМЕННО ХРАНИМЫХ ЧАСТЕЙ НЕ ПРОШЕДШИХ ТОЧНУЮ ПРОВЕРКУ
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
const TASK_LIST = 'tasklists';
const TASKS = 'tasks';
const TRACK_DATA = 'aatracking';

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
