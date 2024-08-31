<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'task-manager/TaskManager.php';
$page = 'task_manager';
$preview_archive = false;
// preview all tasks
$task = R::findAll(TASKS);
// preview tasks by list
if (isset($_GET['list_id'])) {
    $task = R::findAll(TASKS, 'lists_id = ?', [_E($_GET['list_id'])]);
}
// tasks archivated erlier
if (isset($_GET['archive'])) {
    $task = R::findAll(TASKS, 'archivated = 1');
    $preview_archive = true;
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
NavBarContent(['title' => 'Task Manager', 'user' => $user, 'page_name' => $page]); ?>

<div class="wrapper mt-3">
    <!-- Menu Starts Here -->
    <div class="menu">
        <a role="button" class="btn btn-outline-success" href="/add-task">Add New Task</a>
        <a role="button" class="btn btn-outline-primary" href="/manage-list">Manage Lists</a>
        <a role="button" class="btn btn-outline-dark" href="/task_list?archive">Archive</a>

        <div class="btn-group">
            <button type="button" id="dp-btn" class="btn btn-outline-proma dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <?= (isset($_GET['list_id'])) ?
                    // Отображаем имя списка
                    'Task List: ' . htmlspecialchars(R::load(TASK_LIST, $_GET['list_id'])->list_name ?? '')
                    // По умолчанию
                    : "All Tasks"; ?>
            </button>

            <ul class="dropdown-menu">
                <a class="dropdown-item" href="/task_list">All Tasks</a>
                <?php $res = R::find(TASK_LIST);
                // Отображаем списки в меню
                if ($res) {
                    foreach ($res as $list) { ?>
                        <li><a class="dropdown-item" href="/task_list?list_id=<?= $list['id']; ?>"><?= htmlspecialchars($list['list_name']); ?></a></li>
                        <?php
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <!-- Menu Ends Here -->

    <!-- Tasks Starts Here -->
    <div class="container-fluid mt-3">
        <?php if ($task) {
            //Data is in Database
            $i = 0; // Счетчик для отслеживания числа карточек в ряду
            foreach ($task as $row) {
                if ($row['archivated'] == 0 || $preview_archive) {
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
                            </div>
                        </div>
                    </div>
                    <?php
                    $i++; // Увеличение счетчика
                }
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
// footer and scripts
PAGE_FOOTER(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.in("click", ".routing", function (event) {
            // Получаем task_id из data-атрибута
            let task_id = event.target.closest(".routing").dataset.taskId;
            // Формируем URL и перенаправляем пользователя на новый URL
            window.location.href = "/update-task?task_id=" + task_id + "&update";
        });
    });
</script>
</body>
</html>