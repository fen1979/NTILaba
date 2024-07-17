<?php
/** @noinspection ALL */
/* ICON, TITLE, STYLES AND META TAGS */
function HeadContent($page)
{ ?>
    <!-- root link to this site -->
    <base href="<?= BASE_URL; ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">-->
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="/public/images/nti_logo.png">
    <title><?= L::TITLES($page ?? ''); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.2/font/bootstrap-icons.css">

    <!-- global styles light mode -->
    <link rel="stylesheet" href="/public/css/main.css">
    <?php
    /*  подключаем стили для конкретных  страниц если они есть */
    switch ($page) {
        case 'project':
        case 'add_step':
        case 'edit_step':
        case 'edit_project':
            echo '<link rel="stylesheet" href="/public/css/project-view.css">';
            break;
        case 'admin-panel':
            echo '<link rel="stylesheet" href="/public/css/admin-panel.css">';
            break;
        case 'order':
            echo '<link rel="stylesheet" href="/public/css/orders-view.css">';
            break;
        case 'order_details':
            echo '<link rel="stylesheet" href="/public/css/order-details.css">';
            break;
    }
    ?>
    <style>
        /* google translate nav bar hiding */
        .skiptranslate {
            display: none;
        }
    </style>
    <?php
}

/**
 * NAVIGATION BAR
 * @param $page
 * @param $name
 * @param $pid
 * @param $l // активная кнопка на страницах
 * @return void
 */

// $navBarData = ['active_tab'=>$l, 'some_id'=>$pid, 'title'=>'some title'];
function NavBarContent($page, $user = null, $pid = null, $l = ''): void
{
    $role = $user['app_role']; ?>
    <!-- google translate tag -->
    <div id="google_translate_element"></div>

    <header style="height: 6rem; width: 100%;">
        <form action="" id="routing" class="hidden" method="post"></form>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg fixed-top navbar-scroll blury">
            <div class="container-fluid">
                <!-- TITLE -->
                <h3 class="navbar-brand">
                    <?= (!in_array($page, NO_VIEW_PAGES) && !empty($user['user_name'])) ? 'Hi ' . $user['user_name'] : $user['title']; ?>
                </h3>
                <!-- GAMBURGER BUTTON -->
                <button class="navbar-toggler" type="button" data-mdb-toggle="collapse"
                        data-mdb-target="#navBarContent" aria-controls="navBarContent" aria-expanded="false"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon d-flex justify-content-start align-items-center"></span>
                </button>

                <!-- SEARCH FIELD -->
                <?php
                $hideThis = '';
                $elem = 'full_height';
                $allowed = $page;
                if (in_array($page, NO_VIEW_PAGES)) {
                    $hideThis = 'hidden';
                    $elem = 'searchForm';
                    $allowed = '';
                }
                $w = ($page == 'view_item') ? 'w-25' : 'w-100';
                if ($page == 'order') { ?>
                    <div class="<?= $w ?> d-flex mainSearchForm" data-title="<?= FIND_T[$allowed ?? '']; ?>"
                         style="justify-content: space-evenly; align-items: center;">

                        <form action="" id="<?= $elem; ?>" class="form <?= $hideThis; ?>" style="width: 70%;">
                            <input type="search" role="searchbox" aria-label="Search" class="searchThis form-control"
                                   data-request="<?= $page; ?>_nav" autofocus placeholder="Global Search" required>
                        </form>

                        <form action="" id="orid-form" class="form">
                            <input type="search" role="searchbox" aria-label="Search" class="searchThis form-control"
                                   data-request="order_id_search" placeholder="Search by ID" required>
                        </form>
                    </div>
                <?php } else { ?>
                    <div class="<?= $w ?> ms-2 me-2 mainSearchForm" data-title="<?= FIND_T[$allowed ?? '']; ?>">
                        <form action="" id="<?= $elem; ?>" class="form <?= $hideThis; ?>">
                            <input type="search" role="searchbox" aria-label="Search" class="searchThis form-control"
                                   data-request="<?= $page; ?>_nav" autofocus placeholder="Search" required>
                        </form>
                    </div>
                <?php } ?>

                <!-- NAVIGATION PANEL BUTTONS -->
                <div class="collapse navbar-collapse" id="navBarContent">
                    <ul class="navbar-nav me-auto">

                        <?php switch ($page) {
                            case 'admin-panel':
                                ADMIN_PANEL_BUTTONS($user, $_GET['route-page']);
                                break;
                            case 'add_step':
                            case 'edit_step':
                                EDIT_AND_ADD_STEP_BUTTONS($pid);
                                break;
                            case 'edit_project':
                                EDIT_PROJECT_PAGE_BUTTONS($pid);
                                break;
                            case 'view_item':
                            case 'arrivals':
                            case 'edit_item':
                            case 'wh':
                            case 'in_out_item':
                                WAREHOUSE_PAGE_BUTTONS($l, $page, $pid);
                                break;
                            default:
                                ALL_PAGES_BUTTONS($page, $l);
                                LANGUAGE_BUTTONS();
                                break;
                        } ?>

                        <!-- LOG OUT BUTTON -->
                        <li class="nav-item">
                            <button type="button" class="url btn btn-sm btn-outline-dark text-white" value="sign-out">
                                <i class="bi bi-door-closed"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
<?php }

