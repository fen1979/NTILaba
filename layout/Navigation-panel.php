<?php
/** @noinspection ALL */
/**
 * страницы которые не содержат строку поиска в навбаре.
 * Pages without search field in to navbar.
 */
const NO_VIEW_PAGES = [
    'new_order', 'edit_order', 'order_bom',
    'customers', 'docs',
    'admin-panel', 'home',
    'new_project', 'edit_project', 'edit_step', 'add_step',
    'import_csv', 'view_item', 'arrivals', 'edit_item', 'replenishment', 'po_replenishment',
    'task_manager', 'pioi', 'tracking'];
/**
 *
 * NAVIGATION BAR
 * - how to use
 * - // NAVIGATION BAR
 * - NavBarContent([
 * - 'title' => 'Title Information', // nav bar title
 * - 'active_btn' => Y['value'], // active tabs
 * - 'page_tab' => $_GET['page'] ?? null, //
 * - 'record_id' => null, // some id
 * - 'user' => $user, // user data
 * - 'page_name' => $page, // page entry name
 * -  // optional options
 * - 'btn_title' => null // admin panel btn's title
 * - ]);
 *
 * @param $navBarData
 * @return void
 */
function NavBarContent($navBarData): void
{
    // формируем данные для работы в нав баре
    $role = $navBarData['user']['app_role'];
    $name = $navBarData['user']['user_name'];
    $link = $navBarData['user']['link'];
    $title = $navBarData['title'] ?? null;
    $page = $navBarData['page_name'];
    $page_tab = $navBarData['page_tab'] ?? null;
    $pid = $navBarData['record_id'] ?? null;
    // admin panel
    $btn_title = $navBarData['btn_title'] ?? null;
    $style = $navBarData['style'] ?? 'style="height: 6rem; width: 100%;"';
    ?>
    <!-- google translate tag -->
    <div id="google_translate_element"></div>

    <header <?= $style ?>>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg fixed-top navbar-scroll blury">
            <div class="container-fluid">
                <!-- TITLE -->
                <h3 class="navbar-brand">
                    <?= (!in_array($page, NO_VIEW_PAGES) && !empty($name)) ? 'Hi ' . $name : $title; ?>
                </h3>
                <!-- GAMBURGER BUTTON -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i id="butter" class="bi bi-three-dots-vertical" onclick="dom.toggleClass('#butter','bi-three-dots')"></i>
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
                $w = 'w-100';
                if ($page == 'order') { ?>
                    <div class="<?= $w ?> d-flex mainSearchForm" data-title="<?= SR::getResourceValue('search_field', $allowed ?? ''); ?>"
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
                    <div class="<?= $w ?> ms-2 me-2 mainSearchForm" data-title="<?= SR::getResourceValue('search_field', $allowed ?? ''); ?>">
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
                                ADMIN_PANEL_BUTTONS($btn_title, $_GET['route-page']);
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
                            case 'replenishment':
                                WAREHOUSE_PAGE_BUTTONS($page, $pid, $page_tab);
                                break;
                            case 'tracking':
                                TRACKING_PAGE_BUTTONS();
                                break;
                            default:
                                ALL_PAGES_BUTTONS($page, $btn_title);
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
    <?php

    /**
     * This method accepts an associative array,
     * where the “color” key sets colors using classes,
     * and the “info” key is information in text form that should be displayed on the page.
     * how to use $args = ['color'=>'some_class_witch_color_settings', 'info'=>'some text to preview on page'];
     * and if was redirect got ingo from $_SESSION['info']
     * when exist arg 'hide' then message isnt close automaticaly
     */

    // Получаем сообщения из сессии
    if (!empty($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        // Очищаем сессионные сообщения после их использования
        unset($_SESSION['flash_messages']);
    } else {
        return; // Если сообщений нет, ничего не выводим
    }

    // Иконка для закрытия сообщения
    $icon = '<i class="bi bi-x-square hide-service-msg" onclick="dom.hide(\'.global-notification\', \'slow\')"></i>';

    // Вывод сообщений
    ?>
    <div class="global-notification">
        <?php foreach ($messages as $message): ?>
            <div class="p-2 mb-2 rounded <?= $message['color'] ?? 'hidden'; ?> <?= (bool)$message['auto_hide'] ? 'fade-out' : ''; ?>">
                <?= empty($message['auto_hide']) ? $icon : ''; ?>
                <?= $message['text'] ?? ''; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php

} // END NAVBAR CONTENNT FUNCTION

function TRACKING_PAGE_BUTTONS()
{
    ?>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary text-white" value="home">Home</button>
    </li>

    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary text-white" value="tracking">Receive</button>
    </li>

    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary text-white" value="track-list?track-list=1">Track List</button>
    </li>
    <?php
}

/**
 * MAIN NAVIGATION BUTTONS
 * @param $l
 * @return void
 */
function ALL_PAGES_BUTTONS($page, $btn_title): void
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

    // выбор количества строк на странице
    NUMBERS_OF_ROW($page);
    ?>

    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="order">Orders
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="project">Projects
        </button>
    </li>

    <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="pioi">P.O
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="new_order">New Order
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="new_project">New Project
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="create_client">Customers
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="wh">Warehouse
        </button>
    </li>
    <li class="nav-item">
        <?php $t = 'List of incoming documents for received parcels'; ?>
        <button type="button" class="url act btn btn-sm btn-outline-secondary" data-title="<?= $t ?>"
                value="staging">P.O.S
        </button>
    </li>
<?php } ?>

    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="task_list">Tasks
        </button>
    </li>

    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-secondary"
                value="wiki">Wiki
        </button>
    </li>

    <!-- SETTINGS BUTTON DROPDOWN LIST -->
    <div class="btn-group nav-item" style="align-items: flex-start;">
        <button class="m-03 btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Settings
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="setup?route-page=1" target="_blank">Columns</a></li>
            <li><a class="dropdown-item" href="setup?route-page=8" target="_blank">Profile</a></li>
            <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="setup?route-page=7" target="_blank">Search</a></li>
                <li><a class="dropdown-item" href="setup?route-page=2" target="_blank">Rout's</a></li>
                <li><a class="dropdown-item" href="setup?route-page=3" target="_blank">Tools</a></li>
                <li><a class="dropdown-item" href="setup?route-page=4" target="_blank">Users</a></li>
                <li><a class="dropdown-item" href="setup?route-page=5" target="_blank">Projects</a></li>
                <li><a class="dropdown-item" href="setup?route-page=6" target="_blank">Orders</a></li>
                <li><a class="dropdown-item" href="setup?route-page=9" target="_blank">Warehouses</a></li>
                <!--            <li><a class="dropdown-item" href="setup?route-page=9" target="_blank">C.O.N</a></li>-->
                <li><a class="dropdown-item" href="resources" target="_blank">Resources</a></li>
                <li><a class="dropdown-item" href="logs" target="_blank">Logs</a></li>
            <?php } ?>
        </ul>
    </div>
    <?php
}

