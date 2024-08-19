<?php
/**
 * Copyright Ajeco corp. Amir Aliev
 * started 2023/11.
 * License GNU F.O.S
 */
require_once 'core/Routing.php';

if (!empty($_SESSION['userBean']) && $_SESSION['userBean']['id'] == 2) {
// вывод ошибок при разработке УДАЛИТЬ  перед продакшеном!!!
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Добавляем заголовки Cache-Control и Pragma
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

$r = new Routing();

// login
$r->addRout('/', 'auth/login.php');
// logout
$r->addRout('/sign-out', 'auth/login.php');

// orders pages
$r->addRout('/order', 'order-view.php');
$r->addRout('/new_order', 'orders/create-order.php');
$r->addRout('/edit-order', 'orders/create-order.php');
$r->addRout('/check_bom', 'orders/order-bom.php');
$r->addRout('/order/preview', 'orders/order-details.php');
$r->addRout('/priority-out', 'pdfOut/priority-out.php');
$r->addRout('/order_pdf', 'pdfOut/order-details.php');
// спец страница для автоматического ввода данных
$r->addRout('/pioi', 'orders/add-po.php');

// production unit pages
$r->addRout('/project', 'product-units-view.php');
$r->addRout('/new_project', 'projects/add-unit.php');
$r->addRout('/edit_project', 'projects/edit-unit.php');
$r->addRout('/add_step', 'projects/add-step.php');
$r->addRout('/edit_step', 'projects/edit-step.php');
$r->addRout('/step_history', 'projects/history-view.php');
$r->addRout('/check_part_list', 'projects/unit-bom.php');

// admin-panel pages
$r->addRout('/setup', 'admin-panel.php');
$r->addRout('/create_client', 'counterparties/customers.php');
$r->addRout('/create_supplier', 'counterparties/suppliers.php');
$r->addRout('/logs', 'admin-panel/logs.php');
// page for ADD AND MANAGE site static rsources
$r->addRout('/resources', 'static-data.php');

// warehouse pages
$r->addRout('/wh', 'warehouse.php');
$r->addRout('/wh/the_item', 'warehouse/item-data.php');
$r->addRout('/import-csv', 'warehouse/items-import.php');
$r->addRout('/movement-log', 'warehouse/wh-log.php');
$r->addRout('/arrivals', 'warehouse/arrivals.php');
$r->addRout('/edit-item', 'warehouse/edit-item.php');
$r->addRout('/replenishment', 'warehouse/replenishment.php');

// wiki storage page
$r->addRout('/wiki', 'wiki.php');

// системные операции для внутренних нужд сайта //
// переход к документации о сайте
$r->addRout('/docs', 'public/docs/docs.php');
// вывод сборочной документации в ПДФ для проекта
$r->addRout('/assy_flow_pdf', 'pdfOut/assyStepFlow.php');
// вывод рут карты в ПДФ для заказа
$r->addRout('/route_card', 'pdfOut/routes.php');
// создание БОМ файла на скачивание для проекта/заказа
$r->addRout('/create_bom', 'orders/order-details.php');
// Data Base changes listeners use for: Order, Chat
$r->addRout('/is_change', 'core/listeners.php');

// shared project view page
$r->addRout('/shared-project', 'public/shared.php');
// cron file for cron requests from server
$r->addRout('/6fef03d1aac6981d6c6eaa35fc9b46d1311b4b5425a305fc7da1b00c2', 'core/cron.php');
// запрос на поиск данных в БД
$r->addRout('/get_data', 'searching/getData.php');

// task manager pages
$r->addRout('/task_list', 'task-manager.php');
$r->addRout('/add-task', 'task-manager/add-task.php');
$r->addRout('/update-task', 'task-manager/update-task.php');
$r->addRout('/manage-list', 'task-manager/manage-list.php');


// call the routing function to view page
$r->route($r->getUrl());
/**
 * getUrl() return string like /page...
 * routing to pages by GET
 * all POST request use directly from $_POST[ANY]
 * */