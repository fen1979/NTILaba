// noinspection JSUnusedLocalSymbols,JSUnusedGlobalSymbols

document.addEventListener("DOMContentLoaded", function () {
    /* ===================================================================================================================== */
    /* ========================= Инициализация всех глобальных функций применяемых на всех страницах ======================= */

    // очистка памяти окна страницы для корректного ввода данных в поля
    win.cleanWindow();

    // навигация по сайту
    dom.doRouting(".url", "routing");

    // просмотр полей ввода паролей
    dom.unhidePassword(".pi", ".eye");

    /*
    * стили для вывода контекста при наведении на выбранные кнопки
    * data-title="..." можно вставлять в любой тег HTML
    * текст появится справа/слева от верхнего левого угла тега в который вставлен титул!
    * пример показан со стандартной бутстрап иконкой (знак информации)
    * <i class="bi bi-info-circle" data-title="Here write all what you need to view to user"></i>
    * function need required arguments {width: '', bg_color: '', color:'', padding:''}
    */
    dom.doTitleView("data-title", {
        width: "350px",
        height: "auto",
        bg_color: "#595e59",
        color: "#ffffff",
        padding: "10px"
    });

    // анимированное удаление отчета о операциях
    // used on all site pages
    dom.doAnimation(".fade-out", 2000, 5000);

    // добавление эффекта blur в нав бар при прокрутке страницы вверх
    win.scrollController(".navbar", "blury");

    // поисковая функция , подключена на всех страницах где есть поле для поиска
    const args = {method: "POST", url: BASE_URL + "get_data", headers: null};
    dom.makeRequest(".searchThis", "keyup", "data-request", args, function (error, result, event, _) {
        // console.log(result);
        if (error) {
            console.error('Error during fetch:', error);
            return;
        }

        // Проверка на нажатие клавиши Esc и скрытие модального окна
        if (event && event.key === "Escape") {
            dom.hide("#searchModal");
            return; // Прерываем дальнейшую обработку, если нажата Esc
        }

        // if pagination is exist on page
        let pagination = dom.e("#pagination-container");
        if (pagination) {
            pagination.classList.add("hidden");
        }

        // вывод информации на разных страницах
        // used on pages: project, order, warehouse, logs, wh_logs
        let searchAnswer = dom.e("#searchAnswer");
        if (searchAnswer) {
            searchAnswer.innerHTML = result;

            // обновление слушателей для страницы
            if (dom.e("#wh_logs"))
                dom.setAccordionListeners(".accordion-toggle", ".accordion-content", "click");
        }

        // вывод информации в модальное окно
        // used on pages: arrival, create-order, create-project
        let modalTable = dom.e("#searchModal");
        if (modalTable && result !== 'EMPTY') {
            dom.e("#search-responce").innerHTML = result;
            dom.show("#searchModal", "", true);
        } else {
            dom.hide("#searchModal");
        }

        // вывод информации на странице заполнения BOM проекта
        // used on pages: project-bom
        let table = dom.e("#itemTable");
        if (table) {
            dom.e("#tbody-responce").innerHTML = result;
        }
    });

    // эвент клик для вывода таблицы инструментов на странице создания проекта
    dom.makeRequest(".searchThisClick", "click", "data-request", args, function (error, result, event, _) {
        if (error) {
            console.error('Error during fetch:', error);
            return;
        }

        // Проверка на нажатие клавиши Esc и скрытие модального окна
        if (event && event.key === "Escape") {
            dom.hide("#searchModal");
            return; // Прерываем дальнейшую обработку, если нажата Esc
        }

        // if pagination is exist on page
        let pagination = dom.e("#pagination-container");
        if (pagination) {
            pagination.classList.add("hidden");
        }

        // вывод информации в модальное окно
        let modalTable = dom.e("#searchModal");
        if (modalTable && result !== 'EMPTY') {
            dom.e("#search-responce").innerHTML = result;
            dom.show("#searchModal", "", true);

            // обработка отметки чекбоксов в таблице
            const inputValue = dom.e("#tools").value;
            const selectedIds = inputValue ? inputValue.split(',').map(id => id.trim()) : [];

            // Сначала снимаем все отметки
            const toolsTableBody = dom.e("#tools-table");
            const allCheckboxes = toolsTableBody.querySelectorAll('.row-checkbox');
            allCheckboxes.forEach(cb => cb.checked = false);

            // Затем отмечаем необходимые чекбоксы
            selectedIds.forEach(id => {
                const checkbox = document.getElementById(id);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

        } else {
            dom.hide("#searchModal");
        }
    });

    // слушатель события при изменениях в БД на сервере (временное решение переделать на соккеты и добавить чат)
    dom.onDBChangeListener = function (trigger, sound, uid) {
        const playSong = dom.e(trigger);
        const id = dom.e(uid);
        if (playSong) {
            // Воспроизводим звук
            let audio = dom.e(sound);
            if (audio) {
                audio.play().then(r => '');
            }
        }

        // Function to check for changes in the database
        function checkForChanges() {
            // Perform a fetch request to your PHP listener
            fetch('is_change', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'uid=' + id.value
            })
                .then(response => response.text())  // Assuming the response is text
                .then(text => {
                    //console.log(text);
                    // Check if the response indicates changes
                    if (text === '1') {
                        // If changes are detected, submit the hidden form
                        dom.e('#has_changes').submit();
                    }
                })
                .catch(error => console.error('Error checking for changes:', error));
        }

        // Set interval to check for changes every 120000 milliseconds (2 minutes)
        setInterval(checkForChanges, 120000);
        // setInterval(checkForChanges, 15000);
    };

    // слушатель колеса загрузки
    dom.in("submit", "form", () => {
        dom.e("#loading").style.display = "flex";
    });

    // скрываем ответ от сервера при клике на страницах
    dom.in("click", "body", function (event) {
        // Проверяем, есть ли на странице модальное окно с таблицей tools
        const toolsTable = dom.e('#tools-table');

        // Если таблица tools подгружена и пользователь кликает вне этого окна, не скрываем модальное окно
        if (toolsTable && toolsTable.contains(event.target)) {
            // Таблица tools открыта, ничего не делаем
            return;
        }

        // Если таблица tools не открыта или клик происходит вне таблицы, скрываем модальное окно
        dom.hide("#searchModal");
    });


    // переделать в свои методы как дойдем
    // navigation butter toggle
    let toggler = dom.e(".navbar-toggler");
    let nav = dom.e("#navBarContent");

    if (toggler && nav) {
        toggler.addEventListener("click", function () {
            nav.classList.toggle("show"); // Переключает класс "show"
        });
    }

    // i установка цвета на активную кнопку в навбаре, Получаем текущий URL
    const url = new URL(window.location.href);
    // Получаем имя страницы из адресной строки
    const routePage = url.pathname + url.search; // Включаем query параметры
    // Получаем все кнопки, у которых есть класс .url.act
    const navButtons = document.querySelectorAll('.url.act');

    // Проходим по всем кнопкам и проверяем их value
    navButtons.forEach(function (button) {
        // Проверяем, включает ли значение кнопки часть текущего URL
        if (routePage.includes(button.value)) {
            // Добавляем активный класс к кнопке, если совпадение найдено
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            // Убираем активный класс у остальных кнопок
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    });

});


/*i =========================== google translate ============================= */
function googleTranslateElementInit() {
    new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
}

function triggerGoogleTranslate(lang) {
    let selectField = document.querySelector(".goog-te-combo");
    if (selectField) {
        selectField.value = lang; // Здесь укажите код языка, на который хотите перевести страницу
        selectField.dispatchEvent(new Event("change"));
    }
}

// Функция для изменения стиля body google translate
function adjustBodyStyle() {
    const bodyStyle = document.body.style;
    if (bodyStyle.top !== "0px") {
        bodyStyle.top = "0px";
    }
}

// Создание экземпляра MutationObserver google translate
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "style") {
            adjustBodyStyle();
        }
    });
});
// Настройка observer для отслеживания изменений атрибутов тега body
observer.observe(document.body, {
    attributes: true // Наблюдать только за изменениями атрибутов
});
// Не забудьте отключить observer, когда он больше не нужен, для избежания утечек памяти
// observer.disconnect();
/*i  ========================== end of google translate ========================= */

