document.addEventListener("DOMContentLoaded", function () {
    // добавляем путь к папке если она есть и в ней есть файлы
    // делаем видимой кнопку в навигации
    if (dom.e("#project_folder_path")) {
        dom.removeClass("#folder_btn_li", "hidden");
        dom.e("#project_folder").value = dom.e("#project_folder_path").textContent.trim();
    }

    /* удаление данного шага с перестроением данных в БД */
    dom.in("click", ".delete-button", function (event) {
        // Проверяем, что клик был по кнопке или по иконке внутри кнопки с классом delete-button
        let target = event.target;

        // Если клик был по иконке, поднимаемся до родительского элемента button
        if (target.tagName.toLowerCase() === 'i') {
            target = target.closest('button');
        }
        // Проверяем, есть ли у элемента нужный класс
        if (target && target.classList.contains('delete-button')) {
            // Получаем значения data-атрибутов
            dom.e("#dfProjectID").value = target.dataset.projectid;
            dom.e("#pid").textContent = target.dataset.projectid;
            dom.e('#dfstepId').value = target.dataset.stepid;
            dom.e('#sid').textContent = target.dataset.stepid;
            dom.show("#deleteModal", "fast", true);
            dom.e("#password").focus();
        }
    });

    // Находим ближайшие элементы изображения и видео относительно кнопки
    document.addEventListener('click', function (event) {
        // Проверяем, что клик произошел по элементу с классом .video-button
        if (event.target.closest('.video-button')) {
            let button = event.target.closest('.video-button');
            // Находим ближайший элемент .card относительно кнопки
            let card = button.closest('.card');
            let image = card.querySelector('.image-preview');
            let video = card.querySelector('.video-preview');
            let btn = card.querySelector('.video-button i');

            // Переключаем видимость
            if (image.style.display !== 'none') {
                image.style.display = 'none';
                video.style.display = 'block';
                btn.classList.remove("bi", "bi-camera-reels-fill");
                btn.classList.add("bi", "bi-camera-fill");
                btn.title = 'Preview Image';
            } else {
                video.style.display = 'none';
                image.style.display = 'block';
                btn.classList.remove("bi", "bi-camera-fill");
                btn.classList.add("bi", "bi-camera-reels-fill");
                btn.title = 'Preview Video';
            }
        }
    });

    /* действиe архивировать проект  */
    dom.in("click", ".archive", function () {
        dom.e("#dnProjectID").value = this.dataset.projectid;
        dom.e("#modal-title").textContent = "Add Project to Archive";
        dom.show("#archive-btn");
        dom.hide("#delete-btn");
        dom.show("#deleteProjectModal", "fast", true);
        dom.e("#pr_password").focus();
    });

    /* действиe удалить проект  */
    dom.in("click", ".deleteProjectButton", function () {
        dom.e("#dnProjectID").value = this.dataset.projectid;
        dom.e("#modal-title").innerHTML = "Delete Project<br><b class=\"text-danger\">Please be advised:" +
            "<br>This action is irreversible and requires thorough consideration." +
            "<br>Once initiated, there is no turning back, so weigh your decision carefully.</b>";
        dom.show("#delete-btn");
        dom.hide("#archive-btn");
        dom.show("#deleteProjectModal", "fast", true);
        dom.e("#pr_password").focus();
    });

    //i ОБРАБОТКА РАБОТЫ СО СПИСКОМ ШАГОВ И ВЫВОДОМ В БОЛЬШУЮ КАРТОЧКУ
    const cards = document.querySelectorAll('.small-card');
    function updateLargeCard(card) {
        // получаем все данные шага для вывода в большом окне
        let info = JSON.parse(card.dataset.info);

        // проверяем если для шага требуется валидация
        if (info.validation === "0") {
            // устанавливаем свойство прозрачности равное ноль
            dom.e("#opacity").setAttribute("style", "opacity:0");
        } else {
            // если трребуется проверка то устанавливаем цвет бокса и добавляем мигание
            const valid = dom.e("#validation-box");
            valid.classList.add("valid", "blinking", "p-2")
        }

        // Добавляем нужные данные в большую карточку
        dom.e('#step-number').textContent = info.step_num;
        dom.e('#step-description').textContent = info.description;
        dom.e("#image").setAttribute("src", info.image);
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
}); // End document.ready
