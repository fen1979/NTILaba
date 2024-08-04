<?php

class Management
{
    /**
     * CHECKING POST DATA FOR AN SCAM DATA
     * @param $post
     * @return array
     */
    private static function checkPostDataAndConvertToArray($post): array
    {
        $postDataArray = [];
        foreach ($post as $key => $item) {
            if (is_array($item)) {
                $postDataArray[$key] = self::checkPostDataAndConvertToArray($item);
            } else {
                $postDataArray[$key] = _E($item);
            }
        }
        return $postDataArray;
    }

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
                        $res['info'] = "Tool $tr deleted successfully!";
                    }
                    break;

            }

            $res['color'] = 'success';

            /* [     LOGS FOR THIS ACTION     ] */
            if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[12], $log_details)) {
                $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
            }
        } else {
            $res[] = ['info' => 'Incorrect password writed!', 'color' => 'danger'];
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
     * @param $thisUser
     * @return array
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function addOrUpdateUsersData($post, $thisUser): array
    {
        $post = self::checkPostDataAndConvertToArray($post);
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
                    $args = ['color' => 'success', 'info' => 'Registration complite!'];

                    $details = 'Worker named: ' . $user->user_name . ', Added successfully on: ' . date('Y/m/d') . ' at ' . date('h:i');
                    $details .= getServerData();
                    logAction($thisUser['user_name'], 'REGISTER', OBJECT_TYPE[11], $details);
                }
            } else {
                $args = ['color' => 'danger', 'info' => 'Registration error Password Data wrong!'];
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
                $args[] = ['info' => 'The user password has been successfully changed!!!<br>To log in, use the new password.', 'color' => 'danger'];
            } else {
                $args[] = ['info' => 'No password changes!<br>Ignore this message if you have not changed your password!', 'color' => 'warning'];
            }

            if (R::store($user)) {
                $log_details .= "<br>Changes: [$user->job_role, $user->app_role, $user->user_name]";
                $args[] = ['info' => 'Changes Saved successfully!', 'color' => 'success'];
            } else {
                $args[] = ['info' => 'Something went wrong!', 'color' => 'danger'];
            }

            logAction($user['user_name'], 'EDITING', OBJECT_TYPE[11], $log_details);
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
        if (isset($post['tools-editing']) && isset($post['tool_id'])) {
            $tool = R::load(TOOLS, _E($post['tool_id']));
            $imagePath = $tool->image ?? '';
            if (!empty($post['image-from-db'])) {
                $imagePath = _E($post['image-from-db']);
            }

            $log_action = 'EDITING';
            $log_details = "Tool id №:$tool->id was edited: [$tool->manufacturer_name, $tool->device_model]";
        }

        if (isset($post['tools-saving'])) {
            $tool = R::dispense(TOOLS);
            $imagePath = '';

            $log_action = 'CREATING';
        }

        $tool->manufacturer_name = _E($post['manufacturer_name']); // имя инструмента от производителя
        $tool->device_model = _E($post['device_model']); // модель инструмента
        $tool->device_type = _E($post['device_type']); // тип инструмента
        $tool->device_location = _E($post['device_location']); // рабочее местонахождение инструмента
        $tool->in_use = _E($post['in_use']); // рабочий который пользуется инструментом
        $tool->calibration = _E($post['calibration']); // NONC = no need calibration, NEC = need calibration
        $tool->serial_num = _E($post['serial_num']); // сирийный номер инструмента после калибровки
        $tool->date_of_inspection = _E($post['date_of_inspection']); // дата последней калибровки - обслуживания инструмента
        $tool->next_inspection_date = _E($post['next_inspection_date']); // следующая дата калибровки - обслуживания инструмента !!!
        $tool->work_life = _E($post['work_life']); // интервал обслуживания/калибровки (месяцев)
        $tool->responsible = _E($post['responsible']); // ответственный за инструмент
        $tool->remarks = _E($post['remarks']); // заметки на полях
        $tool->date_in = _E($post['date_in']) ?? date("Y-m-d h:i"); // дата внесения в БД

        //$nt->colored = $tool['colored']; // надо уточнить
        //$tool->esd = _E($post['esd-sertificate'] ?? 'ESD');


        /* saving image for tool */
        // fixme починить коректный вывод сообщений при обновлении инструмента!!!
        $result = self::saveOrChangeImage($files, $imagePath);
        $tool->image = $result['path']; // путь к фото инструмента или ПДФ


        if (R::store($tool) && $result['status']) {
            $act = $log_action == 'EDITING' ? 'updated' : 'saved';
            $res[] = ['info' => $result['error'] . '<br/> The tool ' . $act . ' successfully!', 'color' => 'success'];
        } else {
            $res[] = ['info' => $result['error'], 'color' => 'danger'];
        }

        //fixme сделать нормальный лог для обновления и создания и прочего
        $log_details .= ($log_action == 'creating_tool') ?
            "Tool id №:$tool->id was created: [$tool->manufacturer_name, $tool->device_model]" :
            "<br> TODO updated information!!!!!!!!!!!!!!!!!";

        /* [     LOGS FOR THIS ACTION     ] */
        if (!logAction($user['user_name'], $log_action, OBJECT_TYPE[9], $log_details)) {
            $res[] = ['info' => 'The log not created.', 'color' => 'danger'];
        }
        return $res;
    }

    /**
     * IMPORT TOOLS DATA FROM AN CSV FILE
     *
     * @param $post
     * @param $files
     * @param $user
     * @return null[]
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function importToolsListByCsvFile($post, $files, $user): array
    {
        $args = [null];
        if (isset($post['import-from-csv-file'])) {

            /* сохраняем файл с данными для работы */
            if (!empty($files['csvFile']['name'][0])) {
                $tmp_name = $files['csvFile']['tmp_name'];
                $uploadedFile = TEMP_FOLDER . basename($files['csvFile']['name']);
                $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

                if ($fileType == 'csv') {
                    // если файл соответствует требованиям сохраняем в ТМП папку
                    $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
                    if ($uploadSuccess) {

                        // Проверка наличия файла
                        if (!file_exists($uploadedFile)) {
                            $_SESSION['info'] = ['info' => 'File not found', 'color' => 'danger'];
                            die();
                        }

                        // Открытие файла для чтения
                        if (($handle = fopen($uploadedFile, "r")) !== FALSE) {
                            // Чтение первой строки с заголовками колонок
                            $headers = fgetcsv($handle, 1000);

                            // Определение ожидаемой структуры заголовков
                            $expectedHeaders = [
                                'manufacturer', 'model', 'device_type', 'location', 'in_use',
                                'calibration', 'serial', 'calibration_date', 'next_calibration',
                                'responsible', 'email', 'remarks', 'image'
                            ];

                            // Проверка структуры файла
                            if ($headers !== $expectedHeaders) {
                                // Закрытие файла
                                fclose($handle);

                                // Удаление временного файла CSV
                                unlink($uploadedFile);

                                // Вывод сообщения пользователю о несоответствии структуры файла
                                $args[] = ['info' => 'Error, file structure does not match expected format!', 'color' => 'danger'];
                                return $args;
                            }

                            // Массив для хранения данных из CSV файла
                            $data = [];

                            // Чтение каждой строки и преобразование в ассоциативный массив
                            while (($row = fgetcsv($handle, 1000)) !== FALSE) {
                                $rowLine = array_combine($headers, $row);
                                $data[] = $rowLine;
                            }

                            // Закрытие файла
                            fclose($handle);
                            $items = 0;

                            // Пример доступа к данным
                            foreach ($data as $rowLine) {
                                $nt = R::dispense(TOOLS);
                                $nt->manufacturer_name = $rowLine['manufacturer']; // имя инструмента от производителя
                                $nt->device_model = $rowLine['model']; // модель инструмента
                                $nt->device_type = $rowLine['device_type']; // тип инструмента
                                $nt->device_location = $rowLine['location']; // рабочее местонахождение инструмента
                                $nt->in_use = $rowLine['in_use']; // рабочий который пользуется инструментом
                                $nt->calibration = $rowLine['calibration']; // NONC = no need calibration, NEC = need calibration
                                $nt->serial_num = $rowLine['serial']; // сирийный номер инструмента после калибровки
                                $nt->date_of_inspection = $rowLine['calibration_date']; // дата последней калибровки - обслуживания инструмента
                                $nt->next_inspection_date = $rowLine['next_calibration']; // следующая дата калибровки - обслуживания инструмента !!!
                                $nt->work_life = '12'; // интервал обслуживания/калибровки (месяцев)
                                $res = json_encode(['name' => $rowLine['responsible'], 'email' => $rowLine['email'] ?? '']);
                                $nt->responsible = $res; // ответственный за инструмент
                                $nt->remarks = $rowLine['remarks']; // заметки на полях
                                $nt->image = $rowLine['image'] ?? null; // путь к фото инструмента или ПДФ
                                $nt->date_in = date('Y-m-d H:i'); // дата внесения в БД

                                R::store($nt);
                                $items++;
                            }

                        } else {
                            $args[] = ['info' => 'Error open file', 'color' => 'danger'];
                        }
                    } // upload success

                    // удаляем временный файл CSV
                    array_map('unlink', glob(TEMP_FOLDER . '*.*'));

                    // выводим сообщение пользователю
                    if ($items > 0) {
                        $log_details = "File was imported correctly.<br> Lines added: $items";
                        $args[] = ['info' => $log_details, 'color' => 'success'];

                        /* [     LOGS FOR THIS ACTION     ] */
                        if (!logAction($user['user_name'], 'IMPORT FILE', OBJECT_TYPE[9], $log_details)) {
                            $args[] = ['info' => 'The log not created.', 'color' => 'danger'];
                        }
                    } else {
                        $args[] = ['info' => 'No items added!', 'color' => 'warning'];
                    }
                } else {
                    $args[] = ['info' => 'Error, File format wrong! Only .csv', 'color' => 'danger'];
                }
            }
        }
        return $args;
    }


