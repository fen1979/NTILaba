<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'projects/Project.php';
$page = 'edit_project';
$role = $user['app_role'];
$_SESSION['editmode'] = $args = $project_for_edit = $projectID = null;

/* delete or archive project */
if (isset($_POST['projectid']) && isset($_POST['password'])) {
    /* adding project to archive */
    if (isset($_POST['archive'])) {
        Project::archiveOrExstractProject($_POST, $_SESSION['userBean']);
    }
    /* удаление проекта и всех его данных включая фотографии и папку проекта */
    if (isset($_POST['delete'])) {
        Project::deleteProject($_POST, $_SESSION['userBean']);
    }
}

/* delete one step from table and restruct the table */
if (isset($_POST['projectid']) && isset($_POST['stepId']) && isset($_POST['delete-step'])) {
    Project::deleteProjectStep($_POST, $user);
}

/* вывод проекта для редактирования в режиме админа  и просмотра в режиме пользователя */
if (isset($_GET['pid']) || isset($_SESSION['projectid'])) {
    $projectID = $_SESSION['projectid'] = $_GET['pid'];
    $project_for_edit = R::Load(PROJECTS, $projectID);
}
// getting unit steps from DB
$unit_staps = R::find(PROJECT_STEPS, "projects_id LIKE ? ORDER BY CAST(step AS UNSIGNED) ASC", [$project_for_edit->id]);
?>
<!DOCTYPE html>
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
$title = '<b class="text-primary fs-4"> Assembly steps for Unit: ' . str_replace('_', ' ', $project_for_edit['projectname']) . '</b>';
NavBarContent(['title' => $title, 'record_id' => $projectID ?? null, 'user' => $user, 'page_name' => $page, 'style' => ' ']); ?>

<!-- Thumbnail Section -->
<div class="thumbnail-container">
    <div class="page-thumbnail">
        <?php foreach ($unit_staps as $s) { ?>
            <div class="thumbnail" data-step="<?= $s['step'] ?>">
                <div class="disabled">
                    <h1>Step <?= $s['step'] ?></h1>
                    <p><?= $s['description'] ?></p>
                    <img src="<?= $s['image'] ?>" alt="Image Placeholder">
                    <div class="buttons-container">
                        <button class="warning">Edit Step</button>
                        <button class="info">View History</button>
                        <button class="danger">Delete Step</button>
                    </div>
                    <p class="description">Additional description text...</p>
                    <div class="input-fields">
                        <input type="text" placeholder="Part Number">
                        <input type="text" placeholder="Route Action">
                    </div>
                    <div class="input-fields">
                        <input type="text" placeholder="Revision">
                        <input type="text" placeholder="Tool Nmae">
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Full Page Section -->
<div class="full-page-container">
    <?php foreach ($unit_staps as $s) {
        $s['front_pic'];
        $s['routeid'];
        $s['validation'];
        ?>
        <div class="full-page" data-step="<?= $s['step'] ?>" id="<?= $s['step'] ?>">
            <h1>Step <?= $s['step'] ?></h1>
            <p><?= $s['description'] ?></p>
            <div class="img-video-container">
                <!-- image -->
                <img src="<?= $s['image'] ?>" alt="Image Placeholder" class="magnify-image">
                <?php list($src, $act) = _if((strpos($s['video'], '.mp4') !== false), [$s['video'], ''], ['', 'hidden']); ?>
                <!-- video -->
                <video controls class="video <?= $act ?>" width="640" height="480" src="<?= $src ?>" id="video-file">
                    Your browser isn't support video!
                </video>

                <div class="magnifier"></div>
            </div>
            <?php if (isUserRole([ROLE_ADMIN, ROLE_SUPERADMIN, ROLE_SUPERVISOR])) { ?>
                <div class="buttons-container">
                    <a role="button" class="warning" href="<?= "edit_step?pid={$s['projects_id']}&sid={$s['id']}"; ?>">
                        Edit Step
                    </a>

                    <a role="button" class="info" href="<?= "step_history?pid={$s['projects_id']}&sid={$s['id']}"; ?>">
                        View History
                    </a>

                    <a role="button" class="danger delete-button" data-projectid="<?= $s['projects_id'] ?>" data-stepid="<?= $s['id'] ?>">
                        Delete Step
                    </a>
                </div>
            <?php } ?>
            <p class="description"><?= $s['note']; ?></p>
            <div class="input-fields">
                <label>Part Number</label>
                <input type="text" placeholder="Part Number" value="<?= $s['part_number']; ?>" readonly>

                <label>Route Action</label>
                <input type="text" placeholder="Route Action" value="<?= $s['routeaction'] ?>" readonly>
            </div>
            <div class="input-fields">
                <label>Revision</label>
                <input type="text" placeholder="Revision" value="<?= $s['revision']; ?>" readonly>

                <label>Tool for step</label>
                <input type="text" placeholder="Tool Name" value="<?= $s['tool']; ?>" readonly>
            </div>
        </div>
    <?php } ?>
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
if (isUserRole([ROLE_SUPERADMIN, ROLE_SUPERVISOR, ROLE_ADMIN])) {
// проверяем если в папке есть файлы и она не затычка в БД
    if (isDirEmpty($project_for_edit->docsdir) && $project_for_edit->docsdir != 'storage/projects/') {
        echo '<span class="hidden" id="project_folder_path">wiki?pr_dir=' . $project_for_edit->docsdir . '</span>';
    }
}

// FOOTER AND SCRIPTS
PAGE_FOOTER($page, false); ?>
<script type="text/javascript" src="/public/js/edit-project.js"></script>
</body>
</html>