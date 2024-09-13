<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'tracking';
$trackingController = new TrackingController($user);
// Для обработки POST-запросов (например, при сохранении посылки)
$print_info = $trackingController->handlePostRequest();
// Для обработки GET-запросов (например, при отображении списка посылок)
$data = $trackingController->handleGetRequest();
$trackList = $data['trackList'];
$result = $data['result'];
$settings = $data['settings'];
//$requestData = RequestData::getInstance();
//$requestData->checkPostRequestAndExecute('save-track', function ($data) use (&$user, &$requestData) {
//
//    $t = R::dispense(TRACK_DATA);
//    $dt = $t->date_in = date('Y/m/d, h:i:s');
//    $co = $t->courier = $data['courier'];
//    $loc = $t->location = $data['location'];
//    $re = $t->receiver = $data['receiver'];
//    $t->transferTo = $data['transferTo'];
//    $tw = R::load(USERS, $data['transferTo']);
//    $desc = $t->description = $data['description'] ?? '';
//
//    list($paths, $attachments) = convertAndSaveImages($requestData->getFiles());
//    $t->file_path = $paths;
//    if (R::store($t)) {
//        $eml = '';
//        $emails = [];
//        // отправка мейла с оповещением
//        foreach (R::findAll(USERS) as $us) {
//            if ($us['notify'] == 1 && strpos($us['notify_type'], '1') !== false) {
//                $eml .= "{$us['email']}, ";
//                $emails[] = $us['email'];
//            }
//        }
//
//        // проверяем если никго не было то для правильной работы присваиваем имейл по умолчанию
//        //$emails = _empty($emails, 'amir@nti.co.il');// by default
//
//        _flashMessage('Users witch emails is viewed has notificated!<br>' . $eml, 'warning', false);
//        $subject = 'Parcel for NTI Accepted';
//        $html_body = '<h1>Hello! NTI Group!</h1>';
//        $html_body .= '<h4>Some parcel was received! Time of record creation: <br>' . $dt . '</h4>';
//        $html_body .= '<p>Courier: ' . $co . '</p>';
//        $html_body .= '<p>Location now: ' . $loc . '</p>';
//        $html_body .= '<p>Receiver: ' . $re . '</p>';
//        $html_body .= '<p>Transfered to: ' . $tw->user_name . '</p>';
//        $html_body .= '<pre>' . $desc . '</pre>';
//        $html_body .= '<br><br>';
//
//        $m = Mailer::SendNotifications($emails, $subject, $html_body, $attachments);
//        _flashMessage('New Delivery created and stored successfully<br>' . $m);
//    }
//});
//
//// вывод списка для просмотра  входящих посылок
//$requestData->executeIfAnyGetKeyExists('track-list', function ($_) use (&$user, &$requestData, &$trackList, &$result, &$settings) {
//    $trackList = true;
//    $result = R::findAll(TRACK_DATA, 'ORDER BY date_in DESC');
//    $settings = getUserSettings($user, TRACK_DATA);
//});
//
//function convertAndSaveImages(array $files): array
//{
//    $paths = '';
//    $attachments = [];
//    $tmp_dir = TEMP_FOLDER; // Временная папка
//    $target_dir = 'storage/tracking/'; // Папка для хранения файлов
//
//    // Создаем папку, если она не существует, с полными правами
//    if (!is_dir($target_dir)) {
//        mkdir($target_dir, 0777, true);
//    }
//
//    // Проходим по каждому файлу в массиве
//    foreach ($files as $key => $fileData) {
//        // Проверяем, является ли файл множественным
//        if (isset($fileData[0])) {
//            foreach ($fileData as $index => $file) {
//                if (!isset($file['error']) || $file['error'] === 0) {
//                    $tmp_path = $file['tmp_name'];
//                    $original_name = pathinfo($file['name'], PATHINFO_FILENAME); // Исходное имя файла без расширения
//                    $timestamp = date('YmdHis'); // Текущая дата и время без пробелов и знаков препинания
//                    $new_file_name = $original_name . '_' . $timestamp . '.webp'; // Генерируем новое имя для файла
//                    $out_path = $target_dir . $new_file_name; // Путь для сохранения конвертированного файла
//
//                    // Конвертация в формат WebP
//                    if (Converter::convertToWebP($tmp_path, $out_path)) {
//                        // Если конвертация успешна, добавляем путь к файлу в массив вложений
//                        $attachments[] = ['path' => $out_path, 'name' => $new_file_name];
//                        // Формируем строку для переменной $paths с путями к каждому файлу
//                        $paths .= $out_path . ', ';
//                    } else {
//                        _flashMessage('Ошибка при конвертации файла: ' . $file['name'], 'danger');
//                    }
//                }
//            }
//        } else {
//            // Для единичных файлов
//            if (!isset($fileData['error']) || $fileData['error'] === 0) {
//                $tmp_path = $fileData['tmp_name'];
//                $original_name = pathinfo($fileData['name'], PATHINFO_FILENAME); // Исходное имя файла без расширения
//                $timestamp = date('YmdHis'); // Текущая дата и время без пробелов и знаков препинания
//                $new_file_name = $original_name . '_' . $timestamp . '.webp'; // Генерируем новое имя для файла
//                $out_path = $target_dir . $new_file_name; // Путь для сохранения конвертированного файла
//
//                // Конвертация в формат WebP
//                if (Converter::convertToWebP($tmp_path, $out_path)) {
//                    // Если конвертация успешна, добавляем путь к файлу в массив вложений
//                    $attachments[] = ['path' => $out_path, 'name' => $new_file_name];
//                    // Формируем строку для переменной $paths с путями к каждому файлу
//                    $paths .= $out_path . ', ';
//                } else {
//                    _flashMessage('Ошибка при конвертации файла: ' . $fileData['name'], 'danger');
//                }
//            }
//        }
//    }
//
//    // Удаляем лишнюю запятую и пробел в конце строки
//    $paths = rtrim($paths, ', ');
//
//    // Удаление временных файлов из временной папки
//    foreach ($files as $fileGroup) {
//        if (isset($fileGroup[0])) {
//            foreach ($fileGroup as $file) {
//                if (file_exists($file['tmp_name'])) {
//                    unlink($file['tmp_name']); // Удаляем файл
//                }
//            }
//        } else {
//            if (file_exists($fileGroup['tmp_name'])) {
//                unlink($fileGroup['tmp_name']); // Удаляем файл
//            }
//        }
//    }
//
//    // Очищаем временную папку после успешного выполнения
//    if (is_dir($tmp_dir)) {
//        $files_in_tmp_dir = glob($tmp_dir . '/*'); // Получаем все файлы в папке
//        foreach ($files_in_tmp_dir as $file) {
//            if (is_file($file)) {
//                unlink($file); // Удаляем каждый файл
//            }
//        }
//    }
//
//    // Если все успешно, выводим сообщение об успехе
//    if (!empty($attachments)) {
//        _flashMessage('Файлы успешно сохранены, конвертированы и временные файлы удалены.');
//    }
//
//    // Возвращаем массив с путями и вложениями
//    return [$paths, $attachments];
//}

