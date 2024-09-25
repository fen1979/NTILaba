<?php
// сделать так что бы после импортинга был возврат на ту страницу с которой пришли суда
// добить импортинг до конца
// разбить импортинг на подфункции что бы генерация была в разных местах а вывод в одном и так же обработку разделить
// на отдельные функции
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'libs/XLSXreader.php';
$page = 'import_files';
// получаем имя таблицы в которую будем вносить файл
$table = isset($_GET['table-name']) ? _E($_GET['table-name']) : (isset($_GET['import']) ? _E($_GET['import']) : '' . redirectTo('home'));
/**
 * Функция для генерации HTML формы на основе структуры таблицы в БД.
 *
 * @param string $tableName Имя таблицы в базе данных.
 * @return string Сгенерированный HTML-код формы.
 * @throws Exception Если таблица не существует в базе данных.
 */
function generateFormFromTable(string $tableName): string
{
    $html = '';

    try {
        // Проверяем существование таблицы
        $fields = R::inspect($tableName);

        // Если таблица пуста (не содержит полей), выбрасываем исключение
        if (empty($fields)) {
            _flashMessage("Таблица '$tableName' не содержит данных или не существует.", 'danger');
        } else {

            //i сделать в ресурсах отдельный кейс с именами форм tables_name
            $pid = (isset($_GET['pid'])) ? 'Project ID - ' . _E($_GET['pid']) . ' add this number in to your imported file!!!' : '';
            $html = '<h1>' . SR::getResourceValue('title', 'project_part_list') . ' Form. ' . $pid . '</h1>';
            // Начинаем формирование HTML
            $html .= '<form action="" method="post" enctype="multipart/form-data">';
            $fields = array_keys($fields);

            // Проходим по именам полей и создаем элементы формы
            foreach ($fields as $field) {
                // Получаем название для label из вашей функции SR::getResourceValue
                list($labelText, $placeHolder) = SR::getResourceValue($tableName, $field, true);

                if ($field != 'id' && $field != 'date_in' && !empty($labelText) && $field != 'projects_id') {
                    // Создаем HTML для каждого поля
                    $html .= '<div class="form-group mb-2">';
                    $html .= '<label for="' . $field . '">' . htmlspecialchars($labelText) . '</label>';
                    $html .= '<input type="text" class="form-control" name="' . htmlspecialchars($field) .
                        '" id="' . htmlspecialchars($field) . '" placeholder="' . $placeHolder . '">';
                    $html .= '</div>';
                }
            }

            // Добавляем поле для загрузки файла
            $html .= '<div class="form-group mb-2">';
            $html .= '<label for="imported-file">Upload XLSX File</label>';
            $html .= '<input type="file" class="form-control" name="imported-file" id="imported-file" required>';
            $html .= '</div>';

            // Закрываем форму
            $html .= '<button type="submit" class="btn btn-primary" name="subbmit-button">Submit</button>';
            $html .= '</form>';
        }
        // Возвращаем готовый HTML-код
    } catch (Exception $e) {
        // message collector (text/ color/ auto_hide = true)
        _flashMessage("Таблица '$tableName' не существует в базе данных. <br>" . $e->getMessage(), 'danger');
    }
    return $html;
}

/**
 * Функция для обработки загруженного XLSX файла и внесения данных в таблицу в БД.
 *
 * @param string $tableName Имя таблицы в базе данных.
 * @param array $formFields Поля формы, содержащие названия колонок в XLSX файле.
 * @param array $file Информация о загруженном файле.
 * @throws Exception Если возникла ошибка при загрузке или чтении файла.
 */
function processImportedFile(string $tableName, array $formFields, array $file): bool
{
    // Проверяем, что файл загружен
    if (!isset($file['imported-file']) || $file['imported-file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла');
    }

    // Загружаем файл XLSX
    $xlsx = XLSXreader::parse($file['imported-file']['tmp_name']);
    if (!$xlsx) {
        throw new Exception('Не удалось прочитать файл');
    }

    // Получаем названия колонок из формы (названия колонок в файле)
    $formColumns = array_filter($formFields, fn($field) => !empty($field));

    // Получаем данные из первой строки (заголовки)
    $header = $xlsx->rows()[0];

    // Идем по всем строкам файла начиная со второй
    foreach ($xlsx->rows() as $rowIndex => $row) {
        if ($rowIndex === 0) continue; // Пропускаем первую строку (заголовки)

        // Создаем новую запись в таблице
        $record = R::dispense($tableName);

        // Заполняем поля записи данными из файла
        foreach ($formColumns as $formField => $xlsxColumnName) {
            // Находим индекс колонки в файле
            $columnIndex = array_search($xlsxColumnName, $header);

            if ($columnIndex !== false && isset($row[$columnIndex])) {
                $record[$formField] = $row[$columnIndex];
            }
        }

        // Сохраняем запись в БД
        R::store($record);
    }

    return true;
}

