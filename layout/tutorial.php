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
<!--suppress JSJQueryEfficiency, JSUnusedLocalSymbols -->
<script>
    $(document).ready(function () {
        let currentStep = 0;
        let steps = [];

        // Загружаем JSON-файл с шагами туториала
        $.getJSON('/layout/t.json', function (data) {
            <?php echo $pageArray; ?> // steps = data.orders; ...ETC
            if (steps.length > 0) {
                console.log("Данные загружены успешно:", steps); // Для проверки
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

            console.log("Подсвечиваем элемент:", step.id);

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