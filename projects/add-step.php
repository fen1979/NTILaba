<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'add_step';
$project = $projectName = $projectDir = $args = null;
$nextStepNumber = 1;

/* сохранение данных в БД и переход к новому шагу */
if (isset($_POST['next-button'])) {
    $project_id = _E($_GET['pid']);
    Project::addNewStepToProject($_POST, $user, $_FILES, $project_id);
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
NavBarContent(['title' => 'Add New', 'record_id' => $_GET['pid'] ?? null, 'user' => $user, 'page_name' => $page]); ?>

<div class="container mt-5">
    <div class="row">
        <div class="col">
            <h3 class="title"><?= "Name: $projectName"; ?></h3>
        </div>
    </div>
    <div class="mb-3">
        <form id="addDataForm" action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Step N: &nbsp;&nbsp;&nbsp;
                    <input type="number" class="text-danger step-number" name="actionNumber" value="<?= $nextStepNumber; ?>" readonly>
                </label>
            </div>

            <div class="row mb-3">
                <div class="checkbox col">
                    <div class="form-check form-switch fs-3">
                        <input class="validation form-check-input" type="checkbox" id="validation" name="validation">
                        <label class="form-check-label" for="validation" style="font-size: large">If a step needs validation!</label>
                    </div>
                </div>

                <div class="checkbox col">
                    <div class="form-check form-switch fs-3">
                        <input class="form-check-input" type="checkbox" id="front-picture" value="1" name="front-picture">
                        <label class="form-check-label" for="front-picture" style="font-size: large">Set Step As Main Photo</label>
                    </div>
                </div>

                <div class="col">
                    <div class="dropdown">
                        <button class="btn btn-outline-dark form-control dropdown-toggle" type="button" id="dropDownButton"
                                data-bs-toggle="dropdown" aria-expanded="false">
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
                <input type="text" name="part-number" id="part-number" class="form-control" placeholder="Part number if exist [optional]">
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">Notice</label>
                <input type="text" name="note" id="note" class="form-control" placeholder="Some notes for step [optional]">
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

            <div class="mb-3">
                <?php $t = 'IMPORTANT! A full detailed description of the actions supported by a photo or video next to this step is required, 
                    be sure to indicate all possible actions required to complete this step correctly!'; ?>
                <i class="bi bi-info-circle text-primary" data-title="<?= $t ?>"></i>
                <label for="actionDescription" class="form-label">Description</label>
                <textarea class="form-control" id="actionDescription" name="actionDescription" required></textarea>
            </div>

            <input type="hidden" id="toolId" name="tool">
            <input type="hidden" id="routeid" name="routeid">
            <input type="file" id="photo" name="photoFile" accept="image/*" hidden data-required="<?= $project->sub_assembly; ?>">
            <input type="file" id="video" name="videoFile" accept="video/*" hidden>
            <input type="hidden" name="choosed-step-image-path" id="step-image-path">
            <input type="hidden" name="choosed-step-video-path" id="step-video-path">
            <input type="hidden" name="next-button" value="1">
        </form>
    </div>
    <!-- привью файлов пользователя -->
    <div class="preview-box border-top p-2">
        <div class="resource-box">
            <p>Step Image</p>
            <img id="photoPreview" alt="Превью фотографии" src="/public/images/ips.webp">
        </div>

        <div class="resource-box">
            <p>Step Video</p>
            <video controls id="videoPreview" src="" poster="/public/images/vps.png">
                Your browser isn't support video!!!
            </video>
        </div>

        <div class="resource-box">
            <p id="tool-name-label">Tool no choosen yet</p>
            <img src="/public/images/ips.webp" alt="x tools" id="toolImage">
        </div>
    </div>
</div>

<?php

SearchResponceModalDialog($page, 'search-responce');

PAGE_FOOTER($page, false); ?>
<!--suppress JSIncompatibleTypesComparison -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // игнорируемые обьекты при клике на странице с открытым модальным окном!
        dom.bodyClick(["#grabPic","#grabVideo", ".modal-content"]);

        // поиск по списку элементов рут карты
        const routeActionInput = document.getElementById('routeAction');
        const dropdownItems = document.querySelectorAll('#routeActionUl li.dropdown-item');

        routeActionInput.addEventListener('input', function () {
            const searchValue = routeActionInput.value.toLowerCase();
            dropdownItems.forEach(function (item) {
                const itemText = item.textContent.toLowerCase();
                if (itemText.includes(searchValue)) {
                    item.style.display = "";
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // выбор файлов загруженных ранее со сменой имени в БД
        // выборка фоток из БД которые существуют
        const args = {method: "POST", url: "get_data", headers: null};
        dom.makeRequest("#grabPic", "click", "data-request", args, function (error, result, _) {
            if (error) {
                console.error('Error during fetch:', error);
                return;
            }

            // вывод информации в модальное окно
            let modalTable = dom.e("#searchModal");
            if (modalTable) {
                dom.e("#search-responce").innerHTML = result;
                dom.show("#searchModal", "fast", true);
            }
        });

        // установка результата выбора фото из БД
        dom.in("click", "#search-responce td.image-path", function () {
            console.log(this.dataset)
            if (this.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = this.dataset.info;
                let img = dom.e("#photoPreview");
                img.src = info;
                dom.e("#step-image-path").value = info;
            }
            // Очищаем результаты поиска
            dom.hide("#searchModal");
        });

        // кнопки выбора фото/ видео от пользователя
        dom.doClick("#takePic", "#photo");
        dom.doClick("#takeMovie", "#video");
        // Обработчик изменений файлов для обновления превью
        dom.doPreviewFile("#photo", "#photoPreview", checkFormAndToggleSubmit);
        dom.doPreviewFile("#video", "#videoPreview");

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
                dom.e("#tool-name-label").textContent = "Choosen tool: " + liElement.dataset.text;
                dom.e("#toolId").value = liElement.dataset.toolid;
                dom.e("#toolImage").src = liElement.dataset.image;
                // dom.show("#toolImage");

                checkFormAndToggleSubmit();
            }
        });

        // Проверка поля ввода текста в реальном времени
        dom.in("input", "#actionDescription", checkFormAndToggleSubmit);

        // check form and toggle disability of submit button
        function checkFormAndToggleSubmit() {
            let allFilled = true;

            if (/*dom.e('#toolId').value === '' ||*/
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