/**
 * MAIN NAVIGATION BUTTONS
 * @param $l
 * @return void
 */
function ALL_PAGES_BUTTONS($page, $l): void
{
    // change page view btn only for projects page
    if ($page == 'project'): ?>
        <li class="nav-item">
            <button type="button" class="btn btn-sm btn-outline-dark project_preview_mode">
                <?php if (isset($_SESSION['preview_mode']) && !$_SESSION['preview_mode']) { ?>
                    <i class="bi bi-list-task project_preview_mode"></i>
                <?php } else { ?>
                    <i class="bi bi-grid project_preview_mode"></i>
                <?php } ?>
            </button>
        </li>
    <?php endif;

    if ($page != 'arrivals' && $page != 'view_item') {
        // выбор количества строк на странице
        NUMBERS_OF_ROW($page);
    }
    ?>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['ORDER']) ? 'gold' : 'secondary'; ?>"
                value="order">Orders
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['PROJECT']) ? 'gold' : 'secondary'; ?>"
                value="project">Projects
        </button>
    </li>

    <?php if (isUserRole(ROLE_ADMIN) || isUserRole(ROLE_SUPERADMIN) || isUserRole(ROLE_SUPERVISOR)) {
    $text = ($page == 'edit_order') ? 'Edit' : 'New'; ?>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['N_ORDER']) ? 'gold' : 'secondary'; ?>"
                value="new_order"><?= $text; ?> Order
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['N_PROJECT']) ? 'gold' : 'secondary'; ?>"
                value="new_project">New Project
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['CLIENT']) ? 'gold' : 'secondary'; ?>"
                value="create_client">Customers
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['STOCK']) ? 'gold' : 'secondary'; ?>"
                value="wh">Warehouse
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['LOG']) ? 'gold' : 'secondary'; ?>"
                value="logs">Logs
        </button>
    </li>
<?php } ?>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['WIKI']) ? 'gold' : 'secondary'; ?>"
                value="wiki">Wiki
        </button>
    </li>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-secondary" value="setup?route-page=1" data-blank="1">Settings</button>
    </li>
    <?php
}

/**
 * ADMIN PANEL AND SETTINGS BUTTONS
 * @param $user
 * @return void
 */
