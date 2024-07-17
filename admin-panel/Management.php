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
            ['path' => $imagePath, 'error' => '', 'status' => true] :
            ['path' => 'public/images/tools.webp', 'error' => 'Image not exist.', 'status' => true];
    }

    /**
     * удаление rout card/user/tools и всех его данных
     *
     * @param $post
     * @param $user
     * @return array
     */
    public static function deletingAnItem($post, $user): array
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
                        $res['info'] = "Rout Action SKU $tr deleted successfully!";
                    }
                    break;
                case 'user':
                    {
                        /* удаление user и всех его данных */
                        $r = R::load(USERS, $id);
                        $tr = $r['user_name'];

                        $log_details = "User №:$id was deleted, Name: $tr";

                        R::trash($r);
                        $res['info'] = "User named $tr deleted successfully!";
                    }
                    break;
                case 'tools':
                    {
                        /* удаление tools и все его данные включая фотографии */
                        $r = R::load(TOOLS, $id);
                        $tr = $r['toolname'];

                        $log_details = "Tool id №:$id was deleted, Name: $tr";

                        R::trash($r);
                        $res['info'] = "Tool $tr deleted successfully!";
                    }
                    break;
            }
            $res['color'] = 'success';

            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[12], $log_details)) {
                $res['info'] = 'The log not created.';
                $res['color'] = 'danger';
            }
        } else {
            $res['info'] = "Incorrect password writed!";
            $res['color'] = 'danger';
        }

        return $res;
    }

    /**
     * ROUT ACTIONS CODE
     *
     * @param $post
     * @param $user
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateRoutAction($post, $user): array
    {
        if (isset($post['rout-action-editing'])) {
            $routAction = R::load(ROUTE_ACTION, _E($post['rout-action-editing']));
            $log_action = 'UPDATING';
            $log_details = "Rout Action №:$routAction->id was updated successfully";
        } else {
            $routAction = R::dispense(ROUTE_ACTION);
            $log_action = 'CREATING';
            $log_details = "Rout Action №:$routAction->id was created successfully";
        }

        $routAction->sku = _E($post['sku'] ?? '');
        $routAction->actions = _E($post['actions'] ?? '');
        $routAction->description = _E($post['description'] ?? '');
        $routAction->specifications = _E($post['specifications'] ?? '');

        if (R::store($routAction)) {
            $res[] = ['info' => 'Rout Action Saved successfully!', 'color' => 'success'];
        } else {
            $res[] = ['info' => 'Some things go wrong!', 'color' => 'danger'];
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[8], $log_details)) {
            $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
        }
        return $res;
    }

    /**
     * ROUT ACTIONS CODE
     *
     * @param $post
     * @param $user
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateWarehouseType($post, $user): array
    {
        $warehouseType = R::load(WH_TYPES, _E($post['wh-action-editing']));
        if (isset($post['wh-action-editing']) && $warehouseType) {
            $log_action = 'UPDATING';
            $log_details = "Name Type №:$warehouseType->id was updated successfully";
        } else {
            $routAction = R::dispense(WH_TYPES);
            $log_action = 'CREATING';
            $log_details = "Name Type №:$warehouseType->id was created successfully";
        }

        $warehouseType->type_name = _E($post['type_name'] ?? '');
        $warehouseType->description = _E($post['description'] ?? '');

        if (R::store($warehouseType)) {
            $res[] = ['info' => 'Name Type Saved successfully!', 'color' => 'success'];
        } else {
            $res[] = ['info' => 'Some things go wrong!', 'color' => 'danger'];
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[6], $log_details)) {
            $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
        }
        return $res;
    }

    /**
     * USERS ACTIONS CODE
     *
     * @param $post
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function editUserData($post): array
    {
        $user = R::load(USERS, _E($post['user-data-editing']));

        $log_details = "User id №:$user->id was edited, old stepsData:[$user->job_role, $user->app_role, $user->user_name]";

        $user->user_name = _E($post['name'] ?? 'Jon Doe');
        $user->job_role = _E($post['jobrole'] ?? 'Camera Man');
        $user->app_role = _E($post['approle'] ?? 'worker');
        $user->can_change_data = _E($post['can-change-data'] ?? 0);
        $user->date_in = _E($post['datein'] ?? date("Y-m-d h:i"));

        if (R::store($user)) {
            $log_details .= "<br>new stepsData: [$user->job_role, $user->app_role, $user->user_name]";
            $res[] = ['info' => 'Changes Saved successfully!', 'color' => 'success'];
        } else {
            $res[] = ['info' => 'Something went wrong!', 'color' => 'danger'];
        }

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], 'EDITING', OBJECT_TYPE[11], $log_details)) {
            $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
        }
        return $res;
    }

    public static function addNewWorker($post, $thisUser): array
    {
        $name = _E($post['regUserName']);
        $pass = _E($post['regUserPassword']);
        $adminPass = _E($post['adminPassword']);
        $can_change_data = _E($post['can-change-data']) ?? 0;
        $role = _E($post['role']) ?? ROLE_WORKER;
        $jobrole = _E($post['regJobRole']) ?? ROLE[ROLE_WORKER];

        if (checkPassword($adminPass)) {

            $user = R::findOrCreate(USERS, ['user_name' => $name]);

            if (empty($user['user_hash']) && !password_verify($pass, $user['user_hash'])) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $user->user_hash = $hash;
                $user->date_in = date("Y-m-d h:i");
                $user->job_role = $jobrole;
                $user->app_role = $role;
                $user->can_change_data = $can_change_data;
                $user->filterby_status = '-1';
                $user->filterby_user = 'all';
                $user->filterby_client = '';
                $user->link = 'order';
                $user->sound = 1;
                $user->view_mode = 'light';

                /* creation admin-panel for view tables */
                // TODO цикл в котором заполнятся первичные настройки для вывода информации пользователю
                $settings = R::dispense(SETTINGS);
                $settings->table_name = DEFAULT_SETTINGS['table_name'];
                $settings->setup = DEFAULT_SETTINGS['setup'];
                R::store($settings);

                $user->ownSettingsList[] = $settings;
                R::store($user);
                $args = ['color' => 'success', 'info' => 'Registration complite!'];

                $details = 'Worker named: ' . $user->user_name . ', Added successfully on: ' . date('Y/m/d') . ' at ' . date('h:i');
                $details .= getServerData();
                logAction($thisUser['user_name'], 'REGISTER', OBJECT_TYPE[11], $details);
            }
        } else {
            $args = ['color' => 'danger', 'info' => 'Registration error Some Data wrong!'];
        }
        return $args;
    }

    /**
     * TOOLS ACTIONS CODE
     *
     * @param $post
     * @param $files
     * @param $user
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createUpdateTools($post, $files, $user): array
    {
        $log_details = '';
        /* $imagePath = [0] -> path to file, [1] -> errors, [2] -> statment true/false */
        if (isset($post['tools-editing'])) {
            $tool = R::load(TOOLS, _E($post['tools-editing']));
            $imagePath = $tool->image ?? '';

            $log_action = 'EDITING';
            $log_details = "Tool id №:$tool->id was edited, old stepsData:[$tool->toolname, $tool->specifications]";
        }

        if (isset($post['tools-saving'])) {
            $tool = R::dispense(TOOLS);
            $imagePath = '';

            $log_action = 'CREATING';
        }

        $tool->toolname = _E($post['toolname'] ?? '');
        $tool->specifications = _E($post['specifications'] ?? '');
        $tool->esd = _E($post['esd-sertificate'] ?? 'ESD');
        $tool->exp_date = _E($post['date-qc'] ?? 'EOL');
        $tool->date_in = $tool->date_in ?? date("Y-m-d h:i");

        /* saving image for tool */
        $result = self::saveOrChangeImage($files, $imagePath);
        $tool->image = $result['path'];


        if (R::store($tool) && $result['status']) {
            $res = ['info' => $result['error'] . '<br/> New Tool Saved successfully!', 'color' => 'success'];
        } else {
            $res = ['info' => $result['error'], 'color' => 'danger'];
        }

        $log_details .= ($log_action == 'creating_tool') ?
            "Tool id №:$tool->id was created, tool stepsData: [$tool->toolname, $tool->specifications]" :
            "<br> new stepsData:[$tool->toolname, $tool->specifications]";

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[9], $log_details)) {
            $res = ['info' => 'The log not created.', 'color' => 'danger'];
        }
        return $res;
    }

    /**
     * TABLE COLUMNS ACTIONS CODE
     *
     * @param $post
     * @param $userId
     * @return array
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function columnsRedirection($post, $userId): array
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
            $orderedByUser = explode(',', $post['rowOrder']);
            $settings->setup = json_encode($orderedByUser);
            R::store($settings);

            $user->ownSettingsList[] = $settings;
            R::store($user);
        } else {

            // Обновляем существующие настройки
            $settings = R::load(SETTINGS, $existingSetting['id']);
            $orderedByUser = explode(',', $post['rowOrder']);
            $settings->setup = json_encode($orderedByUser);
            R::store($settings);
        }

        // Обновляем данные пользователя в сессии
        $_SESSION['userBean'] = R::load(USERS, $user['id']);

        $res['info'] = 'Settings saved successfully';
        $res['color'] = 'success';

        /* [     LOGS FOR THIS ACTION     ] */
        $log_details = "The column output has been changed, table name: $t_name";
        if (!logAction($user->user_name, 'UPDATING', OBJECT_TYPE[10], $log_details)) {
            $res['info'] = 'The log not created.';
            $res['color'] = 'danger';
        }
        return $res;
    }

    /**
     * USER ACCOUNT SETTUNGS
     * @param $post
     * @param $userId
     * @return array
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function accountSettings($post, $userId): array
    {
        // Получаем пользователя
        $user = R::load(USERS, $userId);
        $user->link = _E($post['link-pages']);
        $user->sound = _E($post['sound'] ?? '0');
        $user->view_mode = _E($post['dark-mode'] ?? 'light'); // light/dark
        $user->preview = _E($post['project-preview'] ?? 'docs'); // docs/image
        R::store($user);

        // Обновляем данные пользователя в сессии
        $_SESSION['userBean'] = R::load(USERS, $user['id']);

        $res['info'] = 'Settings saved successfully';
        $res['color'] = 'success';

        /* [     LOGS FOR THIS ACTION     ] */
        $log_details = "The user settings has been changed and saved";
        if (!logAction($user->user_name, 'ACCOUNT SETTINGS', OBJECT_TYPE[11], $log_details)) {
            $res['info'] = 'The log not created.';
            $res['color'] = 'danger';
        }
        return $res;
    }

    /**
     * USER PASSWORD CHANGEING FUNCTION
     * @param string $userId
     * @param $post
     * @return array
     * @throws //\PHPMailer\PHPMailer\Exception
     */
    public static function updatePasswordForUsers(string $userId, $post): array
    {
        if (isset($post["update-user-password"])) {
            $res = [];
            $user = R::load(USERS, $userId);
            $pass_1 = _E($post["password"]);
            $pass_2 = _E($post["re-password"]);
            if ($pass_1 == $pass_2) {
                $hash = password_hash($pass_1, PASSWORD_DEFAULT);
            }
            $user->user_hash = $hash;
            R::store($user);

            $res[] = ['info' => 'Password updated successfully', 'color' => 'success'];

            if (!empty($post["send-mail"]) && _E($post["send-mail"]) == '1') {
                $body = '<h3>Hello ' . $user->user_name . '</h3>';
                $body .= '<p>Your password has been successfully changed. Below are your login credentials:</p>';
                $body .= '<p style="color: blue;">Username: ' . $user->user_name . '<br>Password: ' . $pass_1 . '</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Please keep your password secure and do not share it with anyone.</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Sharing your password can lead to unauthorized access to your personal information.</p>';
                $body .= '<a style="border: solid 1px black; padding: 10px; background: #8cff79; border-radius: 7px;"' .
                    ' href="https://nti.icu" target="_blank">Log In to System</a>';
                $a = '<a href="https://nti.co.il" target="_blank">support</a>';
                $body .= '<p>If you did not request this account, please ignore this email or contact our ' . $a . ' if you have any concerns.</p>';
                $body .= '<p style="color: red; font-size: 1.1em;">Remember, keeping your password safe is your responsibility!</p>';
                $body .= '<p>Best regards,<br>NTI Group Company</p>';

                $answer = Mailer::SendEmailNotification(_E($post["email"]), $user['user_name'], 'Password Update NTI', $body);
                if ($answer != 'success')
                    $res[] = ['info' => $answer, 'color' => 'danger'];
                else
                    $res[] = ['info' => 'Mail sended successfully!', 'color' => 'success'];
            }

            /*    LOGS FOR THIS ACTION     */
            $log_details = "The user password has been changed and saved";
            if (!logAction($user->user_name, 'ACCOUNT SETTINGS', OBJECT_TYPE[11], $log_details)) {
                $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
            }
        } else {
            $res[] = ['info' => 'Please fill all the required fields.', 'color' => 'danger'];
        }
        $_SESSION['userBean'] = R::load(USERS, $userId);

        return $res;
    }
}