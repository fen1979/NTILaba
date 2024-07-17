<?php

/* project constants on this page under this class */

class L
{
    /* =================== PROTECTED METHODS ==================== */
    private static function getStatusColor($status): string
    {
        /* clors BG for statuses */
        $statusColors = [
            'success' => ['st-1'],
            'warning' => ['st-0', 'st-2', 'st-4', 'st-5', 'st-7'],
            'danger' => ['st-6', 'st-3'],
            'info' => ['st-8', 'st-111'],
            'secondary' => ['st-8', 'st-222'],
            'dark' => []
        ];
        foreach ($statusColors as $color => $statuses) {
            if (in_array($status, $statuses)) {
                return $color;
            }
        }
        return ''; // или значение по умолчанию, если статус не найден
    }

    /* ===================== STATUS FOR ORDERS ================== */
    /**
     * RETURN STATUS FOR ODRDERS
     * $key = null return assoc array of statuses
     * $color = 1 return status color
     * @param $key
     * @param $color
     * @return mixed|string
     */
    public static function STATUS($key = null, $color = null)
    {
        /* TODO  придумать ключ для поиска по статусу заказа */
        $orderStatus['-1'] = 'All';
        $orderStatus['st-0'] = 'Waiting for BOM inspection';
        $orderStatus['st-1'] = 'Approved for work';
        $orderStatus['st-2'] = 'Waiting for Customer approval';
        $orderStatus['st-3'] = 'Waiting for spare parts';
        $orderStatus['st-4'] = 'Waiting for inspection';
        $orderStatus['st-5'] = 'Waiting for Step Validation';
        $orderStatus['st-6'] = 'Project on Pause';
        $orderStatus['st-7'] = 'Waiting delivery';
        $orderStatus['st-8'] = 'Order in work';
        $orderStatus['st-9'] = 'Requires build process creation';
        //$orderStatus[''] = '';
        $orderStatus['st-111'] = 'Completed';
        $orderStatus['st-222'] = 'Archivated';
        if (!$color)
            return ($key === null) ? $orderStatus : $orderStatus[$key];
        else
            return self::getStatusColor($key);
    }

    /* ==================== TITLES FOR PAGES ==================== */
    /**
     * TITLES FOR PAGES
     * @param $key
     * @return string
     */
    public static function TITLES($key): string
    {
        $title['home'] = 'Home';
        //$title['hi'] = 'Hi';

        $title['login'] = 'Authetification';

        $title['order'] = 'Orders';
        $title['new_order'] = 'Create Order';
        $title['edit_order'] = 'Edit Order';
        $title['order_bom'] = 'Fill BOM';
        $title['order_details'] = 'Order Info';

        $title['project'] = 'Projects';
        $title['new_project'] = 'Create Project';
        $title['edit_project'] = 'Edit Project';
        $title['view_project'] = 'View Project';
        $title['add_step'] = 'Add Step';
        $title['edit_step'] = 'Edit Step';
        $title['project_part_list'] = 'Part List';
        $title['shared'] = 'Shared Project';

        $title['wh'] = 'Warehouse';
        $title['import_csv'] = 'Import File';
        $title['wh_log'] = 'Warehouse Log';
        $title['view_item'] = 'Item Information';
        $title['edit_item'] = 'Edit Item Information';
        $title['arrivals'] = 'Add new Item';

        $title['admin-panel'] = 'Management';
        $title['customers'] = 'Clients';
        $title['logs'] = 'Log';
        $title['wiki'] = 'Resources';
        $title['docs'] = 'Docs';
        $title['priority'] = 'Priority';
        //$title[''] = 'No Title';

        return $title[$key] ?? 'No Title Yet';
    }

