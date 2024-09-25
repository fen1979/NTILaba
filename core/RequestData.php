<?php /** @noinspection PhpUnused */

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
            if (str_starts_with($key, 'HTTP_')) {
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
    private function processFileError(int $errorCode): bool|string
    {
        return match ($errorCode) {
            UPLOAD_ERR_OK => true,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum upload size.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown error occurred.',
        };
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
     * Функция проверяет массив POST на наличие всех указанных ключей (например, кнопки submit).
     * Если триггерные ключи найдены в массиве POST (независимо от их значения), очищенные данные POST
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
     * $requestData->executeIfAllPostKeysExist('some_trigger', function($cleanedData) {
     * // Вызов функции с очищенными данными POST
     * someOtherFunction($cleanedData);
     * });
     */
    public function executeIfAllPostKeysExist($keys, callable $callback): void
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
     * Функция проверяет массив POST на наличие хотя бы одного триггерного ключа (например, кнопки submit).
     * Если триггерный ключ найден в массиве POST (независимо от его значения), очищенные данные POST
     * передаются в callback функцию, которая выполняется для дальнейшей обработки.
     *
     * @param $keys
     * @param callable $callback Функция обратного вызова, которая будет вызвана с аргументом $postData, содержащим
     *                           очищенные данные POST, если триггер найден.
     * @return void
     *
     * - // Пример использования:
     * - $requestData = RequestData::getInstance();
     * - $requestData->executeIfAnyPostKeyExists('some_trigger', function($cleanedData) {
     * - // Вызов функции с очищенными данными POST
     * - someOtherFunction($cleanedData);
     * - });
     */
    public function executeIfAnyPostKeyExists($keys, callable $callback): void
    {
        $postData = $this->getPost(); // получаем очищенные POST данные
        $keys = is_array($keys) ? $keys : [$keys]; // Преобразуем одиночный ключ в массив, если это не массив

        $anyKeyExists = false; // Флаг для проверки наличия хотя бы одного ключа

        // Проверяем наличие хотя бы одного ключа в POST
        foreach ($keys as $key) {
            if (isset($postData[$key])) { // Если хотя бы один ключ существует, устанавливаем флаг
                $anyKeyExists = true;
                break;
            }
        }

        // Если хотя бы один ключ существует, вызываем callback и передаем очищенные данные
        if ($anyKeyExists) {
            call_user_func($callback, $postData);
        }
    }


    /**
     * Функция проверяет массив GET на наличие всех указанных ключей (например, кнопки submit, поля формы).
     * Если триггерные ключи найдены в массиве GET (независимо от их значения), очищенные данные GET
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
     * $requestData->executeIfAnyGetKeyExists('some_trigger', function($cleanedData) {
     * // Вызов функции с очищенными данными POST
     * someOtherFunction($cleanedData);
     * });
     */
    public function executeIfAllGetKeysExist($keys, callable $callback): void
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

    /**
     * Функция проверяет массив GET а наличие хотя бы одного триггерного ключа (например, кнопки submit, поля формы).
     * Если триггерные ключи найдены в массиве GET (независимо от их значения), очищенные данные GET
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
     * $requestData->executeIfAnyGetKeyExists('some_trigger', function($cleanedData) {
     * // Вызов функции с очищенными данными POST
     * someOtherFunction($cleanedData);
     * });
     */
    public function executeIfAnyGetKeyExists($keys, callable $callback): void
    {
        $getData = $this->getGet(); // получаем очищенные POST данные
        $keys = is_array($keys) ? $keys : [$keys]; // Преобразуем одиночный ключ в массив, если это не массив

        $anyKeyExists = false; // Флаг для проверки наличия хотя бы одного ключа

        // Проверяем наличие хотя бы одного ключа в GET
        foreach ($keys as $key) {
            if (isset($getData[$key])) { // Если хотя бы один ключ существует, устанавливаем флаг
                $anyKeyExists = true;
                break;
            }
        }

        // Если хотя бы один ключ существует, вызываем callback и передаем очищенные данные
        if ($anyKeyExists) {
            call_user_func($callback, $getData);
        }
    }
}