?>
<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        /* Стили для мобильной вертикальной ориентации */
        body {
            padding: 15px;
        }

        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .preview {
            position: relative;
            width: 48%;
        }

        .preview img {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            padding: 0 6px;
        }

        .add-photo-btn {
            display: block;
            margin-top: 10px;
        }

        input[type="file"] {
            display: none; /* Скрываем реальные input элементы */
        }

        /*    track list styles     */
        /* СТИЛИ ДЛЯ ВЫВОДА ПРОЕКТОВ В ТАБЛИЦЕ */
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: normal;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 6.5%;
        }

        th:last-child, td:last-child {
            text-align: right;
            padding-right: 1rem;
        }

        th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }

        .thumbs-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer; /* Указатель при наведении */
        }

        .thumbs-container {
            display: flex;
            flex-wrap: wrap;
        }

        /* Стили для увеличенного изображения */
        #image-preview {
            display: none; /* Скрываем по умолчанию */
            position: fixed;
            top: 10rem; /* Отступ от верха страницы */
            left: 10px; /* Привязка к левому краю */
            width: 50%;
            height: 50vh;
            border: 1px solid #ccc;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); /* Тень для выделения */
            z-index: 1000;
            object-fit: cover;
        }

        /* Общие стили для модальных окон */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            padding: 20px;
            border-radius: 5px;
            width: 700px;
            text-align: center;
        }

        .modal-content h3 {
            margin-bottom: 15px;
        }

        .modal-content button {
            margin: 10px;
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>

<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Tracking', 'user' => $user, 'page_name' => $page]); ?>
<?php if (!empty($print_info)) {
    echo '<a href="' . $print_info . '" target="_blank" id="relocation" class="hidden"></a>';
} ?>
<?php if (!$trackList) { ?>
    <div class="container form-container">
        <h3 class="text-center mb-4">Форма Приемки</h3>
        <form id="uploadForm" action="tracking" method="POST" enctype="multipart/form-data">
            <!-- Поля формы -->
            <div class="mb-3">
                <label for="courier" class="form-label">Кто принес <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="courier" name="courier" required>
                <div class="invalid-feedback">Это поле обязательно для заполнения.</div>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Где стоит <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="location" name="location" required>
                <div class="invalid-feedback">Это поле обязательно для заполнения.</div>
            </div>

            <div class="mb-3">
                <label for="receiver" class="form-label">Кто принял <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="receiver" name="receiver" value="<?= $user['user_name'] ?>" required>
                <div class="invalid-feedback">Это поле обязательно для заполнения.</div>
            </div>

            <!-- Поле "перевести на" -->
            <div class="mb-3">
                <label for="transferTo" class="form-label">Перевести на: <span class="text-danger">*</span></label>
                <select class="form-select" id="transferTo" name="transferTo" required>
                    <option value="" disabled selected>Выберите пользователя</option>
                    <?php foreach (R::findAll(USERS) as $u) {
                        if ($u['id'] != 1) { ?>
                            <option value="<?= $u['id'] ?>"><?= $u['user_name'] ?></option>
                        <?php }
                    } ?>
                </select>
                <div class="invalid-feedback">Выберите пользователя.</div>
                <div class="invalid-feedback">Это поле обязательно для заполнения.</div>
            </div>

            <div class="mb-3">
                <label for="asmahta" class="form-label">Номер Документа (необязательно)</label>
                <input type="text" class="form-control" id="asmahta" name="asmahta">
            </div>

            <!-- additional information -->
            <div class="mb-3">
                <label for="description" class="form-label">Краткое описание (необязательно)</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <!-- Загрузка фотографий -->
            <div class="mb-3">
                <label class="form-label">Загрузите фотографии (до 5 штук, не обязательно)</label>
                <button type="button" class="btn btn-secondary add-photo-btn" id="add-photo-btn">Взять фото</button>

                <div id="photo-container" class="mt-3 preview-container"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100" name="save-track">Отправить</button>
        </form>
    </div>

<?php PAGE_FOOTER($page, false); ?>
    <!--suppress JSValidateTypes -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // relocation after saving for printing information about
            if (dom.e("#relocation")) {
                dom.e("#relocation").click();
            }

            const photoContainer = document.getElementById('photo-container');
            const addPhotoBtn = document.getElementById('add-photo-btn');
            const uploadForm = document.getElementById('uploadForm'); // Получаем форму
            let photoCount = 0;
            const maxPhotos = 5;
            let lastIndex = 0; // Индекс для отслеживания последнего добавленного поля

            addPhotoBtn.addEventListener('click', () => {
                if (photoCount >= maxPhotos) return;

                lastIndex++; // Увеличиваем индекс при каждом добавлении нового файла

                // Создаем input для файла
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = `photo${lastIndex}`;
                fileInput.accept = 'image/*';
                fileInput.id = `photo${lastIndex}`;
                fileInput.onchange = () => previewImage(fileInput, lastIndex);

                // Добавляем инпут внутрь формы, чтобы он отправлялся на сервер
                uploadForm.appendChild(fileInput);

                // Симулируем клик по скрытому input
                fileInput.click();
            });

            function previewImage(input, index) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const previewContainer = document.createElement('div');
                        previewContainer.classList.add('preview');
                        previewContainer.id = `preview${index}`;

                        const img = document.createElement('img');
                        img.src = e.target.result;

                        const removeBtn = document.createElement('button');
                        removeBtn.innerHTML = '&times;';
                        removeBtn.classList.add('remove-preview');
                        removeBtn.onclick = () => {
                            previewContainer.remove();
                            input.remove();
                            photoCount--;
                        };

                        previewContainer.appendChild(img);
                        previewContainer.appendChild(removeBtn);
                        photoContainer.appendChild(previewContainer);

                        photoCount++;
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }
        });
    </script>
