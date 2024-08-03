<?php
/* ------------------------- GLOBAL USE FUNCTIONS FOR ALL PAGES --------------------------- */
/**
 * Ensures the user is authenticated.
 *
 * This function checks if a specified value is present in the given array. If the value is not present,
 * the user is redirected to a specified page. This function should be included at the
 * beginning of any script that requires user authentication.
 *
 * Usage:
 * Include this function in your script and call it at the beginning to ensure
 * that only authenticated users can access the page.
 *
 * Parameters:
 * @param array $valueForCheck The array to check for the specified value.
 * @param string $valueName The key name of the value to check in the array.
 * @param array $role The key name of users role in application.
 * @param string $redirection The page to redirect to if the value is not present (default is '').
 *
 * Example:
 * <?php
 * require 'path/to/utility.php';
 * EnsureUserIsAuthenticated($_SESSION, 'userBean'); redirection by default to index.php
 * EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'warehouse'); redirection to some page with role checking
 * ?>
 *
 * @return void
 */
function EnsureUserIsAuthenticated(array $valueForCheck, string $valueName, array $role = null, string $redirection = ''): void
{
    if (!empty($role)) {
        if (!isUserRole($role) && !isset($valueForCheck[$valueName])) {
            redirectTo($redirection) and exit();
        }
    } else {
        if (!isset($valueForCheck[$valueName])) {
            redirectTo($redirection) and exit();
        }
    }
}

/**
 * Redirects to a specific URL.
 *
 * @param string $url The URL to redirect to. Defaults to the site root.
 */
function redirectTo(string $url = '', array $args = [null])
{
    $_SESSION['info'] = $args;
    header('Location: /' . $url);
    exit(); // Ensure no further code is executed
}


/**
 * SOME USER DATA FOR LOGS
 * @return string
 */
function getServerData(): string
{
    $out = '';
    $locationData = json_decode(file_get_contents("https://ipinfo.io/{$_SERVER['REMOTE_ADDR']}/json"), true);
    foreach ($locationData as $key => $value) {
        $out .= "$key : $value, ";
    }
    return $out;
}

/**
 * ВСТАВКА ЗНАЧЕНИЙ ИЗ ИНПУТОВ В ФОРМАХ НА СТРАНИЦАХ
 * @param $name
 * @param string $val
 * @return string
 * this function for any places use to return any getted value from POST or GET requests
 */
function set_value($name, string $val = ''): string
{
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }

    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    return $val;
}

/**
 * this function is check what is role of user and return bool
 * @param array $role
 * @return bool
 */
