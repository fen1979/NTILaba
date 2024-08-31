<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'projects/Project.php';
$page = 'new_project';
$user = $_SESSION['userBean'];
$id = null;
$buttonSave = 'Create Project';
$disabled = 'disabled';
$backButton = '/';

/* условие: проект создается при создании заказа */
if (isset($_GET['orders'])) {
    $buttonSave = 'Create project from Orders';
    $backButton = '/new_order';
}

/* СОЗДАЕМ НОВЫЙ ПРОЕКТ И СОХРАНЯЕМ В БД */
if (isset($_POST['projectName']) && !isset($_SESSION['editmode'])) {
    /* создание нового проекта в БД */
    $args = Project::createNewProject($_POST, $user, $_FILES);

    /* возврат на страницу добавления заказа с данными о новом проекте */
    if (!empty($args['id'])) {
        if (isset($_GET['orders'])) {
            $url = "customerName=" . urlencode($args['customerName']) .
                "&customer_id=" . urlencode($args['customerId']) .
                "&projectName=" . urlencode($args['projectName']) .
                "&projectRevision=" . urlencode($args['projectRevision']) .
                "&project_id=" . urlencode($args['id']);
            redirectTo("new_order?$url");
        }

        /* Переадресация на страницу добавления данных к проекту */
        $_SESSION['projectid'] = $args['id'];
        redirectTo("check_part_list?pid={$args['id']}");
    }
}

/* РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕГО ПРОЕКТА */
if (isset($_POST['projectName']) && isset($_SESSION['editmode']) && $_SESSION['editmode'] == 'activated') {
    /* добавляем в пост id проекта */
    $_POST['projectid'] = $_SESSION['projectid'];
    Project::editProjectInformation($_POST, $user, $_FILES);
}

/* АКТИВАЦИЯ EDITING MODE */
if (isset($_GET["pid"]) && $_GET['pid'] == "editmode" || isset($_SESSION['editmode']) && !isset($_GET['back-id'])) {
    $_SESSION['editmode'] = 'activated';
    $project = R::load(PROJECTS, $_SESSION['projectid']);
    $buttonSave = 'Update Project';
    $disabled = '';
    $backButton = "edit_project?pid=" . $_SESSION['projectid'];
    $id = $project->id;
}

// приходим из order-details tab-3 tools, возвращаемся обратно после изменений
if (isset($_GET["pid"]) && isset($_GET['mode']) && $_GET['mode'] == "editmode" && isset($_GET['back-id'])) {
    $_SESSION['editmode'] = 'activated';
    $_SESSION['projectid'] = $_GET["pid"];
    $project = R::load(PROJECTS, $_GET["pid"]);
    $buttonSave = 'Update Project';
    $disabled = '';
    $backButton = "order/preview?orid={$_GET['back-id']}&tab=tab3";
    $id = $project->id;
}

