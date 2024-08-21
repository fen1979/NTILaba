document.addEventListener("DOMContentLoaded", function () {
    // Инициализация DOM-элементов
    const form = dom.e('#create-pioi');

    // Функции для клика на загрузку файлов
    dom.doClick("#projects_files_btn", "#projects_files");
    dom.doClick("#pickFile", "#pdf_file");

    // Предотвращение переключения чекбокса при прямом его клике
    dom.in("click", "input[type=\"checkbox\"]", function (e) {
        e.stopPropagation();
    });

    // Обработчики изменения для инпутов с файлами
    dom.onInputsChange("#projects_files", handleFileInputChange);
    dom.onInputsChange("#pdf_file", handlePDFInputChange);

    // Валидация формы
    form.querySelectorAll('input[type="text"][required], select[required]').forEach(function (element) {
        element.addEventListener('input', validateForm);
        element.addEventListener('change', validateForm);
    });

    // Проверка формы при загрузке страницы
    validateForm();

    // Отключение автоматического закрытия выпадающих списков
    dom.in('click', "#workers", function (e) {
        e.stopPropagation();
    });

    // Обработчик клика по результату поиска клиента
    dom.in("click", "#search-responce tr.customer", handleCustomerSearchClick);

    // Обработчик изменения списка работников
    $('#workers .form-check-input').on('change', handleWorkersChange);

    // Обработчик клика по списку "на кого переведен проект"
    $('#forwarded li').on('click', handleForwardedClick);

    // Обработчик изменения приоритета заказа
    $(document).on("change", "#prioritet", handlePriorityChange);

    // Обработчик изменения чекбокса необходимости серийного номера для проекта
    $('#serial-required').change(handleSerialCheckboxChange);

    // Обработчики изменения инпутов
    $('input[type="text"][required], select[required]').on('input', validateForm);

    // Обработка клика по результату поиска для места хранения
    dom.in("click", "#storageBox", function () {
        // Отправляем POST-запрос на сервер
        $.post('', {'search-for-storage-box': this.value}, function (data) {
            // При успешном получении ответа обновляем значение поля ввода
            dom.e('#storageBox').value = data;
            console.log(data);
        });
    });
});

function handleFileInputChange() {
    if (this.files) {
        let fileCount = this.files.length;
        dom.e("#pick_files_text").textContent = "Picked Files - " + fileCount + "pcs";
    }
}

function handlePDFInputChange() {
    let fileCount = this.files.length;
    if (fileCount > 0) {
        dom.e("#pickFile").textContent = this.files[0].name;
    } else {
        dom.e("#pickFile").textContent = "Upload ProductionUnit Documentation (PDF Only)";
    }
}

function handleCustomerSearchClick() {
    if (this.parentElement.dataset.info) {
        let info = JSON.parse(this.parentElement.dataset.info);
        dom.e("#customerName").value = info.name;
        dom.e("#phone").value = info.phone;
        dom.e("#email").value = info.email;
        dom.e("#customerId").value = info.clientID;
        dom.e("#priorityMakat").value = info.priority;
        dom.e("#headPay").value = info.headpay;

        dom.hide("#searchModal");
        validateForm();
    }
}

function handleWorkersChange() {
    let selectedWorkers = [];
    $('#workers .form-check-input:checked').each(function () {
        selectedWorkers.push($(this).val());
    });
    $('#orderWorkers').val(selectedWorkers.join(','));
    validateForm();
}

function handleForwardedClick() {
    $('#forwardTo').val($(this).text());
    validateForm();
}

function handlePriorityChange() {
    const priorityClasses = {
        "DO FIRST": "danger",
        "HIGH": "danger",
        "MEDIUM": "warning",
        "LOW": "success"
    };

    const selectedValue = $(this).val();
    const selectedClass = priorityClasses[selectedValue];
    $("#prioritet").removeClass("danger warning success").addClass(selectedClass).blur();
    validateForm();
}

function handleSerialCheckboxChange() {
    if ($(this).is(':checked')) {
        let userResponse = confirm($("#prompt-text").text());
        if (!userResponse) {
            $(this).prop('checked', false);
        }
    }
    validateForm();
}

function validateForm() {
    let isValid = true;
    let is_unit_name = true;
    let unit_id = "";

    // Проверка текстовых инпутов и селектов
    $('input[type="text"][required], select[required]').each(function () {
        // console.log('Checking field:', $(this).val().trim()); // Логирование для отладки
        if ($(this).val().trim() === '') {
            isValid = false;
            return false; // Прерывание цикла при нахождении пустого поля
        }
    });

    // Проверка уникальности имени юнита
    let unit_name = dom.e("#pn").value.trim();
    let unit_revision = dom.e("#pr").value.trim();
    let mode = dom.e("#pn").dataset.mode;

    if (unit_name.length >= 5 && mode !== "editmode") {
        fetch("get_data", {
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `unit_name=${encodeURIComponent(unit_name)}&revision=${encodeURIComponent(unit_revision)}&verification=true`
        })
            .then(response => response.json())
            .then(data => {
                is_unit_name = !data.exists;
                unit_id = data.unit_id;
                if (data.exists) {
                    dom.addClass('#pn', 'danger');
                    dom.removeClass('#pn', 'success');
                    dom.e("#pn_label").innerHTML = "Production Unit Name <b class='danger blinking p-2'>This name is exist!</b> &nbsp;";
                    // Создаем новый элемент <a>
                    let link = document.createElement("a");
                    // Устанавливаем атрибуты href и текстовое содержимое для ссылки
                    link.href = "/new_order?pid=" + unit_id + "&nord";
                    link.textContent = "Create order for this Production Unit?";
                    link.classList.add("fs-4");
                    dom.e("#pn_label").appendChild(link);
                } else {
                    dom.addClass('#pn', 'success');
                    dom.removeClass('#pn', 'danger');
                    dom.e("#pn_label").textContent = "Production Unit Name";
                }
                updateSubmitButtonState(isValid && is_unit_name);
            })
            .catch(error => console.error('Error:', error));
    } else {
        updateSubmitButtonState(isValid && is_unit_name);
    }
}

function updateSubmitButtonState(enable) {
    // console.log('Button state:', enable); // Логирование состояния кнопки
    dom.e('#create-po-btn').disabled = !enable;
}