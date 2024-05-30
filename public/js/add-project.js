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
    // Использование функции dom.in для делегирования событий клика на элементы .tools-row
    dom.inAll('click', '.tools-row', function (e) {
        // Prevent the dropdown from closing
        e.stopPropagation();

        // Toggle the checkbox state
        let checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked; // Toggle the checkbox
        checkbox.dispatchEvent(new Event('change')); // Trigger the change event

        // Вызов функции проверки валидности формы после изменения состояния чекбокса
        dom.e('#createProjectForm input[required], #createProjectForm textarea[required], #createProjectForm input[type="checkbox"]', function () {
            this.dispatchEvent(new Event('input')); // Инициирование события input для запуска валидации
        });
    });
    // Обработка клика по результату поиска клиента
    dom.in("click", "#searchAnswer p.customer, #searchAnswer p.customer span", function (event) {
        // Используем closest для получения элемента p.customer, когда клик происходит на span или p
        let customer = event.target.closest('p.customer');

        if (customer) {
            // Извлекаем и парсим данные из атрибута data-info
            let info = JSON.parse(customer.dataset.info);

            // Устанавливаем полученные значения в поля ввода
            dom.e("#customerName").value = info.name; // Устанавливаем имя клиента
            dom.e("#customerId").value = info.clientID; // Устанавливаем ID клиента
            dom.e("#priorityMakat").value = info.priority; // Устанавливаем приоритет
            dom.e("#headPay").value = info.headpay; // Устанавливаем приоритет

            // Очищаем результаты поиска
            dom.e("#searchAnswer").textContent = '';
            dom.e("#searchAnswer").style.display = 'none';
        }
    }, 'body');
    // скрываем ответ от сервера при клике на странице
    dom.in('click', "body", function (event) {
        const searchAnswer = dom.e("#searchAnswer");
        // Проверяем, что клик произошел вне элемента searchAnswer и что он видим
        if (!searchAnswer.contains(event.target) && getComputedStyle(searchAnswer).display !== 'none') {
            searchAnswer.style.display = 'none';
        }
    });

    // выбор чекбокса (не обязательные фото в шагах проекта) при выборе СМТ проект
    dom.in("change", "#project_type", function () {
        dom.e("#sub_assembly").checked = this.checked;
    });

    // Включаем кнопку "Создать проект" при заполнении обязательных полей и выборе минимум 3 чекбоксов
    $('#createProjectForm input[required], #createProjectForm textarea[required], #createProjectForm input[type="checkbox"]')
        .on('input change', function () {
            let checkedCount = $('#createProjectForm input[type="checkbox"]:checked').length;
            let requiredFieldsFilled = true;
            let projectNameValid = true; // По умолчанию предполагаем, что имя проекта валидно

            // Проверяем, заполнены ли все обязательные поля
            $('#createProjectForm input[required], #createProjectForm textarea[required]').each(function () {
                if ($(this).val() === '') {
                    requiredFieldsFilled = false;
                    return false; // Выходим из цикла each
                }
            });

            // Только если все обязательные поля заполнены, проверяем уникальность имени
            if (requiredFieldsFilled) {
                let mode = $("#pn").data("mode");
                let projectName = $("#pn").val().trim();
                if (projectName.length >= 5 && mode !== "editmode") {
                    // запрос на проверку уникальности имени проекта
                    $.post('/searching/getData.php', {project_name: projectName, verification: true}, function (data) {
                        projectNameValid = !data.exists;
                        if (data.exists) {
                            $('#pn').addClass('danger').removeClass('success');
                            $("#pn_label").html("Project Name <b class='danger blinking p-2'>This name is exist!</b>");
                            //$("#folderAdding").attr("href", "").addClass("disabled");
                        } else {
                            $('#pn').addClass('success').removeClass('danger');
                            $("#pn_label").html("Project Name");
                            // $("#folderAdding").attr("href", "/wiki?dir=" + projectName + "&mode=add-project").removeClass("disabled");
                        }
                        // Проверка минимума чекбоксов и активация кнопки
                        enableSubmitButton(requiredFieldsFilled && projectNameValid && checkedCount >= 3);
                    }, 'json');
                } else {
                    // активация кнопки когда все поля заполнены, имя уникально и чекбоксы выбраны
                    enableSubmitButton(requiredFieldsFilled && projectNameValid && checkedCount >= 3);
                }
            } else {
                // деактивация кнопки
                enableSubmitButton(false);
            }
        });
});

function enableSubmitButton(enable) {
    dom.e("#createProjectBtn").disabled = !enable;
}