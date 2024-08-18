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
            dom.e("#pickFile").textContent = "Upload ProductionUnit Documentation (PDF Only)";
        }
    });
    // Использование функции dom.in для делегирования событий клика на элементы .tools-row
    dom.inAll('click', '.tools-row', function (e) {
        // Prevent the dropdown from closing
        e.stopPropagation();

        // Toggle the checkbox state
        let checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked; // Toggle the checkbox
        checkbox.dispatchEvent(new Event('change')); // Trigger the change event

        // Вызов функции проверки валидности формы после изменения состояния чекбокса
        dom.e('#createProjectForm input[required], #createProjectForm textarea[required], #createProjectForm input[type="checkbox"]',
            function () {
                this.dispatchEvent(new Event('input')); // Инициирование события input для запуска валидации
            });
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
            if (projectName.length >= 5 && mode !== "editmode") {

                // запрос на проверку уникальности имени проекта
                fetch("get_data", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `project_name=${encodeURIComponent(projectName)}&verification=true`
                })
                    .then(response => response.json())
                    .then(data => {
                        // console.log(data); // выводим ответ сервера в консоль для отладки
                        projectNameValid = !data.exists;
                        if (data.exists) {
                            dom.addClass('#pn', 'danger');
                            dom.removeClass('#pn', 'success');
                            dom.e("#pn_label").innerHTML = "ProductionUnit Name <b class='danger blinking p-2'>This name is exist!</b>";
                        } else {
                            dom.addClass('#pn', 'success');
                            dom.removeClass('#pn', 'danger');
                            dom.e("#pn_label").textContent = "ProductionUnit Name";
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