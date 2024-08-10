<?php
function emailTaskBody($task, $salt)
{
    // Начинаем буферизацию вывода
    ob_start();
    $task_id = $task['id'];
    $task_name = $task['task_name'];
    $task_desc = $task['task_description'];
    $priority = $task['priority'];
    $deadline = $task['deadline'];
    /* Red *//* Yellow *//* Green */
    $colors = ['high' => '#ff4c4c', 'medium' => '#ffc107', 'low' => '#28a745'];
    $color = $colors[strtolower($priority)];
    ?>
    <h2 style="color: red">Warning! You must be logged in to change or view tasks!</h2>
    <div>
        <div style="color: white;  background-color:<?= $color; ?>; width: fit-content; padding: 10px">Task priority <?= $priority; ?></div>
        <div style="margin-bottom: 1rem">
            <h5 class="card-title">Task name: <?= $task_name; ?></h5>
            <div style="margin-bottom: 1rem">Description: <br> <?= $task_desc; ?></div>
            <?php
            $subTask = json_decode($task['sub_tasks'], true); // Используем $task вместо $row для sub_tasks
            if (!empty($subTask)) {
                foreach ($subTask as $k => $sTask) {
                    if (isset($sTask['done']) && $sTask['done'] == 'checked') {
                        ?>
                        <div style="display: flex; align-items: center;">
                            <div style="text-align:center; color: white; background: blue; width: 15px; height: 15px; border-radius: 10%; border: solid black 1px;">v</div>
                            &nbsp;
                            <div><?= $sTask['text']; ?></div>
                        </div>
                    <?php } else { ?>
                        <div style="display: flex; align-items: center;">
                            <div style="border: solid black 1px; width: 15px; height: 15px; border-radius: 10%;"></div>
                            &nbsp;
                            <div><?= $sTask['text']; ?></div>
                        </div>
                        <?php
                    }
                }
            } ?>
        </div>
        <div style="margin-bottom: 1rem">
            <div>Deadline: <?= $deadline; ?></div>
            <div class="progress">
                Completeness: <?= $task['complite'] ?? '0' ?>%
            </div>
        </div>
        <div>
            <a role="button" href="https://nti.icu/update-task?task_id=<?= $task_id; ?>&update=<?= $salt ?>">
                Update or Change task information
            </a>
        </div>
    </div>
    <?php
    // Получаем содержимое буфера и Возвращаем содержимое как строку
    return ob_get_clean();
}
