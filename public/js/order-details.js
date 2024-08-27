document.addEventListener("DOMContentLoaded", function () {
    // Начальная проверка при загрузке страницы
    checkCheckboxes();

    // при загрузке в чате прокручиваем в конец чата
    let chatContainer = $("#chatWindow");
    // Флаг для отслеживания изменений на странице
    let isDirty = false;

    // noinspection JSValidateTypes
    chatContainer.scrollTop(chatContainer.prop("scrollHeight"));

    // закрываем страницу и перекидываем пользователя на прошлую вкладку
    dom.in("click", "#closeButton", function () {
        if (window.opener) {
            if (isDirty && !confirm('You have unsaved changes. Are you sure you want to leave?')) {
                return; // Пользователь отказался закрывать страницу
            }
            localStorage.setItem('openedByWindowOpen', 'true');
            // Если окно было открыто через window.open
            localStorage.removeItem('openedByWindowOpen');
            window.close(); // Закрыть окно
        } else {
            // Если страница открыта напрямую
            localStorage.removeItem('openedByWindowOpen');
            // возвращаем пользователя на главную страницу
            window.location.href = '/order';
        }
    });

    // выводим диалоговое окно редактирования или удаления сообщения
    dom.in("click", ".msg-url", function () {
        dom.e("#chatMessage").textContent = this.textContent.trim();
        dom.eAll(".actionButtons", "value", this.getAttribute('data-msgid'));
        dom.show("#chatModalDialog", "slow");
    });

    /* закрытие модального диалога редактирвания или удаления сообщения */
    dom.in("click", "#chatModalDialogClose", function () {
        dom.hide("#chatModalDialog", "slow");
    });

    // запрос на создание файла БОМ для заказа/проекта
    const args = {method: "POST", url: BASE_URL + "create_bom", headers: null};
    dom.makeRequest("#download_bom", "click", null, args, function (error, result, _) {
        console.log(result)
        if (error) {
            console.error('Error during fetch:', error);
            return;
        }
        if (result === "success") {
            dom.show("#download_link");
            dom.hide("#download_bom");
        }
        if (result === "error") alert("File not created try again or ...");
    });

    // Подписка на изменения в полях ввода, чекбоксах и радиокнопках
    dom.onInputsChange('input, textarea, select', setDirty);

    // Дополнительно можно отслеживать нажатия на клавиши в текстовых полях
    dom.inAll("keypress", 'input[type="text"], textarea', setDirty);

    // script для переключения табов
    dom.in("click", ".nav-link", function () {
        // Получаем ID целевого таба
        let tabId = this.getAttribute("data-bs-target");
        let targetTab = dom.e(tabId);
        // получаем текущий URL страницы
        let newUrl = new URL(win.location);
        // добавляем номер таба в URL для перезагрузок
        newUrl.searchParams.set('tab', tabId.substring(1));
        // сохраняем этот URL в историю браузера
        history.pushState(null, '', newUrl);
        // Удаляем класс active со всех табов
        dom.removeClass('.nav-link', 'active');
        // Добавляем класс active к текущему табу
        dom.addClass(this, "active");
        // Убираем класс show и active со всех табов
        dom.removeClass('.tab-pane', 'show active');
        // Добавляем классы show и active к целевому табу
        dom.addClass(targetTab, "active show");

        // при переходе на чат прокручиваем в конец чата
        let chatContainer = dom.e("#chatWindow");
        // noinspection JSValidateTypes
        chatContainer.scrollTop = chatContainer.scrollHeight;
    });

    /* делаем кнопку отправить сообщение кликабельной  */
    dom.onInputsChange("#chatTextArea", function () {
        dom.e("chat-send-button").disabled = false;
        //$("#chat-send-button").prop('disabled', false);
    });

    /* триггер для взятия файла (симулятор нажатия кнопки) */
    dom.doClick("#getFileContent", "#fileToTake");

    /* проверка файла на его размер и вывод информации в форму */
    dom.onInputsChange("#fileToTake", function () {
        let file = this.files[0];
        let maxSize = 300 * 1024 * 1024; // Максимальный размер файла 300MB

        if (file) {
            if (file.size > maxSize) {
                dom.e("#chat-send-button").disabled = true;
                alert("Size for choosed file more than 300MB.");
                // Очищаем выбранный файл
                this.value = '';
                dom.hide("#fileNamePreviewContainer");
                dom.e("#fileNamePreview").textContent = "";
            } else {
                dom.e("#chat-send-button").disabled = false;
                dom.show("#fileNamePreviewContainer");
                dom.e("#fileNamePreview").textContent = file.name;
            }
        }
    });

    // При изменении состояния любого чекбокса вызываем функцию проверки
    dom.onInputsChange('.form-check-input', checkCheckboxes);

    // изменение фото шага при клике на него (tab6 project steps)
    dom.in("click", "img.shrincable", function () {
        this.classList.toggle("full-size");
    });

    /* при зажатой кнопке CTRL/COMMAND  появляется опция отметить все чкбоксы сразу кликнув по кнопке */
    const ckbx = dom.e('.workflow');
    // Проверяем, существует ли элемент с id 'check-all-once'
    let checkAllBtn = dom.e("#check-all-once");

    // Detect when the CTRL or COMMAND key is pressed down
    document.addEventListener('keydown', function (event) {
        if (event.ctrlKey || event.metaKey) {
            if (checkAllBtn) {
                checkAllBtn.classList.remove("hidden");
            }
        }
    });

    // Detect when the CTRL or COMMAND key is released
    document.addEventListener('keyup', function (event) {
        if (!event.ctrlKey && !event.metaKey) {
            if (checkAllBtn) {
                checkAllBtn.classList.add("hidden");
            }
        }
    });

    if (checkAllBtn) {
        // Обработчик клика по кнопке для отмечания всех чекбоксов
        checkAllBtn.addEventListener('click', function () {
            ckbx.forEach(function (item) {
                item.checked = !item.checked; // Отмечаем чекбокс
            });
            // разблокировка кнопки старт
            checkCheckboxes();
        });
    }

    /* =============================== audio recording scripts ============================== */
    let mediaRecorder;
    let audioChunks = [];
    // URL для аудиофайла
    let audioUrl;

    const recordButton = dom.e("#recordButton");
    // Инпут для файла
    const audioInput = dom.e("#fileToTake");
    // Форма
    // const form = dom.e("form");

    /* начинаем запись голосового сообщения */
    const startRecording = () => {
        startRotating();
        navigator.mediaDevices.getUserMedia({audio: true})
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                mediaRecorder.start();

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    stopRotating();
                    const audioBlob = new Blob(audioChunks, {type: 'audio/wav'});
                    audioUrl = URL.createObjectURL(audioBlob);
                    setupAudioForSending(audioBlob);
                };
            })
            .catch(error => console.log(error));
    };

    /* добавляем записанный файл к форме отправки сообщения */
    const setupAudioForSending = (audioBlob) => {
        // Создаем новый объект File из Blob
        const file = new File([audioBlob], "audio_message.wav", {type: 'audio/wav'});
        // Создаем новый DataTransfer объект и добавляем в него файл
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        // Обновляем инпут файлом
        audioInput.files = dataTransfer.files;
        // Отображаем имя файла пользователю
        dom.e("#fileNamePreview").textContent = file.name;
        dom.removeClass("#fileNamePreviewContainer", "hidden");
    };

    /* останавливаем запись сообщения */
    const stopRecording = () => {
        if (mediaRecorder) {
            mediaRecorder.stop();
        }
    };

    /* вращение колес старт */
    function startRotating() {
        dom.addClass("#gear-container", "display");
        dom.addClass("#gear1", "rotate-clockwise");
        dom.addClass("#gear2", "rotate-anticlockwise");
    }

    /* вращение колес стоп */
    function stopRotating() {
        dom.removeClass("#gear-container", "display");
        dom.removeClass("#gear1", "rotate-clockwise");
        dom.removeClass("#gear2", "rotate-anticlockwise");
    }

    /* вешаем события на кнопку записи для мобилок и компов */
    recordButton.addEventListener("touchstart", startRecording);
    recordButton.addEventListener("touchend", stopRecording);
    recordButton.addEventListener("mousedown", startRecording);
    recordButton.addEventListener("mouseup", stopRecording);

    // Функция для установки флага при изменении данных
    function setDirty() {
        isDirty = true;
    }

    // i ========================================= SMT LINE scripts actions
    // поиск и установка номера фидера для компонента
    dom.in('keyup', '#feederInput', function () {
        const inputValue = parseInt(this.value, 10); // Преобразовать введённый текст в число
        const selectElement = dom.e('#feeder'); // Получить элемент селекта
        const errorMessage = dom.e('#error-message'); // Получить элемент для сообщения об ошибке
        const counter = selectElement.dataset.counter;

        if (!isNaN(inputValue)) {
            if (inputValue >= 1 && inputValue <= counter) {
                errorMessage.style.display = 'none'; // Скрыть сообщение об ошибке

                selectElement.value = inputValue; // Установить значение для селекта, если входит в допустимый диапазон
                setTimeout(function () {
                    selectElement.dispatchEvent(new Event('change')); // Инициировать событие 'change'
                }, 1500);
            } else {
                // Показать сообщение об ошибке, если номер вне допустимого диапазона
                errorMessage.textContent = 'There is no such feeder in the device!';
                errorMessage.style.display = 'block'; // Показать элемент с ошибкой
            }
        } else {
            errorMessage.style.display = 'none'; // Скрыть сообщение об ошибке при нечисловом вводе
        }
    });

    dom.onInputsChange("#feeder", function () {
        if (this.value)
            dom.e("#cell-" + this.value).classList.add("cell-in-use");
    });

    // проверка на совпадение part_number компонента и введенного part_number компонента пользователем
    dom.onInputsChange("#part_number", function () {
        const part_n = dom.e("#part_number_ref");
        if (part_n) {
            const errorMessage = dom.e('#error-message'); // Получить элемент для сообщения об ошибке
            const submitBtn = dom.e("#submit-btn");
            const v_1 = this.value.trim().toLowerCase();
            const v_2 = part_n.textContent.trim().toLowerCase();
            if (v_1 === v_2) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
                errorMessage.style.display = 'block'; // Показать элемент с ошибкой
                errorMessage.textContent = 'This Manufacture P/N is no compatible with needed P/N!';
            }
        }
    });
});// end of onload

// Функция проверки состояния чекбоксов
function checkCheckboxes() {
    // Проверяем, есть ли отмеченные чекбоксы
    const checkedCheckboxes = dom.e('.workflow:checked');
    const allCheckboxes = dom.e('.workflow');

    // Если отмеченных чекбоксов нет, устанавливаем длину в 0
    const checkedLength = checkedCheckboxes ? checkedCheckboxes.length : 0;

    // Проверяем, отмечены ли все чекбоксы
    const allChecked = allCheckboxes != null && allCheckboxes.length === checkedLength;

    // Включаем или отключаем кнопку в зависимости от результатов проверки
    const initButton = dom.e('#order-progress-init');
    if (initButton) {
        initButton.disabled = !allChecked;
    }
}

