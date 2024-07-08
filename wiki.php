<?php
/* drag and drop file redirect */
if (isset($_POST['filePath']) && isset($_POST['targetDir'])) {
    include_once 'core/Resources.php';
    include_once 'core/Utility.php';
    $file = _E($_POST['filePath']);
    $target = _E($_POST['targetDir']);
    $targetFile = '';
    if ($target == 'wiki') {
        // Формируем путь к файлу в глобальной директории
        $targetFile = WIKI_FOLDER . basename($file);
    } elseif ($target == 'delete') {
        // Формируем путь к файлу в временной директории
        $targetFile = TEMP_FOLDER . basename($file);
        // TODO UNDO delete file/files
    } else {
        // Формируем путь к файлу в целевой директории
        $targetDir = WIKI_FOLDER . $target;
        $targetFile = $targetDir . '/' . basename($file);
    }

    // Проверяем, что файл или директория с таким именем не существует в целевой директории
    // TODO view message about actions
    if (!file_exists($targetFile)) {
        if (rename($file, $targetFile)) {
            echo "Файл успешно перемещен";
        } else {
            echo "Ошибка при перемещении файла";
        }
    } else {
        echo "Файл или папка с таким именем уже существует в целевой директории";
    }
    exit();
}

EnsureUserIsAuthenticated($_SESSION,'userBean');

$directory = (isset($_GET['pr_dir'])) ? _dirPath($_GET) : WIKI_FOLDER; // Путь к директории с файлами
$page = 'wiki';
$user = $_SESSION['userBean'];
$role = $user['app_role'];
$args = null;
$backBtn = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_FILES['some_file'])) {
    // Форма отправлена
    $folderName = trim($_POST['folder_name']);

    if (!empty($folderName)) {
        $targetDirectory = $directory . $folderName;
        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }
    }

    $files = $_FILES['some_file'];
    $numFiles = count($files['name']);

    for ($i = 0; $i < $numFiles; $i++) {
        // Получаем временное имя файла, его оригинальное имя и путь для сохранения
        $tmpName = $files['tmp_name'][$i];
        $fileName = $files['name'][$i];
        $uploadPath = $targetDirectory . "/" . basename($fileName);

        // Перемещаем файл из временного места в целевую папку
        if (move_uploaded_file($tmpName, $uploadPath)) {
            $args['color'] = 'success';
            $args['info'] = "Files $numFiles pcs, uploaded successfully.<br>";
        } else {
            $args['color'] = 'danger';
            $args['info'] = "Error while uploading $fileName.<br>";
        }
    }
}

/* получеам путь к папке которую выбрали для просмотра */
if (isset($_GET['dir']) && !isset($_GET['mode'])) {
    // Добавляем к пути выбранную директорию
    $directory .= $_GET['dir'];
    // делаем видимой кнопку возврата в главное меню
    $backBtn = true;
}

// пришли сюда из создания проекта для добавления разной документации
if (isset($_GET['dir']) && isset($_GET['mode']) && $_GET['mode'] == 'add-project') {
    // create dir for project but first check is dir not exist
    // then in folder open form to adding files
    // upload and save in to project DB
    // after uploading files return result to create or update project page
    $projectName = _E($_GET['dir']);
    // Добавляем к пути выбранную директорию
    $directory = PROJECTS_FOLDER . $projectName;
    // делаем видимой кнопку возврата в главное меню
    $backBtn = false;
}
?>

<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .btn-trash {
            font-size: 2em;
            margin-inline-end: 1em;
            color: #dc3545;
        }

        .btn-container {
            flex-wrap: wrap;
            justify-content: left;
            align-items: center;
            align-content: center;
        }
    </style>