<?php } else { ?>
    <div id="searchAnswer">
        <table class="p-3" id="track-table">
            <!-- header -->
            <thead>
            <tr style="white-space: nowrap">
                <?= CreateTableHeaderUsingUserSettings($settings, 'track-table', TRACK_DATA); ?>
            </tr>
            </thead>
            <!-- table -->
            <tbody>
            <?php
            foreach ($result as $value) {
                if ($value['processed'] != 1) { ?>
                    <tr class="item-list " data-id="<?= $value['id']; ?>" onmouseover="showPreview('<?= $value['file_path'] ?>')" onmouseout="hidePreview()">
                        <?php
                        if ($settings) {
                            foreach ($settings as $item => $_) {
                                echo '<td>' . $value[$item] . '</td>';
                            }
                        }
                        ?>
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>

        <!-- Контейнер для увеличенного изображения -->
        <div id="image-preview"></div>
    </div>

    <!-- Первое модальное окно -->
    <div id="firstModal" class="modal" style="display:none;">
        <div class="modal-content warning">
            <h3>Предупреждение</h3>
            <h4>Вы будете перенаправлены на страницу редактирования входящих данных.
                Важно: при переходе данная запись будет удалена.</h4>
            <button id="continueBtn">Перейти</button>
            <button id="cancelBtn">Отменить</button>
        </div>
    </div>

    <!-- Второе модальное окно (для подтверждения необратимости) -->
    <div id="secondModal" class="modal" style="display:none;">
        <div class="modal-content danger text-white">
            <h3>ВНИМАНИЕ: НЕОБРАТИМЫЙ ПРОЦЕСС</h3>
            <h4>Если вы начнете этот процесс, вам необходимо будет завершить процесс подсчета и добавления данных о полученной посылке!
                В противном случае начальные данные будут утеряны навсегда без возможности востановления!
                Вы уверены, что хотите продолжить?</h4>
            <button id="finalContinueBtn">Перейти</button>
            <button id="finalCancelBtn">Отменить</button>
        </div>
    </div>


    <button type="button" class="url hidden" value="" id="routing-btn"></button>

<?php PAGE_FOOTER($page, false); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const table = document.getElementById('searchAnswer');
            const firstModal = document.getElementById('firstModal');
            const secondModal = document.getElementById('secondModal');
            const continueBtn = document.getElementById('continueBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const finalContinueBtn = document.getElementById('finalContinueBtn');
            const finalCancelBtn = document.getElementById('finalCancelBtn');
            let dataId = null; // Переменная для хранения data-id строки

            // Обработчик событий на таблицу
            table.addEventListener('click', function (event) {
                if (event.target.tagName.toLowerCase() === 'button' ||
                    event.target.tagName.toLowerCase() === 'i' ||
                    event.target.tagName.toLowerCase() === 'a') {
                    return; // Прекращаем выполнение, если клик был по ссылке или кнопке
                }

                // Находим родительский <tr> элемент
                let row = event.target;
                while (row && row.tagName.toLowerCase() !== 'tr') {
                    row = row.parentElement;
                }

                // Если найден <tr> с data-id
                if (row && row.dataset.id) {
                    dataId = row.dataset.id;
                    firstModal.style.display = 'flex'; // Показываем первое модальное окно
                }
            });

            // Кнопка "Перейти" в первом модальном окне
            continueBtn.addEventListener('click', function () {
                firstModal.style.display = 'none'; // Скрываем первое модальное окно
                secondModal.style.display = 'flex'; // Показываем второе модальное окно
            });

            // Кнопка "Отменить" в первом модальном окне
            cancelBtn.addEventListener('click', function () {
                firstModal.style.display = 'none'; // Закрываем первое модальное окно
            });

            // Кнопка "Перейти" во втором модальном окне
            finalContinueBtn.addEventListener('click', function () {
                secondModal.style.display = 'none'; // Скрываем второе модальное окно
                let btn = document.getElementById('routing-btn');
                btn.value = "po-replenishment?tid=" + dataId; // Добавляем data-id к маршруту
                btn.click(); // Выполняем клик по кнопке
            });

            // Кнопка "Отменить" во втором модальном окне
            finalCancelBtn.addEventListener('click', function () {
                secondModal.style.display = 'none'; // Закрываем второе модальное окно
            });
        });

        // прокрутка имеющихся фотографий
        let imageInterval; // Переменная для хранения интервала

        function showPreview(src) {
            if (src) {
                const preview = document.getElementById('image-preview');

                // Разбиваем строку с путями на массив изображений
                const imagePaths = src.split(',');

                let currentIndex = 0; // Индекс текущего изображения

                // Функция для отображения изображения
                function displayImage(index) {
                    preview.style.backgroundImage = `url(${imagePaths[index].trim()})`;
                    preview.style.backgroundSize = 'contain';
                    preview.style.backgroundRepeat = 'no-repeat';
                    preview.style.display = 'block'; // Показываем контейнер
                }

                // Отображаем первое изображение
                displayImage(currentIndex);

                // Если изображений больше одного, запускаем интервал для переключения изображений
                if (imagePaths.length > 1) {
                    imageInterval = setInterval(() => {
                        currentIndex = (currentIndex + 1) % imagePaths.length; // Переход к следующему изображению по кругу
                        displayImage(currentIndex);
                    }, 1000); // Интервал в 1 секунду
                }
            }
        }

        function hidePreview() {
            const preview = document.getElementById('image-preview');
            preview.style.display = 'none'; // Скрываем контейнер

            // Останавливаем смену изображений
            if (imageInterval) {
                clearInterval(imageInterval);
                imageInterval = null; // Сбрасываем интервал
            }
        }
    </script>
<?php } ?>
</body>
</html>
