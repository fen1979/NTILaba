<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
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
                <div class="dropdown">
                    <input type="hidden" id="toolChose" name="toolName">
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
                                ?>
                                <li class="dropdown-item" data-toolid="<?= $tool['id']; ?>" data-image="<?= $tool['image']; ?>" data-text="<?= $text ?>">
                                    <div class="card mb-3" style="max-width: 540px;">
                                        <div class="row g-0">
                                            <div class="col-md-6">
                                                <img src="<?= !empty($tool['image']) ? $tool['image'] : 'public/images/pna_en.webp'; ?>" class="img-fluid rounded-end"
                                                     alt="<?= $tool['serial_num'] ?>">
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
            <div class="col-4">
                <img src="/public/images/pna_en.webp" alt="x tools" width="400px" style="display: block;" id="toolImage" class="hidden">
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

        // кнопки выбора фото/ видео от пользователя
        dom.doClick("#takePic", "#photo");
        dom.doClick("#takeMovie", "#video");
        // Обработчик изменений файлов для обновления превью
        dom.doPreviewFile("photo", "photoPreview", checkFormAndToggleSubmit);
        dom.doPreviewFile("video", "videoPreview");

        // отправка формы после изменений через кнопку в нав панели
        dom.doSubmit("#finishBtn", "#addDataForm");

        /* добавление значения из списка в инпут для отправки на сервер ROUT ACTION */
        dom.in("click", "#routeActionUl li", function (e) {
            e.preventDefault();
            dom.e('#routeAction').value = this.textContent.trim();
            dom.e('#routeid').value = this.dataset.routeid;
            checkFormAndToggleSubmit();
        });

        /* добавление значения из списка в инпут для отправки на сервер TOOL CHOOSE */
        dom.in("click", "#toolChoseUl li", function (e) {
            e.preventDefault();

            // Находим ближайший элемент li, даже если клик был по его потомку
            const liElement = e.target.closest('li');

            if (liElement) {
                dom.e("#dropDownButton").append(liElement.dataset.text);
                dom.e("#toolId").value = liElement.dataset.toolid;
                dom.e("#toolImage").src = liElement.dataset.image;
                dom.show("#toolImage");

                checkFormAndToggleSubmit();
            }
        });

        // Проверка поля ввода текста в реальном времени
        dom.in("input", "#actionDescription", checkFormAndToggleSubmit);

        // check form and toggle disability of submit button
        function checkFormAndToggleSubmit() {
            let allFilled = true;

            if (dom.e('#toolId').value === '' ||
                dom.e('#routeid').value.trim() === '' ||
                dom.e('#actionDescription').value.trim() === '' ||
                (dom.e('#photo').dataset.required === 0 && dom.e('#photo')[0].files.length === 0)
            ) {
                allFilled = false;
            }

            if (allFilled) {
                dom.removeClass("#finishBtn", "disabled");
            } else {
                dom.addClass("#finishBtn", "disabled");
            }
        }

        // Инициализируем проверку при загрузке страницы
        checkFormAndToggleSubmit();
    });
</script>
</body>
</html>
