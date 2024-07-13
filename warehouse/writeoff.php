<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'order');
require 'warehouse/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'writeoff';