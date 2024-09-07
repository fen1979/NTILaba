<?php
spl_autoload_register(function ($class) {
    // Указываем пути, где искать файлы с классами
    $directories = [
        __DIR__ . '/core/',
        __DIR__ . '/libs/',
        __DIR__ . '/controllers/',
        __DIR__ . '/orders/',
        __DIR__ . '/projects/',
        __DIR__ . '/counterparties/'
        // Можете добавить больше папок по необходимости
    ];

    // Проходим по каждому пути и проверяем наличие файла
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});