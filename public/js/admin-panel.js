dom.addEventListener("DOMContentLoaded", function () {

    // кнопки выбора фото/ видео от пользователя
    document.doPreviewFile("#files", "#preview");
    // Обработка кликов на чекбоксы
    dom.in('click', 'tbody input[type="checkbox"]', updateRowOrder);
    // routing for admin-panel pages flow
    dom.in("click", "#navBarContent .swb", function () {
        let el = this.value;
        dom.e("#route-form", function () {
            // noinspection JSPotentiallyInvalidUsageOfThis
            this.value = el;
        }).click();
    });
    // действиe удалить строку из таблицы
    dom.in("click", ".del-but", function () {
        dom.e("#idForUse").value = this.getAttribute("data-id");
        dom.show("#deleteModal", "modal");
        dom.e("#password").focus();
    });
    // searching some data on page in DB
    let args = {url: BASE_URL + "admin-panel/searching.php", method: "POST", headers: null}
    dom.makeRequest("#searchThis", "keyup", "", args, function (error, result, _) {
        if (error) {
            console.error('Error during fetch:', error);
            return;
        }
        dom.e("#searchAnswer").innerHTML = result;
    });
    // переключение записей в рут картах между языками
    dom.in("click", "#lang-switch", function () {
        dom.toggleClass(".eng", 'hidden');
        dom.toggleClass(".noeng", 'hidden');
    });

    /* скрипты для настройки вывода таблиц */
    if (dom.e('tbody tr')) {
        // Инициализация порядка строк при загрузке страницы
        updateRowOrder();

        let draggedRow = null;
        // Делаем строки таблицы перетаскиваемыми
        dom.eAll('tbody tr', 'draggable', 'true');

        // Обработка событий dragstart
        dom.in('dragstart', 'tbody tr', function () {
            draggedRow = this;
        }, 'table');
        // Обработка событий dragover
        dom.in('dragover', 'tbody tr', function (event) {
            event.preventDefault();
        }, 'table');
        // Обработка событий drop
        dom.in('drop', 'tbody tr', function () {
            if (this !== draggedRow) {
                this.parentNode.insertBefore(draggedRow, this);
                updateRowOrder();
            }
        }, 'table');
    }

    /* установка цвета на кнопки в навигационной панели */
    // fixme not work because of changes on maitenance (admin-panel.php)
    let currentPage = $('#page').text();
    $('button[name="sw_bt"]').each(function () {
        if ($(this).val() === currentPage) {
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        }
    });

    /* установка фона ряда для таблиц */
    $('tr').click(function () {
        $('tr').removeClass('bg-light');
        $(this).addClass('bg-light');
    });

    /* действиe удалить строку из таблицы orders */
    // TODO in maintenence
    /* $(".trash-order").on("click", function (event) {
         event.stopPropagation();
         $("#idForUse").val($(this).val());
         /!* Откройте модальное окно *!/
         $("#deleteModal").modal('show');
         $("#password").focus();
     });*/
});

// Функция для обновления порядка строк
function updateRowOrder() {
    let order = [];
    dom.querySelectorAll('tbody tr').forEach(tr => {
        let checkbox = tr.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            order.push(checkbox.value);
        }
    });
    // добавляем в инпут сортированный список выбранных элементов
    if (order.length > 0)
        dom.e('#rowOrder').value = order.join(',');
}