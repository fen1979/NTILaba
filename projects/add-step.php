<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();
require_once 'projects/Project.php';
$page = 'add_step';
$user = $_SESSION['userBean'];
$project = $projectName = $projectDir = $args = null;
$nextStepNumber = 1;

/* сохранение данных в БД и переход к новому шагу */
if (isset($_POST['next-button'])) {
    $project_id = _E($_GET['pid']);
    $args = Project::addNewStepToProject($_POST, $user, $_FILES, $project_id);
}

/* выбираем данные о проекте из БД для показа  */
if (isset($_GET["pid"])) {
    $project = R::load(PROJECTS, $_GET["pid"]);
    $projectName = $project['projectname'];
    $projectDir = $project['projectdir'];
}
$nextStepNumber = R::count(PROJECT_STEPS, 'projects_id = ?', [$project->id]) + 1;
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
/* NAVIGATION BAR */
$title = ['title' => '<b class="text-primary"> Project: ' . $projectName . '</b>', 'app_role' => $user['app_role'], 'link' => $user['link']];
NavBarContent($page, $title, $_GET['pid']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args);
?>
<div class="container mt-5">
    <div class="mb-3">
        <h3><?= "Name: $projectName"; ?></h3>
    </div>

    <form id="addDataForm" action="" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Step N: &nbsp;&nbsp;&nbsp;
                <input type="number" class="text-danger step-number" name="actionNumber" value="<?= $nextStepNumber; ?>" readonly>
            </label>
        </div>

        <div class="checkbox mb-3">
            <div class="form-check form-switch fs-3">
                <input class="form-check-input" type="checkbox" id="front-picture" value="1" name="front-picture">
                <label class="form-check-label" for="front-picture" style="font-size: large">Set Step As Main Photo</label>
            </div>
        </div>

        <div class="checkbox mb-3">
            <div class="form-check form-switch fs-3">
                <input class="validation form-check-input" type="checkbox" id="validation" name="validation">
                <label class="form-check-label" for="validation" style="font-size: large">If a step needs validation!</label>
            </div>
        </div>

        <div class="mb-3">
            <label for="actionDescription" class="form-label">Description</label>
            <textarea class="form-control" id="actionDescription" name="actionDescription" required></textarea>
        </div>

        <div class="mb-3">
            <i class="bi bi-info-circle" data-title="This Needed route action choose one for create route list"></i>
            <label for="routeAction" class="form-label">
                Choise Route Action
            </label>
            <div class="dropdown">
                <?php
                $routeActions = R::find(ROUTE_ACTION);
                $list = '';
                foreach ($routeActions as $action) {
                    $list .= '<li class="dropdown-item" data-routeid="' . $action['id'] . '">' . $action['actions'] . '</li>';
                }
                ?>
                <input type="text" name="routeAction" value="" id="routeAction" class="form-control dropdown-toggle"
                       data-bs-toggle="dropdown" aria-expanded="false" placeholder="Choise Route Action" autocomplete="off">

                <ul class="dropdown-menu ajeco-bg-aqua" aria-labelledby="routeAction" id="routeActionUl">
                    <li class="dropdown-item" data-routeid="0">Not Alloved Actiond For Route Card</li>
                    <?= $list; ?>
                </ul>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-8">
                <label for="toolChose" class="form-label">
                    Choise Tool for this step
                </label>
                <div class="dropdown">
                    <input type="text" class="form-control dropdown-toggle" id="toolChose" name="toolName"
                           data-bs-toggle="dropdown" aria-expanded="false" readonly>
                    <ul class="dropdown-menu ajeco-bg-aqua" aria-labelledby="toolChose" id="toolChoseUl">
                        <?php
                        $tools = R::find(TOOLS);
                        $pTolls = explode(',', $project['tools']);
                        foreach ($tools as $tool) {
                            if (in_array($tool['id'], $pTolls)) {
                                ?>
                                <li class="dropdown-item" data-toolid="<?= $tool['id']; ?>" data-image="<?= $tool['image']; ?>">
                                    <?= $tool['toolname']; ?>
                                    <img src="<?= $tool['image']; ?>" alt="x tools" width="300px" style="display: block;">
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <span class="form-label">
                    Tool preview
                </span>
                <img src="/public/images/tools.webp" alt="x tools" width="400px" style="display: block;" id="toolImage">
            </div>
        </div>

        <input type="hidden" id="toolId" name="tool">
        <input type="hidden" id="routeid" name="routeid">
        <input type="file" id="photo" name="photoFile" accept="image/*" hidden data-required="<?= $project->sub_assembly; ?>">
        <input type="file" id="video" name="videoFile" accept="video/*" hidden>
        <input type="hidden" name="next-button" value="1">

        <!-- привью файлов пользователя -->
        <div class="mb-3">
            <!--
            TODO для работы дизайнера заглушка с изображением 500х500рх
            <img id="photoPreview" class="img-thumbnail" alt="Превью фотографии" src="/public/images/ips.webp" >
            -->
            <img id="photoPreview" class="img-thumbnail" alt="Превью фотографии" src="" style="display: none;">
            <video controls id="videoPreview" width="640" height="480" style="display: none;">
                Your browser isn't support video!
            </video>
        </div>
    </form>
</div>

<?php ScriptContent($page); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        

        // кнопки выбора фото/ видео от пользователя
        document.doClick("#takePic", "#photo");
        document.doClick("#takeMovie", "#video");
        // Обработчик изменений файлов для обновления превью
        document.doPreviewFile("photo", "photoPreview", checkFormAndToggleSubmit);
        document.doPreviewFile("video", "videoPreview");

        // отправка формы после изменений через кнопку в нав панели
        document.doSubmit("#finishBtn", "#addDataForm");

        // поиск по списку элементов рут карты
        const routeActionInput = document.getElementById('routeAction');
        const dropdownItems = document.querySelectorAll('#routeActionUl li.dropdown-item');

        routeActionInput.addEventListener('input', function () {
            const searchValue = routeActionInput.value.toLowerCase();
            dropdownItems.forEach(function (item) {
                const itemText = item.textContent.toLowerCase();
                if (itemText.includes(searchValue)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        /* добавление значения из списка в инпут для отправки на сервер ROUT ACTION */
        $('#routeActionUl li').click(function (e) {
            e.preventDefault();
            $('#routeAction').val($(this).text().trim());
            $('#routeid').val($(this).attr('data-routeid'));
            checkFormAndToggleSubmit();
        });

        /* добавление значения из списка в инпут для отправки на сервер TOOL CHOOSE */
        $('#toolChoseUl li').click(function (e) {
            e.preventDefault();
            $('#toolChose').val($(this).text().trim());
            $('#toolId').val($(this).attr('data-toolid'));
            $('#toolImage').attr('src', $(this).attr('data-image'));
            checkFormAndToggleSubmit();
        });

        function checkFormAndToggleSubmit() {
            let allFilled = true;

            if ($('#toolId').val().trim() === '' ||
                $('#routeid').val().trim() === '' ||
                $('#actionDescription').val().trim() === '' ||
                ($('#photo').data("required") === 0 && $('#photo')[0].files.length === 0)
            ) {
                allFilled = false;
            }

            if (allFilled) {
                $("#finishBtn").removeClass("disabled");
            } else {
                $("#finishBtn").addClass("disabled");
            }
        }

        // Проверка поля ввода текста в реальном времени
        $('#actionDescription').on('input', checkFormAndToggleSubmit);

        // Инициализируем проверку при загрузке страницы
        checkFormAndToggleSubmit();
    });
</script>
</body>
</html>
