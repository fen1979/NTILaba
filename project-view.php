<?php
isset($_SESSION['userBean']) or header("Location: /") and exit();
require_once 'projects/Project.php';
$page = 'project';
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

// preview on page switch
if (isset($_POST['preview-mode'])) {
    $_SESSION['preview_mode'] = !$_SESSION['preview_mode'];
}


/* вывод всех проектов в список */
$result = null;
$viewBtnEdit = false;
$user = $_SESSION['userBean'];
$role = $user['app_role'];

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, $page, PROJECTS, 20);

$result = R::findAll(PROJECTS, 'ORDER BY date_in DESC ' . $pagination);
?>

<!doctype html>
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
NavBarContent($page, $user, null, Y['PROJECT']);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid my-4 content">
    <!-- ---------------------------------- вывод привью всех проектов по возрастанию ------------------------------------------- -->
    <!-- ------------------ так же вывод привью проектов при поиске с заменой содержимого данного контейнера -------------------- -->
    <?php
    /* настройки вывода от пользователя */
    // выводим таблицу
    if ($_SESSION['preview_mode']) { ?>
        <div id="searchAnswer">
            <table class="p-3" id="project-table">
                <!-- header -->
                <thead>
                <tr>
                    <?php
                    if ($settings = getUserSettings($user, PROJECTS)) {
                        foreach ($settings as $item) {
                            echo '<th>' . L::TABLES(PROJECTS, $item) . '</th>';
                        }
                        echo '<th>Share Project</th>';
                    } else {
                        ?>
                        <th>
                            Your view settings for this table isn`t exist yet
                            <a role="button" href="/setup" class="btn btn-outline-info">Edit Columns view settings</a>
                        </th>
                    <?php } ?>
                </tr>
                </thead>
                <!-- table -->
                <tbody>
                <?php
                foreach ($result as $value) {
                    $shareLink = SHARE_LINK_ROUTE . $value['sharelink'];
                    $projectId = $value['id'];
                    ?>
                    <tr class="item-list" data-id="<?= $projectId; ?>">
                        <?php
                        if ($settings) {
                            foreach ($settings as $item) {
                                echo '<td>' . $value[$item] . '</td>';
                            }
                        }
                        ?>
                        <td>
                            <button type="button" class=" w-100 btn btn-sm btn-outline-diliny share-project" data-share-link="<?= $shareLink; ?>">
                                <i class="bi bi-share-fill"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    <?php } else { ?>

        <div class="row" id="searchAnswer">
            <?php
            foreach ($result as $value) {
                if ($value['archivation']) {
                    $projectId = $value['id'];
                    // получаем фото/много фоток
                    $imgPath = getProjectFrontPicture($projectId, $user['preview']);
                    $projectName = str_replace('_', ' ', $value['projectname']);
                    $shareLink = SHARE_LINK_ROUTE . $value['sharelink'];
                    $customerName = $value['customername'];
                    $descInfo = $value['extra'];
                    $date_in = $value['date_in'];
                    $revision = $value['revision'];
                    ?>
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <!--  Project Name and Share Link -->
                            <h5 class="card-title position-relative">
                                <b class="text-primary">Name:</b>
                                <?= $projectName; ?>
                                <span class="text-primary share-project position-absolute end-0 me-3" data-share-link="<?= $shareLink; ?>">
								<i class="bi bi-share-fill"></i>
							</span>
                            </h5>

                            <?php
                            //Project Documentation preview or Last step of project if Docs not exist
                            if ($user['preview'] == 'docs') {
                                if (!empty($value['projectdocs']) && strpos($value['projectdocs'], '.pdf') !== false) { ?>
                                    <iframe src="<?= $value['projectdocs']; ?>"></iframe>
                                    <a href="<?= $value['projectdocs']; ?>" target="_blank" class="mt-2 pdf-link">View Project Docs</a>
                                <?php } else { ?>
                                    <img src="<?= $imgPath; ?>" alt="<?= $projectName; ?>" class="img-fluid">
                                <?php }
                            } ?>
                            <!-- photo gallery for project -->
                            <div class="photo-gallery">
                                <?php
                                if ($user['preview'] == 'image' && $imgPath) {
                                    $firstImg = reset($imgPath); // Сброс указателя массива и получение первого элемента
                                    foreach ($imgPath as $img) {
                                        $display = ($img === $firstImg) ? '' : 'hidden';
                                        ?>
                                        <img src="<?= $img->image; ?>" alt="<?= $projectName; ?>" class="img-fluid gallery-photo <?= $display; ?>">
                                        <?php
                                    }
                                }
                                ?>
                            </div>

                            <div class="card-body">
                                <!--  Customer or Company Name -->
                                <p class="card-text">
                                    <b class="text-primary">Customer:</b>
                                    <br/>
                                    <?= $customerName; ?>
                                </p>

                                <!-- Some additional information about project, view if exist -->
                                <?php if (!empty($descInfo)) { ?>
                                    <p class="card-text">
                                        <b class="text-primary">Additional Info:</b>
                                        <br/>
                                        <?= $descInfo; ?>
                                    </p>
                                <?php } ?>

                                <!-- project created or updated Date and Revision -->
                                <p class="card-text text-primary"><?= $date_in; ?> &nbsp;
                                    <small class="text-danger">Rev:&nbsp;<?= $revision; ?></small>
                                </p>

                                <!--  action buttons group  -->
                                <div class="btn-group">
                                    <a type="button" title="Preview" class="btn btn-outline-diliny" href="/edit_project?pid=<?= $projectId; ?>">
                                        <i class="bi bi-eye"></i>
                                        View Project
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
            } ?>
        </div>
        <?php
    }


    /* POPUP CHAT WINDOW */
    ShowGroupChatPopup($page, $user);
    ?>

    <!--  модальное окно форма для удаления или архивации проекта  -->
    <div class="modal" id="deleteModal" style="backdrop-filter: blur(15px);">
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
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <input type="text" class="form-control" id="dfProjectID" name="projectid" hidden>
                        </div>
                        <button type="submit" id="delete-btn" name="delete" class="btn btn-outline-danger hidden">Delete Project</button>
                        <button type="submit" id="archive-btn" name="archive" class="btn btn-outline-warning hidden">Archive Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- pagination buttons -->
    <?= $paginationButtons ?>

    <!-- Футер -->
    <?php footer($page); ?>
</div>
<!-- END Container  -->
<form action="" method="post" id="project_preview_switch">
    <input type="hidden" name="preview-mode" value="1">
</form>
<button type="button" class="url hidden" value="" id="routing-btn"></button>
<?php
/* SCRIPTS */
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // решить если нужна функция галерея для проекта TODO
        dom.in("click", ".gallery-photo", function (event) {
            if (event.target.classList.contains('gallery-photo')) {
                let current = event.target;
                let gallery = current.closest('.photo-gallery');
                let photos = gallery.querySelectorAll('.gallery-photo');
                let currentIndex = Array.from(photos).indexOf(current);
                // Вычисляем индекс следующей фотографии
                let nextIndex = (currentIndex + 1) % photos.length;
                let next = photos[nextIndex];
                // Скрываем текущую фотографию и показываем следующую
                current.classList.add('hidden');
                next.classList.remove('hidden');
            }
        });

        // переключатель вида вывода проектов (таблица/карточка)
        dom.doSubmit(".project_preview_mode", "#project_preview_switch");

        // Выбираем таблицу с id searchAnswer
        const table = document.getElementById('project-table');

        // Добавляем делегированный обработчик событий на таблицу
        table.addEventListener('click', function (event) {
            console.log(event.target.tagName.toLowerCase());
            // Проверяем, был ли клик по ссылке
            if (event.target.tagName.toLowerCase() === 'button' || event.target.tagName.toLowerCase() === 'i') {
                return; // Прекращаем выполнение функции, если клик был по ссылке
            }

            // Находим родительский <tr> элемент
            let row = event.target;
            while (row && row.tagName.toLowerCase() !== 'tr') {
                row = row.parentElement;
            }

            // Если <tr> элемент найден и у него есть data-id
            if (row && row.dataset.id) {
                // Получаем значение data-id
                const dataId = row.dataset.id;
                let btn = dom.e("#routing-btn");
                btn.value = "edit_project?pid=" + dataId
                btn.click();
            }
        });
    });
</script>
<script type="text/javascript" src="public/js/project-view.js"></script>
</body>
</html>