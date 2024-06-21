document.addEventListener("DOMContentLoaded", function () {

    // добавляем путь к папке если она есть и в ней есть файлы
    // делаем видимой кнопку в навигации
    if (dom.e("#project_folder_path")) {
        dom.removeClass("#folder_btn_li", "hidden");
        dom.e("#project_folder").value = dom.e("#project_folder_path").textContent.trim();
    }

    /* удаление данного шага с перестроением данных в БД */
    dom.in("click", ".delete-button", function () {
        dom.e("#dfProjectID").value = this.dataset.projectid;
        dom.e("#pid").textContent = this.dataset.projectid;
        dom.e('#dfstepId').value = this.dataset.stepid;
        dom.e('#sid').textContent = this.dataset.stepid;
        dom.show("#deleteModal", "fast", true);
        dom.e("#password").focus();
    });

    /* step image full view expande */
    dom.inAll("click", ".expande", function () {
        let cl = this.closest('.expanded-card');
        if (cl) {
            cl.classList.toggle("col-md-6");
        }
    });

    dom.in("click", ".video-button", function () {
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
    dom.in("click", ".archive", function () {
        dom.e("#dnProjectID").value = this.dataset.projectid;
        dom.e("#modal-title").textContent = "Add Project to Archive";
        dom.show("#archive-btn");
        dom.hide("#delete-btn");
        dom.show("#deleteProjectModal", "fast", true);
        dom.e("#pr_password").focus();
    });

    /* действиe удалить проект  */
    dom.in("click", ".deleteProjectButton", function () {
        dom.e("#dnProjectID").value = this.dataset.projectid;
        dom.e("#modal-title").innerHTML = "Delete Project<br><b class=\"text-danger\">Please be advised:" +
            "<br>This action is irreversible and requires thorough consideration." +
            "<br>Once initiated, there is no turning back, so weigh your decision carefully.</b>";
        dom.show("#delete-btn");
        dom.hide("#archive-btn");
        dom.show("#deleteProjectModal", "fast", true);
        dom.e("#pr_password").focus();
    });

}); // End document.ready


/* при вводе в поле номер шага скрыть все елементы кроме этого поля */
// $('#actStepInput').keyup(function () {
//     $("#saveBtn").removeClass("btn-danger").addClass("btn-success");
//     $("#errors").text("");
//
//     $(".version-box").addClass("hidden");
//     $(".image-picker").addClass("hidden");
//     $(".video-picker").addClass("hidden");
//     $(".checkbox").addClass("hidden");
//     $(".desc-box").addClass("hidden");
//     $(".prev-box").addClass("hidden");
// });
