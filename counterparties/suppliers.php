<?php
//
if (isset($_POST['supplier-name']) && isset($_POST['user-data'])) {
    try {
        echo CPController::createSupplierOnFly($_POST);
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    exit();
}

EnsureUserIsAuthenticated($_SESSION, 'userBean', [ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR], 'wh');