    /* ==================== TABLES COLUMN NAMES ================= */
    /**
     * retun tittels for tables columns
     * @param $table / table name
     * @param $column / column name
     * @return mixed
     */
    public static function TABLES($table, $column)
    {
        $tableData['global']['date_in'] = 'Creation Date';

        /* TABLE USERS */
        $tableData['users']['id'] = 'User №';
        $tableData['users'][''] = '';

        /* TABLE ORDERS */
        $tableData['orders']['id'] = 'Order №';
        $tableData['orders']['project_id'] = 'Project №';
        $tableData['orders']['project_name'] = 'Project';
        $tableData['orders']['project_revision'] = 'Revision';
        $tableData['orders']['customers_id'] = 'Customer ID';
        $tableData['orders']['customer_name'] = 'Customer';
        $tableData['orders']['client_priority'] = 'Priority';
        $tableData['orders']['purchase_order'] = 'Purchase Order';
        $tableData['orders']['order_amount'] = 'QTY';
        $tableData['orders']['first_qty'] = 'Head QTY';
        $tableData['orders']['extra'] = 'Description';
        $tableData['orders']['status'] = 'Status';
        $tableData['orders']['workers'] = 'Workers';
        $tableData['orders']['order_progress'] = 'Order Progress';
        $tableData['orders']['forwarded_to'] = 'Forwarded to';
        $tableData['orders']['prioritet'] = 'Prioritet';
        $tableData['orders']['storage_shelf'] = 'Storage Shelf';
        $tableData['orders']['storage_box'] = 'Storage Box';
        $tableData['orders']['pre_assy'] = 'Partial Assembly Allowed';

        /* Orders folders path */
        $tableData['orders']['order_folder'] = 'Order Folder';
        $tableData['orders']['projects_id'] = 'Project ID';

        /* TABLE ASSEMBLY PROGRESS */
        $tableData['assyprogress']['date_start'] = 'Date Start';
        $tableData['assyprogress']['date_end'] = 'Date End';
        $tableData['assyprogress']['stepcount'] = 'Steps';
        $tableData['assyprogress']['laststep'] = 'Last Step';
        $tableData['assyprogress']['validtime'] = 'Waiting Time';

        /* TABLE PROJECTS */
        $tableData['projects']['id'] = 'Project №';
        $tableData['projects']['customername'] = 'Customer Name';
        $tableData['projects']['customerid'] = 'Customer ID';
        $tableData['projects']['projectname'] = 'Project Name';
        $tableData['projects']['revision'] = 'Revision';
        $tableData['projects']['priority'] = 'Priority';
        $tableData['projects']['headpay'] = 'Head Pay';
        $tableData['projects']['executor'] = 'Executor Name';
        $tableData['projects']['creator'] = 'Creator Name';
        $tableData['projects']['extra'] = 'Description';
        $tableData['projects']['tools'] = 'Tools';
        $tableData['projects']['sharelink'] = 'Link to share';
        /* Projects folder paths */
        $tableData['projects']['projectdir'] = 'Project Folder';
        $tableData['projects']['historydir'] = 'History Folder';
        $tableData['projects']['projectdocs'] = 'Docs Folder';

        /* TABLE PROJECT STEPS */
        //$tableData['projectsteps']['id'] = 'Step №';
        $tableData['projectsteps']['projects_id'] = 'Project Id';
        $tableData['projectsteps']['step'] = 'Step Number';
        $tableData['projectsteps']['description'] = 'Description';
        $tableData['projectsteps']['revision'] = 'Revision';
        $tableData['projectsteps']['validation'] = 'Validation';
        $tableData['projectsteps']['routid'] = 'Rout Act ID';
        $tableData['projectsteps']['routaction'] = 'Rout Action';
        $tableData['projectsteps']['tools'] = 'Step Tool';

        /* TABLE PROJECT/ORDER BOM */
        //$tableData['projectbom']['id'] = 'Part №';
        $tableData['projectbom']['sku'] = 'SKU';  // sku makat
        $tableData['projectbom']['part_name'] = 'Part Name';  // part name
        $tableData['projectbom']['part_value'] = 'Value';  // part value
        $tableData['projectbom']['mounting_type'] = 'Mounting Type';  // part type
        $tableData['projectbom']['footprint'] = 'Footprint';  // footprint
        $tableData['projectbom']['manufacturer'] = 'Manufacturer';  // manufacturer
        $tableData['projectbom']['manufacture_pn'] = 'Manufacture P/N';  // manufacturer p/n
        $tableData['projectbom']['owner_pn'] = 'Owner P/N';  // customer p/n && Our p/n
        $tableData['projectbom']['description'] = 'Description';  // description
        $tableData['projectbom']['notes'] = 'Note';  // note
        $tableData['projectbom']['amount'] = 'Required QTY [pcs, m]';  // amount for one peace


        /* TABLE CUSTOMERS */
        //$tableData['customers']['id'] = 'Customer №';
        $tableData['customers']['name'] = 'Customer Name';
        $tableData['customers']['priority'] = 'Priority';
        $tableData['customers']['head_pay'] = 'Head Pay';
        $tableData['customers']['address'] = 'Address';
        $tableData['customers']['phone'] = 'Phone';
        $tableData['customers']['contact'] = 'Contact';
        $tableData['customers']['information'] = 'Information';
        $tableData['customers']['extra_phone'] = 'More Phones';
        $tableData['customers']['extra_address'] = 'More Addresses';


        /* TABLE PROJECT STEP EDITING HISTORY */
        //$tableData['history']['id'] = 'History №';
        $tableData['history']['projectid'] = 'Project Id';
        $tableData['history']['steps_id'] = 'Identificator';
        $tableData['history']['changedate'] = 'Date';
        $tableData['history']['username'] = 'Who Changed';
        $tableData['history']['validation'] = 'Validation';
        $tableData['history']['step'] = 'Step Number';
        $tableData['history']['revision'] = 'Revision';
        $tableData['history']['description'] = 'Description';
        $tableData['history']['routeid'] = 'Route Id';
        $tableData['history']['routeaction'] = 'Route Act';
        $tableData['history']['toolid'] = 'Tool to use';
        $tableData['history']['image'] = 'Image';
        $tableData['history']['video'] = 'Video';

        /* TABLE CHAT LOG FOR ORDERS */
        $tableData['chats']['id'] = 'Chat №';

        /* TABLE TOOLS */
        $tableData['tools']['id'] = 'Tool №';
        $tableData['tools']['toolname'] = 'Tool Name';
        $tableData['tools']['image'] = 'Preview';
        $tableData['tools']['specifications'] = 'Specifications';
        $tableData['tools']['esd'] = 'ESD';
        $tableData['tools']['exp_date'] = 'Expaire Date';

        /* TABLE ROUTE ACTIONS FOR STEPS */
        $tableData['routactions']['id'] = 'SKU';
        $tableData['routactions']['routactions'] = 'Stage';
        $tableData['routactions']['actions'] = 'Action';
        $tableData['routactions']['actions_eng'] = 'Action English';
        $tableData['routactions']['specifications'] = 'Specifications';

        /* TABLE SETTINGS */
        //$tableData['settings']['id'] = 'Setting №';

        /* TABLES OF WAREHOUSE */
        $tableData['whitems']['part_name'] = 'Part Name';
        $tableData['whitems']['part_value'] = 'Part Value';
        $tableData['whitems']['mounting_type'] = 'Mounting Type';
        $tableData['whitems']['footprint'] = 'Footprint';
        $tableData['whitems']['manufacture_pn'] = 'Manufacture P/N';
        $tableData['whitems']['manufacturer'] = 'Manufacturer';
        $tableData['whitems']['datasheet'] = 'Datasheet';
        $tableData['whitems']['notes'] = 'Note';
        $tableData['whitems']['description'] = 'Description';
        $tableData['whitems']['min_qty'] = 'Min. Amount';
        $tableData['whitems']['class_number'] = 'Class';
        $tableData['whitems']['shelf_life'] = 'Shelf Life';
        $tableData['whitems']['item_image'] = 'Image';

        // warehouse sub table
        $tableData['whitems']['quantity'] = 'Amount';
        $tableData['whitems']['owner_pn'] = 'Owner P/N';
        $tableData['whitems']['owner'] = 'Part Owner';
        $tableData['whitems']['storage_shelf'] = 'Storage Shelf';
        $tableData['whitems']['storage_box'] = 'Storage Box';

        // invoice sub table
        $tableData['whitems']['invoice'] = 'Invoice';
        $tableData['whitems']['lot'] = 'Item Lot';
        $tableData['whitems']['quantity'] = 'QTY [pcs, m]';
        $tableData['whitems']['supplier'] = 'Supplier';
        $tableData['whitems']['manufacture_date'] = 'Mfr. Date';
        $tableData['whitems']['date_in'] = 'Date In';


        /* TABLE GLOBAL LOGS */
        $tableData['logs']['id'] = 'Log №';

        if ($column == null) {
            /* return array for some table */
            $tableData[$table]['date_in'] = $tableData['global']['date_in'];
            return $tableData[$table];
        }

        /* return string value from one cell */
        $table = ($column == 'date_in') ? 'global' : $table;
        return $tableData[$table][$column] ?? '';
    }
}

