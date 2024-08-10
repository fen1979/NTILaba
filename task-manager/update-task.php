<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'TaskManager.php';
$user = $_SESSION['userBean'];
$page = 'task_manager';
$task = null;
$args = TaskManager::deleteTaskOrList($_GET, $_POST, $user);

// update task information or status
if (isset($_POST['task-id']) && isset($_POST['update'])) {
    $args = TaskManager::updateTask($_POST, $user);
    redirectTo('task_list', $args);
}

// preview task before updateing
if (isset($_GET['task_id']) && isset($_GET['update'])) {
    $task = R::load(TASKS, $_GET['task_id']);
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
    <form method="POST" action="">
        <div class="row">
            <div class="col-9"></div>
            <div class="col-3 fs-4">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="archivate" name="archivate" value="1">
                    <label class="form-check-label" for="archivate">Switch for Archivate Task</label>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" class="form-control" id="priority" required>
                    <?php foreach (['High', 'Medium', 'Low'] as $v) { ?>
                        <option value="<?= $v ?>" <?= ($v == $task['proirity']) ? 'selected' : '' ?>><?= $v ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="col">
                <label for="list-select" class="form-label">Select Task List</label>
                <select name="list_id" class="form-control" id="list-select" required>
                    <?php $res = R::findAll(TASK_LIST);
                    if ($res) {
                        //display all lists on dropdown from database
                        foreach ($res as $row) {
                            $list_id = $row['id'];
                            $list_name = $row['list_name'];
                            ?>
                            <option value="<?= $list_id ?>" <?= ($list_id == $task['lists_id']) ? 'selected' : '' ?>><?= $list_name; ?></option>
                            <?php
                        }
                    } ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="task-name" class="form-label">Task Name</label>
            <input type="text" name="task_name" id="task-name" class="form-control" placeholder="Type your Task Name"
                   value="<?= $task['task_name'] ?? '' ?>" required/>
        </div>

        <div class="mb-3">
            <label for="task-description" class="form-label">Task Description</label>
            <textarea name="task_description" id="task-description" class="form-control"
                      placeholder="Type Task Description" required><?= trim($task['task_description'] ?? '') ?></textarea>
        </div>

        <div class="row mb-3">
            <div class="col">
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input" type="checkbox" role="switch" id="send-email" name="send-email" value="1" checked>
                    <label class="form-check-label" for="send-email">Turn it off if you don't need to send emails.</label>
                </div>

                <div class="dropdown">
                    <input type="text" id="view-choose" class="form-control dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                           placeholder="Choose Users for this task" readonly>
                    <ul class="dropdown-menu p-2">
                        <?php
                        $e = explode(',', $task['users']);
                        foreach (R::find(USERS) as $key => $u) {
                            if ($u['id'] != '1') { ?>
                                <li class="form-check fs-4">
                                    <input type="checkbox" id="u-<?= $key; ?>" name="users[]" value="<?= $u['email']; ?>"
                                           class="form-check-input" <?= in_array($u['email'], $e) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="u-<?= $key; ?>"><?= $u['user_name']; ?></label>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="col">
                <label for="deadline" class="form-label">Deadline</label>
                <input type="date" name="deadline" id="deadline" class="form-control" value="<?= $task['deadline'] ?? '' ?>" required/>
            </div>
        </div>

        <div class="mb-3" id="task-input-row">
            <!-- Поле для ввода подзадачи и кнопка добавления -->
            <label for="task-input" class="form-label">Write a subtask position</label>
            <div style="display: flex;">
                <input type="text" id="task-input" placeholder="Enter subtask text" class="form-control me-3"/>
                <button type="button" id="add-task-btn" class="btn btn-outline-secondary">Add a subtask</button>
            </div>
        </div>

        <div class="mb-3">
            <h4 id="sub-task-list">A subtasks list</h4>
            <ol id="task-list" class="fs-5 sunset rounded">
                <!-- Используем OL для нумерации -->
            </ol>
        </div>

        <input type="hidden" name="check_tasks" id="check_tasks"/>
        <input type="hidden" name="task-id" value="<?= $task['id'] ?>"/>

        <button type="submit" name="update" class="btn btn-outline-success form-control mb-3">Update Task</button>

        <?php if (isUserRole([ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
            <a role="button" class="btn btn-sm btn-outline-danger" href="/update-task?task_id=<?= $task_id; ?>&delete">
                <i class="bi bi-trash"></i> &nbsp; Delete Task
            </a>
        <?php } ?>
    </form>
</div>

<?php ScriptContent(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const db = <?= json_encode(json_decode($task['sub_tasks'], true)) ?>;
        const addTaskBtn = document.getElementById('add-task-btn');
        const taskInput = document.getElementById('task-input');
        const taskList = document.getElementById('task-list');
        const checkTasksInput = document.getElementById('check_tasks');

        let taskCount = 0;
        let tasks = (db !== null) ? db : {};

        // Функция для обновления скрытого поля JSON
        function updateHiddenField() {
            checkTasksInput.value = JSON.stringify(tasks);
        }

        // Функция для добавления задачи в список
        function addTaskToList(taskId, taskText, taskDone) {
            const listItem = document.createElement('li');
            listItem.classList.add("border-bottom", "p-2");
            listItem.innerHTML = `
            <input type="checkbox" class="form-check-input" id="task-${taskId}" ${taskDone ? 'checked' : ''}>
            <label for="task-${taskId}">${taskText}</label>
            <i type="button" class="bi bi-x-square text-danger remove-task-btn"></i>
        `;
            listItem.setAttribute('data-task-id', taskId);
            taskList.appendChild(listItem);

            // Обработка изменения состояния чекбокса
            listItem.querySelector('input[type="checkbox"]').addEventListener('change', function () {
                tasks[taskId].done = this.checked ? 'checked' : '';
                updateHiddenField();
            });

            // Удаление задачи из списка и JSON при клике на "Remove"
            listItem.querySelector('.remove-task-btn').addEventListener('click', function () {
                delete tasks[taskId];
                taskList.removeChild(listItem);
                updateHiddenField();
            });
        }

        // Загрузка задач из JSON, если они есть
        if (tasks) {
            for (const [taskId, task] of Object.entries(tasks)) {
                addTaskToList(taskId, task.text, task.done === 'checked');
                taskCount = Math.max(taskCount, parseInt(taskId));
            }
            updateHiddenField();
        }

        function addTask() {
            const taskText = taskInput.value.trim();
            if (taskText !== "") {
                taskCount++;
                tasks[taskCount] = {
                    text: taskText,
                    done: ""
                };

                addTaskToList(taskCount, taskText, false);
                taskInput.value = "";
                updateHiddenField();
            }
        }

        // Событие для кнопки "Добавить подзадачу"
        addTaskBtn.addEventListener('click', addTask);

        // Событие для нажатия клавиши "Enter" в поле ввода подзадачи
        taskInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Предотвращаем стандартное поведение (например, отправку формы)
                addTask();
            }
        });

        // выбор чекбоксов с пользователями
        const checkboxes = document.querySelectorAll('input[name="users[]"]');
        const viewChooseInput = document.getElementById('view-choose');

        // Функция для обновления поля ввода с выбранными пользователями
        function updateViewChooseInput() {
            const selectedUsers = [];
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedUsers.push(checkbox.value);
                }
            });
            viewChooseInput.value = selectedUsers.join(', ');
        }

        // Добавляем обработчик событий для всех чекбоксов
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateViewChooseInput);
        });

        // Инициализируем поле ввода с уже выбранными значениями (если нужно)
        updateViewChooseInput();
    });

</script>
</body>
</html>