/**
 * ADMIN PANEL AND SETTINGS BUTTONS
 * @param $btn_title
 * @param $page
 * @param $width
 * @return void
 */
function ADMIN_PANEL_BUTTONS($btn_title, $page, $width = false): void
{
    // уровень администратора позволяет работать с админ панелью
    if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) {
        // страница где отображается кнопка импортировать из файла
        if ($page == 3 || $page == 2) {
            $table = _if(($page == 3), 'tools', 'routeaction'); ?>
            <li class="nav-item">
                <button type="button" value="import-file?table-name=<?= $table ?>" class="url btn btn-sm btn-outline-info">
                    Import from file <i class="bi bi-filetype-xlsx"></i>
                </button>
            </li>
        <?php }

        // страницы где не отображается кнопка добавить новый
        if (!in_array($page, [1, 7, 8])) { ?>
            <li class="nav-item">
                <button type="button" id="create-btn" class="btn btn-sm btn-outline-success m-03">
                    <i class="bi bi-plus"></i> Create new <?= $btn_title ?>
                </button>
            </li>
        <?php } ?>

        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="7">Search</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="1">Columns</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="2">Rout's</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="3">Tools</button>
        </li>

        <li class="nav-item">
            <button type="button" name="sw_bt" class="url act btn btn-sm btn-outline-secondary" value="resources">Resources</button>
        </li>
        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-secondary" value="logs">Logs</button>
        </li>
        <?php
        // только высокий уровень пользователя может работать с персональными данными
        if (isUserRole([ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
            <li class="nav-item">
                <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="4">Users</button>
            </li>
        <?php } ?>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="5" disabled>Projects</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="6" disabled>Orders</button>
        </li>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="9">Warehouses</button>
        </li>

    <?php } else { ?>
        <li class="nav-item">
            <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="1">Columns</button>
        </li>
    <?php } ?>

    <li class="nav-item">
        <button type="button" name="sw_bt" class="swb btn btn-sm btn-outline-secondary" value="8">Profile</button>
    </li>

    <li class="divider-vertical"></li>

    <?php
    // site map buttons burger buttons
    DROPDOWN_BUTTONS($_SESSION['userBean']);
}

/**
 * EDIT PROJECT PAGE BUTTONS
 * @param $pid
 * @return void
 */
function EDIT_PROJECT_PAGE_BUTTONS($pid): void
{
    if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) {
        // сделать чтоб открывалось в новой вкладке
        ?>
        <li class="nav-item">
            <button type="button" title="Delete" class="m-03 btn btn-sm btn-outline-danger deleteProjectButton" data-projectid="<?= $pid; ?>">
                <i class="bi bi-trash3-fill"></i>
                Delete Project
            </button>
        </li>
        <li class="nav-item">
            <button type="button" title="Archive" class="m-03 btn btn-sm btn-outline-diliny archive" data-projectid="<?= $pid; ?>">
                <i class="bi bi-archive-fill"></i>
                Archive Project
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="new_order?pid=<?= $pid; ?>&nord">
                <i class="bi bi-tools"></i>
                Create Order
            </button>
        </li>
        <li class="nav-item hidden" id="folder_btn_li">
            <button type="button" id="project_folder" data-blank="1" value="order" class="url act btn btn btn-sm btn-outline-dark">
                <i class="bi bi-folder"></i>
                View Project Folder
            </button>
        </li>
        <li class="nav-item">
            <button type="button" value="assy_flow_pdf?pid=<?= $pid; ?>" class="url act btn btn btn-sm btn-outline-diliny" data-blank="1">
                <i class="bi bi-bar-chart-steps"></i>
                Assembly steps PDF
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="check_part_list?orid=none&pid=<?= $pid; ?>">
                <i class="bi bi-receipt"></i>
                Fill Project BOM
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="new_project?pid=editmode">
                <i class="bi bi-pencil-square"></i>
                Edit Project information
            </button>
        </li>
    <?php } ?>
    <li class="nav-item">
        <button type="button" class="url act btn btn-sm btn-outline-diliny" value="add_step?pid=<?= $pid; ?>">
            <i class="bi bi-plus-circle"></i>
            Add new step
        </button>
    </li>

    <li class="divider-vertical"></li>
    <?php

    // site map buttons burger buttons
    DROPDOWN_BUTTONS($_SESSION['userBean']);
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
        <button type="button" class="url act btn btn-sm btn-outline-danger" id="back-btn" value="edit_project?pid=<?= $pid; ?>">
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
function WAREHOUSE_PAGE_BUTTONS($page, $pid, $page_tab = ''): void
{
    if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) {
        $edit_url = _if(!empty($page_tab),
            "edit-item?item_id=$pid&page=$page_tab&#row-$pid",
            "edit-item?item_id=$pid");
        $back_url = _if(!empty($page_tab),
            "wh?page=$page_tab&#row-$pid",
            "wh");
        //$pt = !empty($page_tab) ? "?page=$page_tab&#row-$pid" : null;
        /*value="edit-item<?= !empty($pt) ? "$pt&" : '?'; ?>item_id=<?= $pid; ?>"*/
        if ($page !== 'wh' && $page !== 'arrivals' && !empty($page_tab)) { ?>
            <li class="nav-item">
                <button type="button" class="url act btn btn-sm btn-outline-danger" value="<?= $back_url ?>">
                    Back To List
                </button>
            </li>
            <?php
        }

        if ($page == 'view_item' && $pid != null) {
            ?>
            <li class="nav-item">
                <button type="button" class="url act btn btn-sm btn-outline-warning" value="<?= $edit_url ?>">
                    Edit Item <i class="bi bi-pen"></i>
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="url act btn btn-sm btn-outline-info" value="replenishment<?= !empty($pt) ? "$pt&" : '?'; ?>item_id=<?= $pid; ?>">
                    Replenishment
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="url act btn btn-sm btn-outline-primary" value="ordered-info<?= !empty($pt) ? "$pt&" : '?'; ?>item_id=<?= $pid; ?>">
                    Add Orders info <i class="bi bi-list"></i>
                </button>
            </li>
            <?php
        }

        // выбор количества строк на странице
        NUMBERS_OF_ROW($page);
        ?>

        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="po-replenishment">
                P.O. Replenishment <i class="bi bi-plus"></i>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="arrivals">
                Add New Item <i class="bi bi-plus"></i>
            </button>
        </li>

        <!--        <li class="nav-item">-->
        <!--            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="import-csv" disabled>-->
        <!--                Import Items From File <i class="bi bi-filetype-csv"></i>-->
        <!--            </button>-->
        <!--        </li>-->

        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-diliny" value="import-file?import=warehouse">
                Import Items From File <i class="bi bi-filetype-xlsx"></i>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="url act btn btn-sm btn-outline-dark" value="movement-log">
                Check Warehouse Logs <i class="bi bi-list-task"></i>
            </button>
        </li>

        <li class="divider-vertical"></li>

        <?php
        // site map buttons burger buttons
        DROPDOWN_BUTTONS($_SESSION['userBean']);
    }
}

