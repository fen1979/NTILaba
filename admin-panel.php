<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
/* class для работы с таблицами */
require 'admin-panel/Management.php';

$page = 'admin-panel';
/* переменные для общего пользования */
$user = $_SESSION['userBean'];
$role = $user['app_role'];
/* удаление rout card/user/tools и всех его данных ---------------------------- */
if (isset($_POST['idForUse']) && isset($_POST['password'])) {
    $args = Management::deletingAnItem($_POST, $user);
}
/* ROUT ACTIONS CODE ---------------------------------------------------------- */
if (isset($_POST['rout-action-saving']) || isset($_POST['rout-action-editing'])) {
    $args = Management::createUpdateRoutAction($_POST, $user);
}
/* WAREHOUSE ACTIONS CODE ---------------------------------------------------------- */
if (isset($_POST['wh-action-saving']) || isset($_POST['wh-action-editing'])) {
    $args = Management::createUpdateWarehouseType($_POST, $user);
}
/* USERS ACTIONS CODE --------------------------------------------------------- */
if (isset($_POST['update-user-data']) || isset($_POST['add-new-user'])) {
    $args = Management::addOrUpdateUsersData($_POST, $user);
}
/* TOOLS ACTIONS CODE --------------------------------------------------------- */
if (isset($_POST['tools-saving']) || isset($_POST['tools-editing'])) {
    $args = Management::createUpdateTools($_POST, $_FILES['imageFile'], $user);
}
if(isset($_POST['import-from-csv-file']) && isset($_FILES['csvFile'])){
    $args = Management::importToolsListByCsvFile($_POST, $_FILES, $user);
}
/* TABLE COLUMNS ACTIONS CODE ------------------------------------------------- */
if (isset($_POST['rowOrder']) && isset($_POST['save-settings'])) {
    $args = Management::columnsRedirection($_POST, $user['id']);
}
/* USER ACCOUNT SETTINGS ACTIONS CODE ------------------------------------------ */
if (isset($_POST['user-account-settings'])) {
    $args = Management::accountSettings($_POST, $user['id']);
    $user = $_SESSION['userBean'];
}
/* UPDATE USER PASSWORD CODE ------------------------------------------ */
if (isset($_POST['update-user-password'])) {
    $args = Management::updatePasswordForUsers($user['id'], $_POST);
    $timer = '<meta http-equiv="refresh" content="6;url=/sign-out">';
    $args[] = ['info' => 'The password has been changed! Re-authorization required! You will be redirected to the login page. Wait!', 'color' => 'danger'];
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