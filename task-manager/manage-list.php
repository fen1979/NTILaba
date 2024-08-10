<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require('TaskManager.php');
$user = $_SESSION['userBean'];
$page = 'task_manager';
$args = TaskManager::deleteTaskOrList($_GET, $_POST, $user);
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
</head>
<body>
<?php
// NAVIGATION BAR
$navBarData['title'] = 'Task Manager';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid mt-3">
    <a role="button" class="btn btn-outline-success" href="/manage-list">Add List</a>

    <div class="row">
        <div class="col-8">
            <table>
                <thead>
                <tr>
                    <th>S.N.</th>
                    <th>List Name</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php $res = R::findAll(TASK_LIST);
                //Check whether there is data in database of not
                if ($res) {
                    //There's data in database' Display in table
                    foreach ($res as $row) {
                        //Getting the data from database
                        $list_id = $row['id'];
                        $list_name = $row['list_name'];
                        ?>
                        <tr>
                            <td><?= $list_id; ?>.</td>
                            <td><?= $list_name; ?></td>
                            <td>
                                <a role="button" class="btn btn-outline-warning" href="manage-list?list_id=<?= $list_id; ?>&update">Update</a>
                                <?php if (isUserRole([ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                                    <a role="button" class="btn btn-outline-danger" href="manage-list?list_id=<?= $list_id; ?>&delete">Delete</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="3">No List Added Yet.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="col-4">
            <?php include_once 'add-list.php' ?>
        </div>
    </div>
</div>

<?php
footer();
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {

    });
</script>
</body>
</html>