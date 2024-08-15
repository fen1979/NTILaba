// noinspection JSUnusedGlobalSymbols
document.addEventListener("DOMContentLoaded", function () {
    // some keys commands listeners
    const div = document.querySelectorAll('.item-btn');
    let ctrlPressed = false;
    // Detect when the CTRL or COMMAND key is pressed down
    dom.addEventListener('keydown', function (event) {
        if (event.ctrlKey || event.metaKey) {
            ctrlPressed = true;
        }
    });
    // Detect when the CTRL or COMMAND key is released
    dom.addEventListener('keyup', function (event) {
        if (!event.ctrlKey && !event.metaKey) {
            ctrlPressed = false;
        }
    });

    // Change the div background color on mouseover
    div.forEach(function (item) {
        item.addEventListener('mouseover', function () {
            const itemId = item.getAttribute('data-id'); // атрибут data-id
            const itemNum = item.getAttribute('data-num'); // атрибут data-num
            if (ctrlPressed) {
                item.setAttribute('onclick', `deleteItem(${itemId},${itemNum})`);
                item.classList.add('danger'); // CTRL/COMMAND is pressed = danger
            } else {
                item.setAttribute('onclick', `editItem(${itemId})`);
                this.classList.add('warning');// CTRL/COMMAND is not pressed = warning
            }
        });

        // Reset the background color when the mouse leaves the div
        item.addEventListener('mouseout', function () {
            this.classList.remove('danger');// Default background color = secondary
            this.classList.remove('warning');// Default background color = secondary
        });
    });

    // выбор файла для загрузки
    dom.doClick("#import_csv", "#csv_input");

    // удаление атрибута required в полях формы при импорте из файла
    dom.in("change", "#csv_input", function () {
        if (this.value) {
            // Получаем форму
            dom.e("#uploadForm").querySelectorAll("input")
                // Обходим все инпуты внутри формы
                .forEach(input => {
                    // Убираем атрибут 'required'
                    input.removeAttribute('required');
                    // Очищаем значение инпута, если это не текущий инпут (инпут файла)
                    if (input !== this) {
                        input.value = '';
                    }
                });

            // Обновляем текст кнопки с именем файла
            const fileName = this.files[0].name;
            dom.e("#import_csv").textContent = `Picked File: ${fileName}`;
        }
    });

    // выборка запчасти в поля для заполнения без поля количество!
    dom.in("click", "#tbody-responce tr.item-list", function () {
        if (this.parentElement.dataset.info) {
            // Извлекаем и парсим данные из атрибута data-info
            let info = JSON.parse(this.parentElement.dataset.info);

            // Устанавливаем полученные значения в поля ввода
            dom.e("#item_id").value = info.item_id; // Устанавливаем Item ID
            dom.e("#owner_id").value = JSON.parse(info.owner_id).id; // Устанавливаем owner ID
            dom.e("#pn").value = info.partName; // Устанавливаем Item Name
            dom.e("#pv").value = info.partValue; // Устанавливаем Item Value
            dom.e("#mounting_type").value = info.mountingType; // Устанавливаем Item Type
            dom.e("#footprint").value = info.footprint; // Устанавливаем Footprint
            dom.e("#mf").value = info.manufacturer; // Устанавливаем
            dom.e("#mf_pn").value = info.MFpartName; // Устанавливаем
            dom.e("#owner_pn").value = info.ownerPartName; // Устанавливаем
            dom.e("#desc").value = info.description; // Устанавливаем
            dom.e("#nt").value = info.notes; // Устанавливаем
        }
    });
});

function deleteItem(id, num) {
    dom.e("#item_id_for_delete").value = id;
    dom.e("#item-number").textContent = num;
    dom.e("#password").focus();
    dom.show("#deleteItem");
}

function editItem(id) {
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('edit-item', id); // Устанавливаем или заменяем параметр 'edit-item'
    window.location.href = currentUrl.href; // Перезагружаем страницу с обновленным URL
}