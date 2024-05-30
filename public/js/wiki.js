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
                $.post('/wiki.php', {
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

    // набор текста в поле для поиска
    $(".searchThis").on("keyup", function () {
        let value = $(this).val().toLowerCase();
        $("#search_here .col-md-2").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $(document).on("click", "#add-files", function () {
        $("#addimg-form").toggle("hidden");
    });

    // Обработчик события изменения для инпута с файлами
    $("#some_file, #some_folder").on("change", function () {
        // Получаем количество выбранных файлов
        let fileCount = this.files.length;
        // Базовый текст для лейбла в зависимости от инпута
        let labelBaseText = this.id === "some_file" ? "Picked Files" : "Picked Folders";

        if (fileCount > 0) {
            // Если выбраны файлы, обновляем текст лейбла, добавляя количество
            $(this).next('label').text(`${labelBaseText} - ${fileCount}pcs`);
        } else {
            // Если файлы не выбраны, возвращаем базовый текст лейбла
            $(this).next('label').text(labelBaseText);
        }
    });

    // отслеживание изменений в инпуте для взятия папок
    $("#some_folder").on("change", function () {
        if (this.files.length > 0) {
            $("#file_label").hide();
        }
        checkFolderName();
    });

    // отслеживание для инпута взятия файлов
    $("#some_file").on("change", function () {
        if (this.files.length > 0) {
            $("#folder_label").hide();
        }
        checkFolderName();
    });

    // меняем лейбля для кнопок при изменениях
    $("#folder_name").on("input", function () {
        // Если поле с именем папки изменено или очищено
        if ($(this).val() === '') {
            // Сбрасываем выбранные файлы/папки
            $("#some_file, #some_folder").val('');

            // Возвращаем видимость обоим лейблам
            let labelBaseText = ["Pick a File", "Pick a Folder"];
            $("#file_label").text(labelBaseText[0]);
            $("#folder_label").text(labelBaseText[1]);
            $("#file_label, #folder_label").show();
        }
    });

    // Активируем кнопку отправки, если введено имя папки
    $("#folder_name").on("input", function () {
        if ($(this).val() !== '' && $(this).val().length >= 3) {
            $("#submit-btn").prop("disabled", false);
        } else {
            $("#submit-btn").prop("disabled", true);
        }
    });

    // Отображаем элемент загрузки при клике на кнопку отправки
    $("#submit-btn").click(function () {
        $("#loading").css("display", "flex");
    });
});

function checkFolderName() {
    let f_name = $("#folder_name");
    if (f_name.val() !== '' && f_name.val().length >= 3) {
        $("#submit-btn").prop("disabled", false);
    } else {
        $("#submit-btn").prop("disabled", true);
    }
}