// sorting tables columns functions
const currentSort = {
    column: null,
    direction: 'asc'
};

// sort table by text char value
function sortTable(columnIndex, table_id = 0) {
    let tab_id = (table_id !== 0) ? table_id : "itemTable";
    let table, rows, switching, i, x, y, shouldSwitch;
    table = document.getElementById(tab_id);
    switching = true;

    // Определить направление сортировки
    if (currentSort.column === columnIndex) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.direction = 'asc';
        currentSort.column = columnIndex;
    }

    while (switching) {
        switching = false;
        rows = table.getElementsByTagName("TR");

        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[columnIndex];
            y = rows[i + 1].getElementsByTagName("TD")[columnIndex];

            // Сравнение строк в зависимости от направления сортировки
            if (currentSort.direction === 'asc') {
                if (y) {
                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                }
            } else if (currentSort.direction === 'desc') {
                if (y) {
                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
        }
    }
}

// sort table by number value
function sortNum(columnIndex, table_id = 0) {
    let table, rows, switching, i, x, y, shouldSwitch, xValue, yValue;
    let tab_id = (table_id !== 0) ? table_id : "itemTable";
    table = document.getElementById(tab_id);
    switching = true;

    // Определить направление сортировки
    if (currentSort.column === columnIndex) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.direction = 'asc';
        currentSort.column = columnIndex;
    }

    while (switching) {
        switching = false;
        rows = table.getElementsByTagName("TR");

        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[columnIndex];
            y = rows[i + 1].getElementsByTagName("TD")[columnIndex];

            // Конвертация текста в числа для корректного сравнения
            xValue = parseFloat(x.innerHTML);
            yValue = parseFloat(y.innerHTML);

            // Сравнение чисел в зависимости от направления сортировки
            if (currentSort.direction === 'asc') {
                if (xValue > yValue) {
                    shouldSwitch = true;
                    break;
                }
            } else if (currentSort.direction === 'desc') {
                if (xValue < yValue) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
        }
    }
}

// open global chat form
function openForm() {
    dom.show("#popup-window");
}

// close global chat form
function closeForm() {
    dom.hide("#popup-window");
}