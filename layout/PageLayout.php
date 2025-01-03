<?php
/** @noinspection ALL */
/* ICON, TITLE, STYLES AND META TAGS */
function HeadContent($page)
{ ?>
    <!-- root link to this site -->
    <base href="<?= BASE_URL; ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">-->
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="/public/images/nti_logo.png">
    <title><?= SR::getResourceValue('title', $page ?? ''); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
<!--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.2/font/bootstrap-icons.css">-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- global styles light mode -->
    <link rel="stylesheet" href="/public/css/main.css">
    <?php
    /*  подключаем стили для конкретных  страниц если они есть */
    switch ($page) {
        case 'project':
        case 'add_step':
        case 'edit_step':
            echo '<link rel="stylesheet" href="/public/css/projects-view.css">';
            break;
        case 'edit_project':
            echo '<link rel="stylesheet" href="/public/css/edit-project.css">';
            break;
        case 'admin-panel':
            echo '<link rel="stylesheet" href="/public/css/admin-panel.css">';
            break;
        case 'order':
            echo '<link rel="stylesheet" href="/public/css/orders-view.css">';
            break;
        case 'order_details':
            echo '<link rel="stylesheet" href="/public/css/order-details.css">';
            break;
        case 'task_manager':
            echo '<link rel="stylesheet" href="/public/css/task-manager.css"/>';
            break;
    }
    ?>
    <style>
        /* google translate nav bar hiding */
        .skiptranslate {
            display: none;
        }
    </style>
    <form action="" id="routing" class="hidden" method="post"></form>

    <?php
}

/**
 * This page include all navigation panel bar
 * all buttons and diamic creation buttons
 */
include_once 'Navigation-panel.php';

/**
 * PAGINATION BUTTONS ON PAGES
 * orders, projects, warehouse
 * limit by default 25 per page
 * @param $get
 * @param $page
 * @param $table
 * @param int $limit
 * @param array $conditions ['query'=>'', 'data'=>'']
 * @return string[]
 */
function PaginationForPages($get, $page, $table, int $limit = 25, array $conditions = []): array
{
    $pagination = $paginationButtons = '';
    if (isset($get['limit']))
        $limit = (int)$get['limit'];

    // установка лимита для запроса в БД
    if ($limit != 0) {
        $currentPage = isset($get['page']) ? (int)$get['page'] : 1;
        $offset = ($currentPage - 1) * $limit;
        $pagination = "LIMIT $limit OFFSET $offset";
        // SQL-запрос для получения общего количества записей
        if (!empty($conditions)) {
            $totalResult = R::count($table, $conditions['query'], [$conditions['data']]);
        } else {
            $totalResult = R::count($table);
        }
        $totalPages = ceil($totalResult / $limit);

        $limit_n = (isset($_GET['limit'])) ? '&limit=' . $_GET['limit'] : '';
        // Пагинация
        $paginationButtons = '<div class="pagination" id="pagination-container">';
        // the previos button
        if ($currentPage > 1) {
            $href = "$page?page=" . ($currentPage - 1) . $limit_n;
            $paginationButtons .= "<a href='$href'>&laquo; Previous</a>";
        }
        // the pages buttons
        for ($i = 1; $i <= $totalPages; $i++) {
            $href = "$page?page=" . $i . $limit_n;
            $paginationButtons .= "<a href='$href' class='" . ($i == $currentPage ? 'active' : '') . "'>$i</a>";
        }
        // the next button
        if ($currentPage < $totalPages) {
            $href = "$page?page=" . ($currentPage + 1) . $limit_n;
            $paginationButtons .= "<a href='$href'>Next &raquo;</a>";
        }
        $paginationButtons .= '</div>';
    }

    return [$pagination, $paginationButtons];
}

/**
 * Функция PAGE_FOOTER
 *
 * Эта функция генерирует HTML-код для нижнего колонтитула (footer) страницы,
 * - а также подключает необходимые JavaScript библиотеки и дополнительные скрипты, специфичные для текущей страницы.
 * - В зависимости от переданных параметров, функция также может отображать элемент загрузки (spinner).
 * - Окно быстрых сообщений (chat)
 * - Туториал по страницам программы используя настройки пользователя для отключения или всключения отображения туториала
 *
 * Параметры:
 *
 * @param string $page (по умолчанию '')
 * - Определяет, какой JavaScript файл должен быть подключен в зависимости от текущей страницы.
 * - Возможные значения: 'admin-panel', 'order', 'order_details', 'priority'. Если значение не указано, специфический скрипт не подключается.
 * @param bool $footer (по умолчанию true)
 * - Определяет, показывать нижний колонтитул на странице или нет.
 * @param bool $spinner (по умолчанию true)
 * - Определяет, должен ли отображаться элемент загрузки (spinner) на странице. Если передано значение true, то элемент загрузки отображается.
 */
