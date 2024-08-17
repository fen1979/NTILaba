dom.addEventListener('DOMContentLoaded', function () {
    /* drag and drop file to move from page to folder */
    let draggedItem = null;

    // Обработка событий перетаскиваемых файлов
    dom.querySelectorAll('.file').forEach(function (item) {
        item.addEventListener('dragstart', function () {
            draggedItem = this; // Запоминаем перетаскиваемый элемент
        });
    });

    // Обработка событий целевых папок
    dom.querySelectorAll('.drop-target').forEach(function (item) {
        item.addEventListener('dragover', function (e) {
            e.preventDefault(); // Разрешаем сбрасывать элементы в эту цель
        });

        item.addEventListener('drop', function (e) {
            e.preventDefault();
            if (draggedItem) {
                // Получаем целевую директорию
                let targetDir = this.getAttribute('data-dir');
                // Получаем путь к перетаскиваемому файлу
                let filePath = draggedItem.getAttribute('data-filepath');
                // Выполняем POST запрос на сервер для перемещения файла
                $.post('/wiki', {
                    filePath: filePath,
                    targetDir: targetDir
                }).done(function (response) {
                    console.log("Response: ", response);
                    // После успешного запроса можно обновить интерфейс или перезагрузить страницу
                    location.reload();
                }).fail(function (xhr, status, error) {
                    console.log("Error: ", status, error);
                });
            }
        });
    });

    // Набор текста в поле для поиска
    dom.in("keyup", ".searchThis", function () {
        let value = this.value.toLowerCase();

        dom.e("#search_here .col-md-2", function () {
            let text = this.textContent.toLowerCase();
            this.style.display = text.indexOf(value) > -1 ? "" : "none";
        });
    });

    // open - close btn add files
    dom.in("click", "#add-files", function () {
        dom.toggleClass("#addimg-form", "hidden");
    });

    // Обработчик события изменения для инпута с файлами
    dom.in("change", "#some_file, #some_folder", function () {
        // Получаем количество выбранных файлов
        let fileCount = this.files.length;
        // Базовый текст для лейбла в зависимости от инпута
        let labelBaseText = this.id === "some_file" ? "Picked Files" : "Picked Folders";

        if (fileCount > 0) {
            // Если выбраны файлы, обновляем текст лейбла, добавляя количество
            this.nextElementSibling.textContent = `${labelBaseText} - ${fileCount}pcs`;
        } else {
            // Если файлы не выбраны, возвращаем базовый текст лейбла
            this.nextElementSibling.textContent = labelBaseText;
        }
    });

    // отслеживание изменений в инпуте для взятия папок
    dom.in("change", "#some_folder", function () {
        if (this.files.length > 0) {
            dom.hide("#file_label");
        }
        checkFolderName();
    });

    // отслеживание для инпута взятия файлов
    dom.in("change", "#some_file", function () {
        if (this.files.length > 0) {
            dom.hide("#folder_label");
        }
        checkFolderName();
    });

    // меняем лейбля для кнопок при изменениях
    dom.in("input", "#folder_name", function () {
        // Если поле с именем папки изменено или очищено
        if (this.value === '') {
            // Сбрасываем выбранные файлы/папки
            dom.e("#some_file").value = '';
            dom.e("#some_folder").value = '';

            // Возвращаем видимость обоим лейблам
            let labelBaseText = ["Pick a File", "Pick a Folder"];
            dom.e("#file_label").textContent = labelBaseText[0];
            dom.e("#folder_label").textContent = labelBaseText[1];
            dom.show("#file_label");
            dom.show("#folder_label");
        }
    });

    // Активируем кнопку отправки, если введено имя папки
    dom.in("input", "#folder_name", function () {
        dom.e("#submit-btn").disabled = !(this.value !== '' && this.value.length >= 3);
    });
});

function checkFolderName() {
    let f_name = dom.e("#folder_name");
    dom.e("#submit-btn").disabled = !(f_name.value !== '' && f_name.value.length >= 3);
}