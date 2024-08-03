dom.addEventListener("DOMContentLoaded", function () {
    /*=================== ОБЩИЙ ФУНКЦИОНАЛ СТРАНИЦ АДМИН ПАНЕЛИ =================================*/
    // routing for admin-panel pages flow
    dom.in("click", "#navBarContent .swb", function () {
        const value = this.value;
        const currentUrl = new URL(window.location.href);
        // Check if the 'route-page' parameter exists in the URL
        if (currentUrl.searchParams.has('route-page')) {
            // If it exists, update its value
            currentUrl.searchParams.set('route-page', value);
        } else {
            // If it doesn't exist, add it with the clicked button's value
            currentUrl.searchParams.append('route-page', value);
        }
        // Navigate to the updated URL
        window.location.href = currentUrl.toString();
    });

    // colorising button of nav bar when roted on page
    setActiveColorToNavLinkBtnList(".swb");

    // some sotr of routing when user press the create new tool btn
    dom.doSubmit('#create-btn', '#create-form');

    // delete one item from database modal open
    dom.in("click", "#delete_btn", function () {
        if (this.dataset.id) {
            dom.e("#idForUse").value = this.dataset.id;
            dom.show("#deleteModal");
            dom.e("#password").focus();
        } else {
            console.log("do id in dataset");
        }
    });

    // Обработка кликов на чекбоксы
    dom.in('click', 'tbody input[type="checkbox"]', updateRowOrder);

    // Добавляем делегированный обработчик событий click на таблицу
    const tBody = dom.e("#data-container");

    if (tBody) {
        // Добавляем делегированный обработчик событий на таблицу
        tBody.addEventListener('click', function (event) {
            // // Проверяем, был ли клик по ссылке
            // fixme если будет ссылка на даташит в будущем
            // if (event.target.tagName.toLowerCase() === 'a') {
            //     return; // Прекращаем выполнение функции, если клик был по ссылке
            // }

            // Находим родительский <tr> элемент
            let row = event.target;
            while (row && row.tagName.toLowerCase() !== 'tr') {
                row = row.parentElement;
            }

            // Если <tr> элемент найден и у него есть data-id
            if (row && row.dataset.id) {
                // Получаем значение data-id
                const id = row.dataset.id;

                // Создаем скрытую форму
                const form = document.createElement('form');
                form.method = 'post';

                // Создаем скрытый инпут
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'edit';
                input.value = id;

                // Добавляем инпут в форму
                form.appendChild(input);

                // Добавляем форму на страницу
                document.body.appendChild(form);

                // Отправляем форму
                form.submit();
            }
        });
    }
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  COLUMNS.PHP ===============================*/
    dom.in("click", ".dob", function () {
        dom.e("#table-name").value = this.textContent;
        dom.e("#table-selector").value = this.value;
        dom.e("#select-form").submit();
    });

    /* скрипты для настройки вывода таблиц */
    if (dom.e('tbody tr')) {
        // Инициализация порядка строк при загрузке страницы
        updateRowOrder();

        let draggedRow = null;
        // Делаем строки таблицы перетаскиваемыми
        dom.eAll('tbody tr', 'draggable', 'true');

        // Обработка событий dragstart
        dom.in('dragstart', 'tbody tr', function () {
            draggedRow = this;
        }, 'table');
        // Обработка событий dragover
        dom.in('dragover', 'tbody tr', function (event) {
            event.preventDefault();
        }, 'table');
        // Обработка событий drop
        dom.in('drop', 'tbody tr', function () {
            if (this !== draggedRow) {
                this.parentNode.insertBefore(draggedRow, this);
                updateRowOrder();
            }
        }, 'table');
    }

    // скрываем ответ от сервера при клике на странице
    dom.in('click', "body", function () {
        dom.hide("#searchModal");
    });

    function setActiveColorToNavLinkBtnList($listId) {
        // Получаем текущий URL
        const url = new URL(window.location.href);
        // Извлекаем значение параметра route-page
        const routePage = url.searchParams.get('route-page');
        // Get all the buttons in the navigation bar
        const navButtons = document.querySelectorAll($listId);
        // Loop through the buttons and check their value
        navButtons.forEach(function (button) {
            if (button.value === routePage) {
                // Add the active class to the button with the matching value
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-outline-primary');
            } else {
                // Remove the active class from all other buttons
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-outline-secondary');
            }
        });
    }

    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  ROUTES.PHP ================================*/
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  TOOLS.PHP =================================*/
    // take picture for tool
    dom.doClick("#take_a_pic", "#image");

    // preview image taken by user
    dom.doPreviewFile("#image", "#preview", function () {
        dom.e("#take_a_pic").textContent = dom.e("#image").files[0].name;
    });

    // при изменени списка ответственного за инструмент открываем кнопку сохранить/изменить инструмент
    dom.in("change", "#responsible", function () {
        dom.e("#save-btn").disabled = false;
    });


    // событие клик на выбор файла для импорта инструментов из файла
    dom.doClick("#import-from-file", "#import-file-input", function (elem) {
        if (elem.files[0]) {
            const file = elem.files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (fileExtension !== 'csv') {
                alert('Invalid file type. Please select a CSV file.');
                elem.value = ''; // Clear the selected file
            } else {
                dom.e("#import-file-form").submit();
            }
        }
    });

    // выборка фоток из БД которые существуют
    const tools = {method: "POST", url: "get_data", headers: null};
    dom.makeRequest("#db-image-btn", "click", "data-request", tools, function (error, result, _) {
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
            dom.e("#preview").src = info;
            dom.e("#db-image").value = info;
        }
        // Очищаем результаты поиска
        dom.hide("#searchModal");
    });

    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  USERS.PHP =================================*/
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  PROJECT.PHP ===============================*/
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  ORDER.PHP =================================*/
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  WAREHOUSE.PHP =============================*/
    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  PROFILE.PHP ===============================*/
    // кнопки выбора фото пользователя и Обработчик обновления превью
    dom.doClick("#btn-take-image", "#file-input");
    dom.doPreviewFile("file-input", "profile-img");

    // делаем видимой форму смены пароля
    dom.in("click", "#change-password", function () {
        if (this) {
            dom.removeClass("#password-form", "hidden");
        }
    });

    // проверка валидности формы смены пароля
    dom.inAll("input", "input", function () {
        const pass_1 = dom.e('#password_1');
        const pass_2 = dom.e('#password_2');
        // Проверка совпадения паролей
        const passwordsMatch = pass_1.value === pass_2.value && pass_1.value !== '';
        // Проверка валидности имейла
        const emailValid = dom.e('#email').checkValidity(); // возвращает true, если поле валидно
        // Проверка состояния чекбокса
        const checkboxChecked = dom.e('#check').checked;
        // Управление атрибутом required для имейла
        dom.e('#email').required = checkboxChecked;
        // Управление доступностью кнопки
        dom.e("#pass-btn").disabled = !(passwordsMatch && (!checkboxChecked || (checkboxChecked && emailValid)));
    });

    /*=================== ФУНКЦИОНАЛ ДЛЯ СТРАНИЦЫ  SEARCHING.PHP =============================*/
    // searching some data on page in DB
    // fixme change to 'get_data_ap' soon
    let args = {url: BASE_URL + "admin-panel/searching.php", method: "POST", headers: null}
    dom.makeRequest("#searchThis", "keyup", "", args, function (error, result, _) {
        if (error) {
            console.error('Error during fetch:', error);
            return;
        }
        dom.e("#searchAnswer").innerHTML = result;
    });
});

// Функция для обновления порядка строк
function updateRowOrder() {
    let order = [];
    dom.querySelectorAll('tbody tr').forEach(tr => {
        let checkbox = tr.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            order.push(checkbox.value);
        }
    });
    // добавляем в инпут сортированный список выбранных элементов
    if (order.length > 0)
        dom.e('#rowOrder').value = order.join(',');
}