function PAGE_FOOTER($page = '', $footer = true, $spinner = true)
{

    // tutorial container
    $pageArray = '';
    $scriptOn = false;
    if ($_SESSION['userBean']['tutorial'] == '1' && !empty($page)) {
        $pageArray = "steps = data.$page;";
        $scriptOn = true;
        ?>
        <div id="tutorialContainer" class="d-none">
            <!-- Затемнение всей страницы -->
            <div class="overlay"></div>

            <!-- Подсказка для текущего элемента -->
            <div class="tooltip"></div>

            <!-- Кнопка для перехода к следующему шагу -->
            <button id="nextStep" class="btn btn-success d-none">Next</button>

            <!-- Кнопка для отмены туториала -->
            <button id="cancelTutorial" class="btn btn-danger d-none">Cancel</button>
        </div>
        <?php
    }

    // вывод адресной формы для страницы приорити
    if ($page == 'priority') {
        echo '<form action="" id="routing" class="hidden" method="post"></form>';
    }

    // SPINNER SECTION
    if ($spinner) { ?>
        <div class="spinner-box" id="loading">
            <span class="spinner-text fs-4 blinking">Loading...</span>
            <div class="coloring">
                <div class="spinner-border" role="status"></div>
            </div>
        </div>
        <?php
    }

    // FOOTER SECTION
    if ($footer) { ?>
        <footer class="border-top mt-auto">
            <div class="row py-3">
                <!-- Копирайт -->
                <div class="col-md-8 text-left ms-3">
                    <?= '2016 - ' . date('Y') . '&nbsp; Created by &copy; Ajeco.ltd'; ?>
                </div>

                <!-- Счетчик проектов -->
                <div class="col-md-3 text-right">
                    <?= 'NTI Group Projects Live - ' . R::count(PROJECTS); ?>
                </div>
            </div>
        </footer>
        <?php
    }

    // JAVASCRIPTS SECTION
    ?>
    <!-- Bootstrap JS & Popper.js & Jquery.js -->
    <script src="/libs/jQuery3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- google translator script lib free use -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <!-- custom lib .js -->
    <script src="/libs/ajeco-re-max.js"></script>
    <!-- all global calls and functions -->
    <script src="/public/js/globalScripts.js"></script>

    <?php
    // pages js files
    switch ($page) {
        case 'admin-panel':
            echo '<script src="/public/js/admin-panel.js"></script>';
            break;
        case 'order':
        case 'order_details':
            echo '<script src="/public/js/order-view.js"></script>';
            break;
    }
    if (!empty($pageArray) && $scriptOn) { ?>
        <!--suppress JSJQueryEfficiency, JSUnusedLocalSymbols -->
        <script>
            $(document).ready(function () {
                let currentStep = 0;
                let steps = [];

                // Загружаем JSON-файл с шагами туториала
                $.getJSON('/layout/t.json', function (data) {
                    <?php echo $pageArray; ?> // steps = data.orders; ...ETC
                    if (steps.length > 0) {
                        $('#tutorialContainer').removeClass('d-none');  // Показать контейнер туториала
                        $('#nextStep').removeClass('d-none');  // Показать кнопку "Следующий шаг"
                        $('#cancelTutorial').removeClass('d-none');  // Показать кнопку "Отменить"
                        highlightStep(steps[currentStep]);  // Запустить первый шаг
                    } else {
                        console.error("Туториал не содержит шагов.");
                    }
                }).fail(function () {
                    console.error("Не удалось загрузить файл туториала.");
                });

                // Функция для подсветки текущего элемента и отображения подсказки
                function highlightStep(step) {
                    const element = $(step.id);
                    if (element.length === 0) {
                        console.error("Элемент с ID " + step.id + " не найден.");
                        return;
                    }

                    const position = element.offset();
                    const width = element.outerWidth();
                    const height = element.outerHeight();
                    const windowWidth = $(window).width();
                    const windowHeight = $(window).height(); // Получаем высоту окна
                    const padding = 10; // Расширяем рамку на 10px с каждой стороны

                    // Настраиваем тултип
                    let tooltipLeft = position.left;
                    let tooltipTop = position.top + height + 30; // По умолчанию ниже элемента

                    // Если тултип выходит за правый край окна, сдвигаем его влево
                    const tooltipWidth = $('.tooltip').outerWidth();
                    if ((tooltipLeft + tooltipWidth) > windowWidth) {
                        tooltipLeft = windowWidth - tooltipWidth - 10; // 10px отступ от правого края
                    }

                    // Если тултип выходит за нижний край окна, сдвигаем его наверх элемента
                    const tooltipHeight = $('.tooltip').outerHeight();
                    if ((tooltipTop + tooltipHeight) > windowHeight) {
                        tooltipTop = position.top - tooltipHeight - 10; // Сдвигаем тултип наверх
                    }

                    // Отображаем тултип с учетом корректировок
                    $('.tooltip').text(step.text).css({
                        top: tooltipTop + 'px',
                        left: tooltipLeft + 'px',
                        opacity: 1
                    }).show();

                    // Обводим элемент с увеличением размеров
                    $('.highlight').remove();
                    $('<div class="highlight"></div>')
                        .css({
                            position: 'absolute',
                            top: (position.top - padding) + 'px', // Поднимаем рамку выше элемента
                            left: (position.left - padding) + 'px', // Сдвигаем рамку влево
                            width: (width + padding * 2) + 'px', // Увеличиваем ширину рамки
                            height: (height + padding * 2) + 'px', // Увеличиваем высоту рамки
                            border: '5px solid red',
                            zIndex: 9001
                        })
                        .appendTo('body');

                    // Корректируем позицию кнопок
                    adjustButtonPosition(tooltipTop, tooltipHeight);
                }

                // Функция для корректировки положения кнопок относительно тултипа
                function adjustButtonPosition(tooltipTop, tooltipHeight) {
                    const windowHeight = $(window).height();
                    const nextButton = $('#nextStep');
                    const cancelButton = $('#cancelTutorial');
                    const buttonHeight = nextButton.outerHeight();

                    // Проверяем, пересекается ли кнопка с тултипом
                    const nextButtonTop = windowHeight - 50 - buttonHeight; // Текущая позиция кнопки снизу

                    // Если тултип перекрывает кнопку, сдвигаем кнопку вверх
                    if (tooltipTop + tooltipHeight > nextButtonTop) {
                        nextButton.css({
                            top: "6rem"
                        });
                        cancelButton.css({
                            top: "6rem"
                        });
                    } else {
                        // Возвращаем кнопки на стандартную позицию, если они не перекрываются
                        nextButton.css({
                            top: ''
                        });
                        cancelButton.css({
                            top: ''
                        });
                    }
                }

                // Функция для перехода к следующему шагу
                $('#nextStep').on('click', function () {
                    currentStep++;
                    if (currentStep < steps.length) {
                        highlightStep(steps[currentStep]);
                    } else {
                        endTutorial();  // Если шагов больше нет, завершить туториал
                    }
                });

                // Функция для завершения туториала
                $('#cancelTutorial').on('click', endTutorial);

                function endTutorial() {
                    $('#tutorialContainer').addClass('d-none');  // Скрыть контейнер туториала
                    $('.overlay, .tooltip').hide();    // Скрыть затемнение и подсказку
                    $('.highlight').remove();  // Убрать обводку элемента
                    $('#nextStep, #cancelTutorial').addClass('d-none'); // Скрыть кнопки
                    currentStep = 0;  // Сбросить шаг
                }
            });
        </script>
        <?php
    }
} // end of PAGE FOOTER

