<?php

class Converter
{

    /**
     *  конвертирование и уменьшение изображений
     * @param  $inputImagePath => original image file from user
     * @param  $outputImagePath => path for converted image
     * @param int $quality => for output image, by default 100%
     * @return true olways
     *
     */
    public static function convertToWebP($inputImagePath, $outputImagePath, int $quality = 100): bool
    {
        error_log(print_r($inputImagePath, true));
        // Команда для конвертации изображения в WebP с использованием ffmpeg
        $command = "ffmpeg -i " . escapeshellarg($inputImagePath) . " -quality " . escapeshellarg($quality) . " " . escapeshellarg($outputImagePath);

        // Выполнение команды
        exec($command, $output, $returnVar);

        // Проверка успешности выполнения команды
        return $returnVar === 0;
    }

    // Функция для проверки формата видео
    public static function isMp4H264($filePath): bool
    {
        $output = shell_exec("ffprobe -v error -select_streams v:0 -show_entries stream=codec_name,codec_long_name -of default=noprint_wrappers=1:nokey=1 '$filePath'");
        return strpos($output, 'h264') !== false;
    }

    // Функция для конвертации видео в MP4 H.264
    public static function convertToMp4H264($source, $target)
    {
        shell_exec("ffmpeg -i '$source' -vcodec libx264 -acodec aac '$target'");
    }

}
