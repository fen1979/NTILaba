// noinspection JSUnusedGlobalSymbols

document.addEventListener("DOMContentLoaded", function () {
    // выбор файла для загрузки
    dom.doClick("#import_csv", "#csv_input");
    // $(document).on("click", "#import_csv", function () {
    //     $("#csv_input").click();
    // });

    $('#csv_input').on('change', function () {
        // Если файл выбран
        if ($(this).val()) {
            // Убираем атрибут 'required' со всех полей
            $('#uploadForm input[required]').removeAttr('required');

            // Очищаем значения всех полей, кроме текущего (поля с файлом)
            $('#uploadForm input').not(this).val('');
        } else {
            // Возвращаем атрибут 'required', если файл не выбран
            $('#uploadForm input[class="form-control"]').attr('required', 'required');
        }
    });

    // some keys commands listeners
    const div = document.querySelectorAll('.item-btn');
    let ctrlPressed = false;

    // Detect when the CTRL or COMMAND key is pressed down
    document.addEventListener('keydown', function (event) {
        if (event.ctrlKey || event.metaKey) {
            ctrlPressed = true;
        }
    });

    // Detect when the CTRL or COMMAND key is released
    document.addEventListener('keyup', function (event) {
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

    // выборка запчасти в поля для заполнения без поля количество!
    $(document).on("click", "#tbody-responce tr.item-list", function () {
        // Извлекаем и парсим данные из атрибута data-info
        let info = JSON.parse($(this).attr('data-info'));
        // Устанавливаем полученные значения в поля ввода
        $("#pn").val(info.partName); // Устанавливаем
        $("#pv").val(info.partValue); // Устанавливаем
        $("#part_type").val(info.partType); // Устанавливаем
        $("#footprint").val(info.footprint); // Устанавливаем
        $("#mf").val(info.manufacturer); // Устанавливаем
        $("#mf_pn").val(info.MFpartName); // Устанавливаем
        $("#owner_pn").val(info.ownerPartName); // Устанавливаем
        $("#desc").val(info.description); // Устанавливаем
        $("#nt").val(info.notes); // Устанавливаем
    });
});

function deleteItem(id, num) {
    $("#deleteItem").modal('show');
    $("#item_id").val(id);
    $("#item-number").text(num);
    $("#password").focus();
}

function editItem(id) {
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('edit-item', id); // Устанавливаем или заменяем параметр 'edit-item'
    window.location.href = currentUrl.href; // Перезагружаем страницу с обновленным URL
}