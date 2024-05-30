<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php HeadContent('docs'); ?>
    <style>
        body {
            font-family: 'Open Sans', serif;
        }

        pre {
            padding-left: 10px;
            width: 100%;
            height: 70vh;
            overflow-y: auto; /* Добавляет вертикальную прокрутку, если содержимое больше высоты элемента */
        }

        .nav-tabs .nav-link.active {
            background-color: #007bff; /* Синий цвет фона */
            color: white; /* Цвет текста */
        }

        .modal-dialog {
            max-width: 90%; /* Задаем ширину модального окна */
        }

        .modal.show {
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
            background-color: #f0f8ff6b; /* Применяем блюр к фону */
        }
    </style>
</head>
<body>

<div class="modal show" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <?php NavBarContent('docs', $_SESSION['userBean']); ?>
            <div class="container-fluid pt-2 ps-2 pe-2" style="height: 85vh;">
                <!-- Табы -->
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Таб 1 -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-target="#tab1" type="button" role="tab">TO DO</button>
                    </li>
                    <!-- Таб 2 -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-target="#tab2" type="button" role="tab">Описание проекта</button>
                    </li>
                    <!-- Таб 3 -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-target="#tab3" type="button" role="tab">Технические Доки</button>
                    </li>
                    <!-- Таб 4 -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-target="#tab4" type="button" role="tab">Тексты На русском</button>
                    </li>
                    <!-- Таб 5 -->
                    <li class="nav-item" role="presentation">
                        <!--ТЗ к приложению для админки-->
                        <button class="nav-link" data-bs-target="#tab5" type="button" role="tab">Other</button>
                    </li>
                </ul>

                <!-- Контент Табов -->
                <div class="tab-content mb-3">
                    <!-- Контент Таба 1 -->
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
                        <h2>В Разработке</h2>
                        <pre style="background:#f6f684;"><?php include_once 'todo.txt'; ?></pre>
                    </div>
                    <!-- Контент Таба 2 -->
                    <div class="tab-pane fade show" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
                        <h2>Правила и Сервисы</h2>
                        <pre style="background:lightgreen;"><b><?php include_once 'docs.txt'; ?></b></pre>
                    </div>
                    <!-- Контент Таба 3 -->
                    <div class="tab-pane fade show" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
                        <h2>Технические Доки</h2>
                        <pre style="background:lightblue;"><?php include_once 'tech.txt'; ?></pre>
                    </div>
                    <!-- Контент Таба 4 -->
                    <div class="tab-pane fade show" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
                        <h2>Тексты На русском</h2>
                        <pre style="background:lightgrey;"><?= 'Not exist yet'; ?></pre>
                    </div>
                    <!-- Контент Таба 5 -->
                    <div class="tab-pane fade show" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
                        <h2>Code Basket</h2>
                        <pre style="background:lightyellow;"><?= 'Not exist yet'; ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
/* JAVASCRIPTS */
ScriptContent();
?>
<script>
    // jQuery для переключения табов и обработки кнопки закрыть
    $(document).ready(function () {
        // jQuery для переключения табов
        $('.nav-link').click(function () {
            // Удаляем класс active со всех табов
            $('.nav-link').removeClass('active');
            // Добавляем класс active к текущему табу
            $(this).addClass('active');

            // Получаем ID целевого таба
            let targetTab = $(this).data('bs-target');
            // Убираем класс show и active со всех табов
            $('.tab-pane').removeClass('show active');
            // Добавляем классы show и active к целевому табу
            $(targetTab).addClass('show active');
        });
    });
</script>
</body>
</html>