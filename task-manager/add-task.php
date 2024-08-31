<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'TaskManager.php';
$page = 'task_manager';

//Check whether the SAVE button is clicked or not
if (isset($_POST['submit'])) {
    $args = TaskManager::createNewTask($_POST, $user);
    redirectTo('task_list', $args);
}
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page); ?>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Add Task', 'user' => $user, 'page_name' => $page]); ?>

<div class="wrapper mt-3">
    <form method="POST" action="">
        <div class="row mb-3">
            <div class="col">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" class="form-control" id="priority" required>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
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
                            <option value="<?= $list_id ?>"><?= $list_name; ?></option>
                            <?php
                        }
                    } else {
                        //Display None as option
                        ?>
                        <option value="0">None</option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="task-name" class="form-label">Task Name</label>
            <input type="text" name="task_name" id="task-name" class="form-control" placeholder="Type your Task Name" required/>
        </div>

        <div class="mb-3">
            <label for="task-description" class="form-label">Task Description</label>
            <textarea name="task_description" id="task-description" class="form-control" placeholder="Type Task Description" required></textarea>
        </div>

        <div class="row mb-3">
            <div class="col">
                <div class="dropdown">
                    <label class="form-label" for="view-choose">
                        Choose Users for this task
                    </label>
                    <input type="text" id="view-choose" class="form-control dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" readonly>
                    <ul class="dropdown-menu p-2">
                        <?php
                        foreach (R::find(USERS) as $key => $u) {
                            if ($u['id'] != '1') { ?>
                                <li class="form-check fs-4">
                                    <input type="checkbox" id="u-<?= $key; ?>" name="users[]" value="<?= $u['email']; ?>"
                                           class="form-check-input">
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
                <input type="date" name="deadline" id="deadline" class="form-control" required/>
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
            <ol id="task-list" class="fs-5 sunset rounded"></ol> <!-- Используем OL для нумерации -->
        </div>

        <button type="submit" name="submit" class="btn btn-outline-success form-control">Save Task</button>
        <input type="hidden" name="check_tasks" id="check_tasks"/>
    </form>
</div>

<?php PAGE_FOOTER($page, false); ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const addTaskBtn = document.getElementById('add-task-btn');
        const taskInput = document.getElementById('task-input');
        const taskList = document.getElementById('task-list');
        const checkTasksInput = document.getElementById('check_tasks');

        let taskCount = 0;
        const tasks = {};

        function addTask() {
            const taskText = taskInput.value.trim();
            if (taskText !== "") {
                taskCount++;
                tasks[taskCount] = {
                    text: taskText,
                    done: ""
                };

                const listItem = document.createElement('li');
                listItem.classList.add("border-bottom", "p-2");
                listItem.innerHTML = `
                <input type="checkbox" class="form-check-input" id="task-${taskCount}">
                <label for="task-${taskCount}">${taskText}</label>
                <i type="button" class="bi bi-x-square text-danger remove-task-btn"></i>
            `;
                listItem.setAttribute('data-task-id', taskCount); // Добавляем идентификатор задачи
                taskList.appendChild(listItem);

                taskInput.value = "";
                checkTasksInput.value = JSON.stringify(tasks);

                // Удаление задачи из списка и JSON при клике на "Remove"
                listItem.querySelector('.remove-task-btn').addEventListener('click', function () {
                    const taskId = listItem.getAttribute('data-task-id'); // Получаем идентификатор задачи
                    delete tasks[taskId]; // Удаляем задачу из массива
                    taskList.removeChild(listItem); // Удаляем задачу из DOM
                    checkTasksInput.value = JSON.stringify(tasks); // Обновляем JSON в скрытом поле
                });
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




































