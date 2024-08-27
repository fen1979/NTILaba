<style>
    .left-column {
        height: 87VH;
        overflow-y: auto;
        background-color: #f8f9fa;
    }

    .right-column {
        height: 100%;
        background-color: #ffffff;
        position: relative;
    }

    .card-list {
        padding: 10px;
    }

    .small-card {
        margin-bottom: 10px;
        padding: 10px;
        background-color: #ffffff;
        border: 1px solid #ddd;
        cursor: pointer;
        transition: transform 0.3s;
    }

    .large-card {
        position: absolute;
        overflow: auto;
        top: 31rem;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 20px;
        background-color: #ffffff;
        border: 1px solid #ddd;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        height: 87vh;
    }

    .small-card:hover {
        transform: scale(1.05);
    }

    .valid {
        background: #ff000033;
        border-radius: 10px;
    }

    .image-container {
        position: relative;
        display: inline-block;
        max-width: 50rem; /* Ограничение по ширине */
        width: 100%; /* Делает контейнер адаптивным */
    }

    .image-container img {
        display: block;
        width: 100%;
        height: auto;
    }

    /*.image-container::before {*/
    /*    content: "ONLY FOR EDITING"; !* Текст водяного знака *!*/
    /*    position: absolute;*/
    /*    top: 0;*/
    /*    left: 0;*/
    /*    padding: 35px;*/
    /*    font-size: 30px; !* Размер текста водяного знака *!*/
    /*    color: rgba(255, 255, 255, 0.5); !* Цвет текста с прозрачностью *!*/
    /*    z-index: 2; !* Водяной знак будет поверх изображения *!*/
    /*    pointer-events: none; !* Чтобы водяной знак не мешал кликам по изображению *!*/
    /*    background: rgba(255, 0, 0, 0.35);*/
    /*}*/

    /*.image-container::after {*/
    /*    content: "";*/
    /*    position: absolute;*/
    /*    top: 0;*/
    /*    left: 0;*/
    /*    width: 100%;*/
    /*    height: 100%;*/
    /*    background: rgba(0, 0, 0, 0.1); !* Полупрозрачный слой над изображением *!*/
    /*    z-index: 1; !* Слой водяного знака *!*/
    /*    pointer-events: none;*/
    /*}*/

    /* Стили для полноэкранного изображения */
    .full-screen-image {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9); /* Фон для полноэкранного изображения */
        z-index: 1000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .full-screen-image img {
        max-width: 100%;
        max-height: 100%;
        height: auto;
    }

    /* Класс для скрытия элемента */
    .hidden {
        display: none;
    }
</style>

<div class="container-fluid ">
    <div class="row">
        <!-- Левая колонка с карточками -->
        <div class="col-3 left-column">
            <div class="card-list">
                <!-- Карточки, которые будут вертикально расположены -->
                <?php foreach ($unit_staps as $step) {
                    $infoData = json_encode(['unit_id' => $step['projects_id'], 'step_id' => $step['id'], 'image' => $step['image'],
                        'video' => (strpos($step['video'], '.mp4') !== false) ? $step['video'] : 'none',
                        'description' => $step['description'], 'step_num' => $step['step'],
                        'revision' => $step['revision'], 'validation' => $step['validation']]); ?>

                    <div class="card small-card" id="card-<?= $step['id'] ?>" data-info='<?= htmlspecialchars($infoData, ENT_QUOTES, 'UTF-8'); ?>'>
                        <h4>Step <?= $step['step'] ?></h4>
                        <p>
                            <small class="text-primary">Part Number</small>
                            <?= $step['part_number'] ?? 'N/A' ?>
                        </p>
                        <p>
                            <small class="text-primary">Description</small>
                            <br>
                            <?= $step['description'] ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Правая колонка для отображения выбранной карточки -->
        <div class="col-9 right-column">
            <div class="card large-card" id="large-card-display">
                <!-- Здесь будет отображаться выбранная карточка -->
                <h3 class="mb-2" id="validation-box">Assembly Step
                    <span id="step-number"></span> &nbsp;
                    <span class="text-danger ms-2 mt-2 mb-2" id="opacity"><i class="bbi bi-check2-square"></i> &nbsp; Step needs validation!</span>
                </h3>
                <div class="image-container" id="image-container">
                    <img src="" alt="<?= $step['projects_id']??''; ?>" id="image">
                </div>

                <!-- Полноэкранный контейнер -->
                <div class="full-screen-image hidden" id="full-screen-image">
                    <img src="" alt="<?= $step['projects_id']; ?>">
                </div>

                <h4 class="mb-2" id="step-description">Description will appear here.</h4>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        //i ОБРАБОТКА РАБОТЫ СО СПИСКОМ ШАГОВ И ВЫВОДОМ В БОЛЬШУЮ КАРТОЧКУ
        const cards = document.querySelectorAll('.small-card');

        function updateLargeCard(card) {
            // получаем все данные шага для вывода в большом окне
            let info = JSON.parse(card.dataset.info);

            // проверяем если для шага требуется валидация
            if (info.validation === "0") {
                // устанавливаем свойство прозрачности равное ноль
                // dom.e("#opacity").setAttribute("style", "opacity:0");
                document.querySelector("#opacity").setAttribute("style", "opacity:0");
            } else {
                // если трребуется проверка то устанавливаем цвет бокса и добавляем мигание
                const valid = dom.e("#validation-box");
                valid.classList.add("valid", "blinking", "p-2")
            }

            // Добавляем нужные данные в большую карточку
            // dom.e('#step-number').textContent = info.step_num;
            // dom.e('#step-description').textContent = info.description;
            // dom.e("#image").setAttribute("src", info.image);

            document.querySelector('#step-number').textContent = info.step_num;
            document.querySelector('#step-description').textContent = info.description;
            document.querySelector("#image").setAttribute("src", info.image);
        }

        cards.forEach(card => {
            card.addEventListener('mouseover', function () {
                updateLargeCard(card);
            });
        });

        // Инициализация первой карточки при загрузке страницы
        if (cards.length > 0) {
            updateLargeCard(cards[0]);
        }

        // full view actions
        document.getElementById('image').addEventListener('click', function () {
            const screenContainer = document.getElementById('image-container');
            const fullScreenContainer = document.getElementById('full-screen-image');
            const fullScreenImage = fullScreenContainer.querySelector('img');
            fullScreenImage.src = this.src;
            fullScreenContainer.classList.remove('hidden');
            screenContainer.classList.add('hidden');
        });

        document.getElementById('full-screen-image').addEventListener('click', function () {
            const screenContainer = document.getElementById('image-container');
            this.classList.add('hidden');
            screenContainer.classList.remove('hidden');
        });
    });
</script>