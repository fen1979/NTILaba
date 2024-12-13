<?php

class Project
{
    /* ============================ PROTECTED METHODS =============================== */

    /**
     * функция сохранения файла документации к проекту
     * @param $files
     * @param $projectName
     * @param mixed $filename
     * @param bool $isNew
     * @return array
     */
    private static function saveProjectDocumentation($files, $projectName, mixed $filename = '', bool $isNew = true): array
    {
        //var_dump($files);
        $dataArray = ['args' => null, 'filename' => null];
        $docDir = PROJECTS_FOLDER . "$projectName/docs/";

        if (!empty($files['dockFile']['name'])) {
            $tmp_name = $files['dockFile']['tmp_name'];
            $fileSize = $files['dockFile']['size'];
            $originalName = basename($files['dockFile']['name']);
            $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'application/pdf'];
            $maxSize = 20 * 1024 * 1024;

            // если размер больше чем надо или файл не того расширения отеняем операцию
            if (!in_array($fileType, $allowedExtensions) || $fileSize > $maxSize) {
                _flashMessage('Invalid file type or size.', 'danger');
                return [null];
            }

            // Переименование старого файла если это замена
            if (!$isNew) {
                if (str_contains($filename, '.pdf') && is_file($filename)) {
                    rename($filename, 'archivated-' . $filename);
                }
            }

            // Получение оригинального имени без расширения
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);

            // creating path with full name of file
            $uploadedFile = $docDir . $fileName . '.' . $fileType;

