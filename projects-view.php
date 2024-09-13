<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'project';
$result = null;
$viewBtnEdit = false;
$role = $user['app_role'];

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

// preview on page switch
if (isset($_POST['preview-mode'])) {
    $_SESSION['preview_mode'] = !$_SESSION['preview_mode'];
}

// Параметры пагинации
list($pagination, $paginationButtons) = PaginationForPages($_GET, $page, PROJECTS, 20);

$result = R::findAll(PROJECTS, 'ORDER BY id DESC ' . $pagination);
// get user settings for this page
$settings = getUserSettings($user, PROJECTS);
?>

<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page); ?>
    <style>
        .pdf-modal {
            display: none;
            position: fixed;
            width: 600px;
            height: 700px;
            background-color: white;
            border: 1px solid #ccc;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .pdf-modal iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .pdf-modal.visible {
            display: block;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
navBarContent(['active_btn' => Y['PROJECT'], 'user' => $user, 'page_name' => $page]); ?>

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
                    <?= CreateTableHeaderUsingUserSettings($settings, 'project-table', PROJECTS,
                        '<th>Project Docs</th>'
                        . '<th>Share Project</th>') ?>
                </tr>
                </thead>
                <!-- table -->
                <tbody>
                <?php
                foreach ($result as $value) {
                    $shareLink = SHARE_LINK_ROUTE . $value['sharelink'];
                    $projectId = $value['id']; ?>

                    <tr class="item-list " data-id="<?= $projectId; ?>">
                        <?php
                        if ($settings) {
                            foreach ($settings as $item => $_) {
                                echo '<td>' . $value[$item] . '</td>';
                            }
                        }
                        ?>
                        <td>
                            <?php
                            $d = _if((!str_contains($value['projectdocs'], '.pdf')), 'disabled', '');
                            $ds = _if((str_contains($value['projectdocs'], '.pdf')), $value['projectdocs'], '');
                            ?>
                            <a type="button" class="w-100 btn btn-sm btn-outline-info <?= $d; ?> pdf-element" target="_blank"
                               href="<?= $value['projectdocs'] ?>" data-pdf-path="<?= $ds; ?>">
                                <i class="bi bi-filetype-pdf"></i>
                            </a>
                        </td>
                        <td>
                            <button type="button" class="w-100 btn btn-sm btn-outline-diliny share-project" data-share-link="<?= $shareLink; ?>">
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
                                if (!empty($value['projectdocs']) && str_contains($value['projectdocs'], '.pdf')) { ?>
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
    echo $paginationButtons; ?>
</div>
<!-- END Container  -->
<form action="" method="post" id="project_preview_switch">
    <input type="hidden" name="preview-mode" value="1">
    <input type="hidden" id="uid" name="uid" value="<?= $user['id']; ?>">

</form>
<button type="button" class="url hidden" value="" id="routing-btn"></button>

<div id="pdfModal" class="pdf-modal">
    <iframe src="" id="pdfFrame"></iframe>
</div>
<?php
/* FOOTER FOR PAGE SCRIPTS */
PAGE_FOOTER($page); ?>
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

        // Выбираем таблицу в контейнере с id searchAnswer
        const table = document.getElementById('searchAnswer');

        // Добавляем делегированный обработчик событий на таблицу
        table.addEventListener('click', function (event) {

            // Проверяем, был ли клик по ссылке
            if (event.target.tagName.toLowerCase() === 'button'
                || event.target.tagName.toLowerCase() === 'i'
                || event.target.tagName.toLowerCase() === 'a') {
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

        // Hаведение на пдф
        const pdfElements = document.querySelectorAll('.pdf-element');
        const pdfModal = document.getElementById('pdfModal');
        const pdfFrame = document.getElementById('pdfFrame');

        pdfElements.forEach(element => {
            element.addEventListener('mouseenter', function () {
                const pdfPath = this.getAttribute('data-pdf-path');
                if (pdfPath) {
                    pdfFrame.src = pdfPath;

                    const rect = this.getBoundingClientRect();
                    const modalHeight = 700; // Высота модального окна

                    // Расположение окна слева от кнопки
                    pdfModal.style.left = `${rect.left - 610}px`; // сдвиг на ширину окна + немного отступа

                    // Динамическое положение по вертикали
                    if (rect.top + modalHeight > window.innerHeight) {
                        // Если окно выходит за нижнюю границу, располагаем его выше
                        pdfModal.style.top = `${rect.top - (rect.top + modalHeight - window.innerHeight)}px`;
                    } else {
                        // Обычное расположение
                        pdfModal.style.top = `${rect.top}px`;
                    }

                    pdfModal.classList.add('visible');
                }
            });

            element.addEventListener('mouseleave', function () {
                pdfModal.classList.remove('visible');
            });

            element.addEventListener('click', function () {
                if (/Mobi|Android/i.test(navigator.userAgent)) {
                    const pdfPath = this.getAttribute('data-pdf-path');
                    if (pdfPath) {
                        window.open(pdfPath, '_blank');
                    }
                }
            });
        });
    });
</script>
<script type="text/javascript" src="public/js/projects-view.js"></script>
<!-- chat work on jquery lib -->
<script type="text/javascript" src="chat/chat.js"></script>
</body>
</html>