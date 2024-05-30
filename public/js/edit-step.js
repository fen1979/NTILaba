document.addEventListener("DOMContentLoaded", function () {
    
    const v = document.getElementById('back-btn');
    const d = document.getElementById('step-number');
    v.value = v.value + "&#" + d.value;

    // кнопки выбора фото/ видео от пользователя
    document.doClick("#takePic", "#photo");
    document.doClick("#takeMovie", "#video");
    // Обработчик изменений файлов для обновления превью
    document.doPreviewFile("photo", "photoPreview");
    document.doPreviewFile("video", "videoPreview");

    // отправка формы после изменений через кнопку в нав панели
    document.doSubmit("#finishBtn", "#addDataForm");

    // filtering searching in the route actions
    const routeActionInput = document.getElementById('routeAction');
    const dropdownItems = document.querySelectorAll('#routeActionUl li.dropdown-item');

    routeActionInput.addEventListener('input', function () {
        const searchValue = routeActionInput.value.toLowerCase();
        dropdownItems.forEach(function (item) {
            const itemText = item.textContent.toLowerCase();
            if (itemText.includes(searchValue)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    /* добавление значения из списка в инпут для отправки на сервер ROUTE ACTION */
    document.querySelectorAll('#routeActionUl li').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            // Получаем текстовое содержимое
            const routeActionInput = document.getElementById('routeAction');
            routeActionInput.value = this.textContent.trim();
            // сообщаем ДОМ элементам что данный инпут был изменен програмно для корректной работы формы
            forceInputChangeEvent(routeActionInput);
            // Помещаем значение data-routeid в скрытый input
            document.getElementById('routeid').value = this.getAttribute('data-routeid');
        });
    });

    /* добавление значения из списка в инпут для отправки на сервер TOOL CHOOSE */
    document.querySelectorAll('#toolChoseUl li').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            let toolInput = document.getElementById('toolChose')
            // Получаем текстовое содержимое
            toolInput.value = this.textContent.trim();
            // сообщаем ДОМ элементам что данный инпут был изменен програмно для корректной работы формы
            forceInputChangeEvent(toolInput);
            // Помещаем значение data-toolid в скрытый input
            document.getElementById('toolId').value = this.dataset.toolid;
            document.getElementById('toolImage').src = this.dataset.image;
        });
    });

    /* отслеживание изменения в полях формы для работы в PHP над изменениями */
    let changedFields = [];
    $('.track-change').on('change', function () {
        let fieldId = $(this).data('field-id');
        if (changedFields.indexOf(fieldId) === -1) {  // Использование indexOf вместо $.inArray
            changedFields.push(fieldId);
            $('#changedFields').val(changedFields.join(','));
        }
    });
});

function forceInputChangeEvent(element) {
    if (!element) {
        console.error("Element not provided or does not exist.");
        return;
    }

    // Создаем и диспатчим событие 'change'
    let changeEvent = new Event("change", { bubbles: true, cancelable: false });
    element.dispatchEvent(changeEvent);
}
