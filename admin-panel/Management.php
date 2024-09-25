<?php

class Management
{
    /**
     * МЕТОДЫ ОБЩЕГО ПОЛЬЗОВАНИЯ
     * @param $files
     * @param $imagePath
     * @return array
     */
    private static function saveOrChangeImage($files, $imagePath): array
    {
        if (!empty($files['name'])) {
            $answer = [];
            $uploadedFile = TEMP_FOLDER . basename($files['name']);
            $imageFileType = strtolower(pathinfo(basename($files['name']), PATHINFO_EXTENSION));
            $targetDir = TOOLS_FOLDER . unicum() . ".webp";

            if ($imageFileType == 'webp') {
                /* добавляем новое фото */
                if (move_uploaded_file($files['tmp_name'], $targetDir)) {
                    /* удаляем старое фото */
                    if (file_exists($imagePath))
                        unlink($imagePath);
                    $answer['error'] = 'Image for this tool saved correctly';
                    $answer['status'] = true;
                }
            } else {
                if (move_uploaded_file($files['tmp_name'], $uploadedFile)) {
                    try {
                        /* конвертируем и добавляем в папку новое фото */
                        if (Converter::convertToWebP($uploadedFile, $targetDir)) {
                            /* удаляем временное фото */
                            array_map('unlink', glob(TEMP_FOLDER . "*.*"));
                            /* удаляем старое фото */
                            if (!empty($imagePath))
                                unlink($imagePath);
                            $answer['error'] = 'Image for new tool saved correctly';
                            $answer['status'] = true;
                        } else {
                            $answer['error'] = 'Conversion error, image format not supported!';
                            $answer['status'] = false;
                        }
                    } catch (Exception $e) {
                        $answer['error'] = print($e);
                        $answer['status'] = false;
                    }
                } else {
                    $answer['error'] = 'File format Wrong! Use JPG, PNG, WEBP, PDF Only!!!';
                    $answer['status'] = false;
                }
            }

            $answer['path'] = $targetDir;
            return $answer;

        }

        return (!empty($imagePath)) ?
            ['path' => $imagePath, 'error' => 'Image from DB added,', 'status' => true] :
            ['path' => 'public/images/tools.webp', 'error' => 'Image not exist.', 'status' => true];
    }

    /**
     * PASTED IMAGE CONVERTER AND SAVER
     * @param $imageData
     * @param $toolName
     * @return string[]
     */
    private static function convertAndSavePastedImageForItem($imageData, $toolName): array
    {
        //$partName = preg_replace(['/[^a-z0-9 \-_]/', '/[ \-]+/'], ['', '_'], strtolower($toolName));
        // Убираем префикс data:image/...;base64, если он есть
        if (str_contains($imageData, "base64,")) {
            list($typePart, $imageData) = explode('base64,', $imageData);
            // Получаем тип изображения из строки data URI
            preg_match("/^data:image\/(\w+);/", $typePart, $matches);
            $imageType = $matches[1] ?? null;
        } else {
            $imageType = null; // Тип не определён
        }

        $imageData = base64_decode($imageData);
        // Определяем путь сохранения в зависимости от типа изображения
        if ($imageType !== 'webp') {
            // Если изображение не webp, сохраняем во временную папку для дальнейшей конвертации
            $filePathRaw = TEMP_FOLDER . 'image.' . $imageType; // Используем расширение полученное из типа
            // Сохраняем изображение
            file_put_contents($filePathRaw, $imageData);
            // Путь для сохранения конвертированного изображения
            $filePathWebp = TOOLS_FOLDER . $toolName . ".webp";
            // Вызываем метод конвертации
            if (Converter::convertToWebp($filePathRaw, $filePathWebp))
                array_map('unlink', glob(TEMP_FOLDER . "*.*"));
        } else {
            // Если изображение уже в webp, формируем путь для сохранения
            $filePathWebp = TOOLS_FOLDER . $toolName . ".webp";
            // Сохраняем изображение
            file_put_contents($filePathWebp, $imageData);
        }

        return ['file-path' => $filePathWebp];
    }