/**
 * numbers of row for preview on pages
 */
function NUMBERS_OF_ROW($page)
{
    if (!in_array($page, NO_VIEW_PAGES)) {
        ?>
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
 * dropdown list site map buttons
 * in use on warehouse, admin-panel pages
 * @param $user
 * @return void
 */
function DROPDOWN_BUTTONS($user)
{ ?>
    <div class="btn-group nav-item" style="align-items: center;">
        <button class="url act btn btn-outline-secondary btn-sm" type="button" value="<?= $user['link'] ?>">
            <?= SR::getResourceValue('title', $user['link']) ?>
        </button>
        <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Site Pages</span>
        </button>
        <ul class="dropdown-menu">
            <li class="nav-item">
                <a href="/order" class="dropdown-item">Orders</a>
            </li>
            <li class="nav-item">
                <a href="/project" class="dropdown-item">Projects</a>
            </li>
            <li class="nav-item">
                <a href="/task_list" target="_blank" class="dropdown-item">Tasks</a>
            </li>

            <li class="nav-item">
                <a href="/wiki" target="_blank" class="dropdown-item">Wiki</a>
            </li>
            <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                <li class="nav-item">
                    <a href="/create_client" class="dropdown-item">Customers</a>
                </li>
                <li class="nav-item">
                    <a href="/wh" class="dropdown-item">Warehouse</a>
                </li>
                <li class="nav-item">
                    <a href="/setup?route-page=1" target="_blank" class="dropdown-item">Settings</a>
                </li>
                <li class="nav-item">
                    <a href="/logs" class="dropdown-item">Logs</a>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php
}