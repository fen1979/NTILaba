// noinspection JSUnusedLocalSymbols,JSUnusedGlobalSymbols
// noinspection JSCheckFunctionSignatures

/*
 * Ajeco corp. ltd
 * created by Amir Aliev
 *
 * All code is provided "as is".
 * The creator assumes no responsibility for any consequences arising from the use of this code.
 * Financial and other liabilities do not extend to the use of the code provided in this file.
 * Anyone may use this code, modify and upgrade it at their discretion, pass it on to third parties, etc.
 * The original code author offers no warranties and assumes no obligations,
 * regardless of the code's functionality or other reasons and circumstances.
 *
 * NOTICE:
 * This disclaimer clarifies that the creator of the code is not liable for any direct, indirect, incidental, or consequential damages
 * that may result from the use of, or inability to use, the provided code.
 * This includes but is not limited to the loss of data or data being rendered inaccurate or losses sustained by you or third parties.
 *
 * Required carefull inspection and uses properties!!!
 *
 * Copyright Ajeco Foundation
 * Released under the MIT license
 * /public/LICENSE.txt
 * Date: 2023-08-28 13:37
 *
 * This file is all site use functions and constants
 * this scripts is loading first  be careful to change some functions!!!
 */

const BASE_URL = document.querySelector("base").baseURI; //"https://nti.icu/";
const dom = document;
const win = window;

/**
 * Добавляет обработчик события клика к документу, который инициирует клик на целевом элементе,
 * если источником события является элемент, соответствующий `triggerSelector`.
 *
 * // Пример использования функции
 * dom.doClick('.trigger-button', '#file-input', function (inputElement) {
 *     console.log('Файл выбран:', inputElement.files[0]);
 *     console.log('Событие вызвано элементом:', this);
 * });
 *
 * @param {string} triggerSelector - Селектор элемента, при клике на который будет инициирован клик на целевом элементе.
 * @param {string} targetSelector - Селектор целевого элемента, на который будет произведено событие клика.
 * @param callback - возвращает обьекты this и input на который был произведено нажатие
 */
dom.doClick = function (triggerSelector, targetSelector, callback = null) {
    document.addEventListener('click', function (event) {
        if (event.target.matches(triggerSelector)) {
            const targetElement = document.querySelector(targetSelector);
            if (targetElement) {
                targetElement.click();

                if (typeof callback === 'function') {
                    targetElement.addEventListener('change', function () {
                        if (callback && typeof callback === 'function') {
                            callback.call(event.target, this);
                        }
                    }, {once: true});
                }
            }
        }
    });
};

/**
 * Добавляет обработчик события клика к документу, который отправляет форму, если источником события
 * является элемент, соответствующий `triggerSelector`.
 * @param {string} triggerSelector - Селектор элемента, при клике на который будет отправлена форма.
 * @param {string} formSelector - Селектор формы, которая будет отправлена.
 */
dom.doSubmit = function (triggerSelector, formSelector) {
    dom.addEventListener('click', function (event) {
        if (event.target.matches(triggerSelector)) {
            const formElement = dom.querySelector(formSelector);
            if (formElement) {
                formElement.submit();
            }
        }
    });
};

/**
 * DOMContentLoaded required functions
 */