    /**
     * удаление rout card/user/tools и всех его данных
     *
     * @param $post
     * @param $user
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function deletingAnItem($post, $user): void
    {
        list($who, $id) = explode('-', $post['idForUse']);

        if (checkPassword($post['password'])) {
            switch ($who) {
                case 'rout':
                    {
                        $r = R::load(ROUTE_ACTION, $id);
                        $tr = $r['sku'];

                        $log_details = "Rout Action №:$id was deleted, RA: $r->action, SKU: $tr";

                        R::trash($r);
                        _flashMessage("Rout Action SKU $tr deleted successfully!");
                    }
                    break;
                case 'user':
                    {
                        /* удаление user и всех его данных */
                        $r = R::load(USERS, $id);
                        $tr = $r['user_name'];

                        $log_details = "User №:$id was deleted, Name: $tr";

                        R::trash($r);
                        _flashMessage("User named $tr deleted successfully!");
                    }
                    break;
                case 'tools':
                    {
                        /* удаление tools и все его данные включая фотографии и размещения по БД */
                        $r = R::load(TOOLS, $id);
                        $tr = $r['manufacturer_name'] . ' ' . $r['device_model'];
                        $imagePath = $r['image'];

                        // Проверка, есть ли путь к фото только в этом инструменте
                        $imageCount = R::count(TOOLS, 'image LIKE ?', [$imagePath]);
                        if ($imageCount == 1) {
                            // Если нет совпадений, очищаем место файла
                            unlink($imagePath);
                        }

                        // Проверка в таблице projects
                        $projects = R::findAll(PROJECTS, 'tools LIKE ?', ['%' . $id . '%']);
                        foreach ($projects as $project) {
                            $tools = explode(',', $project->tools);
                            if (in_array($id, $tools)) {
                                // Удаление id инструмента из списка
                                $tools = array_filter($tools, function ($toolId) use ($id) {
                                    return $toolId != $id;
                                });
                                $project->tools = implode(',', $tools);
                                R::store($project);
                            }
                        }

                        $log_details = "Tool id №:$id was deleted, Name: $tr";
                        R::trash($r);
                        _flashMessage("Tool $tr deleted successfully!");
                    }
                    break;

            }

            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[12], $log_details)) {
                _flashMessage('The log not created.', 'danger');
            }
        } else {
            _flashMessage('Incorrect password writed!', 'danger');
        }
    }

    /**
     * ROUT ACTIONS CODE
     *
     * @param $post
     * @param $user
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateRoutAction($post, $user): void
    {
        $post = checkPostDataAndConvertToArray($post);

        if (isset($post['rout-action-editing'])) {
            $routAction = R::load(ROUTE_ACTION, _E($post['rout-action-editing']));
            $log_action = 'UPDATING';
            $log_details = "Rout Action №:$routAction->id was updated successfully";
        } else {
            $routAction = R::dispense(ROUTE_ACTION);
            $log_action = 'CREATING';
            $log_details = "Rout Action №:$routAction->id was created successfully";
        }

        $routAction->sku = $post['sku'] ?? '';
        $routAction->actions = $post['actions'] ?? '';
        $routAction->description = $post['description'] ?? '';
        $routAction->specifications = $post['specifications'] ?? '';

        if (R::store($routAction)) {
            _flashMessage('Rout Action Saved successfully!');
        } else {
            _flashMessage('Some things go wrong!', 'danger');
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[8], $log_details)) {
            _flashMessage('The log not created.', 'danger');
        }
    }

    /**
     * ROUT ACTIONS CODE
     *
     * @param $post
     * @param $user
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateWarehouseType($post, $user): void
    {
        $warehouseType = R::load(WH_TYPES, _E($post['wh-action-editing']));
        if (isset($post['wh-action-editing']) && $warehouseType) {
            $log_action = 'UPDATING';
            $log_details = "Name Type №:$warehouseType->id was updated successfully";
        } else {
            $warehouseType = R::dispense(WH_TYPES);
            $log_action = 'CREATING';
            $log_details = "Name Type №:$warehouseType->id was created successfully";
        }

        $warehouseType->type_name = _E($post['type_name'] ?? '');
        $warehouseType->description = _E($post['description'] ?? '');

        if (R::store($warehouseType)) {
            _flashMessage('Name Type Saved successfully!');
        } else {
            _flashMessage('Some things go wrong!', 'danger');
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[6], $log_details)) {
            _flashMessage('The log not created.', 'danger');
        }
    }

    /**
     * USERS ACTIONS CODE
     *
     * @param $post
     * @param $thisUser
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function addOrUpdateUsersData($post, $thisUser): void
    {
        $post = checkPostDataAndConvertToArray($post);
        $name = $post['name'];
        $email = $post['email'];
        $phone = $post['phone'] ?? null;
        $can_change_data = $post['can-change-data'] ?? 0;
        $role = $post['approle'] ?? ROLE_WORKER;
        $jobrole = $post['jobrole'] ?? ROLE[ROLE_WORKER];
        $pass = $post['user_password'] ?? null;
        $adminPass = $post['admin_password'] ?? null;
        $date_in = $post['date_in'];

        // CREATE NEW USER
        if (isset($post['add-new-user'])) {
            if (!empty($adminPass) && !empty($pass) && checkPassword($adminPass)) {

                $user = R::findOrCreate(USERS, ['user_name' => $name]);

                if (empty($user['user_hash']) && !password_verify($pass, $user['user_hash'])) {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $user->user_hash = $hash;
                    $user->email = $email;
                    $user->phone = $phone;
                    $user->job_role = $jobrole;
                    $user->app_role = $role;
                    $user->can_change_data = $can_change_data;
                    $user->filterby_status = '-1';
                    $user->filterby_user = 'all';
                    $user->filterby_client = '';
                    $user->link = 'order';
                    $user->sound = 1;
                    $user->view_mode = 'light';
                    $user->date_in = date("Y-m-d h:i");

                    /* creation admin-panel for view tables */
                    // fixme цикл в котором заполнятся первичные настройки для вывода информации пользователю
                    $settings = R::dispense(SETTINGS);
                    $settings->table_name = DEFAULT_SETTINGS['table_name'];
                    $settings->setup = DEFAULT_SETTINGS['setup'];
                    R::store($settings);

                    $user->ownSettingsList[] = $settings;
                    R::store($user);
                    _flashMessage('Registration complite!');

                    $details = 'Worker named: ' . $user->user_name . ', Added successfully on: ' . date('Y/m/d') . ' at ' . date('h:i');
                    $details .= getServerData();
                    logAction($thisUser['user_name'], 'REGISTER', OBJECT_TYPE[11], $details);
                }
            } else {
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('Registration error Password Data wrong!', 'danger');
            }
        }

        // UPDATE USER INFORMATION
        if (isset($post['update-user-data'])) {
            $uid = $post['update-user-data'];
            $user = R::load(USERS, $uid);

            $log_details = "User id №:$user->id was edited, brfore:[$user->job_role, $user->app_role, $user->user_name]";
            $log_details .= "<br> [$user->phone, $user->email, permitions = $user->can_change_data]";

            $user->user_name = $name ?? 'Jon Doe';
            $user->email = $email ?? 'Jon@Doe.com';
            $user->phone = $phone ?? 'Jon@Doe.com';
            $user->job_role = $jobrole ?? 'Camera Man';
            $user->app_role = $role ?? 'worker';
            $user->can_change_data = $can_change_data;
            // если время изменилось то сохраним новое
            $user->date_in = ($date_in != $user->date_in) ? $date_in : $user->date_in;

            // password changing if need
            if (!empty($adminPass) && !empty($pass) && checkPassword($adminPass)) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $user->user_hash = $hash;
                $log_details .= '<br>password was changed!';
                _flashMessage('The user password has been successfully changed!!!<br>To log in, use the new password.', 'danger');
            } else {
                _flashMessage('No password changes!<br>Ignore this message if you have not changed your password!', 'warning');
            }

            if (R::store($user)) {
                $log_details .= "<br>Changes: [$user->job_role, $user->app_role, $user->user_name]";
                _flashMessage('Changes Saved successfully!');
            } else {
                _flashMessage('Something went wrong!', 'danger');
            }

            logAction($user['user_name'], 'EDITING', OBJECT_TYPE[11], $log_details);
        }
    }

    /**
     * TOOLS ACTIONS CODE
     *
     * @param $post
     * @param $files
     * @param $user
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateTools($post, $files, $user): void
    {
        $post = checkPostDataAndConvertToArray($post);
        $log_details = '';
        /* $imagePath = [0] -> path to file, [1] -> errors, [2] -> statment true/false */
        if (isset($post['tools-editing']) && isset($post['tool_id'])) {
            $tool = R::load(TOOLS, $post['tool_id']);
            $imagePath = $tool->image ?? '';

            // check if image exist in DB and get copied
            if (!empty($post['image-from-db'])) {
                $imagePath = $post['image-from-db'];
            }

            $log_action = 'EDITING';
            $log_details = "Tool id №:$tool->id was edited: [$tool->manufacturer_name, $tool->device_model]";
        }

        if (isset($post['tools-saving'])) {
            $tool = R::dispense(TOOLS);
            $imagePath = '';

            $log_action = 'CREATING';
        }

        $tool->manufacturer_name = $post['manufacturer_name']; // имя инструмента от производителя
        $tool->device_model = $post['device_model']; // модель инструмента
        $tool->device_type = $post['device_type']; // тип инструмента
        $tool->device_location = $post['device_location']; // рабочее местонахождение инструмента
        $tool->in_use = $post['in_use']; // рабочий который пользуется инструментом
        $tool->calibration = $post['calibration']; // NONC = no need calibration, NEC = need calibration
        $tool->serial_num = $post['serial_num']; // сирийный номер инструмента после калибровки
        $tool->date_of_inspection = $post['date_of_inspection']; // дата последней калибровки - обслуживания инструмента
        $tool->next_inspection_date = $post['next_inspection_date']; // следующая дата калибровки - обслуживания инструмента !!!
        $tool->work_life = $post['work_life']; // интервал обслуживания/калибровки (месяцев)
        $tool->responsible = $post['responsible']; // ответственный за инструмент
        $tool->remarks = $post['remarks']; // заметки на полях
        $tool->date_in = $post['date_in'] ?? date("Y-m-d h:i"); // дата внесения в БД

        /* saving image for tool */
        // если фото было где то скопированно а не выбрано физически
        $pastedImagexist = false;
        if (!empty($_POST['imageData']) && str_starts_with($_POST['imageData'], 'data:image')) {
            $pastedImagexist = true;
            $name = $post['manufacturer_name'] . '_' . $post['device_model'];
            $result = self::convertAndSavePastedImageForItem($post['imageData'], $name);
            $tool->image = $result['file-path'] ?? 'public/images/ips.webp';
        }

        // fixme починить коректный вывод сообщений при обновлении инструмента!!!
        // если фото было выбрано с компа пользователя
        if (!$pastedImagexist) {
            $result = self::saveOrChangeImage($files, $imagePath);
            $tool->image = $result['path']; // путь к фото инструмента или ПДФ
        }

        if (R::store($tool) && $result['status']) {
            $act = $log_action == 'EDITING' ? 'updated' : 'saved';
            _flashMessage($result['error'] . '<br/> The tool ' . $act . ' successfully!');
        } else {
            _flashMessage($result['error'], 'danger');
        }

        // fixme сделать нормальный лог для обновления и создания и прочего
        $log_details .= ($log_action == 'creating_tool') ?
            "Tool id №:$tool->id was created: [$tool->manufacturer_name, $tool->device_model]" :
            "<br> TODO updated information!!!!!!!!!!!!!!!!!";

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[9], $log_details)) {
            _flashMessage('The log not created.', 'danger');
        }
    }

    /**
     * TABLE COLUMNS ACTIONS CODE
     *
     * @param $post
     * @param $userId
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function columnsRedirection($post, $userId): void
    {
        // Получаем пользователя
        $user = R::load(USERS, $userId);
        $t_name = '';
        // Поиск существующих настроек
        $existingSetting = R::findOne(SETTINGS, 'users_id = ? AND table_name = ?', [$user['id'], $post['save-settings']]);

        if (empty($existingSetting['table_name'])) {
            // Создаем новую запись настроек
            $settings = R::dispense(SETTINGS);
            $t_name = $settings->table_name = _E($post['save-settings']);
            $columnsOrderedByUser = json_decode($post['rowOrder'], true);
            $settings->setup = json_encode($columnsOrderedByUser);
            R::store($settings);
            $user->ownSettingsList[] = $settings;
            R::store($user);
        } else {
            // Обновляем существующие настройки
            $settings = R::load(SETTINGS, $existingSetting['id']);
            $columnsOrderedByUser = json_decode($post['rowOrder'], true);
            var_dump($columnsOrderedByUser);
            $settings->setup = json_encode($columnsOrderedByUser);
            R::store($settings);
        }

        // Обновляем данные пользователя в сессии
        $_SESSION['userBean'] = R::load(USERS, $user['id']);

        _flashMessage('Settings saved successfully');

        /* [     LOGS FOR THIS ACTION     ] */
        $log_details = "The column output has been changed, table name: $t_name";
        if (!logAction($user->user_name, 'UPDATING', OBJECT_TYPE[10], $log_details)) {
            _flashMessage('The log not created.', 'danger');
        }
    }

    /**
     * USER ACCOUNT SETTUNGS
     * @param $post
     * @param $userId
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function accountSettings($post, $userId): void
    {
        // Получаем пользователя
        $user = R::load(USERS, $userId);
        $user->link = _E($post['link-pages']);
        $user->sound = _E($post['sound'] ?? '0');
        $user->view_mode = _E($post['dark-mode'] ?? 'light'); // light/dark
        $user->preview = _E($post['project-preview'] ?? 'docs'); // docs/image
        $user->tutorial = _E($post['tutorial'] ?? '0'); // Preview tutorial
        $user->notify = _E($post['notify'] ?? '0'); // уведомления вкл/выкл
        $user->notify_type = isset($post['notify_type']) ? implode(',', $post['notify_type']) : ''; // Типы уведомлений

        R::store($user);

        // Обновляем данные пользователя в сессии
        $_SESSION['userBean'] = R::load(USERS, $user['id']);

        _flashMessage('Settings saved successfully');

        /* [     LOGS FOR THIS ACTION     ] */
        $log_details = "The user settings has been changed and saved";
        if (!logAction($user->user_name, 'ACCOUNT SETTINGS', OBJECT_TYPE[11], $log_details)) {
            _flashMessage('The log not created.', 'danger');
        }
    }

    /**
     * USER PASSWORD CHANGEING FUNCTION
     * @param string $userId
     * @param $post
     * @throws //\PHPMailer\PHPMailer\Exception
     */
    public static function updatePasswordForUsers(string $userId, $post): void
    {
        if (isset($post["update-user-password"])) {
            $user = R::load(USERS, $userId);
            $pass_1 = _E($post["password"]);
            $pass_2 = _E($post["re-password"]);
            if ($pass_1 == $pass_2) {
                $hash = password_hash($pass_1, PASSWORD_DEFAULT);
            }
            $user->user_hash = $hash;
            // if email exist in query
            $user->email = !empty($post["email"]) ? $post["email"] : null;
            R::store($user);

            _flashMessage('Password updated successfully');

            if (!empty($post["send-mail"]) && _E($post["send-mail"]) == '1') {
                $body = '<h3>Hello ' . $user->user_name . '</h3>';
                $body .= '<p>Your password has been successfully changed. Below are your login credentials:</p>';
                $body .= '<p style="color: blue;">Username: ' . $user->user_name . '<br>Password: ' . $pass_1 . '</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Please keep your password secure and do not share it with anyone.</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Sharing your password can lead to unauthorized access to your personal information.</p>';
                $body .= '<a style="border: solid 1px black; padding: 10px; background: #8cff79; border-radius: 7px;"' .
                    ' href="https://nti.icu" target="_blank">Login to System</a>';
                $a = '<a href="https://nti.co.il" target="_blank">support</a>';
                $body .= '<p>If you did not request this account, please ignore this email or contact our ' . $a . ' if you have any concerns.</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Remember, keeping your password safe is your responsibility!</p>';
                $body .= '<p>Best regards,<br>NTI Group Company</p>';

                $answer = Mailer::SendEmailNotification(_E($post["email"]), $user['user_name'], 'Password Update NTI', $body);
                if ($answer != 'success')
                    _flashMessage($answer, 'danger');
                else
                    _flashMessage('Mail sended successfully!');
            }

            /*    LOGS FOR THIS ACTION     */
            $log_details = "The user password has been changed and saved";
            if (!logAction($user->user_name, 'ACCOUNT SETTINGS', OBJECT_TYPE[11], $log_details)) {
                _flashMessage('The log not created.', 'danger');
            }
        } else {
            _flashMessage('Please fill all the required fields.', 'danger');
        }
        $_SESSION['userBean'] = R::load(USERS, $userId);
    }
}