/* ==================================================== PROJECT CONSTANTS ==================================================== */
const BASE_URL = 'https://nti.icu/'; // path to site root catalog (index.php)

$lang = substr($_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 2);
define("LANG", $lang);

/*
 *  ACTIVE LINKS FOR NAV BUTTONS
 * */
const Y = ['ORDER' => 0, 'PROJECT' => 1, 'N_ORDER' => 2, 'N_PROJECT' => 3, 'CLIENT' => 4, 'STOCK' => 5, 'LOG' => 6, 'WIKI' => 7, 'E_ORDER' => 8];
const STORAGE_STATUS = ['smt' => 'In SMT Line', 'shelf' => 'On Shelf', 'work' => 'In Work'];
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

//I DATABASE TABLES NAMES CONSTANTS
const ORDERS = 'orders';
const PROJECTS = 'projects';
const PROJECT_STEPS = 'projectsteps';
const PROJECT_BOM = 'projectbom';
const CLIENTS = 'customers';
const USERS = 'users'; // users tabla
const SUPPLIERS = 'suppliers';

const WH_ITEMS = 'whitems'; // перечень товарной базы
const WH_INVOICE = 'whinvoice'; // товарный склад, приход/рарсход
const WAREHOUSE = 'warehouse'; // динамический склад, кол-во и прочее
const WAREHOUSE_LOGS = 'whlogs'; // логи склада
const WH_RESERV = 'whreserv'; // созданный резерв запчастей для заказов
const WH_SLOTS = 'whslots'; // места для хранения товара
const WH_TYPES = 'whtypes'; // СПИСОК ИМЕН И ТИПОВ СКЛАДОВ
const ASSY_PROGRESS = 'assyprogress';
const TOOLS = 'tools'; // tools table
const ROUTE_ACTION = 'routeaction';
const HISTORY = 'history';
const ORDER_CHATS = 'orderchats';
//const USER_CHATS = 'userchats'; // TODO user global chats
const HASHES = 'hashdump';
const SETTINGS = 'settings';
const SMT_LINE = 'smtline';
const LOGS = 'logs';
const UNDO_TABLE = 'deletedrecords';

