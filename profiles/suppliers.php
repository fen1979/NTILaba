<?php
//
if (isset($_POST['supplier-name']) && isset($_POST['user-data'])) {
    require 'Profiler.php';
    echo Profiler::createSupplierOnFly($_POST);
    exit();
}

EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');
require 'Profiler.php';