dom.addEventListener("DOMContentLoaded", function () {

    /**
     * Возвращает первый элемент DOM, соответствующий указанному селектору, и опционально выполняет коллбэк с этим элементом.
     * @param {string} selector - CSS-селектор для поиска элемента.
     * @param {function} [callback] - Функция, которая будет вызвана с найденным элементом в качестве контекста.
     * @returns {Element} Найденный элемент или `null`, если элемент не найден.
     */
    dom.e = function (selector, callback = null) {
        const elements = document.querySelectorAll(selector);

        // Если найден только один элемент, работаем с ним как с объектом
        if (elements.length === 1) {
            if (callback && typeof callback === 'function') {
                // Вызываем callback с найденным элементом в контексте `this`
                callback.call(elements[0]);
            }
            return elements[0];
        } else if (elements.length > 1) {
            // Если найдено несколько элементов, возвращаем NodeList
            if (callback && typeof callback === 'function') {
                elements.forEach(element => {
                    callback.call(element);
                });
            }
            return elements;
        }

        // Если элементы не найдены, возвращаем null или undefined
        return null; //elements[0];
    };

    /**
     * Устанавливает атрибут для всех элементов, соответствующих указанному селектору.
     * @param {string} selector - CSS-селектор для выбора элементов.
     * @param {string} attribute - Название атрибута, который нужно установить.
     * @param {string} value - Значение атрибута.
     */
    dom.eAll = function (selector, attribute, value) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.setAttribute(attribute, value);
        });
    };

    /**
     * Плавно отображает элемент, изменяя его CSS свойства `display` и `opacity`.
     * Так же назначает события нажатий вне окна и закрытия окна кнопкой закрыть и ESC
     * @param {string} selector - Селектор элемента, который будет показан.
     * @param {number|string} speed - Длительность анимации в миллисекундах или ключевые слова "slow" или "fast".
     * @param {boolean} blur - Затемнение задней части окна.
     */
    dom.show = function (selector, speed = "fast", blur = false) {
        const element = dom.e(selector);
        if (element) {
            // Проверяем, скрыт ли элемент
            const hasHiddenClass = element.classList.contains('hidden');
            const styleDisplay = element.style.display;
            const styleOpacity = element.style.opacity;
            const hasStyleDisplayNoneOpacityZero = (styleDisplay === 'none' && styleOpacity === '0');
            const computedDisplay = window.getComputedStyle(element).display === 'none';

            const isHidden = hasHiddenClass || hasStyleDisplayNoneOpacityZero || computedDisplay;

            if (isHidden) {
                // Определяем длительность анимации
                let duration = (speed === "slow" ? 600 : speed === "fast" ? 200 : speed) || 0;
                // Если элемент является модальным окном Bootstrap
                if (speed === "modal") {
                    new bootstrap.Modal(element).show("slow");
                } else {
                    // Удаляем класс 'hidden' если он есть
                    if (hasHiddenClass) {
                        element.classList.remove('hidden');
                    }

                    // Устанавливаем нужные стили
                    element.style.display = 'block';
                    element.style.opacity = "1";
                    element.style.transition = `opacity ${duration}ms`;

                    // Если элемент имел стили 'display: none; opacity: 0;', то обновляем их
                    if (hasStyleDisplayNoneOpacityZero) {
                        element.style.display = 'block';
                        element.style.opacity = "1";
                        element.style.transition = `opacity ${duration}ms`;
                    }

                    if (blur) element.classList.add("modal-blur");

                    // Добавляем небольшую задержку для применения стилей
                    setTimeout(() => {
                        element.style.opacity = "1";
                    }, 50);

                    // Устанавливаем обработчики
                    attachDismissHandlers(selector);
                    attachEscKeyHandler(selector);
                }
            } else {
                console.log("Элемент уже виден, пропускаем выполнение действий.");
            }
        }

        // Функция для установки обработчиков закрытия модального окна
        function attachDismissHandlers(selector) {
            // Найти все элементы с атрибутом data-aj-dismiss="modal"
            const elements = document.querySelectorAll('[data-aj-dismiss="modal"]');

            // Присвоить событие click каждому найденному элементу
            elements.forEach(element => {
                element.addEventListener('click', function () {
                    dom.hide(selector);
                });
            });
        }

        // Обработка нажатия клавиши ESC для скрытия
        function attachEscKeyHandler(selector) {
            function handleEscKey(event) {
                if (event.key === 'Escape') {
                    dom.hide(selector);
                    document.removeEventListener('keydown', handleEscKey);
                }
            }

            document.addEventListener('keydown', handleEscKey);
        }
    };

    /*dom.show = function (selector, speed = "fast", blur = false) {
        const element = dom.e(selector);
        if (element) {
            // Проверяем, скрыт ли элемент
            const isHidden = window.getComputedStyle(element).display === 'none';

            if (isHidden) {
                // Определяем длительность анимации
                let duration = (speed === "slow" ? 600 : speed === "fast" ? 200 : speed) || 0;
                // Если элемент является модальным окном Bootstrap
                if (speed === "modal") {
                    new bootstrap.Modal(element).show("slow");
                } else {
                    if (duration > 0) {
                        element.style.display = 'block';
                        element.style.opacity = "0";
                        element.style.transition = `opacity ${duration}ms`;
                        if (blur) element.classList.add("modal-blur");

                        setTimeout(() => {
                            element.style.opacity = "1";
                        }, 50); // Небольшая задержка, чтобы стили применились
                    } else {
                        // проверяем класслист элемента
                        if (element.classList && element.classList.contains('hidden')) {
                            element.classList.remove("hidden");
                            element.style.display = 'block';
                            element.style.opacity = "1";
                        } else {
                            // Элемент не содержит класс 'hidden'
                            // Показываем элемент, если он был скрыт
                            element.style.display = 'block';
                            element.style.opacity = "1";
                        }

                        if (blur) element.classList.add("modal-blur");
                    }

                    // Устанавливаем обработчики
                    attachDismissHandlers(selector);
                    attachEscKeyHandler(selector);
                }
            } else {
                console.log("Элемент уже виден, пропускаем выполнение действий.");
            }
        }

        // устанавливаем событие клик на кнопки закрытия модального окна
        function attachDismissHandlers(selector) {
            // Найти все элементы с атрибутом data-aj-dismiss="modal"
            const elements = document.querySelectorAll('[data-aj-dismiss="modal"]');

            // Присвоить событие click каждому найденному элементу
            elements.forEach(element => {
                element.addEventListener('click', function () {
                    dom.hide(selector);
                });
            });
        }

        // Обработка нажатия клавиши ESC для скрытия
        function attachEscKeyHandler(selector) {
            function handleEscKey(event) {
                if (event.key === 'Escape') {
                    dom.hide(selector);
                    document.removeEventListener('keydown', handleEscKey);
                }
            }

            document.addEventListener('keydown', handleEscKey);
        }
    };*/

    /**
     * Функция для обработки кликов по документу с учетом исключений.
     *
     * Используется для закрытия модального окна при клике вне указанных элементов,
     * переданных в массиве исключений. Если клик произошел по элементу или внутри элемента,
     * соответствующего селектору из массива исключений, модальное окно не закрывается.
     *
     * @function bodyClick
     * @param {string[]} exclusionSelectors - Массив селекторов, которые не должны закрывать модальное окно.
     *                                        Каждый элемент массива — это строка с CSS-селектором.
     *                                        Примеры:
     *                                        - "#id-el" — исключение по ID.
     *                                        - ".class-el" — исключение по классу.
     *                                        - "tag-el" — исключение по тегу.
     *
     * @param hiddingSelector - идентификатор элемента для скрытия на странице
     * _ по умолчанию это основное модальное окно ресурса
     * @example
     * // Инициализация с исключениями
     * document.bodyClick(["#tools-table", ".modal-content", "button"]);
     *
     * // Логика:
     * // - Клик по #tools-table → модальное окно не закрывается.
     * // - Клик по .modal-content → модальное окно не закрывается.
     * // - Клик по кнопке (button) → модальное окно не закрывается.
     * // - Клик по .other-content → модальное окно закрывается.
     *
     * @note Если переданный массив пуст, функция будет закрывать модальное окно при любом клике.
     * @note Убедитесь, что переданные селекторы корректны и соответствуют вашим элементам.
     *
     * @returns {void}
     */
    dom.bodyClick = function (exclusionSelectors, hiddingSelector = "#searchModal") {
        document.addEventListener("click", function (event) {
            const target = event.target;

            // Проверяем, есть ли совпадение с исключением
            const isExcluded = exclusionSelectors.some(selector => {
                // Если элемент или его родитель соответствует селектору
                return target.matches(selector) || target.closest(selector);
            });

            if (isExcluded) {
                console.info("Клик на элементе из исключений. Окно не закрывается.");
                return;
            }

            // Действие при клике вне исключений
            console.info("Клик вне исключений. Закрываем модальное окно.");
            dom.hide(hiddingSelector); // Закрываем модальное окно
        });
    };

    /**
     * Плавно скрывает элемент, изменяя его CSS свойство `opacity` и затем `display`.
     * @param {string} selector - Селектор элемента, который будет скрыт.
     * @param {number|string} speed - Длительность анимации в миллисекундах или ключевые слова "slow" или "fast".
     */
    dom.hide = function (selector, speed = "fast") {
        let element = dom.e(selector);
        if (element) {
            // Определяем длительность анимации
            let duration = speed === "slow" ? 600 : speed === "fast" ? 200 : 0;
            if (duration > 0) {
                // Применяем стили для плавного скрытия
                element.style.transition = `opacity ${duration}ms`;
                element.style.opacity = "0";
                // После завершения транзиции скрываем элемент
                setTimeout(() => {
                    element.classList.add("hidden");
                    element.style.display = "none";
                }, duration);
            } else {
                // Мгновенное скрытие элемента
                element.classList.add("hidden");
                element.style.display = "none";
            }
        }
    };

    /**
     * Переключает класс для всех элементов, соответствующих селектору.
     * @param {string} selector - Селектор элементов, для которых будет переключен класс.
     * @param {string} className - Название класса, который будет добавлен или удален.
     */
    dom.toggleClass = function (selector, className) {
        if (selector instanceof Element) {
            // Добавляем классы напрямую элементу
            selector.forEach(el => {
                el.classList.toggle(className);
            });
        } else {
            dom.querySelectorAll(selector).forEach(el => {
                el.classList.toggle(className);
            });
        }
    };

    /**
     *
     * @param selector
     * @param className
     */
    dom.addClass = function (selector, className) {
        // Проверяем, является ли targets элементом DOM
        if (selector instanceof Element) {
            // Добавляем классы напрямую элементу
            className.split(" ").forEach(cls => {
                selector.classList.add(cls);
            });
        } else {
            // Ищем элементы по селектору и добавляем классы каждому
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                className.split(" ").forEach(cls => {
                    element.classList.add(cls);
                });
            });
        }
    };

    /**
     *
     * @param selector
     * @param className
     */
    dom.removeClass = function (selector, className) {
        // Проверяем, является ли targets элементом DOM
        if (selector instanceof Element) {
            // Удаляем классы напрямую у элемента
            className.split(" ").forEach(cls => {
                selector.classList.remove(cls);
            });
        } else {
            // Ищем элементы по селектору и удаляем классы у каждого
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                className.split(" ").forEach(cls => {
                    element.classList.remove(cls);
                });
            });
        }
    };

    /**
     * window reset post/get
     */
    win.cleanWindow = function () {
        if (win.history.replaceState) {
            win.history.replaceState(null, null, win.location.href);
        }
    }

    /**
     * This function control the scroll changes and add some class to some element
     * @param selector
     * @param styleClass
     */
    win.scrollController = function (selector, styleClass) {
        win.addEventListener("scroll", function () {
            // Получаем все элементы по селектору
            const elements = document.querySelectorAll(selector);
            // Проверяем, найдены ли элементы
            if (elements.length > 0) {
                elements.forEach(element => {
                    if (win.scrollY > 0) {
                        element.classList.add(styleClass);
                    } else {
                        element.classList.remove(styleClass);
                    }
                });
            }
        });
    };

    /**
     * Предоставляет предварительный просмотр файла, выбранного в input[type="file"], в указанном элементе.
     * @param {string} source - Идентификатор элемента input, откуда берётся файл.
     * @param {string} target - Идентификатор элемента (img или video), куда выводится предпросмотр.
     * @param {function} callback - Функция обратного вызова, вызываемая после загрузки файла.
     */
    dom.doPreviewFile = function (source, target, callback = null) {
        const fileInput = dom.e(source);
        const targetElement = dom.e(target);

        if (!fileInput) {
            console.log("Input element not found: #" + source);
            return; // Прекращаем выполнение, если элемент ввода не найден
        }
        if (!targetElement) {
            console.log("Target element not found: #" + target);
            return; // Прекращаем выполнение, если целевой элемент не найден
        }

        // Настраиваем обработчик события изменения для входного элемента
        fileInput.addEventListener('change', function (event) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    // проверяем класслист элемента
                    if (targetElement.classList && targetElement.classList.contains('hidden')) {
                        // Элемент содержит класс 'hidden' удаляем класс для показа элемента
                        targetElement.classList.remove("hidden");
                    } else {
                        // Элемент не содержит класс 'hidden'
                        // Показываем элемент, если он был скрыт
                        targetElement.style.display = 'block';
                    }

                    // Устанавливаем в качестве источника изображения результат чтения файла
                    targetElement.src = e.target.result;

                    if (typeof callback === 'function') {
                        //callback(); // Вызываем callback, если он задан
                        callback.call(this, event);
                    }
                };
                // Читаем файл и конвертируем его в Data URL
                reader.readAsDataURL(this.files[0]);
            }
        });
    };

    /**
     * Управляет маршрутизацией внутри приложения, обрабатывая клики по элементам с указанным селектором и отправляя форму.
     * @param {string} selector - Селектор элементов, по которым клик инициирует отправку формы.
     * @param {string} form_id - Идентификатор формы, которая будет отправлена.
     */
    dom.doRouting = function (selector, form_id) {
        // Вешаем обработчик на document, который будет ловить клики по всем элементам
        dom.addEventListener("click", function (event) {
            // Проверяем, соответствует ли элемент, по которому был совершен клик, нужному селектору
            const target = event.target.closest(selector);
            if (target) {
                let blank = target.dataset.blank ?? '0';
                routeLocation(event, target.value, form_id, blank);
            }
        });

        function routeLocation(event, url, form_id, blank = '0') {
            // Получаем форму по её идентификатору
            let form = dom.getElementById(form_id);
            if (!form) {
                console.error("Routing form not found");
                return;
            }

            // Если нажата Ctrl или Command, открываем в новой вкладке
            let isCtrlOrCmdPressed = event.ctrlKey || event.metaKey || blank === '1';
            form.target = isCtrlOrCmdPressed ? "_blank" : "_self";

            // Устанавливаем действие и отправляем форму
            form.action = "/" + url;
            form.submit();
        }
    };

    /**
     * Добавляет всплывающие подсказки к элементам, используя атрибут с определённым названием.
     * @param {string} selector - Название атрибута, содержащего текст подсказки.
     * @param {object} styles - Объект со стилями для подсказок.
     */
    dom.doTitleView = function (selector, styles) {
        dom.querySelectorAll("[" + selector + "]").forEach(element => {
            element.onmouseover = function (event) {
                let tooltipSpan = dom.createElement("span");
                tooltipSpan.style.position = "absolute";
                tooltipSpan.style.zIndex = "99999";
                tooltipSpan.style.width = styles.width;
                tooltipSpan.style.height = styles.height;
                tooltipSpan.style.backgroundColor = styles.bg_color;
                tooltipSpan.style.color = styles.color;
                tooltipSpan.style.padding = styles.padding;
                tooltipSpan.style.boxShadow = "1px 1px 3px #424040";
                tooltipSpan.style.border = "1px solid #424040";
                tooltipSpan.style.borderRadius = "5px";
                tooltipSpan.style.whiteSpace = "wrap";
                tooltipSpan.style.visibility = "hidden";
                tooltipSpan.style.opacity = "0";
                tooltipSpan.innerHTML = element.getAttribute(selector);
                dom.body.appendChild(tooltipSpan);

                let tooltipWidth = tooltipSpan.offsetWidth;
                let pageWidth = dom.body.scrollWidth;
                let elementRect = event.target.getBoundingClientRect();
                let elementRightEdge = elementRect.right + tooltipWidth;

                if (elementRightEdge > pageWidth) {
                    // Position tooltip to the left of the element if it goes off-screen
                    tooltipSpan.style.left = (elementRect.left - tooltipWidth) + "px";
                } else {
                    // Standard positioning to the right of the element
                    tooltipSpan.style.left = elementRect.right + "px";
                }
                tooltipSpan.style.top = elementRect.top + "px";
                tooltipSpan.style.visibility = "visible";
                tooltipSpan.style.opacity = "1";

                element.onmouseout = function () {
                    dom.body.removeChild(tooltipSpan);
                };
            };
        });
    };

    /**
     * Переключает тип поля ввода между 'password' и 'text', позволяя показать или скрыть содержимое.
     * @param {string} inputSelector - Селектор полей ввода, которые нужно переключить.
     * @param {string} triggerSelector - Селектор элементов, по клику на которые происходит переключение.
     */
    dom.unhidePassword = function (inputSelector, triggerSelector) {
        // Найти все триггеры
        dom.querySelectorAll(triggerSelector).forEach(trigger => {
            // Добавляем обработчик клика на каждый триггер
            trigger.addEventListener('click', function () {
                // Переключение типа каждого поля ввода
                dom.querySelectorAll(inputSelector).forEach(input => {
                    if (input.type === "password") {
                        input.type = "text";
                    } else if (input.type === "text") {
                        input.type = "password";
                    }
                });
            });
        });
    };

    /**
     * Выполняет анимацию исчезновения для элементов, соответствующих селектору, после задержки.
     * @param {string} selector - Селектор анимируемых элементов.
     * @param {number} speed - Длительность анимации в миллисекундах.
     * @param {number} timeOut - Время задержки перед началом анимации в миллисекундах.
     */
    dom.doAnimation = function (selector, speed, timeOut) {
        setTimeout(function () {
            dom.querySelectorAll(selector).forEach(element => {
                element.animate({
                    opacity: [1, 0], // Старт с 1, конец на 0
                    height: ['auto', 0],
                    paddingTop: [element.style.paddingTop, 0],
                    paddingBottom: [element.style.paddingBottom, 0],
                    marginTop: [element.style.marginTop, 0],
                    marginBottom: [element.style.marginBottom, 0]
                }, {
                    duration: speed,
                    fill: 'forwards' // Указываем, чтобы стили "заморозились" на последнем кадре
                });
            });
        }, timeOut);
    };

    /**
     * specific on change action listener for an inputs
     * @param {string} inputSelector - Селектор инпута события которого отслеживаются
     * @param {function} callback - Функция обратного вызова, вызываемая после изменений инпута.
     */
    dom.onInputsChange = function (inputSelector, callback) {
        dom.querySelectorAll(inputSelector).forEach(input => {
            input.addEventListener("change", function (event) {
                if (typeof callback === 'function') {
                    callback.call(input, event); // Вызываем callback, если он задан
                }
            });
        });
    };

    /**
     * Добавляет обработчик событий к родительскому элементу, который реагирует на события, возникающие на потомках,
     * соответствующих указанному селектору.
     * @param {string} types - Типы событий, которые должен обрабатывать обработчик, разделённые запятой (например, 'click,change').
     * @param {string} selector - Селектор потомков, для которых должен срабатывать обработчик.
     * @param {function} callback - Функция обратного вызова, вызываемая при срабатывании события.
     * @param {string} [parentSelector='body'] - Селектор родительского элемента, к которому привязывается обработчик.
     */
    dom.in = function (types, selector, callback, parentSelector) {
        // Если родитель не задан, используем 'body' по умолчанию
        const parent = dom.querySelector(parentSelector || 'body');
        const selectors = selector.split(',').map(s => s.trim()); // Разделяем селекторы и удаляем лишние пробелы

        parent.addEventListener(types, function (event) {
            // Проверяем, что элемент, вызвавший событие, соответствует хотя бы одному из селекторов
            for (const sel of selectors) {
                if (event.target.closest(sel)) {
                    if (typeof callback === 'function') {
                        callback.call(event.target, event); // Устанавливаем контекст this в callback
                    }
                    break; // Если нашли соответствие, выходим из цикла
                }
            }
        });
    };

    // dom.in = function (types, selector, callback, parentSelector) {
    //     // Если родитель не задан, используем 'body' по умолчанию
    //     const parent = dom.querySelector(parentSelector || 'body');
    //
    //     parent.addEventListener(types, function (event) {
    //         // Проверяем, что элемент, вызвавший событие, соответствует селектору
    //         if (event.target.closest(selector)) {
    //             if (typeof callback === 'function') {
    //                 callback.call(event.target, event); // Устанавливаем контекст this в callback
    //             }
    //         }
    //     });
    // };

    /**
     * Добавляет обработчик событий к каждому элементу из выборки, который реагирует на события, возникающие непосредственно на этих элементах.
     * @param {string} types - Типы событий, которые должен обрабатывать обработчик, разделённые запятой (например, 'click,change').
     * @param {string} selector - Селектор элементов, к которым добавляется обработчик событий.
     * @param {function} callback - Функция обратного вызова, вызываемая при срабатывании события.
     */
    dom.inAll = function (types, selector, callback) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.addEventListener(types, function (event) {
                if (typeof callback === 'function') {
                    callback.call(element, event);
                }
            });
        });
    };

    /**
     * Добавляет обработчик событий к родительскому элементу, который реагирует на события, возникающие на потомках,
     * соответствующих указанному селектору.
     *
     * Пример использования
     * dom.inMulty('input,change', 'input[required], textarea[required], input[type="checkbox"]', function (event) {
     *     console.log(`Событие ${event.type} на элементе ${this.tagName} с селектором ${event.target}`);
     * });
     *
     * @param {string} types - Типы событий, которые должен обрабатывать обработчик, разделённые запятой (например, 'click,change').
     * @param {string} selectors - Селекторы потомков, для которых должен срабатывать обработчик, разделённые запятой.
     * @param {function} callback - Функция обратного вызова, вызываемая при срабатывании события.
     * @param {string} [parentSelector='body'] - Селектор родительского элемента, к которому привязывается обработчик.
     */
    dom.inMulty = function (types, selectors, callback, parentSelector) {
        // Если родитель не задан, используем 'body' по умолчанию
        const parent = dom.querySelector(parentSelector || 'body');

        // Разделяем типы событий и селекторы
        const eventTypes = types.split(',');
        const selectorList = selectors.split(',');

        eventTypes.forEach(type => {
            parent.addEventListener(type.trim(), function (event) {
                // Проверяем каждый селектор в списке селекторов
                selectorList.forEach(selector => {
                    if (event.target.closest(selector.trim())) {
                        if (typeof callback === 'function') {
                            callback.call(event.target, event); // Устанавливаем контекст this в callback
                        }
                    }
                });
            });
        });
    };

    /**
     * searching function set requests to some server and back responce for preview to page
     * function have some default parameters for haeding !!!
     * additional Json of headers can be added by using args property
     * using default headers ags properti have be null !!!
     * @param selector
     * @param eventType
     * @param dataAttribute
     * @param args
     * @docs args = {"Content-Type": "application/x-www-form-urlencoded"} dy default
     * @param callback
     * @docs callbacr return (error, result, event)
     */
    dom.makeRequest = function (selector, eventType, dataAttribute, args, callback) {
        dom.querySelectorAll(selector).forEach(input => {
            input.addEventListener(eventType, function (event) { // Добавляем параметр event
                let search = this.value;
                let req = this.getAttribute(dataAttribute);
                let addons = this.getAttribute("data-additions");
                let body = (req !== undefined)
                    ? `suggest=${encodeURIComponent(search)}&request=${encodeURIComponent(req)}&additions=${encodeURIComponent(addons)}`
                    : `suggest=${encodeURIComponent(search)}&additions=${encodeURIComponent((addons !== undefine) ? addons : "")}`;

                const headers = (args.headers === null) ? {"Content-Type": "application/x-www-form-urlencoded"} : args.headers;
                fetch(args.url, {
                    method: args.method,
                    headers: headers,
                    body: body
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(result => {
                        if (typeof callback === 'function') {
                            callback(null, result, event, this); // Передаем event в коллбек
                        }
                    })
                    .catch(error => {
                        if (typeof callback === 'function') {
                            callback(error, null, event, this); // Передаем event в коллбек при ошибке
                        }
                    });
            });
        });
    };

    /**
     * Checks if all required fields in a form are filled and enables/disables the submit button accordingly.
     *
     * @param {string} form_id - The ID of the form to check.
     * @param {string} button_id - The ID of the submit button to enable/disable.
     *
     * This function ensures that all required fields (input, select, textarea) in the form are filled.
     * If all required fields are filled, the submit button is enabled. Otherwise, it is disabled.
     * The check is performed both on form load and on any input/change event within the form.
     *
     * Example usage:
     * dom.checkForm('myForm', 'submitBtn');
     */
    dom.checkForm = function (form_id, button_id) {
        // Get the form and the submit button elements by their IDs
        const form = document.getElementById(form_id);
        const saveBtn = document.getElementById(button_id);

        // Function to check if all required fields are filled
        function checkForm() {
            // Select all required input, select, and textarea elements within the form
            const requiredElements = form.querySelectorAll('input[required], select[required], textarea[required]');
            let allFilled = true;

            // Iterate over each required element to check if it's filled
            requiredElements.forEach(element => {
                if (!element.value.trim()) {
                    allFilled = false;
                }
            });

            // Enable or disable the submit button based on whether all required fields are filled
            saveBtn.disabled = !allFilled;
        }

        // Add event listeners to check the form on input and change events
        form.addEventListener('input', checkForm);
        form.addEventListener('change', checkForm);

        // Initial check when the page loads
        checkForm();
    };

    /**
     * Функция dom.requestOnFly предназначена для асинхронной отправки данных формы на сервер с использованием fetch API. Она предотвращает перезагрузку страницы при отправке формы и вызывает callback-функцию с ответом сервера или ошибкой.
     *
     * Параметры:
     * @param event (string): Тип события, который будет отслеживаться (например, 'submit').
     * @param target (string): CSS селектор формы, для которой будет установлен обработчик события.
     * @param callback (function): Функция обратного вызова, которая будет вызвана с ответом сервера или ошибкой.
     * @param routing (string, optional): URL, на который будет отправлен запрос. Если не указан, будет использован URL из атрибута action формы.
     * Описание:
     * Событие: Функция обрабатывает указанный тип события (например, 'submit') для указанного CSS селектора формы.
     * Предотвращение перезагрузки: Предотвращает стандартное поведение формы, которое приводит к перезагрузке страницы.
     * Сбор данных: Собирает данные из формы, используя объект FormData.
     * Настройка запроса: Настраивает параметры для fetch-запроса, включая метод 'POST' и тело запроса с данными формы.
     * Отправка данных: Отправляет данные на сервер по указанному URL или URL из атрибута action формы.
     * Обработка ответа: Обрабатывает ответ сервера, предполагая, что сервер возвращает JSON. Вызывает callback-функцию с ответом сервера или ошибкой.
     *
     * // Пример вызова функции для отправки формы с id "form_id"
     * dom.requestOnFly("submit", "#form_id", function (response, error, event) {
     *     if (error) {
     *         console.error('Произошла ошибка:', error);
     *         return;
     *     }
     *     let someDataFromServer = response;
     *     dom.e("#someId").innerText = someDataFromServer.someData;
     * }, "request_page"); or some url like "example.php"
     */
    dom.requestOnFly = function (event, target, callback, routing = null) {
        // Обработчик события отправки формы
        document.addEventListener(event, function (e) {
            // Проверяем, что событие произошло на целевой форме
            if (e.target && e.target.matches(target)) {
                e.preventDefault(); // Предотвращаем перезагрузку страницы

                // Сбор данных из формы
                const form = e.target;
                const formData = new FormData(form);
                // console.log(formData);

                // Настройки для fetch-запроса
                const requestOptions = {
                    method: 'POST',
                    body: formData
                };

                // URL для отправки запроса
                const url = routing || form.action;

                // Отправка данных на сервер
                fetch(url, requestOptions)
                    .then(response => response.json()) // Предполагаем, что сервер возвращает JSON
                    //.then(response => response) // Предполагаем, что сервер возвращает JSON
                    .then(data => {
                        // Вызываем callback с ответом сервера
                        callback(data, null, e);
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        // Вызываем callback с ошибкой
                        callback(null, error, e);
                    });
            }
        });
    }

    // Функция для установки обработчиков событий на аккордеоны
    dom.setAccordionListeners = function (mainSelector, accSelector, eventType) {
        const toggles = document.querySelectorAll(mainSelector);

        toggles.forEach(toggle => {
            toggle.addEventListener(eventType, function () {
                const content = this.nextElementSibling;

                // Close all open accordions except the one clicked
                document.querySelectorAll(accSelector).forEach(acc => {
                    if (acc !== content) {
                        acc.style.display = 'none';
                    }
                });

                // Toggle the clicked accordion
                content.style.display = content.style.display === 'table-row' ? 'none' : 'table-row';
            });
        });
    }

    dom.hideEmptyColumnsInTable = function (selector, callback) {
        const table = dom.querySelector(selector);
        const rows = table.rows;
        const columnCount = rows[0].cells.length;

        for (let i = 0; i < columnCount; i++) {
            let isEmptyColumn = true;

            // Проверяем только tbody, начиная со второй строки
            for (let j = 1; j < rows.length; j++) {
                const cell = rows[j].cells[i];
                if (cell && cell.textContent.trim() !== '' && cell.textContent.trim() !== 'N/A') {
                    isEmptyColumn = false;
                    break;
                }
            }

            // Если колонка пустая, скрываем её
            if (isEmptyColumn) {
                for (let j = 0; j < rows.length; j++) {
                    rows[j].cells[i].style.display = 'none';
                }
            }
        }
    }

}); // end dom loaded

