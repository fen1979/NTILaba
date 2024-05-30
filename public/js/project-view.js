document.addEventListener("DOMContentLoaded", function () {
    // Main search field Enter press event
    dom.in("keypress", ".mainSearchForm input", function (event) {
        // Проверка, была ли нажата клавиша Enter
        if (event.which === 13 || event.keyCode === 13) {
            event.preventDefault();  // Отмена стандартного действия (отправка формы)
            // Поиск первой кнопки с указанными классами
            const button = dom.e('a.btn.btn-outline-info.anchor-btn');
            if (button) {
                button.click();  // Программный клик по кнопке
            }
        }
    });

    /* share link button if navigator.share work move user to share menu else copy text to clipboard */
    $(".share-project").click(function () {
        let linkToCopy = $(this).data("share-link");

        if (navigator.share) {
            navigator.share({
                title: 'Share Project',
                text: 'Not Editable',
                url: linkToCopy
            })
                .then(() => console.log('Поделиться успешно'))
                .catch((error) => console.error('Ошибка при попытке поделиться:', error));
        } else {
            console.log('API "Поделиться" не поддерживается в вашем браузере.');
            copyToClipboard(linkToCopy);
        }
    });

    /* действиe архивировать проект  */
    $(".archive").on("click", function () {
        let projectID = $(this).data("projectid");
        $("#dfProjectID").val(projectID);

        /* Откройте модальное окно */
        $("#modal-title").text("Add Project to Archive");
        $("#archive-btn").show();
        $("#delete-btn").hide();
        $("#deleteModal").modal('show');
        $("#password").focus();
    });

    /* действиe удалить проект  */
    $(".delete-button").on("click", function () {
        let projectID = $(this).data("projectid");
        $("#dfProjectID").val(projectID);

        /* Откройте модальное окно */
        $("#modal-title").html("Delete Project<br><b class=\"text-danger\">Please be advised:" +
            "<br>This action is irreversible and requires thorough consideration." +
            "<br>Once initiated, there is no turning back, so weigh your decision carefully.</b>");
        $("#delete-btn").show();
        $("#archive-btn").hide();
        $("#deleteModal").modal('show');
        $("#password").focus();
    });

}); // End document.ready

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function () {
        alert("Ссылка скопирована! Теперь вы можете поделиться ею.");
    }).catch(function (err) {
        console.error('Не удалось скопировать текст.', err);
    });
}