</head>
<body>
<?php
NavBarContent($page, $user, null, Y['WIKI']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
?>

<h4 class="text-center">
    <?php echo $n = rtrim($_GET['dir'] ?? (isset($_GET['pr_dir']) ? _dirPath($_GET) : ''), '/'); ?>
</h4>

<div class="row ms-3 me-3">
    <div class="col-3">
        <?php if (isUserRole(ROLE_ADMIN)) { ?>
            <button class='btn drop-target btn-trash' data-dir='delete'><i class='bi bi-trash'></i></button>
        <?php }
        if ($backBtn) { ?>
            <a type="button" href="/wiki" class="btn btn-outline-dark drop-target" data-dir="wiki">
                <i class="bi bi-box-arrow-in-left"></i>
                Back
            </a>
        <?php } ?>
        <button type="button" id="add-files" class="btn btn-outline-danger">
            <i class="bi bi-folder-plus"></i>&nbsp;
            Add Files or Folder
        </button>
    </div>
    <div id="addimg-form" class="col-9 bg-info rounded p-2 hidden">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label for="folder_name" class="form-label">Folder Name <b class="text-danger">*</b></label>
                </div>
                <div class="col">
                    <input type="text" id="folder_name" name="folder_name" class="form-control" placeholder="Folder name"
                           value="<?= $n ?? ''; ?>" <?= ($n != null) ? '' : 'required' ?>>
                </div>
                <div class="col-auto">
                    <input type="file" webkitdirectory mozdirectory directory multiple id="some_folder" name="some_file[]" style="display: none;">
                    <label for="some_folder" class="btn btn-light">Pick Folder <b class="text-danger">*</b></label>
                </div>
                <div class="col-auto">
                    <input type="file" multiple id="some_file" name="some_file[]" style="display: none;">
                    <label for="some_file" class="btn btn-light">Pick Files <b class="text-danger">*</b></label>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success" id="submit-btn" disabled>Upload files</button>
                </div>
            </div>
        </form>
    </div>

</div>
<main style="margin: 0; overflow-y: scroll; height: 80vh;">
    <div class="row p-3" id="search_here">
        <?php
        function scanDirectory($directory, $isRoot = true)
        {
            $files = scandir($directory);
            // Выводим кнопки для директорий на самом верхнем уровне
            if ($isRoot) {
                echo '<div class="d-flex btn-container">';
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $filePath = $directory . '/' . $file;
                    if (is_dir($filePath)) {
                        $path = (isset($_GET['pr_dir'])) ? _dirPath($_GET) : WIKI_FOLDER; // Путь к директории с файлами
                        if ($path == 'storage/wiki/') {
                            // Вывод кнопки для директории wiki
                            echo "<button class='btn drop-target' data-dir='" . htmlspecialchars($file) . "'
                        onclick=\"window.location.href='/wiki?dir=" . urlencode($file) . "/'\"><i class='bi bi-folder fs-4'></i> - $file</button>";
                        } else {
                            // Вывод кнопки для директории projects
                            echo "<button class='btn drop-target' data-dir='" . htmlspecialchars($file) . "'
                        onclick=\"window.location.href='/wiki?pr_dir=$path" . urlencode($file) . "/'\"><i class='bi bi-folder fs-4'></i> - $file</button>";
                        }
                    }
                }
                echo '</div>';
            }

            // выводим файлы из дериктории
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $filePath = $directory . '/' . $file;
                if (is_dir($filePath)) {
                    // Если не корневая директория, рекурсивно сканируем поддиректории
                    if (!$isRoot) scanDirectory($filePath, false);
                } else {
                    $fileInfo = pathinfo($filePath);
                    if (isset($fileInfo['extension'])) {
                        $fileExtension = strtolower($fileInfo['extension']);
                        // Тут ваш код для работы с файлами
                        ?>
                        <div class="col-md-2 file" draggable='true' data-filepath="<?= htmlspecialchars($directory . '/' . $file); ?>">
                            <div class="card shadow-sm">
                                <!--  Project Name and Share Link -->
                                <h5 class="card-title position-relative p-2">
                                    <b class="text-primary">File Name:</b>
                                    <?= htmlspecialchars($file); ?>
                                </h5>
                                <?php
                                // Определение типа файла и вывод соответствующего контента
                                switch ($fileExtension) {
                                    case 'svg':
                                    case 'jpg':
                                    case 'png':
                                    case 'webp':
                                        echo "<img src='$filePath'  alt='gaga' class='img-fluid'/>";
                                        break;
                                    case 'mp4':
                                        echo "<video controls class='img-fluid'>
                                                <source src='$filePath' type='video/mp4'>
                                                Your browser does not support the video tag.
                                            </video>";
                                        break;
                                    case 'm4a':
                                    case 'wav':
                                    case 'mp3':
                                        echo "<audio controls style='width:200px'>
                                                <source src='$filePath' type='audio/aac'>
                                                <source src='$filePath' type='audio/mp3'>
                                                Your browser does not support the audio element.
                                               </audio>";
                                        break;
                                    case 'pdf':
                                        echo "<iframe src='$filePath'></iframe>";
                                        break;
                                }
                                ?>
                                <div class="card-body d-flex gap-2">
                                    <a type="button" href='<?= $filePath; ?>' download class='card-text btn btn-info w-50'
                                       data-title="Download File to your computer or smart phone">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <a type="button" href='<?= $filePath; ?>' target="_blank" class='card-text btn btn-secondary w-50'
                                       data-title="Open File in new tab or download">
                                        <i class="bi bi-folder2-open"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
            }
        }

        scanDirectory($directory);
        ?>
    </div>
</main>
<?php
footer($page);
ScriptContent($page);
?>
<script src="public/js/wiki.js"></script>
</body>
</html>