function ADMIN_PANEL_BUTTONS($user, $page): void
{
    if (isUserRole(ROLE_ADMIN) || isUserRole(ROLE_SUPERADMIN) || isUserRole(ROLE_SUPERVISOR)) {
        if (!in_array($page, [1, 7, 8])) { ?>
            <li class="nav-item" style="white-space: nowrap">
                <button type="button" id="create-btn" class="btn btn-sm btn-outline-danger" style="margin: .3rem">
                    <i class="bi bi-plus"></i> Create new <?= $user['btn-title'] ?>
                </button>
            </li>
        <?php } ?>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="7">Search</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="1">Columns</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="2">Rout's</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="3">Tools</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="4">Users</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="5">Projects</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="6">Orders</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="9">Warehouses</button>
        </li>

    <?php } else { ?>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="1">Columns</button>
        </li>
    <?php } ?>

    <li class="nav-item">
        <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-primary" value="8">Profile</button>
    </li>

    <li class="divider-vertical"></li>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-secondary" value="<?= $user['link']; ?>">
            <?= L::TITLES($user['link'] ?? 'Home') ?>
        </button>
    </li>
    <?php
}

/**
 * EDIT PROJECT PAGE BUTTONS
 * @param $pid
 * @return void
 */
function EDIT_PROJECT_PAGE_BUTTONS($pid): void
{
    if (isUserRole(ROLE_ADMIN) || isUserRole(ROLE_SUPERADMIN) || isUserRole(ROLE_SUPERVISOR)) {
        // сделать чтоб открывалось в новой вкладке
        ?>
        <li class="nav-item" style="white-space: nowrap">
            <button type="button" title="Delete" class="btn btn-sm btn-outline-danger deleteProjectButton" data-projectid="<?= $pid; ?>">
                <i class="bi bi-trash3-fill"></i>
                Delete Project
            </button>
        </li>
        <li class="nav-item" style="white-space: nowrap">
            <button type="button" title="Archive" class="btn btn-sm btn-outline-diliny archive" data-projectid="<?= $pid; ?>">
                <i class="bi bi-archive-fill"></i>
                Archive Project
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-diliny" value="new_order?pid=<?= $pid; ?>&nord">
                <i class="bi bi-tools"></i>
                Create Order
            </button>
        </li>
        <li class="nav-item hidden" id="folder_btn_li">
            <button type="button" id="project_folder" data-blank="1" value="order" class="url btn btn btn-sm btn-outline-dark">
                <i class="bi bi-folder"></i>
                View Project Folder
            </button>
        </li>
        <li class="nav-item">
            <button type="button" value="assy_flow_pdf?pid=<?= $pid; ?>" class="url btn btn btn-sm btn-outline-diliny">
                <i class="bi bi-bar-chart-steps"></i>
                Assembly steps PDF
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-diliny" value="check_part_list?orid=none&pid=<?= $pid; ?>">
                <i class="bi bi-receipt"></i>
                Fill Project BOM
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-diliny" value="new_project?pid=editmode">
                <i class="bi bi-pencil-square"></i>
                Edit project information
            </button>
        </li>
    <?php } ?>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-diliny" value="add_step?pid=<?= $pid; ?>">
            <i class="bi bi-plus-circle"></i>
            Add new step
        </button>
    </li>

    <li class="divider-vertical"></li>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-secondary" value="project">Projects</button>
    </li>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-secondary" value="order">Orders</button>
    </li>
    <?php if (isUserRole(ROLE_ADMIN) || isUserRole(ROLE_SUPERADMIN) || isUserRole(ROLE_SUPERVISOR)) { ?>
    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-secondary" value="wh">Warehouse</button>
    </li>
<?php }
}

/**
 * ADD STEP OR EDIT STEP BUTTONS
 * @param $pid
 * @return void
 */
