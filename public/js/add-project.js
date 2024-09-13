/*
* обработка отметки чекбоксов в таблице выбора инструментов для проекта находится в файле
* dlobalScripts.js на 90 линии файла
* функция : dom.makeRequest(".searchThisClick", "click", "data-request", args, function (error, result, event, _)
* */
document.addEventListener("DOMContentLoaded", function () {
    // click on upload files for project button
    dom.doClick("#projects_files_btn", "#projects_files");
    // click on upload PDF files for project
    dom.doClick("#pickFile", "#pdf_file");
    // Предотвращение переключения чекбокса при прямом его клике
    dom.in("click", "input[type=\"checkbox\"]", function (e) {
        e.stopPropagation();
    });
    // Обработчик события изменения для инпута с файлами
    dom.onInputsChange("#projects_files", function () {
        if (this.files) {
            let fileCount = this.files.length;
            dom.e("#pick_files_text").textContent = "Picked Files - " + fileCount + "pcs";
        }
    });
    // Обработчик события изменения для инпута PDF
    dom.onInputsChange("#pdf_file", function () {
        let fileCount = this.files.length;
        if (fileCount > 0) {
            // выводим имя первого выбранного файла
            dom.e("#pickFile").textContent = this.files[0].name;
        } else {
            // Если файлы не выбраны, возвращаем базовый текст
            dom.e("#pickFile").textContent = "Upload Project Documentation (PDF Only)";
        }
    });

    // Обработка клика по результату поиска клиента
    dom.in("click", "#search-responce tr.customer", function () {
        if (this.parentElement.dataset.info) {
            // Извлекаем и парсим данные из атрибута data-info
            let info = JSON.parse(this.parentElement.dataset.info);
            // Устанавливаем полученные значения в поля ввода
            dom.e("#customerName").value = info.name; // Устанавливаем имя клиента
            dom.e("#customerId").value = info.clientID; // Устанавливаем ID клиента
            dom.e("#priorityMakat").value = info.priority; // Устанавливаем приоритет
            dom.e("#headPay").value = info.headpay; // Устанавливаем приоритет

            // Очищаем результаты поиска
            dom.hide("#searchModal");
        }
    });

    // выбор чекбокса (не обязательные фото в шагах проекта) при выборе СМТ проект
    dom.in("change", "#project_type", function () {
        dom.e("#sub_assembly").checked = this.checked;
    });

    // Включаем кнопку "Создать проект" при заполнении обязательных полей и выборе минимум 3 чекбоксов
    const form = dom.e('#createProjectForm');

    function enableSubmitButton(enable) {
        dom.e('#createProjectBtn').disabled = !enable;
    }

    function checkForm() {
        //let checkedCount = form.querySelectorAll('input[type="checkbox"]:checked').length;
        let requiredFieldsFilled = true;
        let projectNameValid = true; // По умолчанию предполагаем, что имя проекта валидно
        let unit_id = "";

        // Проверяем, заполнены ли все обязательные поля
        form.querySelectorAll('input[required], textarea[required]').forEach(function (field) {
            if (field.value.trim() === '') {
                requiredFieldsFilled = false;
            }
        });

        // Только если все обязательные поля заполнены, проверяем уникальность имени
        if (requiredFieldsFilled) {
            let mode = dom.e("#pn").dataset.mode;
            let projectName = dom.e("#pn").value.trim();
            let unit_revision = dom.e("#pr").value.trim();

            if (projectName.length >= 5 && mode !== "editmode") {
                // запрос на проверку уникальности имени проекта
                fetch("get_data", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `unit_name=${encodeURIComponent(projectName)}&revision=${encodeURIComponent(unit_revision)}&verification=true`
                })
                    .then(response => response.json())
                    .then(data => {
                        // console.log(data); // выводим ответ сервера в консоль для отладки
                        projectNameValid = !data.exists;
                        unit_id = data.unit_id;
                        if (data.exists) {
                            dom.addClass('#pn', 'danger');
                            dom.removeClass('#pn', 'success');
                            dom.e("#pn_label").innerHTML = "Project Name <b class='danger blinking p-2'>This name is exist!</b>";
                            // Создаем новый элемент <a>
                            let link = document.createElement("a");
                            // Устанавливаем атрибуты href и текстовое содержимое для ссылки
                            link.href = "/new_order?pid=" + unit_id + "&nord";
                            link.textContent = "Create order for this Project?";
                            link.classList.add("fs-4");
                            dom.e("#pn_label").appendChild(link);
                        } else {
                            dom.addClass('#pn', 'success');
                            dom.removeClass('#pn', 'danger');
                            dom.e("#pn_label").textContent = "Project Name";
                        }
                        // Проверка минимума чекбоксов и активация кнопки
                        // enableSubmitButton(requiredFieldsFilled && projectNameValid && checkedCount >= 3);
                        enableSubmitButton(requiredFieldsFilled && projectNameValid);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                // активация кнопки когда все поля заполнены, имя уникально и чекбоксы выбраны
                // enableSubmitButton(requiredFieldsFilled && projectNameValid && checkedCount >= 3);
                enableSubmitButton(requiredFieldsFilled && projectNameValid);
            }
        } else {
            // деактивация кнопки
            enableSubmitButton(false);
        }
    }

    form.querySelectorAll('input[required], textarea[required], input[type="checkbox"]').forEach(function (element) {
        element.addEventListener('input', checkForm);
        element.addEventListener('change', checkForm);
    });

    // Проверка формы при загрузке страницы
    checkForm();
});

function saveSelection() {
    // Получаем все чекбоксы с классом 'row-checkbox'
    const checkboxes = document.querySelectorAll('.row-checkbox:checked'); // Только выбранные чекбоксы
    // Собираем значения чекбоксов
    const selectedValues = Array.from(checkboxes).map(checkbox => checkbox.value);
    // Формируем строку из значений через запятую
    dom.e("#tools").value = selectedValues.join(',');
    // добавляем текст в кнопку для визуализации
    dom.e("#tools-btn").textContent = "Selected: " + selectedValues.join(',');
    // скрываем окно
    dom.hide("#searchModal");
}