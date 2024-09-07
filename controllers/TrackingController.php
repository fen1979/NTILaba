<?php

class TrackingController
{
    private ?object $user;
    private ?object $requestData;

    public function __construct($user)
    {
        $this->user = $user;
        $this->requestData = RequestData::getInstance();
    }

    // Метод для обработки POST-запросов (сохранение данных и отправка уведомлений)
    public function handlePostRequest()
    {
        $this->requestData->checkPostRequestAndExecute('save-track', function ($data) {
            $track = R::dispense(TRACK_DATA);
            $track->date_in = date('Y/m/d, h:i:s');
            $track->courier = $data['courier'];
            $track->location = $data['location'];
            $track->receiver = $data['receiver'];
            $track->asmahta = $data['asmahta'];
            $track->transferTo = $data['transferTo'];
            $transferUser = R::load(USERS, $data['transferTo']);
            $track->description = $data['description'] ?? '';

            // Конвертируем и сохраняем изображения
            list($paths, $attachments) = $this->convertAndSaveImages($this->requestData->getFiles());
            $track->file_path = $paths;

            if (R::store($track)) {
                $this->sendNotification($track, $transferUser, $attachments);
            }
        });
    }

    // Метод для обработки GET-запросов (получение списка трекинговых данных)
    public function handleGetRequest(): array
    {
        $tl = false;
        $result = $settings = null;
        $this->requestData->checkGetRequestAndExecute('track-list', function () use (&$tl, &$result, &$settings) {
            $result = R::findAll(TRACK_DATA, 'ORDER BY id DESC');
            $settings = getUserSettings($this->user, TRACK_DATA);
            $tl = true;
        });

        return ['trackList' => $tl, 'result' => $result, 'settings' => $settings];
    }

    // Метод для конвертации и сохранения изображений
    private function convertAndSaveImages(array $files): array
    {
        $paths = '';
        $attachments = [];
        $target_dir = 'storage/tracking/'; // Папка для хранения файлов

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($files as $fileData) {
            if (isset($fileData[0])) {
                foreach ($fileData as $file) {
                    if (!isset($file['error']) || $file['error'] === 0) {
                        $this->processFile($file, $paths, $attachments, $target_dir);
                    }
                }
            } else {
                if (!isset($fileData['error']) || $fileData['error'] === 0) {
                    $this->processFile($fileData, $paths, $attachments, $target_dir);
                }
            }
        }

        $paths = rtrim($paths, ', ');
        return [$paths, $attachments];
    }

    // Метод для обработки каждого файла (конвертация и сохранение)
    private function processFile($file, &$paths, &$attachments, $target_dir)
    {
        $tmp_path = $file['tmp_name'];

        // Генерация имени файла только на основе текущей даты и времени
        $timestamp = time(); // Текущая дата и время без пробелов и знаков препинания
        $new_file_name = $timestamp . '.webp'; // Новое имя файла

        $out_path = $target_dir . $new_file_name; // Путь для сохранения файла

        // Конвертация изображения в формат WebP
        if (Converter::convertToWebP($tmp_path, $out_path)) {
            // Если конвертация успешна, добавляем путь к файлу в массив вложений
            $attachments[] = ['path' => $out_path, 'name' => $new_file_name];
            $paths .= $out_path . ', ';
        } else {
            _flashMessage('Ошибка при конвертации файла: ' . $file['name'], 'danger');
        }
    }

    // Метод для отправки уведомлений
    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendNotification($track, $transferUser, $attachments)
    {
        $eml = '';
        $emails = [];
        $salt = SALT_PEPPER;

        foreach (R::findAll(USERS) as $us) {
            if ($us['notify'] == 1 && strpos($us['notify_type'], '1') !== false) {
                $eml .= "{$us['email']}, ";
                $emails[] = $us['email'];
            }
        }

        _flashMessage('Users with emails have been notified!<br>' . $eml, 'warning', false);

        $subject = 'Parcel for NTI Accepted';
        $html_body = "<h1>Hello! NTI Group!</h1>";
        $html_body .= "<h4>A parcel was received! Time of record creation: <br>" . $track->date_in . "</h4>";
        $html_body .= "<p>Courier: " . $track->courier . "</p>";
        $html_body .= "<p>Location now: " . $track->location . "</p>";
        $html_body .= "<p>Receiver: " . $track->receiver . "</p>";
        $html_body .= "<p>Transferred to: " . $transferUser->user_name . "</p>";
        $html_body .= "<pre>" . $track->description . "</pre>";
        $html_body .= "<br><br>";
        $html_body .= "<a style='font-size: x-large;' href='https://nti.icu/tracking?track-list=1&update=$salt'>Preview Track List</a>";

        $mailerResult = Mailer::SendNotifications($emails, $subject, $html_body, $attachments);
        _flashMessage('New delivery created and stored successfully.<br>' . $mailerResult);
    }
}
