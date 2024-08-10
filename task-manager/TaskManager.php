<?php

class TaskManager
{
    private static function checkPostDataAndConvertToArray($post): array
    {
        $postDataArray = [];
        foreach ($post as $key => $item) {
            if (is_array($item)) {
                $postDataArray[$key] = self::checkPostDataAndConvertToArray($item);
            } else {
                $postDataArray[$key] = _E($item);
            }
        }
        return $postDataArray;
    }

    private static function compliteness($subTaskJson): int
    {
        $result = 0;
        // Декодируем JSON, чтобы получить массив подзадач
        $subtasks = json_decode($subTaskJson, true);
        // Проверяем, если есть подзадачи
        if (is_array($subtasks) && count($subtasks) > 0) {
            $totalTasks = count($subtasks); // Общее количество подзадач
            $completedTasks = 0; // Количество выполненных подзадач
            // Подсчитываем количество выполненных задач
            foreach ($subtasks as $subtask) {
                if (isset($subtask['done']) && $subtask['done'] === 'checked') {
                    $completedTasks++;
                }
            }
            // Рассчитываем процент завершенности
            $result = ($completedTasks / $totalTasks) * 100;
        }
        // Возвращаем результат как целое число
        return (int)round($result);
    }

    private static function getUsersForThisTask($emails, $user): string
    {
        if (!empty($emails)) {
            // Фильтруем массив, чтобы убрать пустые значения
            $emails = array_filter($emails, function ($email) {
                return !empty($email);
            });

            // Если после фильтрации массив пуст, используем email текущего пользователя
            if (empty($emails)) {
                $emails[] = $user['email'];
            }
        } else {
            // Если $emails пуст или не существует, используем email текущего пользователя
            $emails = [$user['email']];
        }

        // Преобразуем массив в строку, разделенную запятыми
        return implode(',', $emails);
    }

    private static function sendNotificationToMail($emails, $user, $task): array
    {
        require 'emails-body.php';
        // Преобразуем строку с email-ами в массив, разделяя по запятой
        $emails = array_map('trim', explode(',', $emails));

        $subject = 'NTI Group Task Manager';
        $res = [];

        // Отправка уведомления на каждый email в массиве
        foreach ($emails as $email) {
            if (Mailer::SendEmailNotification($email, $user['user_name'], $subject, emailTaskBody($task, SALT_PEPPER))) {
                $res[] = ['info' => $email . ' Successfully sent', 'color' => 'success'];
            } else {
                $res[] = ['info' => $email . ' Error while sending!', 'color' => 'danger'];
            }
        }

        return $res;
    }

    public static function createNewTask($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);

        try {
            $task = R::dispense(TASKS);
            $task->task_name = $post['task_name'];
            $task->task_description = $post['task_description'];
            $task->lists_id = $post['list_id'];
            $task->priority = $post['priority'];
            $task->deadline = $post['deadline'];
            $task->sub_tasks = $post['check_tasks']; // json string
            $task->complite = 0;
            $emails = $task->users = self::getUsersForThisTask($post['users'], $user);
            R::store($task);

            // sent notification to attached users
            $args = self::sendNotificationToMail($emails, $user, $task);

            //Query Executed and Task Inserted Successfully
            $args = ['info' => 'Task Added Successfully.', 'color' => 'success'];
        } catch (Exception $e) {
            //FAiled to Add TAsk
            $args = ['info' => 'Failed to Add Task ' . $e->getMessage(), 'color' => 'danger'];
        }
        return $args;
    }

    public static function updateTask($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        try {
            $task = R::load(TASKS, $post['task-id']);
            $task->task_name = $post['task_name'];
            $task->task_description = $post['task_description'];
            $task->lists_id = $post['list_id'];
            $task->priority = $post['priority'];
            $task->deadline = $post['deadline'];
            $task->sub_tasks = $post['check_tasks']; // json string
            $task->complite = self::compliteness($post['check_tasks'] ?? '{}');
            $task->archivated = isset($post['archivate']) ? 1 : 0;
            $emails = $task->users = self::getUsersForThisTask($post['users'], $user);

            R::store($task);

            // sent notification to attached users
            if (isset($post['send-email'])) // check if need send or not
                $args = self::sendNotificationToMail($emails, $user, $task);

            //Query Executed and Task Inserted Successfully
            $args[] = ['info' => 'Task Updated Successfully.', 'color' => 'success'];
        } catch (Exception $e) {
            //FAiled to Add TAsk
            $args[] = ['info' => 'Failed to Update Task ' . $e->getMessage(), 'color' => 'danger'];
        }
        return $args;
    }

    public static function createNewTasksList($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
        //Get the values from form and save it in variables
        $list_name = $_POST['list_name'];
        $list_description = $_POST['list_description'];
        try {
            $list = R::dispense(TASK_LIST);
            $list->list_name = $list_name;
            $list->list_description = $list_description;
            R::store($list);
            $args = ['info' => 'List Added Successfully', 'color' => 'success'];
            redirectTo('manage-list', $args);
        } catch (Exception $e) {
            $args = ['info' => 'Failed to Add List ' . $e->getMessage(), 'color' => 'danger'];
        }

        return $args;
    }

    public static function updateTasksList($post, $user): array
    {
        $post = self::checkPostDataAndConvertToArray($post);

        try {
            $list_id = $_POST['update'];
            $list_name = $_POST['list_name'];
            $list_description = $_POST['list_description'];
            $list = R::load(TASK_LIST, $list_id);
            $list->list_name = $list_name;
            $list->list_description = $list_description;
            R::store($list);
            $args = ['info' => 'List Updated Successfully', 'color' => 'success'];
            redirectTo('manage-list', $args);
        } catch (Exception $e) {
            $args = ['info' => 'Failed to Update List ' . $e->getMessage(), 'color' => 'danger'];
        }

        return $args;
    }

    public static function deleteTaskOrList($get, $post, $user): array
    {
        // delete task
        if (isset($get['task_id']) && isset($get['delete'])) {
            try {
                $task_id = $get['task_id'];
                R::trash(R::load(TASKS, $task_id));
                $args = ['info' => 'Task Deleted Successfully.', 'color' => 'success'];
            } catch (Exception $e) {
                $args = ['info' => 'Failed to Delete Task ' . $e->getMessage(), 'color' => 'danger'];
            }
        }

        // delete list
        if (isset($_GET['list_id']) && isset($_GET['delete'])) {
            //Get the list_id value from URL or Get Method
            $list_id = $_GET['list_id'];
            try {
                R::trash(R::load(TASK_LIST, $list_id));
                $args = ['info' => 'List Deleted Successfully', 'color' => 'success'];
            } catch (Exception $e) {
                $args = ['info' => 'Failed to Delete List ' . $e->getMessage(), 'color' => 'danger'];
            }
        }

        return $args ?? [null];
    }
}