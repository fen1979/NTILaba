<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'projects/ProductionUnit.php';
$page = 'edit_project';
$user = $_SESSION['userBean'];
$role = $user['app_role'];
$_SESSION['editmode'] = $args = $projectForView = $projectID = null;

/* delete or archive project */
if (isset($_POST['projectid']) && isset($_POST['password'])) {
    /* adding project to archive */
    if (isset($_POST['archive'])) {
        $args = ProductionUnit::archiveOrExstractProject($_POST, $_SESSION['userBean']);
    }
    /* удаление проекта и всех его данных включая фотографии и папку проекта */
    if (isset($_POST['delete'])) {
        $args = ProductionUnit::deleteProject($_POST, $_SESSION['userBean']);
    }
}

/* delete one step from table and restruct the table */
if (isset($_POST['projectid']) && isset($_POST['stepId']) && isset($_POST['delete-step'])) {
    $args = ProductionUnit::deleteProjectStep($_POST, $user);
}

/* вывод проекта для редактирования в режиме админа  и просмотра в режиме пользователя */
if (isset($_GET['pid']) || isset($_SESSION['projectid'])) {
    $projectID = $_SESSION['projectid'] = $_GET['pid'];
    $projectForView = R::Load(PRODUCT_UNIT, $projectID);
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

    <style>
        /*body, html {*/
        /*    height: 100%;*/
        /*    margin: 0;*/
        /*    overflow: hidden;*/
        /*}*/

        /*.container-fluid {*/
        /*    height: 100%;*/
        /*}*/

        /*.left-column {*/
        /*    height: 90vh;*/
        /*    overflow-y: auto;*/
        /*    background-color: #f8f9fa;*/
        /*}*/

        /*.right-column {*/
        /*    height: 100%;*/
        /*    background-color: #ffffff;*/
        /*    position: relative;*/
        /*}*/

        /*.card-list {*/
        /*    padding: 10px;*/
        /*}*/

        /*.small-card {*/
        /*    margin-bottom: 10px;*/
        /*    padding: 10px;*/
        /*    background-color: #ffffff;*/
        /*    border: 1px solid #ddd;*/
        /*    cursor: pointer;*/
        /*    transition: transform 0.3s;*/
        /*}*/

        /*.large-card {*/
        /*    position: absolute;*/
        /*    top: 5rem;*/
        /*    left: 50%;*/
        /*    transform: translate(-50%, -50%);*/
        /*    padding: 20px;*/
        /*    background-color: #ffffff;*/
        /*    border: 1px solid #ddd;*/
        /*    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);*/
        /*    width: 80%;*/
        /*    height: auto;*/
        /*}*/

        /*.small-card:hover {*/
        /*    transform: scale(1.05);*/
        /*}*/
    </style>
</head>
<body>

<?php
// NAVIGATION BAR
$navBarData['title'] = '<b class="text-primary"> ProductionUnit: ' . $projectForView['projectname'] . '</b>';
$navBarData['record_id'] = $projectID ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
$projectSteps = R::find(PROJECT_STEPS, "projects_id LIKE ? ORDER BY CAST(step AS UNSIGNED) ASC", [$projectForView->id]);
?>


<div class="container-fluid hidden">
    <div class="row">
        <!-- Левая колонка с карточками -->
        <div class="col-3 left-column">
            <div class="card-list">
                <!-- Карточки, которые будут вертикально расположены -->
                <?php foreach ($projectSteps as $step) {
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
                    <div class="card small-card" id="card-<?= $step_id ?>"
                         data-title="Step <?= $stepNumber ?>"
                         data-description="<?= htmlspecialchars($description); ?>"
                         data-image="<?= $imgPath ?>"
                         data-video="<?= $videoPath ?>"
                         data-verifay="<?= $chkbx ?>"
                         data-ref="<?= $ref ?>"
                         data-rev="<?= $revision ?>"
                         data-opacity="<?= $opacity ?>"
                    >
                        <h3>Step <?= $stepNumber ?></h3>
                        <p><?= $description ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Правая колонка для отображения выбранной карточки -->
        <div class="col-9 right-column">
            <div class="card large-card" id="large-card-display">
                <!-- Здесь будет отображаться выбранная карточка -->
                <span class="text-danger ms-2 mt-2 mb-2" id="opacity"><i class="bbi bi-check2-square"></i> &nbsp; Step needs validation!</span>
                <h3 id="large-step-title">Select a step</h3>
                <div class="image-container">
                    <!-- фотография -->
                    <img class="image-preview expande" src="" alt="<?= $projectID; ?>" id="image">
                    <!--                    <div class="watermark">ONLY FOR EDITING</div>-->
                </div>
                <p id="large-step-description">Description will appear here.</p>
            </div>
        </div>
    </div>
</div>


<div class="ms-3 me-3 mt-4">
    <div class="row mb-3 border-bottom navbar sticky-top">
        <h3 class="me-3">Assembly steps for project: <?= str_replace('_', ' ', $projectForView['projectname']); ?></h3>
    </div>


    <!-- ----------------------- вывод одного проекта выбранного для просмотра или редактирования ------------------- -->
    <div class="container-fluid">
        <?php
        /* fill and preview project steps */
        $columnCount = 0; // Счётчик колонок

        foreach ($projectSteps as $step) {
            if ($columnCount % 3 == 0) { // Открываем новый ряд каждые 3 колонки
                if ($columnCount > 0) {
                    echo '</div>'; // Закрываем предыдущий ряд, если он существует
                }
                echo '<div class="row mb-4">'; // Открываем новый ряд
            }

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
            <div class="col-4 mb-4 expanded-card" title="<?= "Step N: $stepNumber"; ?>" id="<?= $stepNumber; ?>">
                <div class="card shadow-sm">
                    <span class="text-danger ms-2 mt-2 mb-2" <?= $opacity; ?>><i class="bbi bi-check2-square"></i> &nbsp; Step needs validation!</span>

                    <div class="image-container">
                        <!-- фотография -->
                        <img class="image-preview expande" src="<?= $imgPath; ?>" alt="<?= $projectID; ?>">
                        <div class="watermark">ONLY FOR EDITING</div>
                    </div>
                    <!-- video -->
                    <?php if ($videoPath != 'none') { ?>
                        <div class="image-container">
                            <video controls class="video-preview" width="640" height="480" src="/<?= $videoPath; ?>" style="display: none">
                                Your browser isn't support video!
                            </video>
                            <div class="watermark">ONLY FOR EDITING</div>
                        </div>
                    <?php } ?>

                    <div class="card-body">
                        <!-- описание к фотографии -->
                        <h3 class="card-text"><?= $description; ?></h3>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($videoPath != 'none') { ?>
                                <button type="button" class="btn btn-outline-info ms-1 video-button" title="Preview Video">
                                    <i class="bi bi-camera-reels-fill"></i>
                                </button>
                            <?php } ?>

                            <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                                <div class="btn-group">
                                    <a class="btn btn-outline-warning" title="Edit Step" href="<?= $ref; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Кнопка для открытия модального окна -->
                                    <button type="button" class="btn btn-outline-danger ms-1 delete-button" title="Delete Step"
                                            data-projectid="<?= $projectID ?>" data-stepid="<?= $step_id ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <?php
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
            <?php
            $columnCount++; // Увеличиваем счётчик колонок
        }

        if ($columnCount > 0) {
            echo '</div>'; // Закрываем последний ряд, если он существует
        }
        ?>
    </div>

    <!--  END row  -->
</div>
<!-- END Container  -->

<!--  модальное окно форма для удаления одного шага в проекте  -->
<div class="modal" id="deleteModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Заголовок модального окна -->
            <div class="modal-header">
                <h5 class="modal-title">Delete Step</h5>
                <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <p>Project id: <span id="pid"></span>, Step id: <span id="sid"></span>. If you not see this numbers that some went wrong!</p>
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
<div class="modal" id="deleteProjectModal">
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

                <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="pr_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="pr_password" name="password" required autofocus>
                        <input type="hidden" class="form-control" id="dnProjectID" value="" name="projectid">
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
<script type="text/javascript" src="/public/js/edit-unit.js"></script>
<script>
    // document.addEventListener("DOMContentLoaded", function () {
    //     const cards = document.querySelectorAll('.small-card');
    //     const largeCardDisplay = document.getElementById('large-card-display');
    //     const largeStepTitle = document.getElementById('large-step-title');
    //     const largeStepDescription = document.getElementById('large-step-description');
    //     const verify = dom.e("#opacity");
    //     const img = dom.e("#image");
    //
    //     function updateLargeCard(card) {
    //         // Получаем данные из data-* атрибутов карточки
    //         const title = card.getAttribute('data-title');
    //         const description = card.getAttribute('data-description');
    //         const opacity = card.getAttribute('data-opacity');
    //         const image = card.getAttribute('data-image');
    //
    //         largeStepTitle.textContent = title;
    //         largeStepDescription.textContent = description;
    //         verify.setAttribute("style", opacity);
    //         img.setAttribute("src", image);
    //     }
    //
    //     function handleScroll() {
    //         let activeCard = null;
    //
    //         cards.forEach(card => {
    //             const rect = card.getBoundingClientRect();
    //
    //             if (rect.top >= 0 && rect.bottom <= window.innerHeight) {
    //                 activeCard = card;
    //             }
    //         });
    //
    //         if (activeCard) {
    //             updateLargeCard(activeCard);
    //         }
    //     }
    //
    //     cards.forEach(card => {
    //         card.addEventListener('click', function () {
    //             updateLargeCard(card);
    //         });
    //     });
    //
    //     document.querySelector('.left-column').addEventListener('scroll', handleScroll);
    //
    //     // Инициализация первой карточки
    //     if (cards.length > 0) {
    //         updateLargeCard(cards[0]);
    //     }
    // });
</script>
</body>
</html>