function isUserRole(array $role): bool
{
    if (!empty($role)) {
        foreach ($role as $v) {
            if (!empty($_SESSION['userBean'])) {
                if ($v == $_SESSION['userBean']['app_role']) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * ПРОВЕРКА ПАРОЛЯ МЕТОДАМИ ПХП
 * @param $password
 * @param bool $role
 * @param null $user
 * @return int
 * function for check superuser password for all pages
 */
function checkPassword($password, bool $role = false, $user = null): int
{
    if ($role) {
        $user = R::load(USERS, $user['id']);
    } else {
        $user = R::load(USERS, "1");
    }
    if ($user && password_verify($password, $user['user_hash'])) {
        return 1;
    }
    return 0;
}

/**
 * БЛОКИРОВКА СКРИПТОВ И ОПАСНОГО КОДА ИЗ ПОЛЕЙ ФОРМ НА СТРАНИЦАХ
 * @param $ts // some text or any field from Form
 * @return string // clear string or empty string
 */
function _E($ts): string
{
    // Удаление вредоносных скриптов и тегов через регулярные выражения
    $pattern = '/<script.*?>.*?<\/script>|javascript:[^\'"]*/is';
    $cleaned = preg_replace($pattern, '', $ts);

    // Проверка, были ли удалены опасные элементы; если нет, возвращаем исходный текст
    if ($cleaned === $ts) {
        return $ts; // Возврат исходного текста, если в нем нет вредоносных скриптов
    } else {
        // Преобразование опасных символов только в измененных частях
        return htmlentities($cleaned, ENT_QUOTES | ENT_IGNORE, "UTF-8");
    }
}

/**
 * РАНДОМИЗАТОР ГЕНЕРАТОР УНИКАЛЬНЫХ ЗНАЧЕНИЙ
 * @param string $someKey
 * @param int $num
 * @return string
 * @throws //\RedBeanPHP\RedException\SQL
 */
function unicum(string $someKey = "", int $num = 5): string
{
    /* ключ фраза из которой будет собиратся уникальный идентификатор */
    $ms = 'Mqw1ert2YUI3OPg4hjk5QWE6asd7fGH8JSX9cvb0NRT9yui8opA7lDF6KLz5xCV4Bnm3Z' . $someKey;
    $randkey = "";
    $u = true; // Используем булевское значение true
    /* цикл поиска коллизий (использован фреймворк Red Bean PHP для работы с БД) */
    /* создается таблица всех когда либо сгенерированных HASH для конкретного проекта */
    while ($u) {
        $randkey = '';
        /* создаем соль для фразы */
        $i = ($num * 2) + $num;
        /* набираем рандомально фразу уникального идентификатора */
        while ($i > 0) {
            $randkey .= $ms[(rand(1, strlen($ms) - 1))];
            $i--;
        }
        /* ищем в базе данных если есть коллизия, если нет то значение уникально */
        $existingHash = R::findOne(HASHES, 'uid = ?', [$randkey]);

        if ($existingHash) {
            // Запись уже существует
            $u = true;
        } else {
            // Записи не существует, создаем новую
            $newHash = R::dispense(HASHES);
            $newHash->uid = $randkey;
            R::store($newHash);
            $u = false;
        }
    }

    /* выводим значение для пользователя */
    return $randkey;
}

/**
 * вывод фото на главную страницу
 * если фото нет в БД то выводим заглушку
 * если установлена галерея то выводим набор фото
 * @param $projectId
 * @param $mode
 * @param bool $search
 * @return array|mixed|string|true|null
 */
function getProjectFrontPicture($projectId, $mode, bool $search = false)
{
    // mode = docs
    if ($mode == 'docs') {
        // если такого нет получаем последнее из списка
        $one = R::findOne(PROJECT_STEPS, "projects_id = ? ORDER BY step DESC LIMIT 1", [$projectId]);

        if ($one) {
            return $one["image"];
        } else {
            // если фото нет отдаем дефолтную заглушку
            return "public/images/ips.webp";
        }
    }

    // mode = image
    if ($mode == 'image') {
        // ищем все указанные как фронт пик и возвращаем массив
        $all = R::find(PROJECT_STEPS, "projects_id = ? AND front_pic = ?", [$projectId, 1]);

        // Преобразуем результат из объекта в массив
        $images = ($all) ? array_values($all) : [];

        // Если объектов нет, добавляем заглушку
        if (empty($images)) {
            // если вызов был не из поисковика то отдаем обьект
            $images[] = ($search) ? ["image" => "public/images/ips.webp"] : (object)["image" => "public/images/ips.webp"];
        }

        return $images;
    }
    return null;
}


/**
 * ЛОГИРОВАНИЕ ДЕЙСТВИЙ ПРОИСХОДЯЩИХ НА САЙТЕ
 * @param $userName
 * @param $action
 * @param $objectType
 * @param string $details
 * @return bool
 * @throws /\RedBeanPHP\RedException\SQL
 */
function logAction($userName, $action, $objectType, string $details = ''): bool
{
    // Создание объекта для взаимодействия с базой данных, если используется RedBeanPHP
    $log = R::dispense(LOGS);
    $log->date = date('Y-m-d H:i:s');
    // имя пользователя который делал акицю
    $log->user = $userName;
    // наименование произведенной акции
    $log->action = $action;
    // тип обьекта над которым была произведена акция
    $log->objectType = $objectType;
    // детали события
    $log->details = $details;

    if (R::store($log))
        return true;
    else
        return false;
}

/**
 * this function grab user settinga and return array if exist
 * @param $user
 * @param $args
 * @return mixed|null
 */
function getUserSettings($user, $args)
{
    /* настройки вывода от пользователя */
    if ($user) {
        foreach ($user['ownSettingsList'] as $item) {
            if (isset($item['table_name']) && $item['table_name'] == $args) {
                $settings = json_decode($item['setup']);
                break;
            }
        }
    }
    return !empty($settings) ? $settings : null;
}

/**
 * function return audio tag to some page where this needed
 * @param $sound
 * @return string
 */
function getNotificationSound($sound): string
{
    switch ($sound) {
        case '1':
            return '<audio id="notificationSound" src="public/sounds/sms.mp3"></audio>';
        case '2':
            return '<audio id="notificationSound" src="public/sounds/notify.mp3"></audio>';
    }
    return '';
}

/**
 * Checks if a directory is empty or not.
 *
 * This function checks whether the specified directory is empty (contains no files or subdirectories,
 * excluding '.' and '..'). It returns true if the directory contains at least one file or subdirectory,
 * and false otherwise. The function also checks if the directory exists and is readable to prevent errors.
 *
 * @param string $dirName Full path to the directory.
 * @return bool Returns true if the directory is not empty, false if it is empty or an error occurs.
 */
function isDirEmpty(string $dirName): bool
{
    // Проверяем, существует ли директория и доступна ли она для чтения
    if (!file_exists($dirName) || !is_dir($dirName) || !is_readable($dirName)) {
        return false; // Возвращаем false, если директория не существует или не доступна для чтения
    }

    $files = scandir($dirName);
    // Удаление '.' и '..' из списка файлов
    $files = array_diff($files, array('.', '..'));

    // Возвращаем true, если в директории есть файлы или папки, иначе false
    return count($files) > 0;
}

/**
 * Validates and retrieves the directory path from the URL query parameter 'pr_dir'.
 *
 * This function checks if the 'pr_dir' query parameter exists and starts with 'storage/projects/'.
 * If the parameter is valid, it returns the parameter value. If the parameter is invalid or missing,
 * it redirects the user to the '/order' page and terminates execution.
 * This function is designed to prevent file system path traversal attacks by validating the input path.
 *
 * @param array $params The array of URL query parameters.
 * @return string The directory path from the 'pr_dir' parameter.
 */
function _dirPath(array $params): string
{
    if (!isset($params['pr_dir'])) {
        redirectTo('order');
        exit();
    }

    // Clean and decode URL-encoded string to prevent directory traversal attacks
    $dir = urldecode($params['pr_dir']);
    $dir = str_replace(array('../', '..\\', './', '.\\'), '', $dir);

    // Check if the directory starts with 'storage/projects/' and does not contain illegal characters
    if (strpos($dir, 'storage/projects/') === 0 && !preg_match('/[^a-zA-Z0-9_\/\-]/', $dir)) {
        return $dir;
    } else {
        redirectTo('order');
        exit();
    }
}

function getRessource(string $request, bool $array = true, array $optionals = null): array
{

    return [];
}