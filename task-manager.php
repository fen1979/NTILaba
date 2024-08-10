<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'task-manager/TaskManager.php';
$user = $_SESSION['userBean'];
$page = 'task_manager';
$args = TaskManager::deleteTaskOrList($_GET, $_POST, $user);

$task = R::findAll(TASKS);
if (isset($_GET['list_id'])) {
    $task = R::findAll(TASKS, 'lists_id = ?', [_E($_GET['list_id'])]);
}
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
<div class="wrapper mt-3">
    <!-- Menu Starts Here -->
    <div class="menu">
        <a role="button" class="btn btn-outline-proma" href="/task_list">All Tasks</a>
        <?php
        $res = R::findAll(TASK_LIST);
        //CHeck whether the query executed or not
        if ($res) {
            //Display the lists in menu
            foreach ($res as $row) {
                $list_id = $row['id'];
                $list_name = $row['list_name'];
                ?>
                <a role="button" class="btn btn-outline-proma" href="/task_list?list_id=<?= $list_id; ?>"><?= $list_name; ?></a>
                <?php
            }
        }
        ?>
        <a role="button" class="btn btn-outline-primary" href="/manage-list">Manage Lists</a>
        <a role="button" class="btn btn-outline-success" href="/add-task">Add Task</a>
    </div>
    <!-- Menu Ends Here -->

    <!-- Tasks Starts Here -->
    <div class="container-fluid mt-3">
        <?php if ($task) {
            //Data is in Database
            $i = 0; // Счетчик для отслеживания числа карточек в ряду
            foreach ($task as $row) {
                $task_id = $row['id'];
                $task_name = $row['task_name'];
                $task_desc = $row['task_description'];
                $priority = $row['priority'];
                $deadline = $row['deadline'];

                // Начало нового ряда при необходимости
                if ($i % 5 == 0) {
                    if ($i > 0) {
                        echo '</div>'; // Закрытие предыдущего ряда
                    }
                    echo '<div class="row">'; // Начало нового ряда
                }
                ?>
                <div class="routing col" data-task-id="<?= $task_id; ?>">
                    <div class="card border-success mb-3" style="max-width: 16rem;">
                        <div class="card-header border-success <?= strtolower($priority); ?>-priority">Task priority <?= $priority; ?></div>
                        <div class="card-body">
                            <h5 class="card-title"><?= $task_name; ?></h5>
                            <p class="card-text"><?= $task_desc; ?></p>
                            <?php
                            $subTask = json_decode($row['sub_tasks'], true); // true для возврата ассоциативного массива
                            if (!empty($subTask)) {
                                foreach ($subTask as $k => $sTask) {
                                    $checked = isset($sTask['done']) && $sTask['done'] == 'checked' ? 'checked' : '';
                                    $t_id = 'sub-task-' . $task_id . '-' . $k;
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="<?= $t_id ?>" <?= $checked ?> disabled>
                                        <label class="form-check-label" for="<?= $t_id ?>">
                                            <?= $sTask['text']; ?>
                                        </label>
                                    </div>
                                <?php }
                            } ?>
                        </div>
                        <div class="card-footer bg-transparent border-success">
                            <p>Deadline: <?= $deadline; ?></p>
                            Completeness
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                     style="width: <?= $row['complite'] ?? '0' ?>%;" aria-valuenow="<?= $row['complite'] ?? '0' ?>"></div>
                            </div>
                            <div class="mt-2 text-end">
                                <a role="button" class="btn btn-sm btn-outline-warning" href="/update-task?task_id=<?= $task_id; ?>&update">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (isUserRole([ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                                    <a role="button" class="btn btn-sm btn-outline-danger" href="/task_list?task_id=<?= $task_id; ?>&delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $i++; // Увеличение счетчика
            }
            echo '</div>'; // Закрытие последнего ряда
        } else {
            //No data in Database
            ?>
            <h4>No Task Added Yet.</h4>
            <?php
        }
        ?>
    </div>

    <!-- Tasks Ends Here -->
</div>

<?php
footer();
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.in("click", ".routing", function (event) {
            //alert(event.target.closest(".routing").dataset.taskId)
        });
    });
</script>
</body>
</html>