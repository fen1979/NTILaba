<?php
// подключение Базы Данных МаринаДБ
require "rb-mysql.php";

if (!R::testConnection()) {
    exit ('No database connection');
}

// Установка параметров cookie для сессии
session_set_cookie_params(10800);
// Запуск сессии
session_start();

/* VIEW MODE FOR SITE DARK/LIGHT ================== */
$mode = 'data-bs-theme="' . ($_SESSION['userBean']['view_mode'] ?? 'light') . '"';
define("VIEW_MODE", $mode);

/*
 * Список игнорируемых путей и файлов
 * требуется для правильного возврата пользователя после логина
 * contains = если в путях содержится часть
 * exact = если путь точно как есть
 **/
const IGNORE_LIST = [
    ['type' => 'exact', 'value' => '/'],
    ['type' => 'exact', 'value' => '/sign-out'],
    ['type' => 'exact', 'value' => '/get_data'],
    ['type' => 'exact', 'value' => '/create_bom'],
    ['type' => 'exact', 'value' => '/is_change'],
    ['type' => 'exact', 'value' => '/6fef03d1aac6981d6c6eaa35fc9b46d1311b4b5425a305fc7da1b00c2'],
    ['type' => 'contains', 'value' => '/order_pdf'],
    ['type' => 'contains', 'value' => '.ico'],
    ['type' => 'contains', 'value' => '.css'],
    ['type' => 'contains', 'value' => 'storage/projects/']
    // Добавляйте сюда новые условия для игнорирования
];

/* ==================== SITE PUBLIC RESOURCES ================== */
require_once 'Resources.php';
require_once 'Utility.php';
require_once 'layout/PageLayout.php';
require_once 'libs/Converter.php';
include_once 'libs/Mailer.php';
require_once 'Undo.php';

/* route class */

class Routing
{
    private array $pages = array();

    /**
     * ДОБАВЛЕНИЕ НОВЫХ АДРЕСОВ ДЛЯ ПЕРЕГАПРАВЛЕНИЯ
     * @param $url
     * @param $path
     * @return void
     */
    public function addRout($url, $path)
    {
        $this->pages[$url] = $path;
    }

    /**
     * ПОЛУЧЕНИЕ АДРЕСА ДЛЯ ПЕРЕНАПРАВЛЕНИЯ
     * @return string
     */
    public function getUrl(): string
    {
        // call user action keeper
        self::UserActionKeeper($_SERVER['REQUEST_URI'] ?? null, $_SESSION['userBean']['id'] ?? null);

        $arr = explode('?', $_SERVER['REQUEST_URI']);
        if (empty($arr)) {
            return (count_chars($_SERVER['REQUEST_URI'], 1)[47] > 1) ?
                rtrim($_SERVER['REQUEST_URI'], '/') : $_SERVER['REQUEST_URI'];
        } else {
            return (count_chars($arr[0], 1)[47] > 1) ?
                rtrim($arr[0], '/') : $arr[0];
        }
    }

    /**
     * ПЕРЕНАПРВЛЕНИЕ МЕЖДУ СТРАНИЦАМИ
     * @param $url
     * @return void
     */

    public function route($url)
    {
        $path = $this->pages[$url];

        if (empty($path)) {
            require 'public/404.php';
            die();
        }

        if ($url == '/sign-out') {
            $this->logOut();
        }

        $fileDir = $path;
        if (file_exists($fileDir)) {
            require $fileDir;
        } else {
            require 'public/404.php';
            die();
        }
    }

    /**
     * ВЫХОД ПОЛЬЗОВАТЕЛЕЙ ИЗ СИСТЕМЫ
     * @return void
     */
    private function logOut()
    {
        $user = $_SESSION['userBean'];
        $details = 'User named: ' . $user['user_name'] . ', Sign out successfully in: ' . date('Y/m/d') . ' at ' . date('h:i');
        $details .= '<br>' . getServerData();
        if (logAction($user->user_name, 'LOGOUT', OBJECT_TYPE[11], $details)) {

            // clear session stepsData
            $_SESSION = array();

            // erase the session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            // session destroing
            session_destroy();
            redirectTo();
            exit();
        }
    }

    /**
     * СОХРАНЕНИЕ ПОСЛЕДНЕГО ДЕЙСТВИЯ ПОЛЬЗОВАТЕЛЯ
     * @param $uri
     * @param $user_id
     * @return void
     */
    private function UserActionKeeper($uri, $user_id): void
    {
        // Проверка игнорируемых путей
        foreach (IGNORE_LIST as $ignore) {
            if ($ignore['type'] == 'contains' && strpos($uri, $ignore['value']) !== false) {
                return;
            } elseif ($ignore['type'] == 'exact' && $uri === $ignore['value']) {
                return;
            }
        }

        // Проверка наличия URI в списке роутов
        if (!$this->containsAnyRoute($uri)) {
            return;  // Если URI не соответствует ни одному из роутов, игнорируем его
        }

        if ($uri && $user_id) {
            R::exec("UPDATE users SET last_action = ? WHERE id = ?", [trim($uri), (int)trim($user_id)]);
        }
    }

    /**
     * Проверяет, содержится ли хотя бы один из адресов из $this->pages в данной строке URL.
     * @param string $uri Полный URL для проверки.
     * @return bool Возвращает true, если совпадение найдено, иначе false.
     */
    private function containsAnyRoute(string $uri): bool
    {
        foreach ($this->pages as $route => $path) {
            if (strpos($uri, $route) !== false) {
                return true;
            }
        }
        return false;
    }
}
