<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');
require_once 'projects/ProductionUnit.php';

$page = 'project';
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
list($pagination, $paginationButtons) = PaginationForPages($_GET, $page, PRODUCT_UNIT, 20);

$result = R::findAll(PRODUCT_UNIT, 'ORDER BY id DESC ' . $pagination);
// get user settings for this page
$settings = getUserSettings($user, PRODUCT_UNIT);
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
// NAVIGATION BAR
//$navBarData['title'] = '';
$navBarData['active_btn'] = Y['PROJECT'];
//$navBarData['page_tab'] = $_GET['page'] ?? null;
//$navBarData['record_id'] = $item->id ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

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
                <tr style="white-space: nowrap">
                    <?= CreateTableHeaderUsingUserSettings($settings, 'project-table', PRODUCT_UNIT, '<th>Share ProductionUnit</th>') ?>
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
                            foreach ($settings as $item => $_) {
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
                            <!--  ProductionUnit Name and Share Link -->
                            <h5 class="card-title position-relative">
                                <b class="text-primary">Name:</b>
                                <?= $projectName; ?>
                                <span class="text-primary share-project position-absolute end-0 me-3" data-share-link="<?= $shareLink; ?>">
								<i class="bi bi-share-fill"></i>
							</span>
                            </h5>

                            <?php
                            //ProductionUnit Documentation preview or Last step of project if Docs not exist
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

    /* PAGINATION BUTTONS */
    echo $paginationButtons;

    /* FOOTER FOR PAGE */
    footer($page);
    ?>
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
<script type="text/javascript" src="public/js/units-view.js"></script>
</body>
</html>