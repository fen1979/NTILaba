document.addEventListener("DOMContentLoaded", function () {
    // просмотр папки проекта если она не пуста
    // добавляем путь к папке если она есть и в ней есть файлы
    // делаем видимой кнопку в навигации
    if (dom.e("#project_folder_path")) {
        dom.removeClass("#folder_btn_li", "hidden");
        dom.e("#project_folder").value = dom.e("#project_folder_path").textContent.trim();
    }

    const thumbnails = document.querySelectorAll('.thumbnail');
    const fullPages = document.querySelectorAll('.full-page');
    let isScrollingDisabled = false;

    // Функция для удаления активного класса со всех миниатюр
    function removeActiveClass() {
        thumbnails.forEach(thumbnail => {
            thumbnail.classList.remove('active');
        });
    }

    // Прокрутка к нужной секции при клике на миниатюре
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function () {
            const step = this.getAttribute('data-step');
            const targetPage = document.querySelector(`.full-page[data-step="${step}"]`);
            if (targetPage) {
                // Отключаем обработчик скролла временно
                isScrollingDisabled = true;
                targetPage.scrollIntoView({behavior: 'smooth'});
                removeActiveClass();
                this.classList.add('active');

                // Включаем обработчик скролла снова через 1 секунду
                setTimeout(() => {
                    isScrollingDisabled = false;
                }, 1000);
            }
        });
    });

    // Отслеживание скролла в full-page-container
    document.querySelector('.full-page-container').addEventListener('scroll', function () {
        if (isScrollingDisabled) return;

        let lastActiveThumbnail = null;

        fullPages.forEach(fullPage => {
            const rect = fullPage.getBoundingClientRect();
            const step = fullPage.getAttribute('data-step');

            // Проверяем, виден ли хотя бы 50% страницы
            const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
            const halfVisible = rect.top < window.innerHeight && rect.bottom > 0;

            if (isVisible || halfVisible) {
                removeActiveClass();
                const activeThumbnail = document.querySelector(`.thumbnail[data-step="${step}"]`);
                activeThumbnail.classList.add('active');
                lastActiveThumbnail = activeThumbnail;
            }
        });

        if (lastActiveThumbnail) {
            lastActiveThumbnail.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }
    });

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

    // увеличительное стекло для фотографий
    const imageContainers = document.querySelectorAll('.img-video-container');
    imageContainers.forEach(container => {
        const img = container.querySelector('.magnify-image');
        // предзагрузка для тех фото что не отрисованы в самом начале
        onImageLoaded(img, () => {
            const magnifier = container.querySelector('.magnifier');
            const magnifyImg = document.createElement('img');
            magnifyImg.src = img.src;
            magnifier.appendChild(magnifyImg);

            const zoomFactor = 2;
            magnifyImg.style.width = `${img.width * zoomFactor}px`;
            magnifyImg.style.height = `${img.height * zoomFactor}px`;

            img.addEventListener('mousemove', function (e) {
                const imgRect = img.getBoundingClientRect();
                const magnifierRect = magnifier.getBoundingClientRect();
                const x = e.clientX - imgRect.left;
                const y = e.clientY - imgRect.top;

                if (x > 0 && x < img.width && y > 0 && y < img.height) {
                    magnifier.style.display = 'block';
                    magnifier.style.left = `${e.pageX - magnifierRect.width / 2}px`;
                    magnifier.style.top = `${e.pageY - magnifierRect.height / 2}px`;

                    let rx = (x / imgRect.width * magnifyImg.width) - magnifierRect.width / 2;
                    let ry = (y / imgRect.height * magnifyImg.height) - magnifierRect.height / 2;

                    magnifyImg.style.left = `-${rx}px`;
                    magnifyImg.style.top = `-${ry}px`;

                    if (e.pageX + magnifierRect.width / 2 > window.innerWidth) {
                        magnifier.style.left = `${window.innerWidth - magnifierRect.width}px`;
                    }
                    if (e.pageY + magnifierRect.height / 2 > window.innerHeight) {
                        magnifier.style.top = `${window.innerHeight - magnifierRect.height}px`;
                    }
                    if (e.pageX - magnifierRect.width / 2 < 0) {
                        magnifier.style.left = `0px`;
                    }
                    if (e.pageY - magnifierRect.height / 2 < 0) {
                        magnifier.style.top = `0px`;
                    }
                } else {
                    magnifier.style.display = 'none';
                }
            });

            img.addEventListener('mouseleave', function () {
                magnifier.style.display = 'none';
            });
        });
    });
}); // End document.ready

// увеличительное стекло для фотографий помошник предзагрузки
const onImageLoaded = (image, callback) => {
    if (image.complete && image.naturalWidth && image.naturalHeight) {
        callback();
    } else {
        image.addEventListener('load', () => callback());
    }
}