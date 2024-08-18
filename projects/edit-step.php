<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'projects/ProductionUnit.php';
/* страница редактирования одного шага в проекте */
$page = 'edit_step';
$user = $_SESSION['userBean'];
$max = $ns = 0;
$step = $projectid = '';
$args = array();

if (isset($_POST['save-changes'])) {
    $args = ProductionUnit::editProjectStep($_POST, $_SESSION['userBean'], $_FILES, _E($_POST['step_id']));
}

/* finding stepsData for step editing */
if (isset($_GET['pid']) && isset($_GET['sid'])) {
    $project = R::load(PRODUCT_UNIT, _E($_GET['pid']));
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
    <style>
        /* Основные стили */
        .preview-box {
            display: flex;
            justify-content: space-around;
            align-items: center;
            width: 100%;
        }

        .preview-box > div {
            text-align: center;
            width: 30%;
        }

        .preview-box img, .preview-box video {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }

        .preview-box p {
            margin-bottom: 10px;
        }

        .resource-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .resource-box img, .resource-box video {
            width: 100%;
            height: 200px; /* фиксированная высота для всех ресурсов */
            object-fit: cover; /* это свойство помогает поддерживать соотношение сторон */
        }

        /* Стили для мобильного отображения */
        @media (max-width: 768px) {
            .navbar-nav {
                display: flex;
                padding-left: 0;
                margin-bottom: 0;
                list-style: none;
                flex-direction: row;
                justify-content: flex-end;
            }

            .preview-box {
                flex-direction: column;
            }

            .preview-box > div {
                width: 100%;
                margin-bottom: 20px; /* отступ между элементами */
            }

            .resource-box img, .resource-box video {
                height: 150px; /* уменьшенная высота для мобильных устройств */
            }

            .title {
                font-size: 17px;
                color: #0d6efd;
                padding-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
$navBarData['title'] = 'Edit step';
$navBarData['record_id'] = $_GET['pid'] ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
?>

<div class="container mt-5">
    <div class="row">
        <div class="col">
            <h3 class="title"><?= "Name: $projectName"; ?></h3>
        </div>
    </div>
    <div class="mb-3">
        <form id="addDataForm" action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="projectid" value="<?= $step['projects_id']; ?>" readonly>
            <input type="hidden" name="step_id" value="<?= $step['id']; ?>" readonly>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">
                        <input type="number" name="newStepNumber" value="<?= $ns; ?>" max="<?= $max; ?>" min="1"
                               style="width: 3.5rem; border-radius: 5px;" class="me-2 track-change" data-field-id="2" id="step-number">
                        Step Number
                        <input type="hidden" name="oldStepNumber" value="<?= $ns; ?>" readonly>
                    </label>
                </div>

                <div class="col">
                    <label class="form-label" for="revision">
                        <input type="text" name="revision" value="<?= $revision; ?>" id="revision" style="width: 3.5rem; border-radius: 5px;"
                               class="me-2 track-change" data-field-id="3">
                        Revision
                    </label>
                </div>
            </div>

            <div class="row mb-3">
                <div class="checkbox col">
                    <div class="form-check form-switch fs-3">
                        <input class="validation form-check-input track-change" data-field-id="1" type="checkbox"
                               id="validation" name="validation" value="1" <?= (!empty($validation)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="validation" style="font-size: large">If a step needs validation!</label>
                    </div>
                </div>

                <div class="checkbox col">
                    <div class="form-check form-switch fs-3">
                        <input class="form-check-input track-change" data-field-id="9" type="checkbox"
                               id="front-picture" name="front-picture" value="1" <?= (!empty($front_pic)) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="front-picture" style="font-size: large">Set Step As Main Photo</label>
                    </div>
                </div>

                <div class="col">
                    <div class="dropdown">
                        <!--                    <input type="hidden" id="toolChose" name="toolName" class="track-change" data-field-id="6">-->
                        <button class="btn btn-outline-dark form-control dropdown-toggle" type="button" id="dropDownButton" data-bs-toggle="dropdown" aria-expanded="false">
                            Choose Tools for Step
                        </button>
                        <ul class="dropdown-menu ajeco-bg-aqua" aria-labelledby="toolChose" id="toolChoseUl">
                            <?php
                            $tools = R::find(TOOLS);
                            $pTolls = explode(',', $project['tools']);
                            foreach ($tools as $tool) {
                                if (in_array($tool['id'], $pTolls)) {
                                    $text = $tool['manufacturer_name'] . ' ' . $tool['device_model'];
                                    if ($tool['id'] == $toolID) {
                                        list($tName, $tImage) = [$tool['manufacturer_name'], $tool['image']];
                                    }
                                    ?>
                                    <li class="dropdown-item" data-toolid="<?= $tool['id']; ?>" data-image="<?= $tool['image']; ?>" data-text="<?= $text ?>">
                                        <div class="card mb-3" style="max-width: 540px;">
                                            <div class="row g-0">
                                                <div class="col-md-6">
                                                    <img src="<?= !empty($tool['image']) ? $tool['image'] : 'public/images/pna_en.webp'; ?>"
                                                         class="img-fluid rounded-end" alt="<?= $tool['serial_num'] ?>">
                                                </div>
                                                <div class="col-md-6 border-start">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= $tool['manufacturer_name'] ?></h5>
                                                        <p class="card-text"><?= $tool['device_model'] ?></p>
                                                        <p class="card-text"><?= $tool['device_type'] ?></p>
                                                        <p class="card-text"><small class="text-muted"><?= $tool['next_inspection_date'] ?></small></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="part-number" class="form-label">Part NUmber</label>
                <input type="text" name="part-number" id="part-number" class="form-control"
                       data-field-id="10" placeholder="Part number if exist [optional]">
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">Notice</label>
                <input type="text" name="note" id="note" class="form-control"
                       data-field-id="11" placeholder="Some notes for step [optional]">
            </div>

            <div class="mb-3">
                <?php $t = 'This field must be selected in case of default operations set, 
                    if there are no operations set for this step, then it should be set as Not Alloved Actiond For Route Card!
                    This field contains a search by the typed value!'; ?>
                <i class="bi bi-info-circle text-primary" data-title="<?= $t ?>"></i>
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

            <div class="mb-3">
                <?php $t = 'IMPORTANT! A full detailed description of the actions supported by a photo or video next to this step is required, 
                    be sure to indicate all possible actions required to complete this step correctly!'; ?>
                <i class="bi bi-info-circle text-primary" data-title="<?= $t ?>"></i>
                <label for="stepDescription" class="form-label">Description</label>
                <?php
                echo '<textarea class="form-control track-change" data-field-id="4" id="stepDescription" name="stepDescription" required>';
                if (!empty($stepDescription)) {
                    echo $stepDescription;
                }
                echo '</textarea>';
                ?>
            </div>

            <!-- Скрытый инпут для отслеживания изменений -->
            <input type="hidden" name="tool" id="toolId" class="track-change" data-field-id="6">
            <input type="file" id="photo" name="imageFile" accept="image/*" hidden class="track-change" data-field-id="7">
            <input type="file" id="video" name="videoFile" accept="video/*" hidden class="track-change" data-field-id="8">
            <input type="hidden" id="changedFields" name="changedFields" value="none">
            <input type="hidden" name="save-changes" value="update">
        </form>
    </div>

    <!-- привью файлов пользователя -->
    <div class="preview-box border-top p-2">
        <div class="resource-box">
            <p>Step Image</p>
            <img id="photoPreview" alt="Превью фотографии" src="<?= $stepImage ?? '/public/images/ips.webp'; ?>">
        </div>

        <div class="resource-box">
            <p>Step Video</p>
            <video controls id="videoPreview" src="<?= ($stepVideo == 'none') ? '' : $stepVideo; ?>" poster="/public/images/vps.png">
                Your browser isn't support video!!!
            </video>
        </div>

        <div class="resource-box">
            <p id="tool-name-label">Tool no choosen yet</p>
            <img src="<?= !empty($tImage) ? $tImage : '/public/images/ips.webp'; ?>" alt="x tools" id="toolImage">
        </div>
    </div>
</div>

<!-- JAVASCRIPTS -->
<?php ScriptContent($page); ?>
<script src="/public/js/edit-step.js"></script>
</body>
</html>
