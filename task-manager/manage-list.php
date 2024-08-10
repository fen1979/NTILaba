<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require('TaskManager.php');
$user = $_SESSION['userBean'];
$page = 'task_manager';
$args = TaskManager::deleteTaskOrList($_GET, $_POST, $user);

// creation new list of tasks
if (isset($_POST['save-new'])) {
    $args = TaskManager::createNewTasksList($_POST, $user);
}

// task list information updating
if (isset($_POST['update'])) {
    $args = TaskManager::updateTasksList($_POST, $user);
}

$hide_new = isset($_GET['list_id']) && isset($_GET['update']) ? 'hidden' : null;
$list = isset($_GET['list_id']) && isset($_GET['update']) ? R::load(TASK_LIST, $_GET['list_id']) : null;
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
    <a role="button" class="btn btn-outline-success" href="/manage-list" <?= empty($hide_new) ? 'hidden' : ''; ?>>Add New Task List</a>

    <div class="row">
        <div class="col-8 p-2">
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

        <div class="col-4 p-4">
            <form method="POST" action="" <?= $hide_new ?? '' ?>>
                <h4>Create New Task List form</h4>

                <div class="mb-3">
                    <label for="list_name" class="form-label">List Name</label>
                    <input type="text" name="list_name" id="list_name" class="form-control" placeholder="Type list name here" required="required"/>
                </div>
                <div class="mb-3">
                    <label for="list_description" class="form-label">List Description:</label>
                    <textarea name="list_description" class="form-control" id="list_description" placeholder="Type List Description Here"></textarea>
                </div>

                <button type="submit" name="save-new" class="btn btn-outline-success form-control">Save new List</button>
            </form>


            <form method="POST" action="" <?= empty($hide_new) ? 'hidden' : ''; ?>>
                <h4>Update current information</h4>

                <div class="mb-3">
                    <label for="list-name" class="form-label">List Name</label>
                    <input type="text" name="list_name" id="list-name" placeholder="Type list name here" class="form-control"
                           value="<?= $list['list_name'] ?? '' ?>" required="required"/>
                </div>
                <div class="mb-3">
                    <label for="list-description" class="form-label">List Description:</label>
                    <textarea name="list_description" id="list-description" class="form-control"
                              placeholder="Type List Description Here"><?= trim(($list['list_description'] ?? '')) ?></textarea>
                </div>

                <button type="submit" name="update" class="btn btn-outline-success form-control" value="<?= $list['id'] ?? '' ?>">Update this List</button>
            </form>
        </div>
    </div>
</div>

<?php
footer();
ScriptContent($page);
?>
</body>
</html>