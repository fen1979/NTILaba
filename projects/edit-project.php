<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();
require_once 'projects/Project.php';
$page = 'edit_project';
$user = $_SESSION['userBean'];
$role = $user['app_role'];
$_SESSION['editmode'] = $args = $projectForView = $projectID = null;

/* delete or archive project */
if (isset($_POST['projectid']) && isset($_POST['password'])) {
    /* adding project to archive */
    if (isset($_POST['archive'])) {
        $args = Project::archiveOrExstractProject($_POST, $_SESSION['userBean']);
    }
    /* удаление проекта и всех его данных включая фотографии и папку проекта */
    if (isset($_POST['delete'])) {
        $args = Project::deleteProject($_POST, $_SESSION['userBean']);
    }
}

/* delete one step from table and restruct the table */
if (isset($_POST['projectid']) && isset($_POST['stepId']) && isset($_POST['delete-step'])) {
    $args = Project::deleteProjectStep($_POST, $user);
}

/* вывод проекта для редактирования в режиме админа  и просмотра в режиме пользователя */
if (isset($_GET['pid']) || isset($_SESSION['projectid'])) {
    $projectID = $_SESSION['projectid'] = $_GET['pid'];
    $projectForView = R::Load(PROJECTS, $projectID);
}
?>
<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .image-container {
            position: relative;
            display: inline-block;
        }

        .image-container img, .image-container iframe {
            display: block;
            width: 100%; /* or your desired width */
            height: auto;
        }

        .watermark {
            position: absolute;
            width: 100%;
            top: 45%; /* adjust as necessary */
            right: 0; /* adjust as necessary */
            color: white; /* text color */
            background-color: rgba(255, 99, 99, 0.5); /* semi-transparent background */
            font-size: 20px; /* adjust as necessary */
            padding-top: 20px;
            padding-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<?php
/* NAVIGATION PANEL */
$title = ['title' => '<b class="text-primary"> Project: ' . $projectForView['projectname'] . '</b>', 'app_role' => $user['app_role'], 'link' => $user['link']];
NavBarContent($page, $title, $projectID);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
?>

<div class="ms-3 me-3 mt-4">
    <div class="row mb-3 border-bottom navbar sticky-top">
        <h3>Assembly steps for project: <?= str_replace('_', ' ', $projectForView['projectname']); ?></h3>
    </div>

    <!-- ----------------------- вывод одного проекта выбранного для просмотра или редактирования ------------------- -->
    <div class="row">
        <?php
        /* fill and preview project steps */
        $projectSteps = R::find(PROJECT_STEPS, "projects_id LIKE ? ORDER BY CAST(step AS UNSIGNED) ASC", [$projectForView->id]);
        foreach ($projectSteps as $step) {
            $projectID = $step['projects_id'];
            $step_id = $step['id'];
            $imgPath = $step['image'];
            $videoPath = (strpos($step['video'], '.mp4') !== false) ? $step['video'] : 'none';
            $description = $step['description'];
            $stepNumber = $step['step'];
            $revision = $step['revision'];
            $validation = $step['validation'];
            /* getting checkbox value */
            $chkbx = $step['validation'];
            $opacity = ($chkbx) ? "" : 'style="opacity:0;"';
            $ref = "edit_step?pid=$projectID&sid=$step_id";
            ?>
            <div class="col-md-6 mb-4 expanded-card" title="<?= "Step N: $stepNumber"; ?>" id="<?= $stepNumber; ?>">
                <div class="card shadow-sm">
                    <span class="text-danger ms-2 mt-2 mb-2" <?= $opacity; ?>><i class="bbi bi-check2-square"></i> &nbsp; Step needs validation!</span>

                    <div class="image-container">
                        <!-- фотография -->
                        <img class="image-preview expande" src="<?= $imgPath; ?>" alt="<?= $projectID; ?>">
                        <div class="watermark">ONLY FOR EDITING</div>
                    </div>
                    <!-- video -->
                    <?php if (isUserRole(ROLE_WORKER) && $videoPath != 'none') { ?>
                        <div class="image-container">
                            <video controls class="video-preview" width="640" height="480" src="<?= $videoPath; ?>" style="display: none">
                                Your browser isn't support video!
                            </video>
                            <div class="watermark">ONLY FOR EDITING</div>
                        </div>
                    <?php } ?>

                    <div class="card-body">
                        <!-- описание к фотографии -->
                        <h3 class="card-text"><?= $description; ?></h3>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if (isUserRole(ROLE_WORKER) && $videoPath != 'none') { ?>
                                <button type="button" class="btn btn-outline-info ms-1 video-button" title="Preview Video">
                                    <i class="bi bi-camera-reels-fill"></i>
                                </button>
                            <?php } ?>

                            <?php if (isUserRole(ROLE_ADMIN)) { ?>
                                <div class="btn-group">
                                    <a class="btn btn-outline-warning" title="Edit Step" href="<?= $ref; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Кнопка для открытия модального окна -->
                                    <button type="button" class="btn btn-outline-danger ms-1 delete-button" title="Delete Step">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <?php
                                echo '<p class="data-for-modal" data-projectid="' . $projectID . '" data-stepid="' . $step_id . '" hidden></p>';
                            }
                            $t = 'By clicking on the revision, 
                            you will be taken to the archive page of the change history of this step for this project, 
                            if there are no changes for this step, the page will be empty! 
                            Changes to the step history are allowed only to the project administrator!';
                            ?>
                            <b>
                                <i class="bi bi-info-circle" data-title="<?= $t; ?>"></i>
                                <button type="button" class="url btn btn-outline"
                                        value="step_history?pid=<?= $projectID; ?>&stid=<?= $step_id; ?>">
                                    <i class="bi bi-eye"></i>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <small class="text-warning">Version&nbsp;<?= $revision; ?></small>
                                </button>
                            </b>
                            <b><small class="text-danger">Step №<?= $stepNumber; ?></small></b>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <!--  END row  -->
</div>
<!-- END Container  -->

<!--  модальное окно форма для удаления одного шага в проекте  -->
<div class="modal" id="deleteModal" style="backdrop-filter: blur(15px);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Заголовок модального окна -->
            <div class="modal-header">
                <h5 class="modal-title">Delete Step</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autofocus>
                        <input type="hidden" id="dfProjectID" name="projectid">
                        <input type="hidden" id="dfstepId" name="stepId">
                    </div>
                    <button type="submit" class="btn btn-outline-danger" name="delete-step">Delete Step</button>
                </form>
            </div>

        </div>
    </div>
</div>

<!--  модальное окно форма для удаления или архивации проекта  -->
<div class="modal" id="deleteProjectModal" style="backdrop-filter: blur(15px);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Заголовок модального окна -->
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">
                    Delete Project
                    <br>
                    <b class="text-danger">Please be advised:
                        <br>This action is irreversible and requires thorough consideration.
                        <br>Once initiated, there is no turning back, so weigh your decision carefully.
                    </b>
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="pr_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="pr_password" name="password" required autofocus>
                        <input type="text" class="form-control" id="dnProjectID" name="projectid" hidden>
                    </div>
                    <button type="submit" id="delete-btn" name="delete" class="btn btn-outline-danger hidden">Delete Project</button>
                    <button type="submit" id="archive-btn" name="archive" class="btn btn-outline-warning hidden">Archive Project</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
// проверяем если в папке есть файлы и она не затычка в БД
if (isDirEmpty($projectForView->docsdir) && $projectForView->docsdir != 'storage/projects/') {
    echo '<span class="hidden" id="project_folder_path">wiki?pr_dir=' . $projectForView->docsdir . '</span>';
}
ScriptContent($page); ?>
<script type="text/javascript" src="/public/js/edit-project.js"></script>

</body>
</html>