// TODO fixme
// код для работы по клонированию проектов
//function copyFileToDirectory($filePath, $destinationDir): string
//{
//    // Проверяем, существует ли файл
//    if (!file_exists($filePath)) {
//        return "File does not exist: $filePath";
//    }
//
//    // Проверяем, существует ли целевая директория
//    if (!is_dir($destinationDir)) {
//        return "Destination directory does not exist: $destinationDir";
//    }
//
//    // Получаем имя файла из полного пути
//    $fileName = basename($filePath);
//
//    // Полный путь к новому расположению файла
//    $destinationFilePath = $destinationDir . DIRECTORY_SEPARATOR . $fileName;
//
//    // Копируем файл в целевую папку
//    if (copy($filePath, $destinationFilePath)) {
//        return "File copied successfully to $destinationFilePath";
//    } else {
//        return "Failed to copy file: $filePath";
//    }
//}
//
//$t40 = R::findAll(PROJECT_STEPS, 'projects_id = 40');
//foreach ($t40 as $item) {
//    //echo copyFileToDirectory($item['image'], 'storage/projects/ELC7038-B00/');
//    $t84 = R::dispense(PROJECT_STEPS);
//    $t84->projects_id = 84;
//    $t84->routeid = $item['routeid'];
//    $t84->step = $item['step'];
//    $t84->routeaction = $item['routeaction'];
//    $t84->validation = $item['validation'];
//    $t84->image = str_replace('A00', 'B00', $item['image']);
//    $t84->video = str_replace('A00', 'B00', $item['video']);
//    $t84->description = $item['description'];
//    $t84->tool = $item['tool'];
//    $t84->revision = $item['revision'];
//    $t84->front_pic = $item['front_pic'];
//
//    //R::store($t84);
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
        .tool-name {
            font-size: 170%;
            display: flex;
            align-items: center; /* Выравнивание по центру вертикально */
            text-align: left; /* Выравнивание текста к левому краю */
        }

        .tools-row {
            display: inline-flex;
            flex-wrap: nowrap;
            text-align: center;
        }

        /* СТИЛИ ДЛЯ ВЫВОДА ТАБЛИЦ */
        .modal-body {
            /* убираем падинги от бутстрапа */
            padding: 0;
        }

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
            top: 0;
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

        #pasteArea {
            height: 30rem;
            background-image: url(/public/images/drop-here.png);
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>
<body>
<?php
if ($backButton == '/') {
    // NAVIGATION BAR
    NavBarContent(['title' => 'Project Creation', 'active_btn' => Y['N_PROJECT'], 'user' => $user, 'page_name' => $page]);

} else {
    //back button to edit-project or home
    ?>
    <header style="height: 6rem;">
        <form action="" id="routing" class="hidden" method="post"></form>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg fixed-top navbar-scroll blury">
            <div class="container-fluid">
                <!-- TITLE -->
                <h3 class="navbar-brand">Edit Project Information</h3>
                <!-- GAMBURGER BUTTON -->
                <button class="navbar-toggler" type="button" data-mdb-toggle="collapse"
                        data-mdb-target="#navBarContent" aria-controls="navBarContent" aria-expanded="false"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon d-flex justify-content-start align-items-center"></span>
                </button>
                <div class="w-100 mainSearchForm"></div>
                <div class="collapse navbar-collapse" id="navBarContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <button type="button" value="<?= $backButton ?>" class="url btn btn-outline-danger">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
<?php } ?>