function EDIT_AND_ADD_STEP_BUTTONS($pid): void
{
    ?>
    <li class="nav-item">
        <button type="button" class="swb btn btn-sm btn-outline-success step-btn" id="finishBtn" name="save-changes">
            <i class="bi bi-check2-square"></i>
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="swb btn btn-sm btn-outline-warning step-btn" id="takePic">
            <i class="bi bi-camera-fill"></i>
        </button>
    </li>

    <li class="nav-item">
        <button type="button" class="swb btn btn-sm btn-outline-warning step-btn" id="takeMovie">
            <i class="bi bi-camera-reels-fill"></i>
        </button>
    </li>

    <li class="nav-item">
        <button type="button" class="url btn btn-sm btn-outline-danger" id="back-btn" value="edit_project?pid=<?= $pid; ?>">
            <i class="bi bi-x-lg"></i>
        </button>
    </li>

    <?php
}

/**
 * WAREHOUSE PAGE BUTTONS
 * @param $l
 * @return void
 */
function WAREHOUSE_PAGE_BUTTONS($l, $page, $pid): void
{
    if (isUserRole(ROLE_ADMIN) || isUserRole(ROLE_SUPERADMIN) || isUserRole(ROLE_SUPERVISOR)) {
        if ($page == 'view_item' && $pid != null) {
            ?>
            <li class="nav-item">
                <button type="button" class="url btn btn-sm btn-outline-warning" value="edit-item?item_id=<?= $pid; ?>">
                    Edit Item <i class="bi bi-pen"></i>
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="url btn btn-sm btn-outline-diliny" value="in-out-item?item_id=<?= $pid; ?>">
                    WriteOff Item <i class="bi bi-scissors"></i>
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="url btn btn-sm btn-outline-diliny" value="in-out-item?item_id=<?= $pid; ?>">
                    Arrival Item <i class="bi bi-airplane"></i>
                </button>
            </li>
            <?php
        }

        // выбор количества строк на странице
        if ($page != 'arrivals' && $page != 'view_item') {
            NUMBERS_OF_ROW($page);
        }
        ?>

        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-diliny" value="arrivals">
                Add New Item <i class="bi bi-plus"></i>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-diliny" value="import-csv" disabled>
                Import Items From File <i class="bi bi-filetype-csv"></i>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-dark" value="movement-log">
                Check Warehouse Logs <i class="bi bi-list-task"></i>
            </button>
        </li>

        <li class="divider-vertical"></li>

        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['ORDER']) ? 'gold' : 'secondary'; ?>"
                    value="order">Orders
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['PROJECT']) ? 'gold' : 'secondary'; ?>"
                    value="project">Projects
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['CLIENT']) ? 'gold' : 'secondary'; ?>"
                    value="create_client">Customers
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url btn btn-sm btn-outline-<?= ($l == Y['STOCK']) ? 'gold' : 'secondary'; ?>"
                    value="wh">Warehouse
            </button>
        </li>
        <?php
    }
}

/**
 * numbers of row for preview on pages
 */
function NUMBERS_OF_ROW($page)
{ ?>
    <li class="nav-item dropdown">
        <button class="m-03 btn btn-outline-diliny btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Numbers of row
        </button>
        <ul class="dropdown-menu dropdown-menu" aria-labelledby="items-view-limit">
            <li><a class="dropdown-item" href="<?= $page ?>?limit=50">50</a></li>
            <li><a class="dropdown-item" href="<?= $page ?>?limit=100">100</a></li>
            <li><a class="dropdown-item" href="<?= $page ?>?limit=500">500</a></li>
            <li><a class="dropdown-item" href="<?= $page ?>?limit=0">All</a></li>
        </ul>
    </li>
    <?php
}

/**
 * LANGUAGE BUTTONS
 * @return void
 */
