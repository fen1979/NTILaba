<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'projects/Project.php';
/* страница редактирования одного шага в проекте */
$page = 'edit_step';
$user = $_SESSION['userBean'];
$max = $ns = 0;
$step = $projectid = '';
$args = array();

if (isset($_POST['save-changes'])) {
    $args = Project::editProjectStep($_POST, $_SESSION['userBean'], $_FILES, _E($_POST['step_id']));
}

/* finding stepsData for step editing */
if (isset($_GET['pid']) && isset($_GET['sid'])) {
    $project = R::load(PROJECTS, _E($_GET['pid']));
    $step = R::load(PROJECT_STEPS, _E($_GET['sid']));
    $max = R::count(PROJECT_STEPS, 'projects_id = ?', [_E($_GET['pid'])]);
    $ns = $step['step'];
    $revision = $step['revision'];
    $validation = $step['validation'];
    $front_pic = $step['front_pic'];
    $stepDescription = $step['description'];
    $routeID = $step['routeid'];
    $toolID = $step['tool'];
    $stepImage = $step['image'];
    $stepVideo = (strpos($step['video'], '.mp4') !== false) ? $step['video'] : 'none';
    $projectName = $project->projectname;
}
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
$navBarData['title'] = '<b class="text-primary"> Project: ' . $projectName . '</b>';
$navBarData['record_id'] = $_GET['pid'] ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
?>

<div class="container mt-5">
    <div class="mb-3">
        <h3 class="text-danger">Step Editing Mode!</h3>
    </div>

    <form id="addDataForm" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="projectid" value="<?= $step['projects_id']; ?>" readonly>
        <input type="hidden" name="step_id" value="<?= $step['id']; ?>" readonly>

        <div class="checkbox mb-3">
            <div class="form-check form-switch fs-3">
                <input class="form-check-input track-change" data-field-id="9" type="checkbox"
                       id="front-picture" name="front-picture" value="1" <?= (!empty($front_pic)) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="front-picture" style="font-size: large">Set Step As Main Photo</label>
            </div>
        </div>

        <div class="checkbox mb-3">
            <div class="form-check form-switch fs-3">
                <input class="validation form-check-input track-change" data-field-id="1" type="checkbox"
                       id="validation" name="validation" value="1" <?= (!empty($validation)) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="validation" style="font-size: large">If a step needs validation!</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">
                <input type="number" name="newStepNumber" value="<?= $ns; ?>" max="<?= $max; ?>" min="1"
                       style="width: 3.5rem; border-radius: 5px;" class="me-2 track-change" data-field-id="2" id="step-number">
                Step Number
                <input type="hidden" name="oldStepNumber" value="<?= $ns; ?>" readonly>
            </label>
        </div>

        <div class="mb-3">
            <label class="form-label" for="revision">
                <input type="text" name="revision" value="<?= $revision; ?>" id="revision" style="width: 3.5rem; border-radius: 5px;"
                       class="me-2 track-change" data-field-id="3">
                Revision
            </label>
        </div>

        <div class="mb-3">
            <label for="stepDescription" class="form-label" data-title="Write some description about this step.">Description</label>
            <?php
            echo '<textarea class="form-control track-change" data-field-id="4" id="stepDescription" name="stepDescription" required>';
            if (!empty($stepDescription)) {
                echo $stepDescription;
            }
            echo '</textarea>';
            ?>
        </div>

        <div class="mb-3">
            <i class="bi bi-info-circle" data-title="This Needed route action choose one for create route list"></i>
            <label for="routeAction" class="form-label">
                Choise Route Action
            </label>
            <div class="dropdown">
                <?php
                $routeActions = R::find(ROUTE_ACTION);
                $routeAction = $list = '';
                foreach ($routeActions as $action) {
                    if ($routeID == $action->id) {
                        $routeAction = $action->actions;
                    }
                    $list .= '<li class="dropdown-item" data-routeid="' . $action['id'] . '">' . $action['actions'] . '</li>';
                }
                ?>
                <input type="hidden" name="routeid" value="<?= $routeID ?? ''; ?>" id="routeid">

                <input type="text" name="routeAction" value="<?= $routeAction; ?>" id="routeAction"
                       class="form-control dropdown-toggle track-change" data-field-id="5"
                       data-bs-toggle="dropdown" aria-expanded="false" autocomplete="off">

                <ul class="dropdown-menu ajeco-bg-aqua" aria-labelledby="routeAction" id="routeActionUl">
                    <li class="dropdown-item" data-routeid="0">Not Alloved Actiond For Route Card</li>
                    <?= $list; ?>
                </ul>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-8">
                <label for="routAction" class="form-label">
                    Choise Tool for this step
                </label>
                <div class="dropdown">
                    <?php
                    $tools = R::find(TOOLS);
                    $pTolls = explode(',', $project['tools']);
                    $toolsList = '';
                    foreach ($tools as $tool) {
                        if (in_array($tool['id'], $pTolls)) {
                            if ($tool['id'] == $toolID) {
                                $tool_choosen = $tool['toolname'];
                                $tool_image = $tool['image'];
                            }
                            $toolsList .= '<li class="dropdown-item" data-toolid="' . $tool['id'] . '" data-image="' . $tool['image'] . '">'
                                . $tool['toolname'] . '
                                <img src="' . $tool['image'] . '" alt="x tools" width="300px" style="display: block;">
                            </li>';
                        }
                    }
                    ?>
                    <input type="text" class="form-control dropdown-toggle track-change" data-field-id="6"
                           id="toolChose" name="toolName" data-bs-toggle="dropdown" aria-expanded="false"
                           readonly value="<?= $tool_choosen ?? 'No needed any tools'; ?>">
                    <input type="hidden" name="tool" id="toolId" value="0" readonly>
                    <ul class="dropdown-menu ajeco-bg-aqua" aria-labelledby="toolChose" id="toolChoseUl">
                        <?= $toolsList; ?>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label">
                    Tool preview
                </label>
                <img src="<?= $tool_image ?? 'public/images/tools.webp'; ?>" alt="x tools" width="400px" style="display: block;" id="toolImage">
            </div>
        </div>

        <!-- привью файлов пользователя -->
        <div class="mb-3">
            <!-- image -->
            <img id="photoPreview" class="img-thumbnail" alt="Превью фотографии" src="<?= $stepImage; ?>">
            <input type="file" id="photo" name="imageFile" accept="image/*" hidden class="track-change" data-field-id="7">
            <!-- video -->
            <?php $video = ($stepVideo == 'none') ? 'style="display:none"' : 'src="' . $stepVideo . '"'; ?>
            <video controls id="videoPreview" width="640" height="480" <?= $video; ?> >
                Your browser isn' t support video!
            </video>
            <input type="file" id="video" name="videoFile" accept="video/*" hidden class="track-change" data-field-id="8">
        </div>

        <!-- Скрытый инпут для отслеживания изменений -->
        <input type="hidden" id="changedFields" name="changedFields" value="none">
        <input type="hidden" name="save-changes" value="update">
    </form>
</div>

<!-- JAVASCRIPTS -->
<?php ScriptContent($page); ?>
<script src="/public/js/edit-step.js"></script>
</body>
</html>
