<?php
//
if (isset($_POST['supplier-name']) && isset($_POST['user-data'])) {
    require 'CPController.php';
    echo CPController::createSupplierOnFly($_POST);
    exit();
}

EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'CPController.php';