/**
 * MAIN NAVBAR SEARCH placeholders ARRAY
 */
const FIND_T = [
    "order" => "Search by: Order id, Project Name, Customer Name, Date Creation",
    "project" => "Searching by: Project Name, Customer Name, Date Creation",
    "admin-panel" => "Searching by: Any",
    "logs" => "Searching by: User, Object Type, Action, Object ID, Date Creation",
    "warehouse" => "Searching by: Part Name, Manufacture P/N, Client P/N, Our P/N, Date Creation",
    "wiki" => "Searching by: file name",
    "" => "Searching by:"
];

/**
 * константа для вывода чекбоксов на странице заказа при его взятии в работу
 */
const CHECK_BOX = [
    'Check if the BOM has been fully added to the box.',
    'Verify that all parts match those in the BOM.',
    'Ensure all tools are available and have not expired.',
    'Make sure your workspace is approved for work.',
    'Confirm the order doesn\'t have any additional information about assembly stages.',
    'Ensure the project\'s assembly steps are fully documented.',
    'Check if the project documentation/version has not changed during client interactions.',
    'See if the order chat contains any additional information about this order/project.',
    'Wear protective equipment and static grounding. Turn on the hood if necessary.',
    'Make yourself a coffee, go to the toilet, turn off your phone, set a timer for a break, paint the sky blue!',
    'Verify that day is day and night is night.',
    'Then take the order to work.'
];

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
 */
const OBJECT_TYPE = ['ORDER', 'ORDER_BOM', 'ORDER_CHAT',
    'PROJECT', 'PROJECT_STEP', 'PROJECT_BOM',
    'WAREHOUSE', 'SETTINGS', 'ROUTE_ACTION',
    'TOOLS', 'COLUMNS', 'USER', 'ADMINKA'];

/**
 * страницы которые не содержат строку поиска в навбаре.
 * Pages without search field in to navbar.
 */
const NO_VIEW_PAGES = [
    'new_order', 'edit_order', 'order_bom',
    'customers', 'docs',
    'admin-panel',
    'new_project', 'edit_project', 'edit_step', 'add_step',
    'import_csv', 'view_item', 'arrivals', 'edit_item'];

/**
 *  Список игнорируемых путей и файлов
 */
const IGNORE_LIST = [
    ['type' => 'exact', 'value' => '/'],
    ['type' => 'exact', 'value' => '/sign-out'],
    ['type' => 'contains', 'value' => '.ico'],
    ['type' => 'contains', 'value' => '.css'],
    ['type' => 'contains', 'value' => 'storage/projects/']
    // Добавляйте сюда новые условия для игнорирования
];

/**
 * СПИСОК ВАРИАНТОВ ВИДОВ ДЕТАЛЕЙ В БД
 */
const MOUNTING_TYPE = ["SMT", "TH", "CM", "PM", "SOLDER", "CRIMP", "LM", "OTHER"];

/**
 * СПИСОК НАЗВАНИЙ ПАРТ НОМЕРОВ ДЛЯ NTI
 */
const NTI_PN = ['NON' => 'Other', 'NCAP' => 'Capacitor', 'NRES' => 'Resistor', 'NDIO' => 'Diode', 'NIC' => 'Micro Chip', 'NTR' => 'Transistor',
    'NCR' => 'Oscilator', 'NFU' => 'Fuse', 'NFB' => 'Ferrite bead', 'NCON' => 'Connector', 'NIND' => 'Inductor', 'NPIN' => 'Pins',
    'NW' => 'Wires', 'NTUBE' => 'Shrink Tube'];