// older version for code

// dom.in = function (types, selector, callback) {
//     dom.querySelectorAll(selector).forEach(element => {
//         element.addEventListener(types, function (event) {
//             if (typeof callback === 'function') {
//                 callback.call(this, event); // Используем call для установления контекста this в callback
//             }
//         });
//     });
// };

// dom.e = function (selector) {
//     return dom.querySelector(selector);
// };

// dom.show = function (selector, args, speed) {
//     const element = dom.e(selector);
//     if (element) {
//         // Определяем длительность анимации
//         let duration = speed === "slow" ? 600 : speed === "fast" ? 200 : 0;
//         if (duration > 0) {
//             // Применяем стили для плавного скрытия
//             element.style.transition = `opacity ${duration}ms`;
//             element.style.opacity = "1";
//             // После завершения транзиции скрываем элемент
//             setTimeout(() => {
//                 element.style.display = 'block';
//             }, duration);
//         } else {
//             // Открываем модальное окно
//             if (args === "modal") {
//                 new bootstrap.Modal(element).show(speed);
//             } else {
//                 // Мгновенное Открытие элемента
//                 element.style.display = 'block';
//             }
//         }
//     }
// };

// dom.doRouting = function (selector, form_id) {
//     dom.querySelectorAll(selector).forEach(button => {
//         button.addEventListener("click", function (event) {
//             console.log("hi");
//             routeLocation(event, this.value, form_id);
//         });
//     });
//
//     // добавить дата-таргет для указания открытия в новом окне из кнопок напрямую
//     function routeLocation(event, url, form_id) {
//         // Получаем форму по её идентификатору
//         let form = dom.getElementById(form_id);
//         if (!form) {
//             console.error("Routing form not found");
//             return;
//         }
//
//         // Если нажата Ctrl или Command, открываем в новой вкладке
//         let isCtrlOrCmdPressed = event.ctrlKey || event.metaKey;
//         form.target = isCtrlOrCmdPressed ? "_blank" : "_self";
//
//         // Устанавливаем действие и отправляем форму
//         form.action = "/" + url;
//         form.submit();
//     }
// };

// dom.e = function (selector, callback = null) {
//     const element = dom.querySelector(selector);
//     if (element && typeof callback === 'function') {
//         // Вызываем callback с this, указывающим на найденный элемент
//         callback.call(element);
//     }
//     // даже если callback передан, возвращаем элемент
//     // можно работать напрямую с элементом
//     return element;
// };