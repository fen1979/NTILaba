<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'admin-panel';
$role = $user['app_role'];
/* удаление rout card/user/tools и всех его данных ---------------------------- */
if (isset($_POST['idForUse']) && isset($_POST['password'])) {
    try {
        Management::deletingAnItem($_POST, $user);
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Error: ' . $e->getMessage(), 'danger');
    }
}
/* ROUT ACTIONS CODE ---------------------------------------------------------- */
if (isset($_POST['rout-action-saving']) || isset($_POST['rout-action-editing'])) {
    Management::createUpdateRoutAction($_POST, $user);
}
/* WAREHOUSE ACTIONS CODE ---------------------------------------------------------- */
if (isset($_POST['wh-action-saving']) || isset($_POST['wh-action-editing'])) {
    Management::createUpdateWarehouseType($_POST, $user);
}
/* USERS ACTIONS CODE --------------------------------------------------------- */
if (isset($_POST['update-user-data']) || isset($_POST['add-new-user'])) {
    try {
        Management::addOrUpdateUsersData($_POST, $user);
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Error: ' . $e->getMessage(), 'danger');
    }
}
/* TOOLS ACTIONS CODE --------------------------------------------------------- */
if (isset($_POST['tools-saving']) || isset($_POST['tools-editing'])) {
    Management::createUpdateTools($_POST, $_FILES['imageFile'], $user);
}

/* TABLE COLUMNS ACTIONS CODE ------------------------------------------------- */
if (isset($_POST['rowOrder']) && isset($_POST['save-settings'])) {
    try {
        Management::columnsRedirection($_POST, $user['id']);
    } catch (\RedBeanPHP\RedException\SQL $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Error: ' . $e->getMessage(), 'danger');
    }
}
/* USER ACCOUNT SETTINGS ACTIONS CODE ------------------------------------------ */
if (isset($_POST['user-account-settings'])) {
    Management::accountSettings($_POST, $user['id']);
    $user = $_SESSION['userBean'];
}
/* UPDATE USER PASSWORD CODE ------------------------------------------ */
if (isset($_POST['update-user-password'])) {
    Management::updatePasswordForUsers($user['id'], $_POST);
    $timer = '<meta http-equiv="refresh" content="6;url=/sign-out">';
    _flashMessage('The password has been changed! Re-authorization required! You will be redirected to the login page. Wait!', 'danger');
}
function deleteModalRouteForm($createFormAction = '')
{ ?>
    <!--  модальное окно форма для удаления  -->
    <div class="modal" id="deleteModal" style="backdrop-filter: blur(15px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Заголовок модального окна -->
                <div class="modal-header">
                    <h5 class="modal-title text-danger">This Action is Irreversible!!!</h5>
                    <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
                </div>

                <!-- Содержимое модального окна -->
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label text-danger">Administrator password required!</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <input type="hidden" id="idForUse" name="idForUse">
                        </div>
                        <button type="submit" class="btn btn-outline-danger form-control">Delete Forever</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- вспомогательный элемент для создания новой записи в таблицах  -->
    <form method="post" action="<?= $createFormAction ?>" hidden id="create-form">
        <input type="hidden" name="create">
    </form>
    <?php
}

switch ($_GET['route-page'] ?? 1) {
    case 1:
        /* страница настройки вывода таблиц */
        include_once 'admin-panel/columns.php';
        break;
    case 2:
        /* страница редактирования и добавления данных по рут картам */
        include_once 'admin-panel/rout-act.php';
        break;
    case 3:
        /* страница редактированиея и добавления инструиента в систему */
        $settings = getUserSettings($user, TOOLS);
        include_once 'admin-panel/tools.php';
        break;
    case 4:
        /* страница вывода информации о пользователях и редактирование */
        include_once 'counterparties/users.php';
        break;
    case 5:
        /* старница вывода информации о проектах TODO */
        include_once 'admin-panel/projects.php';
        break;
    case 6:
        /* страница вывода orders TODO */
        include_once 'admin-panel/orders.php';
        break;
    case 7:
        /* страница вывода поиска по базе данных для данного кейса */
        include_once 'admin-panel/searching.php';
        break;
    case 8:
        /* страница настроек пользовательского аккаунта  */
        include_once 'admin-panel/profile.php';
        break;
    case 9:
        /* страница настроек пользовательского аккаунта  */
        include_once 'admin-panel/wh-types.php';
        break;
}