// Пример использования
if (isset($_POST['subbmit-button']) && isset($_FILES)) {
    try {
        $formFields = $_POST; // Получаем поля формы
        $file = $_FILES; // Получаем файл
        processImportedFile($table, $formFields, $file);
        echo "Данные успешно импортированы!";
    } catch (Exception $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

// ============================================================================================================

/**
 * Генерирует HTML форму на основе структуры трех таблиц в БД.
 * Поля без лейблов и поле 'id' исключаются из формы.
 *
 * @param array $tableNames Массив с именами таблиц.
 * @return string Сгенерированный HTML-код формы.
 */
function generateFormForTables(array $tableNames): string
{
    $html = '<h1>Warehouse Form</h1>';
    $html .= '<form action="" method="post" enctype="multipart/form-data">';

    foreach ($tableNames as $tableName) {
        // Получаем список полей для указанной таблицы
        $fields = R::inspect($tableName);
        $fields = array_keys($fields);

        foreach ($fields as $field) {
            // Исключаем поле 'id' и поля без лейбла
            $labelText = SR::getResourceValue($tableName, $field);
            if ($field === 'id' || $field === 'date_in' || empty($labelText)) {
                continue;
            }

            // Добавляем поля формы
            $html .= '<div class="form-group mb-2">';
            $html .= '<label for="' . $tableName . '_' . $field . '">' . htmlspecialchars($labelText) . '</label>';
            $html .= '<input type="text" class="form-control" name="' . $tableName . '[' . htmlspecialchars($field) . ']" id="' . $tableName . '_' . htmlspecialchars($field) . '">';
            $html .= '</div>';
        }
    }

    // Добавляем поле для загрузки файла
    $html .= '<div class="form-group mb-2">';
    $html .= '<label for="imported-file">Upload XLSX File</label>';
    $html .= '<input type="file" class="form-control" name="imported-file" id="imported-file" required>';
    $html .= '</div>';

    // Закрываем форму
    $html .= '<button type="submit" class="btn btn-primary" name="submit-warehouse">Submit</button>';
    $html .= '</form>';

    return $html;
}

/**
 * Обрабатывает загруженный XLSX файл и распределяет данные по трем таблицам в БД.
 *
 * @param array $tableNames Массив с именами таблиц.
 * @param array $formFields Поля формы, содержащие названия колонок в XLSX файле.
 * @param array $file Информация о загруженном файле.
 * @throws Exception Если возникла ошибка при загрузке или чтении файла.
 */
function processImportedFileAndInsertData(array $tableNames, array $formFields, array $file): bool
{
    if (!isset($file['imported-file']) || $file['imported-file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла');
    }

    $xlsx = XLSXreader::parse($file['imported-file']['tmp_name']);
    if (!$xlsx) {
        throw new Exception('Не удалось прочитать файл');
    }

    $header = $xlsx->rows()[0];

    foreach ($xlsx->rows() as $rowIndex => $row) {
        if ($rowIndex === 0) continue; // Пропускаем заголовки

        // Создаем новую запись в основной таблице (whitems)
        $mainRecord = R::dispense('whitems');
        $relatedData = [];

        foreach ($formFields as $tableName => $fields) {
            foreach ($fields as $field => $xlsxColumnName) {
                $columnIndex = array_search($xlsxColumnName, $header);

                if ($columnIndex !== false && isset($row[$columnIndex])) {
                    if ($tableName === 'whitems') {
                        $mainRecord[$field] = $row[$columnIndex];
                    } else {
                        $relatedData[$tableName][$field] = $row[$columnIndex];
                    }
                }
            }
        }

        // Сохраняем запись в основной таблице
        $mainId = R::store($mainRecord);

        // Сохраняем записи в связанных таблицах
        foreach ($relatedData as $tableName => $data) {
            $record = R::dispense($tableName);
            $record->whitems_id = $mainId; // Устанавливаем связь с основной таблицей
            foreach ($data as $field => $value) {
                $record[$field] = $value;
            }
            R::store($record);
        }
    }

    return true;
}

// Пример использования
$tableNames = ['whitems', 'warehouse', 'whdelivery'];

if (isset($_POST['submit-warehouse']) && isset($_FILES)) {
    try {
        $formFields = $_POST; // Получаем поля формы
        $file = $_FILES; // Получаем файл
        processImportedFileAndInsertData($tableNames, $formFields, $file);
        echo "Данные успешно импортированы!";
    } catch (Exception $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}
?>


<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Task Manager', 'user' => $user, 'page_name' => $page]); ?>

<div class="container">
    <?php
    //  стандартный вывод для одной таблицы
    if ($table != 'warehouse') {
        try {
            echo generateFormFromTable($table);
        } catch (Exception $e) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Error: ' . $e->getMessage(), 'danger');
        }
    }

    // warehouse actions
    if ($table == 'warehouse') {
        // Вывод формы
        try {
            echo generateFormForTables($tableNames);
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage();
        }
    }
    ?>
</div>

<?php
// footer and scripts
PAGE_FOOTER($page, false); ?>
</body>
</html>
