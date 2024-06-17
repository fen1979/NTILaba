<?php
// подключение Базы Данных МаринаДБ
require "rb-mysql.php";
// database name = !!!-> nti_production <-!!!
R::setup('mysql:host=localhost;dbname=db_name', 'root', 'root');
// R::freeze( true ); /* тут выключение режима заморозки */

if (!R::testConnection()) {
    exit ('No database connection');
}

// настройки в файле php.ini
// Установка времени жизни куки 3 часа = 10800 секунд
//ini_set('session.cookie_lifetime',  10800);
// Установка времени жизни данных сессии 3 часа = 10800 секунд
//ini_set('session.gc_maxlifetime', 10800);
// Установка вероятности запуска сборщика мусора
//ini_set('session.gc_probability', 1);
// С вероятностью 1% при каждом запуске сессии
//ini_set('session.gc_divisor', 100);

// Установка параметров cookie для сессии
session_set_cookie_params(10800);
// Запуск сессии
session_start();

/* VIEW MODE FOR SITE DARK/LIGHT ================== */
$mode = 'data-bs-theme="' . ($_SESSION['userBean']['view_mode'] ?? 'light') . '"';
define("VIEW_MODE", $mode);

/* ==================== SITE PUBLIC RESOURCES ================== */
require_once 'Resources.php';
require_once 'Utility.php';
if (empty($anonimus)) {
    require_once 'layout/PageLayout.php';
    require_once 'libs/Converter.php';
    include_once 'libs/Mailer.php';
    require_once 'Undo.php';
}

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
            header('Location: /');
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

        // Игнорировать запросы favicon.ico
//        if (strpos($uri, 'favicon.ico') !== false || strpos($uri, 'storage/projects/') !== false
//            || $uri == '/' || $uri == '/sign-out' || strpos($uri, '.css') !== false) {
//            return;
//        }

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