/**
 * THE CHAT FUNCTION FOR ALL USERS
 * @param $page
 * @param $user
 * @return void
 */
function ShowGroupChatPopup($page, $user = null)
{
    ?>
    <!-- popup main chat button -->
    <button class="open-button" onclick="openForm()">
        <span id="msg_count" class="msg_count">123</span>
        <i class="bi bi-chat"></i>
    </button>

    <!-- popup main chat room window -->
    <div class="chat-popup" id="popup-window">
        <span id="user_information" data-userid="<?= $_SESSION['userBean']['id']; ?>" data-username="<?= $_SESSION['userBean']['user_name']; ?>" class="hidden"></span>
        <form action="" class="form-container" method="post" onsubmit="sendMessage(); return false;">
            <div class="mb-2" onclick="closeForm()">
                <i class="bi bi-x-lg p-2 danger"></i>
            </div>

            <label for="msg"><b>Messages</b></label>
            <div class="msg_content" id="msg_area"></div>
            <textarea placeholder="Type message.." name="msg" id="msg" required></textarea>

            <div class="dropdown w-100">
                <button class="btn btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="dropdown-toggle">
                    Message to User
                </button>
                <div class="dropdown-menu w-100" id="dropdown-menu">
                    <?php
                    foreach (R::find(USERS) as $u) {
                        if ($u['id'] != '1') {
                            ?>
                            <div class="radio-item">
                                <input type="radio" name="user" value="<?= $u['id']; ?>" id="<?= $u['id']; ?>" onchange="loadMessages(<?= $u['id']; ?>)">&nbsp;
                                <label for="<?= $u['id']; ?>"><?= $u['user_name']; ?></label>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Send</button>
        </form>

    </div>
    <?php
}


// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
// used on pages: arrivals,
function SearchResponceModalDialog($page, $answer_id): void
{ ?>
    <div class="modal" id="searchModal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Search Result</h4>
                    <button type="button" class="btn-close" data-aj-dismiss="modal"></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <table id="<?= $answer_id ?>">
                        <!-- table for preview search results -->
                    </table>
                </div>

                <!-- Modal footer x -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-aj-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}