function LANGUAGE_BUTTONS(): void
{ ?>
    <!-- LANGUAGE BUTTONS -->
    <ul class="navbar-nav d-flex flex-row">
        <li class="nav-item">
            <button class="nav-link lang" onclick="triggerGoogleTranslate('en')" value="<?= $page ?? ''; ?>?en">
                <i class="icon flag flag-us"></i>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link lang" onclick="triggerGoogleTranslate('ru')" value="<?= $page ?? ''; ?>?ru">
                <i class="icon flag flag-russia"></i>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link lang" onclick="triggerGoogleTranslate('iw')" value="<?= $page ?? ''; ?>?he">
                <i class="icon flag flag-israel"></i>
            </button>
        </li>
    </ul>
    <?php
}

/**
 * PAGINATION BUTTONS ON PAGES
 * orders, projects, warehouse
 * limit by default 25 per page
 * @param $get
 * @param $page
 * @param $table
 * @param int $limit
 * @param array $conditions ['query'=>'', 'data'=>'']
 * @return string[]
 */
function PaginationForPages($get, $page, $table, int $limit = 25, array $conditions = []): array
{
    $pagination = $paginationButtons = '';
    if (isset($get['limit']))
        $limit = (int)$get['limit'];

    // установка лимита для запроса в БД
    if ($limit != 0) {
        $currentPage = isset($get['page']) ? (int)$get['page'] : 1;
        $offset = ($currentPage - 1) * $limit;
        $pagination = "LIMIT $limit OFFSET $offset";
        // SQL-запрос для получения общего количества записей
        if (!empty($conditions)) {
            $totalResult = R::count($table, $conditions['query'], [$conditions['data']]);
        } else {
            $totalResult = R::count($table);
        }
        $totalPages = ceil($totalResult / $limit);

        $limit_n = (isset($_GET['limit'])) ? '&limit=' . $_GET['limit'] : '';
        // Пагинация
        $paginationButtons = '<div class="pagination" id="pagination-container">';
        // the previos button
        if ($currentPage > 1) {
            $href = "$page?page=" . ($currentPage - 1) . $limit_n;
            $paginationButtons .= "<a href='$href'>&laquo; Previous</a>";
        }
        // the pages buttons
        for ($i = 1; $i <= $totalPages; $i++) {
            $href = "$page?page=" . $i . $limit_n;
            $paginationButtons .= "<a href='$href' class='" . ($i == $currentPage ? 'active' : '') . "'>$i</a>";
        }
        // the next button
        if ($currentPage < $totalPages) {
            $href = "$page?page=" . ($currentPage + 1) . $limit_n;
            $paginationButtons .= "<a href='$href'>Next &raquo;</a>";
        }
        $paginationButtons .= '</div>';
    }

    return [$pagination, $paginationButtons];
}

/* FOOTER FOR PAGES */
function footer($page = '', $blur = '')
{ ?>
    <footer class="d-none d-md-block d-flex flex-wrap justify-content-between align-items-center border-top mt-auto <?= $blur; ?>">
        <div class="row py-3">
            <!-- Копирайт -->
            <div class="col-md-8 text-left ms-3">
                <?= '2016 - ' . date('Y') . '&nbsp; Created by &copy; Ajeco.ltd'; ?>
            </div>

            <!-- Счетчик проектов -->
            <div class="col-md-3 text-right">
                <?= 'NTI Group Projects Live - ' . R::count(PROJECTS); ?>
            </div>
        </div>
    </footer>

<?php }

/* JAVASCRIPTS */
function ScriptContent($page = null)
{ ?>
    <div class="loading-element" id="loading">
        <span style="position: absolute" class="fs-4 blinking">Loading...</span>
        <div class="coloring">
            <div class="spinner-border" role="status"></div>
        </div>
    </div>

    <!-- Bootstrap JS & Popper.js & Jquery.js -->
    <script src="/libs/jQuery3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- google translator script lib free use -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <!-- custom lib .js -->
    <script src="/libs/ajeco-re-max.js"></script>
    <?php
    // pages js files
    switch ($page) {
        case 'admin-panel':
            echo '<script src="/public/js/admin-panel.js"></script>';
            break;
        case 'order':
        case 'order_details':
            echo '<script src="/public/js/order-view.js"></script>';
            break;
    }
}

