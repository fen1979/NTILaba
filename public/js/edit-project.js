document.addEventListener("DOMContentLoaded", function () {

    // добавляем путь к папке если она есть и в ней есть файлы
    // делаем видимой кнопку в навигации
    if (dom.e("#project_folder_path")) {
        dom.removeClass("#folder_btn_li", "hidden");
        dom.e("#project_folder").value = dom.e("#project_folder_path").textContent.trim();
    }

    /* при вводе в поле номер шага скрыть все елементы кроме этого поля */
    $('#actStepInput').keyup(function () {
        $("#saveBtn").removeClass("btn-danger").addClass("btn-success");
        $("#errors").text("");

        $(".version-box").addClass("hidden");
        $(".image-picker").addClass("hidden");
        $(".video-picker").addClass("hidden");
        $(".checkbox").addClass("hidden");
        $(".desc-box").addClass("hidden");
        $(".prev-box").addClass("hidden");
    });

    /* удаление данного шага с перестроением данных в БД */
    $('.delete-button').on('click', function () {
        let projectid = $(this).closest('.card').find('.data-for-modal').data('projectid');
        let ident = $(this).closest('.card').find('.data-for-modal').data('stepid');
        $('#dfProjectID').val(projectid);
        $('#dfstepId').val(ident);

        $('#deleteModal').modal('show');
        $("#password").focus();
    });

    /* step image full view expande */
    $(".expande").on("click", function () {
        let cl = $(this).closest('.expanded-card');
        cl.toggleClass("col-md-6");
    });

    $('.video-button').click(function () {
        // Находим ближайшие элементы изображения и видео относительно кнопки
        let image = $(this).closest('.card').find('.image-preview');
        let video = $(this).closest('.card').find('.video-preview');
        let btn = $(this).closest('.card').find('.video-button i');

        // Переключаем видимость
        if (image.is(':visible')) {
            image.hide();
            video.show();
            btn.removeClass("bi bi-camera-reels-fill").addClass("bi bi-camera-fill");
            btn.title('Preview Image');
        } else {
            video.hide();
            image.show();
            btn.removeClass("bi bi-camera-fill").addClass("bi bi-camera-reels-fill");
            btn.title('Preview Video');
        }
    });


    /* действиe архивировать проект  */
    $(".archive").on("click", function () {
        let projectID = $(this).data("projectid");
        $("#dnProjectID").val(projectID);

        /* Откройте модальное окно */
        $("#modal-title").text("Add Project to Archive");
        $("#archive-btn").show();
        $("#delete-btn").hide();
        $("#deleteProjectModal").modal('show');
        $("#pr_password").focus();
    });

    /* действиe удалить проект  */
    $(".deleteProjectButton").on("click", function () {
        let projectID = $(this).data("projectid");
        $("#dnProjectID").val(projectID);

        /* Откройте модальное окно */
        $("#modal-title").html("Delete Project<br><b class=\"text-danger\">Please be advised:" +
            "<br>This action is irreversible and requires thorough consideration." +
            "<br>Once initiated, there is no turning back, so weigh your decision carefully.</b>");
        $("#delete-btn").show();
        $("#archive-btn").hide();
        $("#deleteProjectModal").modal('show');
        $("#pr_password").focus();
    });
}); // End document.ready