<div class="container mt-5 px-3 py-3 rounded" style="background: beige;">
    <div class="row">
        <div class="col-8"><h3><?= (!$id) ? 'Create Project' : 'Edit Project'; ?></h3></div>
        <?php $lastId = R::getCol("SELECT MAX(id) FROM projects"); ?>
        <div class="col-4"><h3>Project ID: &nbsp; <?= !$id ? $lastId[0] + 1 : $id; ?></h3></div>
    </div>

    <form id="createProjectForm" action="" method="post" enctype="multipart/form-data" autocomplete="off">
        <!--i CUSTOMER NAME ID -->
        <div class="mb-3">
            <div class="row">
                <div class="col-6">
                    <label for="customerName" class="form-label">Customer Name <b class="text-danger">*</b> <i class="bi bi-search"></i></label>
                </div>
                <div class="col-3">
                    <label for="customerId" class="form-label">Customer ID</label>
                </div>
                <div class="col-3"></div>
            </div>
            <div class="row">
                <div class="col-6">
                    <input type="text" class="form-control searchThis" id="customerName" name="customerName"
                           value="<?= (!empty($project['customername'])) ? $project['customername'] : set_value('customerName'); ?>"
                           data-request="customer" required>
                </div>
                <div class="col-3">
                    <input type="text" class="form-control" id="customerId" name="customerId" readonly
                           value="<?= (!empty($project['customerid'])) ? $project['customerid'] : set_value('customerId'); ?>">
                </div>
                <div class="col-3">
                    <?php $href = "/create_client?routed-from=create-project"; ?>
                    <a role="button" class="btn btn-outline-diliny form-control" id="createCustomer" href="<?= $href ?>">
                        Add New Customer
                    </a>
                </div>
            </div>
        </div>
        <!--i CUSTOMER PRIORITY AND HEAD PAY -->
        <div class="mb-3">
            <div class="row">
                <div class="col-6">
                    <label for="priorityMakat" class="form-label">Priority makat <!--<b class="text-danger">*</b>--></label>
                </div>
                <div class="col-6">
                    <label for="headPay" class="form-label">Head Pay <!--<b class="text-danger">*</b>--></label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <input type="text" name="priorityMakat" value="<?= (!empty($project['priority'])) ? $project['priority'] : set_value('priorityMakat'); ?>"
                           class="form-control" id="priorityMakat">
                </div>
                <div class="col-6">
                    <input type="text" name="headPay" value="<?= (!empty($project['headpay'])) ? $project['headpay'] : set_value('headPay'); ?>"
                           id="headPay" class="form-control">
                </div>
            </div>
        </div>
        <!--i PROJECT NAME, INCOMING DATE, REVISION -->
        <div class="mb-3">
            <div class="row">
                <div class="col-7">
                    <label for="pn" class="form-label" id="pn_label">Unit Name <b class="text-danger">*</b></label>
                </div>
                <div class="col-3">
                    <label for="pr" class="form-label">Unit Version <b class="text-danger">*</b></label>
                </div>
                <div class="col-2">
                    <label for="date_in" class="form-label">Unit start Date <b class="text-danger">*</b></label>
                </div>
            </div>

            <div class="row">
                <div class="col-7">
                    <input type="text" name="projectName" value="<?= (!empty($project['projectname'])) ? $project['projectname'] : set_value('projectName'); ?>"
                           class="form-control" id="pn" data-mode="<?= !empty($_GET['pid']) ? $_GET['pid'] : '0'; ?>" required>
                </div>
                <div class="col-3">
                    <input type="text" class="form-control" id="pr" name="projectRevision" required
                           value="<?= (!empty($project['revision'])) ? $project['revision'] : set_value('projectRevision'); ?>">
                </div>
                <div class="col-2">
                    <input type="datetime-local" class="form-control" id="date_in" name="date_in"
                           value="<?= (!empty($project->date_in)) ? $project->date_in : date('Y-m-d H:i'); ?>" required>
                </div>
            </div>
        </div>
        <!--i EXECUTOR PROJECT NAME, ROUTE CARD NAMES  -->
        <div class="mb-4">
            <div class="row">
                <div class="col-6">
                    <label for="en" class="form-label">Executor Name <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="en" name="executorName" required
                           value="<?= (!empty($project['executor'])) ? $project['executor'] : set_value('executorName', 'NTI'); ?>">
                </div>
                <div class="col-6">
                    <label for="rcn" class="form-label">Route Card Name <b class="text-danger">*</b></label>
                    <select id="rcn" name="route_card_name" class="form-select" aria-label="Route Card Name">
                        <option selected>Choose Route Card Name for this Project</option>
                        <?php
                        // создать потом массив имен для рут карт документов и сохранить в БД
                        $opt = ['1' => 'option 1', '2' => 'option 2', '3' => 'option 3', '4' => 'option 4', '5' => 'option 5'];
                        foreach ($opt as $key => $item) {
                            echo '<option value="' . $key . '">' . $item . '</option>';
                        } ?>
                    </select>
                </div>
            </div>

        </div>
        <!--i PROJECT FILES -->
        <div class="row mb-3">
            <div class="p-1 col">
                <button type="button" class="btn btn-outline-primary form-control" id="pickFile"
                        data-who="file">Upload Unit Documentation (PDF Only)
                </button>
                <input type="file" name="dockFile" id="pdf_file" accept=".pdf" hidden/>
            </div>

            <?php
            // если файл пдф был загружен (для редактирования)
            if (!empty($project['projectdocs']) && strpos($project['projectdocs'], '.pdf') !== false) { ?>
                <div class="p-1 col">
                    <a type="button" target="_blank" class="btn btn-outline-info form-control" href="<?= $project['projectdocs'] ?? ''; ?>">
                        View or Download Document
                    </a>
                </div>
                <?php
            }

            // ели папка не пуста и переменная есть в БД (для редактирования)
            if (!empty($project->docsdir) && isDirEmpty($project->docsdir)) {
                $href = "/wiki?pr_dir=$project->docsdir";
                ?>
                <div class="p-1 col">
                    <a type="button" target="_blank" class="btn btn-outline-info form-control" href="<?= $href; ?>">
                        View or Download Unit Files
                    </a>
                </div>
            <?php } ?>

            <div class="p-1 col">
                <!--i добавления файлов к проекту -->
                <button type="button" class="btn btn-outline-primary form-control " id="projects_files_btn">
                    <?php $t = 'Warning! All files must be outside the folders, 
                    saving the folder is possible only in archived form, 
                    the file size cannot exceed 300MB in total! 
                    All types of files are allowed for uploading, 
                    you can download or view files after uploading and saving the project.'; ?>
                    <i class="bi bi-info-circle" data-title="<?= $t; ?>"></i>
                    <span id="pick_files_text">Upload Additional files</span>
                </button>
                <input type="file" name="projects_files[]" id="projects_files" accept="*/*" value="" multiple hidden>
            </div>
        </div>

        <!--i FOR SUB ASSEMBLY PROJECT ROUTECARD -->
        <div class="checkbox mb-3">
            <div class="row">
                <div class="col-9 border-end">
                    <?php
                    if (isset($_SESSION['editmode']) && $_SESSION['editmode'] == 'activated') {
                        echo $sub_assy = (!empty($project['sub_assembly']) && $project['sub_assembly'] == 1) ? 'checked' : '';
                    } else {
                        $sub_assy = 'checked';
                    }
                    ?>
                    <div class="form-check form-switch fs-3">
                        <input class="form-check-input track-change" type="checkbox" id="sub_assembly" name="sub_assembly"
                               value="1" <?= $sub_assy; ?>>
                        <label class="form-check-label fs-5" for="sub_assembly" style="font-size: large">
                            Photos or videos are not required when creating project assembly steps.
                            By selecting this option, you can proceed to save the steps without including media content,
                            which is crucial for compiling the assembly manual.
                        </label>
                    </div>
                </div>
                <div class="col-3 border-start">
                    <?php $project_type = (!empty($project['project_type']) && $project['project_type'] == 1) ? 'checked' : ''; ?>
                    <div class="form-check form-switch fs-3">
                        <input class="form-check-input track-change" type="checkbox" id="project_type" name="project_type"
                               value="1" <?= $project_type; ?>>
                        <label class="form-check-label fs-5" for="project_type" style="font-size: large">
                            Project type: SMT assembly line.
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <!--i ADDITIONAL INFORMATIONS -->
        <div class="mb-3">
            <?php $area = (!empty($project['extra'])) ? $project['extra'] : set_value('extra'); ?>
            <label for="ai" class="form-label">Additional information</label>
            <textarea class="form-control" id="ai" name="extra"><?= $area; ?></textarea>
        </div>

        <!--i CHOOSE TOOL TO PROJECT AND CREATE PROJECT BUTTONS -->
        <div class="row mt-5">
            <div class="col-8">
                <label for="tools">Choose Tools</label>
                <input type="text" name="tools" id="tools" class="searchThis form-control" placeholder="Write here for choose tools" data-request="tools">
            </div>

            <!-- fixme hidden tag -->
            <div class="col hidden">
                <button class="btn btn-outline-dark form-control dropdown-toggle" type="button" id="dropdownMenuTools" data-bs-toggle="dropdown" aria-expanded="false">
                    Choose Tools to Project
                </button>

                <div class="dropdown-menu" aria-labelledby="dropdownMenuTools">
                    <!-- список инструментов на производстве, выбирается при создании проекта -->
                    <div style="overflow-y: scroll; height: 45rem;" class="p-3">
                        <?php
                        $table = R::getAll('SELECT DISTINCT t.* FROM tools t WHERE t.device_model IS NOT NULL AND t.device_model != "" GROUP BY t.device_model');
                        $toolsChoosen = (!empty($project['tools']) && $project['tools'] != 'NC') ? explode(',', $project['tools']) : null;
                        foreach ($table as $row) {
                            $on = '';
                            if ($toolsChoosen != null && in_array($row['id'], $toolsChoosen, true) !== false) {
                                $on = 'checked';
                            }
                            ?>
                            <div class="card mb-3" style="max-width: 540px;">
                                <div class="row g-0">
                                    <div class="col-md-6">
                                        <img src="<?= !empty($row['image']) ? $row['image'] : 'public/images/pna_en.webp'; ?>" class="img-fluid rounded-end"
                                             alt="<?= $row['serial_num'] ?>">
                                    </div>
                                    <div class="col-md-6 border-start">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= $row['manufacturer_name'] ?></h5>
                                            <p class="card-text"><?= $row['device_model'] ?></p>
                                            <p class="card-text"><?= $row['device_type'] ?></p>
                                            <p class="card-text"><small class="text-muted"><?= $row['next_inspection_date'] ?></small></p>
                                            <input class="form-check-input" type="checkbox" name="selected-tools[]" value="<?= $row['id'] ?>" <?= $on ?>
                                                   style="width: 10rem; height: 10rem;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!-- fixme hidden tag -->

            <div class="col-4">
                <button type="submit" class="btn btn-outline-success form-control" id="createProjectBtn" <?= $disabled; ?>>
                    <?= $buttonSave; ?>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-auto" id="tool_preview">

            </div>
        </div>
    </form>
</div>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

// SCRIPTS
PAGE_FOOTER($page, false); ?>
<script src="/public/js/add-project.js"></script>
</body>
</html>