/**
 * This method accepts an associative array,
 * where the “color” key sets colors using classes,
 * and the “info” key is information in text form that should be displayed on the page.
 * how to use $args = ['color'=>'some_class_witch_color_settings', 'info'=>'some text to preview on page'];
 * and if was redirect got ingo from $_SESSION['info']
 * when exist arg 'hide' then message isnt close automaticaly
 * @param $args
 * @return void
 */
function DisplayMessage($args)
{
    $icon = '<i class="bi bi-x-square p-3" onclick="dom.hide(\'.global-notification\', \'slow\')"></i>';
    if (!empty($_SESSION['info']) && $args == null) {
        $args = $_SESSION['info'];
        $_SESSION['info'] = null;
    } else {
        if (!empty($_SESSION['info']))
            $args[] = $_SESSION['info'];
    }

    // вывод сообщения на экран
    if ($args && array_key_exists('info', $args)) {
        /* one message for view */
        $hideType = empty($args['hide']) ? 'fade-out' : '';
        ?>
        <div class="global-notification <?= $hideType . ' ' . $args['color'] ?? 'hidden'; ?>">
            <?= ($hideType == '') ? $icon : ''; ?>
            <?= $args['info'] ?? ''; ?>
        </div>
        <?php
    } else {

        /* multyple information or error messages for view */
        if ($args) {
            $hideType = empty($args['hide']) ? 'fade-out' : '';
            ?>
            <div class="global-notification <?= $hideType; ?>">
                <?php
                // icon button close message
                echo ($hideType == '') ? $icon : '';
                foreach ($args as $info) : ?>
                    <div class="p-2 mb-2 rounded  <?= $info['color'] ?? 'hidden'; ?>">
                        <?= $info['info'] ?? ''; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }
}

/**
 * THE CHAT FUNCTION FOR ALL USERS
 * @param $page
 * @param $user
 * @return void
 */
function ShowGroupChatPopup($page, $user = null)
{
    ?>
    <!-- popup main chat button -->
    <button class="open-button" onclick="openForm()">
        <span id="msg_count" class="msg_count">123</span>
        <i class="bi bi-chat"></i>
    </button>

    <!-- popup main chat room window -->
    <div class="chat-popup" id="popup-window">
        <span id="user_information" data-userid="<?= $_SESSION['userBean']['id']; ?>" data-username="<?= $_SESSION['userBean']['user_name']; ?>" class="hidden"></span>
        <form action="" class="form-container" method="post">
            <div class="mb-2" onclick="closeForm()">
                <i class="bi bi-x-lg p-2 danger" onclick="closeForm()"></i>
            </div>

            <label for="msg"><b>Messages</b></label>
            <div class="msg_content" id="msg_area"></div>
            <textarea placeholder="Type message.." name="msg" id="msg" required></textarea>


            <div class="dropdown w-100">
                <button class="btn btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="dropdown-toggle">
                    Message to User
                </button>
                <div class="dropdown-menu w-100" id="dropdown-menu">
                    <?php
                    foreach (R::find(USERS) as $u) {
                        if ($u['id'] != '1') {
                            ?>
                            <div class="radio-item">
                                <input type="radio" name="user" value="<?= $u['id']; ?>" id="<?= $u['id']; ?>">&nbsp;
                                <label for="<?= $u['id']; ?>"><?= $u['user_name']; ?> </label>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Send</button>
        </form>
    </div>
    <?php
}


// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
// used on pages: arrivals,
function SearchResponceModalDialog($page, $answer_id): void
{ ?>
    <div class="modal" id="searchModal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Search Result</h4>
                    <button type="button" class="btn-close" data-aj-dismiss="modal"></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <table id="<?= $answer_id ?>">
                        <!-- table for preview search results -->
                    </table>
                </div>

                <!-- Modal footer x -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-aj-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}