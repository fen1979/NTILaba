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

// projects pages
$r->addRout('/project', 'project-view.php');
$r->addRout('/new_project', 'projects/add-project.php');
$r->addRout('/edit_project', 'projects/edit-project.php');
$r->addRout('/add_step', 'projects/add-step.php');
$r->addRout('/edit_step', 'projects/edit-step.php');
$r->addRout('/step_history', 'projects/history-view.php');
$r->addRout('/check_part_list', 'projects/project-bom.php');

// admin-panel pages
$r->addRout('/setup', 'admin-panel.php');
$r->addRout('/create_client', 'profiles/customers.php');
$r->addRout('/create_supplier', 'profiles/suppliers.php');
$r->addRout('/logs', 'admin-panel/logs.php');

// warehouse pages
$r->addRout('/warehouse', 'warehouse.php');
$r->addRout('/warehouse/the_item', 'stock/item-data.php');
$r->addRout('/import-csv', 'stock/items-import.php');
$r->addRout('/movement-log', 'stock/warehouse-log.php');
$r->addRout('/arrivals', 'stock/arrivals.php');

// wiki storage page
$r->addRout('/wiki', 'wiki.php');
$r->addRout('/docs', 'public/docs/docs.php');
$r->addRout('/assy_flow_pdf', 'pdfOut/assyStepFlow.php');
$r->addRout('/route_card', 'pdfOut/routes.php');

// shared project view page
$r->addRout('/shared-project', 'public/shared.php');

// call the routing function to view page
$r->route($r->getUrl());
/**
 * getUrl() return string like /page...
 * routing to pages by GET
 * all POST request use directly from $_POST[ANY]
 * */