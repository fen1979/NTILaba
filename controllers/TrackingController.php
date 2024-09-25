<?php

class TrackingController
{
    private ?object $user;
    private ?object $requestData;

    public function __construct($user = null)
    {
        $this->user = $user;
        $this->requestData = RequestData::getInstance();
    }

    // Метод для обработки POST-запросов (сохранение данных и отправка уведомлений)
    public function handlePostRequest(): ?string
    {
        $return = null;
        $this->requestData->executeIfAllPostKeysExist('save-track', function ($data) use (&$return) {
            $track = R::dispense(TRACK_DATA);
            $track->date_in = date('Y/m/d, h:i:s'); // текущая дата и время на момент приемки
            $track->courier = $data['courier']; // кто принес или от кого пришло
            $track->location = $data['location']; // место куда поставили на хранение
            $track->receiver = $data['receiver']; // получатьль в офисе
            $track->asmahta = $data['asmahta']; // номер документа если есть (приходная накладная)

            $transferUser = R::load(USERS, $data['transferTo']); // имя пользователя на кого перевели
            $jsonToSave = '{"name":"' . $transferUser->user_name . '", "id":"' . $transferUser->id . '"}';
            $track->transferTo = $jsonToSave; //  кто ответственный за обработку

            $track->description = $data['description'] ?? ''; // описание чего то
            $track->processed = 0; // обработан ли приход
            $track->recieved = 1; // получена ли посылка требуется для заказанных посылок


            // Конвертируем и сохраняем изображения
            list($paths, $attachments) = $this->convertAndSaveImages($this->requestData->getFiles());
            $track->file_path = $paths; // пути к фото через запятую до 6 штук

            if (R::store($track)) {
                // отправляем письма с вложениями всем у кого рассылка включена!
                $this->sendNotification($track, $transferUser, $attachments);
                $return = 'print-track-info?track-id=' . $track->id;
            }
        });

        return $return;
    }

    // Метод для обработки GET-запросов (получение списка трекинговых данных или вывод на печать)
    public function handleGetRequest(): array
    {
        $tl = 'adding-form';
        $title = 'Tracking';
        $result = $settings = null;
        // preview tracking table list all tracks
        $this->requestData->executeIfAllGetKeysExist('track-list', function () use (&$tl, &$result, &$settings, &$title) {
            $result = R::findAll(TRACK_DATA, 'ORDER BY id DESC');
            $settings = getUserSettings($this->user, TRACK_DATA);
            $tl = 'recieved';
            $title = 'Tracking List';
        });

        // preview table track list one item for print
        $this->requestData->executeIfAllGetKeysExist('track-id', function ($data) use (&$tl, &$result, &$settings, &$title) {
            $result = R::load(TRACK_DATA, $data['track-id']);
            $settings = getUserSettings($this->user, TRACK_DATA);
            $tl = 'printing';
            $title = 'Tracking Print';
        });

        // preview table track list one item for print
        $this->requestData->executeIfAllGetKeysExist('ordered-list', function () use (&$tl, &$result, &$settings, &$title) {
            $result = R::findAll(TRACK_DATA, 'recieved = 0 AND processed = 0');
            $settings = getUserSettings($this->user, TRACK_DATA);
            $tl = 'ordered';
            $title = 'Tracking';
        });

        return ['trackList' => $tl, 'result' => $result, 'settings' => $settings, 'title' => $title];
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
    private function processFile($file, &$paths, &$attachments, $target_dir): void
    {
        $tmp_path = $file['tmp_name'];
        $original_name = $file['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);

        // Генерация имени файла на основе текущей даты и времени
        $timestamp = time();
        $new_file_name = $timestamp . '.webp'; // Новое имя файла

        $out_path = $target_dir . $new_file_name; // Путь для сохранения файла

        // Проверяем, является ли файл уже форматом WebP
        if (strtolower($extension) === 'webp') {
            // Если это уже WebP файл, просто переименовываем и сохраняем его
            if (move_uploaded_file($tmp_path, $out_path)) {
                $attachments[] = ['path' => $out_path, 'name' => $new_file_name];
                $paths .= $out_path . ', ';
            } else {
                _flashMessage('Ошибка при сохранении файла: ' . $original_name, 'danger');
            }
        } else {
            // Если это не WebP, конвертируем его в WebP
            if (Converter::convertToWebP($tmp_path, $out_path)) {
                // Если конвертация успешна, добавляем путь к файлу в массив вложений
                $attachments[] = ['path' => $out_path, 'name' => $new_file_name];
                $paths .= $out_path . ', ';
            } else {
                _flashMessage('Ошибка при конвертации файла: ' . $original_name, 'danger');
            }
        }
    }

    /**
     * - Метод для отправки уведомлений
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendNotification($track, $transferUser, $attachments, $cron = false): void
    {
        $eml = '';
        $emails = [];
        $salt = SALT_PEPPER;

        foreach (R::findAll(USERS) as $us) {
            if ($us['notify'] == 1 && str_contains($us['notify_type'], '1')) {
                $eml .= "{$us['email']}, ";
                $emails[] = $us['email'];
            }
        }

        if (!$cron)
            _flashMessage('Users with emails have been notified!<br>' . $eml, 'warning', false);

        //$subject = 'New parcel received for NTI';
        $subject = "NTI. Parcel from [$track->courier] received";
        $html_body = "<h1>Hello! NTI Group!</h1>";
        $html_body .= "<h4>A parcel was received! Time of record creation: <br>" . $track->date_in . "</h4>";
        $html_body .= "<p>Courier: " . $track->courier . "</p>";
        $html_body .= "<p>Location now: " . $track->location . "</p>";
        $html_body .= "<p>Receiver: " . $track->receiver . "</p>";
        $html_body .= "<p>Transferred to: " . $transferUser->user_name . "</p>";
        $html_body .= "<pre>" . $track->description . "</pre>";
        $html_body .= "<br><br>";
        $html_body .= "<a style='font-size: x-large;' href='https://nti.icu/tracking?track-list=1&$salt'>Preview Track List</a>";

        $mailerResult = Mailer::SendNotifications($emails, $subject, $html_body, $attachments);

        if (!$cron)
            _flashMessage('New delivery created and stored successfully.<br>' . $mailerResult);
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function cronRequest($track, $transferUser, $attachments): void
    {
        $this->sendNotification($track, $transferUser, $attachments, true);
    }
}