//    public static function importToolsListByCsvFile($post, $files, $user): array
//    {
//        $args = [null];
//        if (isset($post['import-from-csv-file'])) {
//
//            /* сохраняем файл с данными для работы */
//            if (!empty($files['csvFile']['name'][0])) {
//                $tmp_name = $files['csvFile']['tmp_name'];
//                $uploadedFile = TEMP_FOLDER . basename($files['csvFile']['name']);
//                $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));
//
//                if ($fileType == 'csv') {
//                    // если файл соответствует требованиям сохраняем в ТМП папку
//                    $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
//                    if ($uploadSuccess) {
//
//                        // Проверка наличия файла
//                        if (!file_exists($uploadedFile)) {
//                            $_SESSION['info'] = ['info' => 'File not found', 'color' => 'danger'];
//                            die();
//                        }
//
//                        // Открытие файла для чтения
//                        if (($handle = fopen($uploadedFile, "r")) !== FALSE) {
//                            // Чтение первой строки с заголовками колонок
//                            $headers = fgetcsv($handle, 1000);
//
//                            // Массив для хранения данных из CSV файла
//                            $data = [];
//
//                            // Чтение каждой строки и преобразование в ассоциативный массив
//                            while (($row = fgetcsv($handle, 1000)) !== FALSE) {
//                                $rowLine = array_combine($headers, $row);
//                                $data[] = $rowLine;
//                            }
//
//                            // Закрытие файла
//                            fclose($handle);
//                            $items = 0;
//                            // Пример доступа к данным
//                            foreach ($data as $rowLine) {
//                                $nt = R::dispense(TOOLS);
//                                $nt->manufacturer_name = $rowLine['manufacturer']; // имя инструмента от производителя
//                                $nt->device_model = $rowLine['model']; // модель инструмента
//                                $nt->device_type = $rowLine['device_type']; // тип инструмента
//                                $nt->device_location = $rowLine['location']; // рабочее местонахождение инструмента
//                                $nt->in_use = $rowLine['in_use']; // рабочий который пользуется инструментом
//                                $nt->calibration = $rowLine['calibration']; // NONC = no need calibration, NEC = need calibration
//                                $nt->serial_num = $rowLine['serial']; // сирийный номер инструмента после калибровки
//                                $nt->date_of_inspection = $rowLine['calibration_date']; // дата последней калибровки - обслуживания инструмента
//                                $nt->next_inspection_date = $rowLine['next_calibration']; // следующая дата калибровки - обслуживания инструмента !!!
//                                $nt->work_life = '12'; // интервал обслуживания/калибровки (месяцев)
//                                $res = json_encode(['name' => $rowLine['responsible'], 'email' => $rowLine['email'] ?? '']);
//                                $nt->responsible = $res; // ответственный за инструмент
//                                $nt->remarks = $rowLine['remarks']; // заметки на полях
//                                $nt->image = $rowLine['image'] ?? null; // путь к фото инструмента или ПДФ
//                                $nt->date_in = date('Y-m-d H:i'); // дата внесения в БД
//
//                                R::store($nt);
//                                $items++;
//                            }
//
//                        } else {
//                            $args[] = ['info' => 'Error open file', 'color' => 'danger'];
//                        }
//                    } // upload success
//
//                    // удаляем временный файл CSV
//                    array_map('unlink', glob(TEMP_FOLDER . '*.*'));
//
//                    // выводим сообщение пользователю
//                    if ($items > 0) {
//                        $log_details = "File was imported correctly.<br> Lines added: $items";
//                        $args[] = ['info' => $log_details, 'color' => 'success'];
//
//                        /* [     LOGS FOR THIS ACTION     ] */
//                        if (!logAction($user['user_name'], 'IMPORT FILE', OBJECT_TYPE[9], $log_details)) {
//                            $args[] = ['info' => 'The log not created.', 'color' => 'danger'];
//                        }
//                    } else {
//                        $args[] = ['info' => 'No items added!', 'color' => 'warning'];
//                    }
//                } else {
//                    $args[] = ['info' => 'Error, File format wrong! Only .csv', 'color' => 'danger'];
//                }
//            }
//        }
//        return $args;
//    }

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
            // if email exist in query
            $user->email = !empty($post["email"]) ? $post["email"] : null;
            R::store($user);

            $res[] = ['info' => 'Password updated successfully', 'color' => 'success'];

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