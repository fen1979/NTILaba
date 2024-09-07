<?php
//class_alias('RequestData', 'RD');

class RequestData
{
    private static ?RequestData $instance = null;
    private array $post;
    private array $get;
    private array $files;
    private array $request;
    private array $cookie;
    private array $server;
    private array $session;
    private array $headers;

    private function __construct()
    {
        $this->post = $this->processData($_POST);
        $this->get = $this->processData($_GET);
        $this->files = $this->processFiles($_FILES);
        $this->request = $this->processData($_REQUEST);
        $this->cookie = $this->processData($_COOKIE);
        $this->server = $_SERVER;
        $this->session = $this->processData($_SESSION ?? []);
        $this->headers = $this->getRequestHeaders();
    }

    public static function getInstance(): ?RequestData
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Recursively processes and sanitizes POST data, converting it into an array.
     *
     * This function takes an associative array, typically the $_POST array, and
     * recursively sanitizes each element to ensure it is safe for further processing.
     * If an element is an array, the function is applied recursively to sanitize
     * all nested elements. Non-array elements are sanitized using the _E() function.
     *
     * @param array $data
     * @return array The sanitized array, where each element has been processed to
     *               ensure it is safe. Nested arrays are also recursively sanitized.
     */
    private function processData(array $data): array
    {
        $processedData = [];
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $processedData[$key] = self::processData($item);
            } else {
                $processedData[$key] = self::_E($item);
            }
        }
        return $processedData;
    }

    private function processFiles($files): array
    {
        return $files;
    }

    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * БЛОКИРОВКА СКРИПТОВ И ОПАСНОГО КОДА ИЗ ПОЛЕЙ ФОРМ НА СТРАНИЦАХ
     * @param $ts // some text or any field from Form
     * @return string // clear string or empty string
     */
    private function _E($ts): string
    {
        // Удаление вредоносных скриптов и тегов через регулярные выражения
        $pattern = '/<script.*?>.*?<\/script>|javascript:[^\'"]*/is';
        $cleaned = preg_replace($pattern, '', $ts);

        // Преобразование опасных символов только в измененных частях
        return htmlentities($cleaned, ENT_QUOTES | ENT_IGNORE, "UTF-8");
    }

    public function getPost(): array
    {
        return $this->post;
    }

    public function getGet(): array
    {
        return $this->get;
    }

    public function getFiles(): array
    {
        $processedFiles = [];

        // Проверяем, есть ли файлы
        if (empty($this->files)) {
            return ['error' => 'No files uploaded.'];
        }

        // Проходим по каждому файлу
        foreach ($this->files as $key => $file) {
            // Если это массив с множеством файлов
            if (is_array($file['name'])) {
                foreach ($file['name'] as $index => $fileName) {
                    $error = $file['error'][$index];

                    // Обрабатываем ошибки загрузки
                    $result = $this->processFileError($error);
                    if ($result !== true) {
                        $processedFiles[$key][$index] = ['error' => $result];
                    } else {
                        $processedFiles[$key][$index] = [
                            'name' => $file['name'][$index],
                            'type' => $file['type'][$index],
                            'tmp_name' => $file['tmp_name'][$index],
                            'size' => $file['size'][$index],
                        ];
                    }
                }
            } else {
                // Обрабатываем единичный файл
                $error = $file['error'];

                // Обрабатываем ошибки загрузки
                $result = $this->processFileError($error);
                if ($result !== true) {
                    $processedFiles[$key] = ['error' => $result];
                } else {
                    $processedFiles[$key] = [
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'tmp_name' => $file['tmp_name'],
                        'size' => $file['size'],
                    ];
                }
            }
        }

        return $processedFiles;
    }

    /**
     * Обрабатывает код ошибки загрузки файла и возвращает описание ошибки
     *
     * @param int $errorCode Код ошибки
     * @return bool|string Возвращает true, если ошибок нет, или сообщение об ошибке
     */
    private function processFileError(int $errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds maximum upload size.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown error occurred.';
        }
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getCookie(): array
    {
        return $this->cookie;
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getSession(): array
    {
        return $this->session;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Проверяет наличие указанного триггерного ключа в массиве POST и вызывает callback функцию с очищенными данными.
     *
     * Функция проверяет массив POST на наличие одного триггерного ключа (например, кнопки submit).
     * Если триггерный ключ найден в массиве POST (независимо от его значения), очищенные данные POST
     * передаются в callback функцию, которая выполняется для дальнейшей обработки.
     *
     * @param $keys
     * @param callable $callback Функция обратного вызова, которая будет вызвана с аргументом $postData, содержащим
     *                           очищенные данные POST, если триггер найден.
     * @return void
     *
     * @example
     * // Пример использования:
     * $requestData = RequestData::getInstance();
     * $requestData->checkPostRequestAndExecute('createOrder', function($postData) use ($user) {
     *     $project = R::load(PROJECTS, _E($postData['project_id']));
     *     $client = R::load(CLIENTS, _E($postData['customer_id']));
     *
     *     // Вызов функции для создания заказа
     *     Orders::createOrder($user, $client, $project, $postData);
     * });
     *
     *
     * Пример общего использования:
     *
     * $requestData->checkPostRequestAndExecute('some_trigger', function($cleanedData) {
     * // Вызов функции с очищенными данными POST
     * someOtherFunction($cleanedData);
     * });
     */


    public function checkPostRequestAndExecute($keys, callable $callback): void
    {
        $postData = $this->getPost(); // получаем очищенные POST данные
        $keys = is_array($keys) ? $keys : [$keys]; // Преобразуем одиночный ключ в массив, если это не массив

        $allKeysExist = true; // Флаг для проверки всех ключей

        // Проверяем наличие всех ключей в POST
        foreach ($keys as $key) {
            if (!isset($postData[$key])) { // Используем isset для проверки наличия каждого ключа
                $allKeysExist = false; // Если хотя бы один ключ не существует, сбрасываем флаг
                break;
            }
        }

        // Если все ключи существуют, вызываем callback и передаем очищенные данные
        if ($allKeysExist) {
            call_user_func($callback, $postData);
        }
    }


    /**
     * Проверяет наличие указанного триггерного ключа в массиве GET и вызывает callback функцию с очищенными данными.
     *
     * Функция проверяет массив POST на наличие одного триггерного ключа (например, кнопки submit).
     * Если триггерный ключ найден в массиве GET (независимо от его значения), очищенные данные GET
     * передаются в callback функцию, которая выполняется для дальнейшей обработки.
     *
     * @param $keys - Ключ-триггер (например, имя кнопки submit), который нужно искать в массиве GET.
     * @param callable $callback Функция обратного вызова, которая будет вызвана с аргументом $getData, содержащим
     *                           очищенные данные POST, если триггер найден.
     * @return void
     *
     * @example
     * // Пример использования:
     * $requestData = RequestData::getInstance();
     * $requestData->checkGetRequestAndExecute('createOrder', function($getData) use ($user) {
     *     $project = R::load(PROJECTS, _E($getData['project_id']));
     *     $client = R::load(CLIENTS, _E($getData['customer_id']));
     *
     *     // Вызов функции для создания заказа
     *     Orders::createOrder($user, $client, $project, $getData);
     * });
     *
     *
     * Пример общего использования:
     *
     * $requestData->checkGetRequestAndExecute('some_trigger', function($cleanedData) {
     * // Вызов функции с очищенными данными POST
     * someOtherFunction($cleanedData);
     * });
     */


    public function checkGetRequestAndExecute($keys, callable $callback): void
    {
        $getData = $this->getGet(); // получаем очищенные POST данные

        $keys = is_array($keys) ? $keys : [$keys]; // Преобразуем одиночный ключ в массив, если это не массив

        $allKeysExist = true; // Флаг для проверки всех ключей

        // Проверяем наличие всех ключей в POST
        foreach ($keys as $key) {
            if (!isset($getData[$key])) { // Используем isset для проверки наличия каждого ключа
                $allKeysExist = false; // Если хотя бы один ключ не существует, сбрасываем флаг
                break;
            }
        }

        // Если все ключи существуют, вызываем callback и передаем очищенные данные
        if ($allKeysExist) {
            call_user_func($callback, $getData);
        }
    }
}