            if (move_uploaded_file($tmp_name, $uploadedFile)) {
                $dataArray['args'] = true;
                $dataArray['filename'] = $uploadedFile;
                _flashMessage('Successfully uploaded the file.');
            } else {
                _flashMessage('Error uploading the file.', 'danger');
            }
        }

        // добавление множества файлов в папку с документами
        if (!empty($files['projects_files'])) {
            $files_s = $files['projects_files'];
            $numFiles = count($files_s['name']);

            for ($i = 0; $i < $numFiles; $i++) {
                // Получаем временное имя файла, его оригинальное имя и путь для сохранения
                $tmpName = $files_s['tmp_name'][$i];
                $fileName = $files_s['name'][$i];
                $uploadPath = $docDir . basename($fileName);

                // Перемещаем файл из временного места в целевую папку
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $dataArray['args'] = (bool)$dataArray['args'];
                    _flashMessage("Files $numFiles pcs, uploaded successfully.");
                } else {
                    _flashMessage("Error while uploading $fileName.", 'danger');
                }
            }

        }

        return $dataArray;
    }

    /**
     * GET DIGITS FROM QUANTITY INPUT
     * @param $qty
     * @return float
     */
    private static function isDigits($qty): float
    {
        // Удаляем все символы, кроме цифр, точек, запятых и пробелов
        $filtered = preg_replace('/[^0-9.,\s]/', '', $qty);

        // Ищем первую последовательность цифр и разделителей (точек, запятых)
        if (preg_match('/\d+([.,]\d+)?/', $filtered, $matches)) {
            // Получаем строку, содержащую только числа и разделители
            $numbersAndSeparators = $matches[0];

            // Заменяем все запятые на точки для унификации
            $standardizedNumber = str_replace(',', '.', $numbersAndSeparators);

            // Преобразуем строку в число с плавающей запятой
            return (float)$standardizedNumber;
        }

        // Если цифр нет, возвращаем 1
        return 1.0;
    }


    /**
     * CHANGE FOLDER NAMES AND PATHS IN TO DB
     * @param $id
     * @param $newName
     * @throws \\RedBeanPHP\RedException\SQL
     */
    private static function changeProjectName($id, $newName): void
    {
        // Загрузка проекта
        $project = R::load(PROJECTS, $id);
        $orders = R::findAll(ORDERS, 'project_name = ?', [$project->projectname]);

        // изменяем имена проектов в заказах как старых так и новых
        // TODO не проверено
        if ($orders) {
            foreach ($orders as $order) {
                if ($order['project_name'] == $project->projectname)
                    $order['project_name'] = $newName;
            }
            R::storeAll($orders);
        }

        // Определение старого и нового имени проекта в путях
        $oldName = basename($project->projectdir); // Извлечение имени проекта из текущего пути
        $baseDir = dirname($project->projectdir);  // Основная директория проектов

        // Новые пути
        $newProjectDir = $baseDir . '/' . $newName;
        $newHistoryDir = $newProjectDir . '/history/';
        $newDocsDir = $newProjectDir . '/docs/';
        // новое имя
        $project->projectname = $newName;

        // Переименование физической папки проекта на диске
        if (!rename($project->projectdir, $newProjectDir)) {
            _flashMessage('Error renaming project directory', 'danger');
        }

        // Обновление путей в объекте проекта
        $project->projectdir = $newProjectDir;
        $project->historydir = $newHistoryDir;
        $project->docsdir = $newDocsDir;

        // если еть докуиентация то меняем ее имя для правильного вывода на страницах
        if (!empty($project->projectdocs) && str_contains($project->projectdocs, '.pdf'))
            $project->projectdocs = str_replace($oldName, $newName, $project->projectdocs);

        // Загрузка всех шагов проекта
        $steps = R::findAll(PROJECT_STEPS, 'projects_id LIKE ?', [$id]);
        if ($steps) {
            // Обновление путей в шагах проекта
            foreach ($steps as $step) {
                $step->image = str_replace($oldName, $newName, $step->image);
                if ($step->video != 'none') {
                    $step->video = str_replace($oldName, $newName, $step->video);
                }
            }
            // Сохранение обновленных данных в БД
            R::storeAll($steps);
        }
        // Сохранение обновленных данных в БД
        R::store($project);

        _flashMessage('Path and folders name changed successfully');
    }

    /* ============================ PROJECT METHODS =============================== */
    /**
     * PROJECT CREATION METHOD
     * @param $post
     * @param $user
     * @param $files
     * @return array|string[]
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function createNewProject($post, $user, $files = null): array
    {
        $log_details = '';
        /* Получаем данные из формы*/
        $post = checkDataAndConvertToArray($post);
        $projectName = preg_replace('/[^a-zA-Z0-9]/', '-', $post['projectName']);

        /* Создаем папку проекта*/
        $projectDir = PROJECTS_FOLDER . $projectName . '/';
        if (!is_dir($projectDir))
            mkdir($projectDir);

        /* создаем папку для документации прокфета */
        $projectDocs = $projectDir . 'docs/';
        if (!is_dir($projectDocs))
            mkdir($projectDocs);

        /* Создаем папку истории изменений проекта*/
        $historyDir = $projectDir . 'history/';
        if (!is_dir($historyDir))
            mkdir($historyDir);

        $log_details .= '<br> folders for project creted';

        $project = R::dispense(PROJECTS);
        $project->projectname = $projectName;
        $project->priority = $post['priorityMakat'] ?? '0';
        $project->headpay = $post['headPay'] ?? '0';
        $customerId = $project->customerid = $post['customerId'] ?? 0; // данные для возврата
        $customerName = $project->customername = $post['customerName']; // данные для возврата
        $project->executor = $post['executorName'];
        $project->creator = $user['user_name'];
        $vers = $project->revision = $post['projectRevision']; // данные для возврата
        $project->extra = $post['extra'];
        $project->projectdir = $projectDir; // папка проекта
        $project->historydir = $historyDir; // папка истории изменений в проекте
        $project->docsdir = $projectDocs; // папка документации к проекту
        $tools = (!empty($post['selected-tools'])) ? $post['selected-tools'] : 'NC';
        $project->tools = ($tools != 'NC') ? implode(',', $tools) : $tools;
        $project->sharelink = unicum();
        $project->sub_assembly = $post['sub_assembly'] ?? 0; // if project include only route actions instructions
        $project->project_type = $post['project_type'] ?? 0; // if project is SMT line assembly type = 1

        // Дополнительно можно провести валидацию даты, с помощью DateTime::createFromFormat
        $date = str_replace('T', ' ', $post['date_in']);
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $date);
        if ($dateTime && $dateTime->format('Y-m-d H:i') === $date) {
            $project->date_in = $date;
        }

        // если есть файлы для сохранения в папку документация
        if (!empty($files['dockFile']['name'][0]) || !empty($files['projects_files'])) {
            // сохраняем ПДФ документацию в папку
            if (!empty($files['dockFile']['name'][0])) {
                $result = self::saveProjectDocumentation($files, $projectName);
                // если файл пдф сохранен
                if ($result['args']) {
                    /* сохранить путь к файлу в базу данных */
                    $project->projectdocs = $result['filename'];
                    $log_details .= '<br> documentation file added to project';
                }
            }

            // сохраняем другие файлы в папку
            if (isset($files['projects_files']) && is_array($files['projects_files']) && count($files['projects_files']) > 0) {
                self::saveProjectDocumentation($files, $projectName);
            }

        } else {
            _flashMessage('Project documentation not added!', 'warning');
        }

        // сохраняем данные в БД и формируем обратные данные для возврата на страницу создания заказа
        $args['id'] = $id = R::store($project); // сохраняем проект в БД
        $args['customerName'] = $customerName;
        $args['customerId'] = $customerId;
        $args['projectName'] = $projectName;
        $args['projectRevision'] = $vers;

        $log_details .= "<br> Data: $customerName, $projectName, Rev-$vers";

        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Project id:$id,| $log_details";
        if (!logAction($user['user_name'], 'CREATION', OBJECT_TYPE[3], $details)) {
            _flashMessage('Log creation failed.', 'danger');
        }

        return $args;
    }

    /**
     * PROJECT DATA EDITING METHOD
     * @param $post
     * @param $user
     * @param $files
     * @return void
     * @throws //\RedBeanPHP\RedException\SQL
     */
    public static function editProjectInformation($post, $user, $files = null): void
    {
        $post = checkDataAndConvertToArray($post);
        $log_details = '<h4>Changes</h4>';

        /* Получаем данные из формы*/
        $projectName = $post['projectName'];
        $projectName = preg_replace('/[^a-zA-Z0-9]/', '-', $projectName);
        $priorityMakat = $post['priorityMakat'] ?? '0';
        $headPay = $post['headPay'] ?? '0';
        $customerId = $post['customerId'] ?? 0;
        $customerName = $post['customerName'];
        $executorName = $post['executorName'];
        $sub_assembly = $post['sub_assembly'] ?? 0;
        $project_type = $post['project_type'] ?? 0;
        $projectRevision = $post['projectRevision'];
        $extra = $post['extra'];
        $changNameNeeded = false;

        /* заполняем таблицу в БД и создаем логи действий */
        $project = R::load(PROJECTS, $post['projectid']);

        // change folders name and path names in DB
        if ($project->projectname != $projectName) {
            $changNameNeeded = true;
        } else {
            $log_details .= "<br> Project name not Changed:  $projectName";
        }

        $log_details .= "<br> Priority Before: $project->priority -> After  $priorityMakat";
        $project->priority = $priorityMakat; // SKU in priority programm

        $log_details .= "<br> HeadPay Before: $project->headpay -> After:  $headPay";
        $project->headpay = $headPay; // customer HP

        $log_details .= "<br> Customer ID Before: $project->customerid -> After:  $customerId";
        $project->customerid = $customerId; // customer id

        $log_details .= "<br> Customer Name Before: $project->customername -> After:  $customerName";
        $project->customername = $customerName; // customer name for this project

        $log_details .= "<br> Executor Name Before: $project->executor -> After:  $executorName";
        $project->executor = $executorName; // company name who is execute the project

        $log_details .= "<br> Revision Before: $project->revision -> After:  $projectRevision";
        $project->revision = $projectRevision; // project revision

        $log_details .= "<br> Note Before: $project->extra <br> After:  $extra";
        $project->extra = $extra; // description or any extra information

        $sas = ($project->sub_assembly == 1) ? 'SAS true' : 'SAS none';
        $log_details .= "<br> Note Before: $project->sub_assembly -> After: $sas ";
        $project->sub_assembly = $sub_assembly; // if project include only route actions instructions

        $smt = ($project->project_type == 1) ? 'SMT true' : 'SMT none';
        $log_details .= "<br> Note Before: $project->project_type -> After: $smt ";
        $project->project_type = $project_type; // if project is SMT line assembly type = 1

        $tools = $_POST['selected-tools'] ?? [];
        $t = $project->tools = implode(',', $tools);
        $log_details .= "<br> Tools ID Before: $project->tools -> After:  $t";

        /* сохраняем документацию в папку */
        if (!empty($files['dockFile']['tmp_name']) || !empty($files['projects_files']['name'][0])) {
            // if project name has changed erlier than added or edited docs file
            $result = self::saveProjectDocumentation($files, $projectName, $project->projectdocs, false);
            if (isset($result['args']) && $result['args']) {
                /* сохранить путь к файлу в базу данных */
                $project->projectdocs = $result['filename'];
            }
        }

        // Проверяем, существует ли в $post ключ 'date_in' и не пустое ли его значение
        if (!empty($post['date_in'])) {
            $date = str_replace('T', ' ', $post['date_in']);
            // Дополнительно можно провести валидацию даты, например, с помощью DateTime::createFromFormat
            $dateTime = DateTime::createFromFormat('Y-m-d H:i', $date);
            if ($dateTime && $dateTime->format('Y-m-d H:i') === $date) {
                // Дата валидна, можно обновлять в БД
                if ($project->date_in != $date) {
                    $log_details .= "<br> Before: $project->date_in -> After:  $date";
                    $project->date_in = $date;
                }
            }
        } // если дата отсутствует то просто оставляем старую как есть

        $id = R::store($project); // save changes

        // if name changed than changing folders and path names
        if ($changNameNeeded) {
            self::changeProjectName($project->id, $projectName);
            $log_details .= "<br> Project Name Before: $project->projectname -> After:  $projectName";
        }
        _flashMessage($log_details, 'success', false);
        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Project id:$id,| $log_details";
        if (!logAction($user['user_name'], 'EDITING', OBJECT_TYPE[3], $details)) {
            _flashMessage('Log creation failed.', 'danger');
        }
    }

    /**
     * MANUALY CREATE ITEM IN PROJECT BOM
     * @param $post
     * @param $user
     * @param $project_id
     * @return void
     * @throws /\RedBeanPHP\RedException\SQL
     */
    public static function createProjectBomItem($post, $user, $project_id): void
    {
        $post = checkDataAndConvertToArray($post);
        $project = R::load(PROJECTS, $project_id);

        $partList = R::dispense(PROJECT_BOM);
        $partList->customerid = $post['owner_id'];  // customer id hidden val
        $partList->sku = $post['sku'];  // sku makat
        $partList->part_name = $post['part_name'] ?? '';  // part name
        $partList->part_value = $post['part_value'] ?? '';  // part value
        $partList->mounting_type = $post['mounting_type'] ?? '';  // part type
        $partList->footprint = $post['footprint'] ?? '';  // footprint
        $partList->manufacturer = $post['manufacturer'] ?? '';  // manufacturer
        $partList->manufacture_pn = $post['manufacture_pn'] ?? '';  // manufacturer p/n
        $partList->owner_pn = $post['owner_pn'] ?? '';  // customer p/n && our p/n

        // добавляем ID детали из БД если она есть в БД
        // если детали нет то оставляем пустое поле
        if (!empty($post['item_id'])) {
            $partList->item_id = $post['item_id'];  // warehouse item id
        }

        $partList->description = $post['description'];  // description
        $partList->notes = $post['note'];  // note
        // before set number to DB check if this digit amount for one peace can be double!!!
        $partList->amount = self::isDigits($post['qty']);  // кол-во для одной штуки в сборке
        $partList->length_mm = self::isDigits($post['length_mm']);  // длина детали для сборки в МИЛЛИМЕТРАХ
        // tables relations
        $partList->projects_id = $project->id;

        R::store($partList);

        _flashMessage('Item Added successfully');
        /* [ LOG WRITING ACTION ] */
        $details = 'Item for project ID= ' . $project_id . ', Added to BOM';
        if (!logAction($user['user_name'], 'CREATING', OBJECT_TYPE[3], $details)) {
            _flashMessage('Log creation failed!', 'danger');
        }
    }

    /**
     * UPDATE AN ITEM IN PROJECT BOM
     * @param $post
     * @param $user
     * @param $project_id
     * @param $item_id
     * @return string[] - args => true
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function updateProjectBomItem($post, $user, $project_id, $item_id): array
    {
        $post = checkDataAndConvertToArray($post);
        $item = R::load(PROJECT_BOM, $item_id);
        $item->sku = $post['sku'];  // sku makat
        $item->part_name = $post['part_name'];  // part name
        $item->part_value = $post['part_value'];  // part value
        $item->mounting_type = $post['mounting_type'];  // part type
        $item->footprint = $post['footprint'];  // footprint
        $item->manufacturer = $post['manufacturer'];  // manufacturer
        $item->manufacture_pn = $post['manufacture_pn'];  // manufacturer p/n
        $item->owner_pn = $post['owner_pn'];  // owner p/n
        $item->description = $post['description'];  // description
        $item->notes = $post['note'];  // note
        // before set number to DB check if this digit amount for one peace can be double!!!
        $item->amount = self::isDigits($post['qty']);  // кол-во для одной штуки в сборке
        $item->length_mm = self::isDigits($post['length_mm']);  // длина детали для сборки в МИЛЛИМЕТРАХ

        R::store($item);

        _flashMessage('Item Updated successfully L');
        /* [ LOG WRITING ACTION ] */
        $details = 'Item SKU=' . $post['sku'] . ',  for project ID= ' . $project_id . ', Updated in BOM';
        if (!logAction($user['user_name'], 'UPDATING', OBJECT_TYPE[5], $details)) {
            _flashMessage('Log creation failed!', 'danger');
        }
        return ['args' => true];
    }

    /**
     * DELETE ITEM FROM PROJECT BOM
     * @param $post
     * @param $user
     * @return void
     */
    public static function deleteProjectBomItem($post, $user): void
    {
        $post = checkDataAndConvertToArray($post);
        if (checkPassword($post['password'], true, $user)) {
            $it = R::load(PROJECT_BOM, $post['itemId']);
            $details = 'Item ID=' . $it->id . ', Deleted from Project ID=' . $it->projects_id . '<br>';
            $details .= 'Item bla bla bla add some description letter';
            _flashMessage('Item deleted!');

            $bomid = Undo::StoreDeletedRecord(PROJECT_BOM, $it->id);
            $url = '<a href="/check_part_list?undo=true&bomid=' . $bomid . '&pid=' . $it->projects_id . '" class="btn btn-outline-dark fs-5">Undo Delete Item</a>';
            _flashMessage($url, 'dark');

            R::trash($it);

            /* [ LOG WRITING ACTION ] */
            if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[5], $details)) {
                _flashMessage('Log creation failed!', 'danger');
            }
        } else {
            _flashMessage('Password incorrect!', 'danger');
        }
    }

    /**
     * ADDING PROJECTS TO ARCHIVE
     * @param $post
     * @param $user
     * @return void
     * @throws \\RedBeanPHP\RedException\SQL
     */
    public static function archiveOrExstractProject($post, $user): void
    {
        $log_details = '';
        /* Project archivation */
        if (isset($post['archive'])) {
            if (checkPassword(_E($post['password']))) {
                $projectid = _E($post['projectid']);
                $project = R::load(PROJECTS, $projectid);
                $project->archivation = ARCHIVATED; // in archive = 0
                R::store($project);
                _flashMessage("Project added to archive successfully!");
                $log_details = "Project name: $project->projectname was added to archive.<br>
                                For extract project from archive go to 'SETTINGS/PROJECTS' 
                                and find project by ID: $project->id";
            } else {
                _flashMessage("Incorrect password writed!", 'danger');
            }
        }

        /* Project extraction */
        if (isset($post['archive-extract'])) {
            if (checkPassword(_E($post['password']))) {
                $projectid = _E($post['projectid']);
                $project = R::load(PROJECTS, $projectid);
                $project->archivation = !ARCHIVATED; // not in archive = 1
                R::store($project);
                _flashMessage("Project extracted from archive successfully!");
                $log_details = "Project name: $project->projectname was extracted from archive.";
            } else {
                _flashMessage("Incorrect password writed!", 'danger');
            }
        }

        if (!logAction($user['user_name'], 'ARCHIVE', OBJECT_TYPE[3], $log_details)) {
            _flashMessage('Log creation failed.', 'danger');
        }
    }

    /**
     * DELETING PROJECT AND DATA
     * @param $post
     * @param $user
     * @return void
     */
    public static function deleteProject($post, $user): void
    {
        if (checkPassword(_E($post['password']))) {
            $projectid = _E($post['projectid']);
            $pTmp = $project = R::load(PROJECTS, $projectid);
            $projectData = R::find(PROJECT_STEPS, "projects_id LIKE ?", [$projectid]);
            $history = R::find(HISTORY, "projects_id LIKE ?", [$projectid]);

            $projectdir = $project['projectdir'];
            $historydir = $project['historydir'];
            $docsdir = $projectdir . "/docs/";

            /* проверяем папку проекта */
            if (is_dir($projectdir)) {
                /* проверяем если есть папка истории */
                if (is_dir($historydir)) {
                    /* удаляем все фото */
                    array_map('unlink', glob("$historydir*.*"));
                    /* удаляем папку */
                    rmdir($historydir);
                }

                /* проверяем если есть папка docs */
                if (is_dir($docsdir)) {
                    /* удаляем все фото */
                    array_map('unlink', glob("$docsdir*.*"));
                    /* удаляем папку */
                    rmdir($docsdir);
                }

                /* удаляем все фото */
                array_map('unlink', glob("$projectdir*.*"));
                /* удаляем папку */
                rmdir($projectdir);

                $log_details = "Project name: {$pTmp['projectname']}, Customer name: {$pTmp['customername']}<br>";

                /* удаляем все данные из БД */
                R::trashAll($projectData);
                R::trashAll($history);
                R::trash($project);
            }

            _flashMessage("Project deleted successfully!");

            if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[3], $log_details)) {
                _flashMessage('Log creation failed.', 'danger');
            }
        } else {
            _flashMessage("Incorrect password writed!", 'danger');
        }
    }

    /* ============================ STEPS METHODS =============================== */
    /**
     * CREATE NEW STEP FOR PROJECT ASSEMBLY LINE
     * @param $post
     * @param $user
     * @param $files
     * @param $project_id
     * @throws /\RedBeanPHP\RedException\SQL
     */
    public static function addNewStepToProject($post, $user, $files, $project_id): void
    {
        $uploadDir = TEMP_FOLDER;
        $project = R::load(PROJECTS, $project_id);
        $projectDir = $project->projectdir;
        /* Получаем данные из формы */
        $post = checkDataAndConvertToArray($post);

        $toSave = 0;
        $log_details = '';

        if (!empty($files['photoFile']['name'][0]) && empty($post['choosed-step-image-path'])) {

            $uniqueID = unicum($project_id);
            $outputFile = "$projectDir$uniqueID.webp";

            $tmp_name = $files['photoFile']['tmp_name'];
            $uploadedFile = $uploadDir . basename($files['photoFile']['name']);
            $imageFileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

            if ($imageFileType != 'webp') {
                $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
            } else {
                $uploadSuccess = move_uploaded_file($tmp_name, $outputFile);
                $toSave = 1;
            }
            /* when file uploaded then converting to webp format if need */
            if ($uploadSuccess && !$toSave) {
                try {
                    if (Converter::convertToWebP($uploadedFile, $outputFile)) {
                        array_map('unlink', glob("$uploadDir*.*"));
                        $toSave = 1;
                        _flashMessage('<br>Image saved successfully');
                    } else {
                        _flashMessage('<br>Conversion error, image format not supported!', 'danger');
                    }
                } catch (Exception $e) {
                    _flashMessage(print($e), 'danger');
                }
            } else {
                if (!$uploadSuccess) {
                    _flashMessage('<br>Error! image uploading file!', 'danger');
                }
            }
        } else {
            // Конвертация выбранного файла, сохраненного ранее
            if (!empty($post['choosed-step-image-path'])) {
                $uploadedFile = $post['choosed-step-image-path']; // путь к файлу
                $uniqueID = unicum($project_id); // генератор имен
                $outputFile = "$projectDir$uniqueID.webp"; // путь для сохранения конвертации

                try {
                    // Проверяем существование файла
                    if (!file_exists($uploadedFile)) {
                        _flashMessage('<br>File does not exist: ' . htmlspecialchars($uploadedFile), 'danger');
                        return;
                    }

                    // Конвертируем файл
                    if (Converter::convertToWebP($uploadedFile, $outputFile)) {
                        // Удаляем исходный файл после успешной конвертации
                        if (unlink($uploadedFile)) {
                            _flashMessage('<br>Image converted and saved successfully. Original file deleted.');
                        } else {
                            _flashMessage('<br>Image converted, but failed to delete the original file.', 'warning');
                        }
                        $toSave = 1;
                    } else {
                        _flashMessage('<br>Conversion error: Image format not supported!', 'danger');
                    }
                } catch (Exception $e) {
                    _flashMessage('<br>Error: ' . htmlspecialchars($e->getMessage()), 'danger');
                }
            }

            if ($project->sub_assembly == 0) {
                // выводим ошибку отсутствие файла !
                _flashMessage('<br>Error! Image file not exist!', 'danger');
            } else {
                // сохраняем при условии что при создании проекта
                // был выбран чекбокс "проект без медиа"
                // или проект при создании получил тип СМТ сборка
                $toSave = 1;
            }
        }

        /* if video file exist for this step */
//        if (isset($files['videoFile'])) {
//
//            $fileName = basename($files['videoFile']['name']);
//            $targetFilePath = $uploadDir . $fileName;
//            $uniqueID = unicum($project_id);
//            $outputVideoFile = "$projectDir$uniqueID.mp4";
//
//            // Проверяем, что файл был загружен через HTTP POST
//            if (is_uploaded_file($files['videoFile']['tmp_name'])) {
//                // Перемещаем файл в целевую директорию
//                if (move_uploaded_file($files['videoFile']['tmp_name'], $targetFilePath)) {
//                    _flashMessage("File uploaded successfully: " . $targetFilePath);
//                    // Здесь можно добавить вызов функции для конвертации видео
//                    // Проверяем, является ли файл видео MP4 с кодеком H.264
//                    if (Converter::isMp4H264($targetFilePath)) {
//                        // Файл уже в нужном формате, переименуем и переместим его
//                        rename($targetFilePath, $outputVideoFile);
//                    } else {
//                        // Файл не в формате MP4 H.264, конвертируем его
//                        Converter::convertToMp4H264($targetFilePath, $outputVideoFile);
//                        // Удаление исходного файла, если необходимо
//                        array_map('unlink', glob("$uploadDir*.*"));
//                    }
//                    $toSave = 1;
//                } else {
//                    _flashMessage('<br>Error! uploading video file!', 'danger');
//                }
//
//            } else {
//                _flashMessage('<br>Notice: Video file not exist!', 'warning');
//                if ($toSave == 1)
//                    $outputVideoFile = 'none';
//            }
//        } else {
//            if ($toSave == 1)
//                $outputVideoFile = 'none';
//        }
        if (!empty($post['choosed-step-video-path']) || isset($files['videoFile'])) {
            $uploadedFile = ''; // Инициализируем переменную для файла
            $uniqueID = unicum($project_id); // Генератор имен
            $outputVideoFile = "$projectDir$uniqueID.mp4"; // Путь для сохранения конвертированного файла

            // Если файл выбран из существующих
            if (!empty($post['choosed-step-video-path'])) {
                $uploadedFile = $post['choosed-step-video-path']; // Путь к существующему файлу

                // Проверяем существование файла
                if (!file_exists($uploadedFile)) {
                    _flashMessage('<br>File does not exist: ' . htmlspecialchars($uploadedFile), 'danger');
                    return;
                }
            }

            // Если файл загружается с формы
            if (isset($files['videoFile'])) {
                $fileName = basename($files['videoFile']['name']);
                $targetFilePath = $uploadDir . $fileName;

                if (is_uploaded_file($files['videoFile']['tmp_name'])) {
                    if (move_uploaded_file($files['videoFile']['tmp_name'], $targetFilePath)) {
                        _flashMessage("File uploaded successfully: " . $targetFilePath);
                        $uploadedFile = $targetFilePath; // Используем загруженный файл для дальнейшей обработки
                    } else {
                        _flashMessage('<br>Error! uploading video file!', 'danger');
                        return;
                    }
                } else {
                    _flashMessage('<br>Notice: Video file not exist!', 'warning');
                    return;
                }
            }

            // Если файл определен (из существующих или загруженных), проверяем и конвертируем
            try {
                // Проверяем, является ли файл видео MP4 с кодеком H.264
                if (Converter::isMp4H264($uploadedFile)) {
                    // Если файл уже в нужном формате, копируем его с новым именем
                    if (copy($uploadedFile, $outputVideoFile)) {
                        _flashMessage('<br>Video is already in MP4 H.264 format. File copied successfully.');
                    } else {
                        _flashMessage('<br>Error copying video file.', 'danger');
                        return;
                    }
                } else {
                    // Если файл не в нужном формате, конвертируем его
                    Converter::convertToMp4H264($uploadedFile, $outputVideoFile);

                    // Проверяем, создался ли выходной файл после конвертации
                    if (file_exists($outputVideoFile)) {
                        _flashMessage('<br>Video converted to MP4 H.264 format successfully.');
                    } else {
                        _flashMessage('<br>Error converting video to MP4 H.264 format.', 'danger');
                        return;
                    }
                }

                $toSave = 1; // Флаг для сохранения шага
            } catch (Exception $e) {
                _flashMessage('<br>Error: ' . htmlspecialchars($e->getMessage()), 'danger');
            }
        } else {
            // Если видео файл не выбран и не загружен
            if ($toSave == 1) {
                $outputVideoFile = 'none';
            }
        }

        /* сохраняем данные в таблицу */
        if ($toSave) {
            $projectData = R::dispense(PROJECT_STEPS);
            $projectData->image = $outputFile ?? '';
            $projectData->front_pic = $post['front-picture'] ?? 0; // создаем галлерею на главной странице
            $projectData->video = $outputVideoFile;
            $projectData->step = $post['actionNumber'];
            $projectData->part_number = $post['part-number'] ?? '';
            $projectData->note = $post['note'] ?? '';
            $projectData->description = trim($post['actionDescription']);
            /* записываем переменную в ЬД проверяя на вредонос */
            $projectData->routeaction = $post['routeAction'];
            /* добавляем id рут акции в рут карту для работы над проектом в заказах */
            $projectData->routeid = (int)$post['routeid'];
            $projectData->validation = (!empty($post['validation']) && $post['validation'] == 'on') ? 1 : 0;
            $projectData->revision = '0';
            $projectData->tool = $post['tool'] ?? 'no choosen'; // id of tool table element

            R::store($projectData);
            /* добавляем шаг к списку шагов в проекте */
            $project->ownStepsList[] = $projectData;
            $id = R::store($project);

            // выводим ответ пользователю
            _flashMessage('<br>Step successfully saved.');
        } else {
            _flashMessage('<br>Error! saving stepsData to DB!', 'danger');
        }

        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Project id:$id,| $log_details";
        if (!logAction($user['user_name'], 'CREATING', OBJECT_TYPE[4], $details)) {
            _flashMessage('<br>Log creation failed.', 'danger');
        }
    }

    /**
     * EDITING EXISTING STEP IN PROJECT ASSEMBLY LINE
     * @param $post
     * @param $user
     * @param $files
     * @param $step_id
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function editProjectStep($post, $user, $files, $step_id): void
    {
        $post = checkDataAndConvertToArray($post);
        $toHistory = $stepToChange = R::load(PROJECT_STEPS, $step_id);
        $project = R::load(PROJECTS, $stepToChange->projects_id);
        $project_id = $stepToChange->projects_id;
        $log_details = array();
        /* 1=validation, 2=step num, 3=revision, 4=decription, 5=route act, 6=tool, 7=image, 8=video */
        $changes = explode(',', $post['changedFields']);

        foreach ($changes as $key) {
            switch ($key) {
                case 1:
                    $out = self::changeValidation($post, $step_id);
                    $log_details['validation'] = $out['log'];
                    break;
                case 2:
                    $out = self::shiftStep($stepToChange->projects_id, $post['oldStepNumber'], $post['newStepNumber']);
                    $log_details['shiftStep'] = $out['log'];
                    break;
                case 3:
                    $out = self::changeRevision($post, $step_id);
                    $log_details['revision'] = $out['log'];
                    break;
                case 4:
                    $out = self::changeDescription($post, $step_id);
                    $log_details['description'] = $out['log'];
                    break;
                case 5:
                    $out = self::changeRouteAction($post, $step_id);
                    $log_details['routeAction'] = $out['log'];
                    break;
                case 6:
                    $out = self::changeTool($post, $step_id);
                    $log_details['tool'] = $out['log'];
                    break;
                case 7:
                    if (!empty($files['imageFile']['name'][0])) {
                        $ou = self::changeImageFile($files, $step_id, $project_id);
                        $log_details['image'] = $ou['log'];
                    }
                    break;
                case 8:
                    if (!empty($files['videoFile']['name'][0])) {
                        $ou = self::changeVideoFile($files, $step_id, $project_id);
                        $log_details['video'] = $ou['log'];
                    }
                    break;
                case 9:
                    $out = self::changeFrontPicture($step_id, $post);
                    $log_details['front-picture'] = $out['log'];
                    break;
                case 10:
                    $out = self::changePartNumber($step_id, $post);
                    $log_details['part-number'] = $out['log'];
                    break;
                case 11:
                    $out = self::changeNotice($step_id, $post);
                    $log_details['note'] = $out['log'];
                    break;
                default:
                    _flashMessage('No Changes added!', 'warning');
                    break;
            }
            // выводим сообщение если оно есть
            if (!empty($out))
                _flashMessage($out['info'], $out['color']);
        }
        /* after all changes in to step saving the history and logs */
        if ($log_details)
            self::saveStepHistory($log_details, $user, $toHistory, $project);
    }

    /* =============================== STEP CHANGES PROTECTED METHODS ================================= */
    private static function shiftStep($projectid, $stepFrom, $stepTo): array
    {
        $stepFrom--;
        $stepTo--;
        $tempDataArray = array_values(R::find(PROJECT_STEPS, "projects_id LIKE ? ORDER BY step ASC", [$projectid]));
        $removedElement = array_splice($tempDataArray, $stepFrom, 1);
        array_splice($tempDataArray, $stepTo, 0, $removedElement);
        $stepCount = 1;
        foreach ($tempDataArray as $item) {
            $item->step = $stepCount;
            $stepCount++;
        }
        R::storeAll($tempDataArray);

        return ['info' => 'Step number changed successfully', 'color' => 'success', 'log' => 'step'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeValidation($post, $step_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $valid = $post['validation'] ?? 0;
        $step['validation'] = $valid;
        R::store($step);

        return ($valid) ?
            ['info' => 'Validation added to this step', 'color' => 'success', 'log' => 'check-1'] :
            ['info' => 'Validation removed from this step', 'color' => 'danger', 'log' => 'check-0'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeRevision($post, $step_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $step->revision = $post['revision'];
        R::store($step);
        return ['info' => 'Step Revision changed successfuly', 'color' => 'success', 'log' => 'vers'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeDescription(array $post, $step_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $step->description = $post['stepDescription'];
        R::store($step);
        return ['info' => 'Step Description changed successfuly', 'color' => 'success', 'log' => 'desc'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeRouteAction(array $post, $step_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $step->routeaction = $post['routeAction'];
        $step->routeid = $post['routeid'];
        R::store($step);
        return ['info' => 'Step Route Action changed successfuly', 'color' => 'success', 'log' => 'route'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeTool(array $post, $step_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $step->tool = $post['tool'] ?? 'no choosen';
        R::store($step);
        return ['info' => 'Step Tool for step changed successfuly', 'color' => 'success', 'log' => 'tool'];
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeImageFile($files, $step_id, $project_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $project = R::load(PROJECTS, $project_id);
        $res = $path = [];
        $file = $files['imageFile'];
        $pathToImage = $step->image;
        /* предпологаем что фото имеет другой формат */
        $isNotWebp = true;
        $uploadedFile = TEMP_FOLDER . basename($file['name']);
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        /* разрешенные типы файлов */
        $allowedExtensions = ['png', 'jpg', 'bmp', 'webp'];

        /* Проверяем, что расширение разрешено */
        if (in_array($fileExtension, $allowedExtensions)) {
            /* если расширение не ребует конвертации меняем путь для сохранения фото */
            if ($fileExtension == "webp") {
                $sourceFile = $project['projectdir'] . unicum($project['id']) . '.webp';

                /* сохраняем файл в папку проекта напрямую */
                $uploadSuccess = move_uploaded_file($file['tmp_name'], $sourceFile);
                $isNotWebp = false;
            } else {

                /* сохраняем во временную папку полученное фото перед конвертацией */
                $uploadSuccess = move_uploaded_file($file['tmp_name'], $uploadedFile);
            }

            /* Перемещаем файл из папки проекта в папку история и удаляем оригинал */
            if (!empty($pathToImage) && is_file($pathToImage)) {
                /* переименовываем обект который переносим в папку история */
                $newString = preg_replace("/(projects\/[^\/]+)\/(.+)/", "$1/history/$2", $pathToImage);
                $newStepImageHistoryPath = $newString;
                if (rename($pathToImage, $newStepImageHistoryPath)) {

                    /* если успешно переместилось то -> сохраняем данные в таблицу history */
                    $path['image_path'] = $newStepImageHistoryPath;
                    _flashMessage('Move file to history success!');
                } else {
                    _flashMessage('Move file to history failed!', 'danger');
                }
            } else {
                _flashMessage('Old file not exist!', 'danger');
            }

            /* если успешно сохранилось конвертируем фото из временной папки с сохранением в папку проекта */
            if ($uploadSuccess && $isNotWebp) {
                /* создаем новое имя для нового файла и сохраняем его в папку проекта */
                $outputFile = $project['projectdir'] . unicum($project['id']) . '.webp';

                if (Converter::convertToWebP($uploadedFile, $outputFile)) {
                    /* удаляем файлы после конвертирования */
                    array_map('unlink', glob(TEMP_FOLDER . '*.*'));
                    /* обновляем путь к файлу в БД и пишем лог */
                    $step->image = $outputFile;
                    _flashMessage('Image changed success.');
                    $res = ['log' => [$path, 'image']];
                } else {
                    _flashMessage('File convertation to webp failed!', 'danger');
                }
            }

            /* если файлы были отправлены и расширение файла .webp */
            if ($uploadSuccess && !$isNotWebp) {
                /* обновляем путь к файлу в БД и пишем лог */
                $step->image = $uploadedFile;
                _flashMessage('Image changed success.');
                $res = ['log' => [$path, 'image']];
            }

            R::store($step);

        } else {
            /* если формат файла не разрешен то отправляем отчет об ошибке ничего не делая в системе */
            _flashMessage('File format is error!', 'danger');
        }

        return $res;
    }

    /**
     * @throws \RedBeanPHP\RedException\SQL
     */
    private static function changeVideoFile($files, $step_id, $project_id): array
    {
        $step = R::load(PROJECT_STEPS, $step_id);
        $project = R::load(PROJECTS, $project_id);
        $file = $files['videoFile'];
        $pathToVideo = $step->video;
        $res = $path = [];
        /* путь к временной папке для сохранения video */
        $targetFilePath = TEMP_FOLDER . basename($file['name']);
        /* новое имя файла для сохранения в папку проекта */
        $outputVideoFile = $project['projectdir'] . unicum($project['id']) . '.mp4';

        /* Перемещаем файл из папки проекта в папку история и удаляем оригинал */
        if ($pathToVideo != 'none' && is_file($pathToVideo)) {
            /* переименовфваем обект который переносим в папку история */
            $newString = preg_replace("/(projects\/[^\/]+)\/(.+)/", "$1/history/$2", $pathToVideo);
            $newStepImageHistoryPath = $newString;
            if (rename($pathToVideo, $newStepImageHistoryPath)) {
                /* если успешно переместилось то -> сохраняем данные в таблицу history */
                $path['video_path'] = $newStepImageHistoryPath;
            }
        }

        // Проверяем, что файл был загружен через HTTP POST
        if (is_uploaded_file($file['tmp_name'])) {
            // Перемещаем файл в целевую директорию
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                $step->video = $outputVideoFile;
                _flashMessage('Video File uploaded successfully');
                $res = ['log' => [$path, 'video']];

                // Проверяем, является ли файл видео MP4 с кодеком H.264
                if (Converter::isMp4H264($targetFilePath)) {
                    // Файл уже в нужном формате, переименуем и переместим его
                    rename($targetFilePath, $outputVideoFile);
                    _flashMessage('Old video changed success.');
                } else {
                    // Файл не в формате MP4 H.264, конвертируем его
                    Converter::convertToMp4H264($targetFilePath, $outputVideoFile);
                    _flashMessage('Video format changed success.');
                    // Удаление исходного файла, если необходимо
                    array_map('unlink', glob(TEMP_FOLDER . '*.*'));
                }
            } else {
                _flashMessage('Error video uploading file!', 'danger');
            }
        } else {
            _flashMessage('Error video file not exist!', 'danger');
        }

        R::store($step);
        return $res;
    }

    private static function changeFrontPicture($step_id, $post): array
    {
        // создаем галлерею на главной странице
        $query = "UPDATE " . PROJECT_STEPS . " SET front_pic = ? WHERE id = ?";
        R::exec($query, [$post['front-picture'] ?? 0, $step_id]);
        return ['info' => 'Project front image changed successfuly', 'color' => 'success', 'log' => 'front-picture'];
    }

    private static function changePartNumber($step_id, $post): array
    {
        //
        $query = "UPDATE " . PROJECT_STEPS . " SET part_number = ? WHERE id = ?";
        R::exec($query, [$post['part-number'] ?? '', $step_id]);
        return ['info' => 'Item part number changed successfuly', 'color' => 'success', 'log' => 'part-number'];
    }

    private static function changeNotice($step_id, $post): array
    {
        //
        $query = "UPDATE " . PROJECT_STEPS . " SET note = ? WHERE id = ?";
        R::exec($query, [$post['note'] ?? '', $step_id]);
        return ['info' => 'Step Note changed successfuly', 'color' => 'success', 'log' => 'note'];
    }

    /**
     * LOG AND HISTORY FOR STEP CHANGES
     * @param $data
     * @param $user
     * @param $step
     * @param $project
     * @return void
     * @throws /\RedBeanPHP\RedException\SQL
     */
    private static function saveStepHistory($data, $user, $step, $project): void
    {
        $changeslog = $paths = $changes = [];
        /* преобразование массива данных для сохранения в БД */
        foreach ($data as $key => $log) {
            $changes[] = $key;
            if (is_array($log) && isset($log[0]) && is_array($log[0])) {
                // Если $log[0] - это массив, объединяем его с $paths
                $paths = array_merge($paths, $log[0]);
                $changeslog[] = $log[1];
            } else {
                $changeslog[] = $log;
            }
        }

        $history = R::dispense(HISTORY);
        /* tech info */
        $history->projectid = $step['projects_id'];
        $history->steps_id = $step['id'];
        $history->changedate = date("Y-m-d h:i");
        $history->username = $user['user_name'];

        /* old step data */
        $history->validation = $step['validation'] ?? 0;
        $history->step = $step['step'];
        $history->revision = $step['revision'];
        $history->part_number = $step['part_number'];
        $history->note = $step['note'];
        $history->description = $step['description'];
        $history->routeid = $step['routid'];
        $history->routeaction = $step['routaction'];
        $history->toolid = $step['tool'];
        $history->image = $paths['image_path'] ?? $step->image;
        $history->video = $paths['video_path'] ?? $step->video;

        /* log for colorise changed text */
        $history->changeslog = implode(',', $changeslog);

        R::store($history);

        _flashMessage('History and Log saved Successfully');
        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Project name: $project->projectname, Step N: $step->step, updated, <br>";
        $details .= "Changes in: [" . implode(', ', $changes) . ']';
        $details .= "<br>Press icon eye on step editing page.";
        if (!logAction($user['user_name'], 'UPDATING', OBJECT_TYPE[4], $details)) {
            _flashMessage('Log creation failed.', 'danger');
        }
    }

    /* =============================== STEP CHANGES PROTECTED METHODS ================================= */

    /**
     * DELETING ONE STEP FROM PROJECT ASSEMBLY LINE
     * @param $post
     * @param $user
     * @return void
     */
    public static function deleteProjectStep($post, $user): void
    {
        if (checkPassword(_E($post['password']))) {
            $project = R::load(PROJECTS, _E($post['projectid']));
            $step_id = _E($post['stepId']);
            $tmpStep = $step = R::load(PROJECT_STEPS, $step_id);
            $history = R::find(HISTORY, "steps_id = ?", [$step_id]);
            $projDir = $project['projectdir'];


            /* delete step in project */
            if ($step) {
                /* проверяем папку проекта */
                if (is_dir($projDir)) {

                    if ($history) {
                        /* проверяем если есть папка истории */
                        if (is_dir($projDir . "history")) {
                            /* удаляем все фото и видео из истории */
                            foreach ($history as $value) {
                                if ($value['image'] != $step['image']) {
                                    unlink($value['image']);
                                }
                                if ($value['video'] != $step['video']) {
                                    unlink($value['video']);
                                }
                            }
                        }
                        R::trashAll($history);
                    }

                    if (is_dir($projDir)) {
                        /* удаляем фото и видео шага */
                        if (!empty($step->image))
                            unlink($step->image);

                        if ($step->video != 'none') {
                            echo $step->video;
                            unlink($step['video']);
                        }
                        /* удаляем все данные из БД */
                        R::trash($step);
                        _flashMessage('Step Was deleted successfully!');
                    }

                } else {
                    /* не найдена папка проекта  */
                    _flashMessage("Project folder not found, step isn`t deleted!", 'danger');
                }
            } else {
                /* не найден шаг в БД */
                _flashMessage("The step not exist, step isn`t deleted!", 'danger');
            }
        } else {
            /* не верный мастер пароль */
            _flashMessage("Incorrect password writed!", 'danger');
        }

        $details = 'Project name: ' . $project->projectname . ', Step number: ' . $tmpStep->step . '<br>';
        $details .= 'Step deleted by administrator or not :)<br>';
        if (!logAction($user['user_name'], 'DELETING', OBJECT_TYPE[4], $details)) {
            _flashMessage('Log creation failed', 'danger');
        }
    }
}
///**
// * IMPORT PROJECT BOM FROM CSV FILE
// * @param $files
// * @param $post
// * @param $user
// * @param $project_id
// * @return void
// * @throws \\RedBeanPHP\RedException\SQL
// */
//public static function importProjectBomFromFile($files, $post, $user, $project_id): void
//{
//    $project = R::load(PROJECTS, $project_id);
//    // converting post to assoc array
//    $fieldsMapping = [];
//    foreach ($post as $key => $name) {
//        $fieldsMapping[$key] = $name;
//    }
//
//    /* uploading the file */
//    if (!empty($files['import_csv']['name'][0])) {
//        $tmp_name = $files['import_csv']['tmp_name'];
//        $uploadedFile = TEMP_FOLDER . basename($files['import_csv']['name']);
//        $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));
//
//        if ($fileType == 'csv') {
//            // save file to temp dir
//            $uploadSuccess = move_uploaded_file($tmp_name, $uploadedFile);
//            if ($uploadSuccess) {
//
//                if (($handle = fopen($uploadedFile, "r")) !== FALSE) {
//                    // Чтение заголовков файла
//                    $headers = fgetcsv($handle, 1000);
//                    $columnIndexes = [];
//
//                    // Определение индексов нужных колонок
//                    foreach ($fieldsMapping as $dbField => $csvColumnName) {
//                        $index = array_search($csvColumnName, $headers);
//                        if ($index !== FALSE) {
//                            $columnIndexes[$dbField] = $index;
//                        }
//                    }
//
//                    // Чтение и обработка каждой строки файла
//                    while (($data = fgetcsv($handle, 1000)) !== FALSE) {
//                        $rowData = [];
//                        foreach ($columnIndexes as $dbField => $index) {
//                            $rowData[$dbField] = $data[$index];
//                        }
//                        //i сделать построчную проверку переменных по manufacture_pn и qty если номер тот же то пюсуем qty к тому что есть
//                        //i а если manufacture_pn отличается то вносим как новый элемент
//
//                        $goods = R::dispense(PROJECT_BOM);
//                        $goods->sku = (int)$rowData['sku'] ?? 0;
//                        $goods->part_name = $rowData['part_name'] ?? '';
//                        $goods->part_value = $rowData['part_value'] ?? '';
//                        $goods->mounting_type = $rowData['mounting_type'] ?? '';
//                        $goods->footprint = $rowData['footprint'] ?? '';
//                        $goods->manufacturer = $rowData['manufacturer'] ?? '';
//                        $goods->manufacture_pn = $rowData['manufacture_pn'] ?? '';
//                        $goods->owner_pn = $rowData['owner_pn'] ?? '';
//                        $goods->amount = trim($rowData['qty']);// требуемое кол-во на одну штуку
//                        $goods->length_mm = self::isDigits(trim($rowData['length_mm'])); // ДЛИНА В ММ ЕСЛИ ЕСТЬ
//                        $goods->description = $rowData['description'] ?? '';
//                        $goods->notes = $rowData['note'] ?? '';
//                        $goods->projects_id = $project->id;
//
//
//                        // добавляем ID детали из БД если она есть в БД
//                        // если детали нет то оставляем пустое поле
////                            if (!empty($rowData['item_id'])) {
////                                $partList->item_id = $rowData['item_id'];  // warehouse item id
////                            } else {
//                        $wh = WareHouse::GetOneItemFromWarehouse($rowData['manufacture_pn'], $rowData['owner_pn']);  // warehouse item id
//                        if ($wh) {
//                            $partList->item_id = $wh->id;
//                            $goods->customerid = $project->customerid;
//                        }
//
//                        $goods->date_in = date("Y-m-d H:i");
//
//                        R::store($goods);
//                        $import = true;
//                    }
//                    fclose($handle);
//                }
//            }
//
//            // deleting temp file
//            array_map('unlink', glob(TEMP_FOLDER . '*.*'));
//
//            if ($import) {
//                _flashMessage('Import success');
//            } else {
//                _flashMessage('Error, somethig went wrong!', 'danger');
//            }
//
//        } else {
//            _flashMessage('Error, File format wrong! Only .csv', 'danger');
//        }
//    }
//
//    /* [ LOG WRITING ACTION ] */
//    $details = 'Items for project ID= ' . $project_id . ', Added to BOM';
//    if (!logAction($user['user_name'], 'CREATING', OBJECT_TYPE[3], $details)) {
//        _flashMessage('Log creation failed!', 'danger');
//    }
//}