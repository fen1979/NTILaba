dom.addEventListener("DOMContentLoaded", function () {

    // Отключение автоматического закрытия выпадающих списков
    dom.in("click", ".dropdown", function (e) {
        e.stopPropagation();
    });

    // При изменении состояния любой радиокнопки внутри .dropdown-menu global chat window
    dom.onInputsChange('#dropdown-menu .radio-item input[type="radio"]', function () {
        dom.e('#dropdown-toggle').textContent = this.nextElementSibling.textContent;
    });

    /* действиe архивировать заказ */
    dom.in("click", ".archive-order", function () {
        dom.e("#idForUse").value = this.value;
        /* Откройте модальное окно */
        dom.show("#archive_order");
        dom.e("#password").focus();
    });

    // выводим список для приорити по заказам которые представлены на странице
    dom.doSubmit("#priority-out-data", "#priority-form");

    // Сброс чекбоксов во всех трех выпадающих списках
    dom.in('click', '#resetFilters', function () {
        // Очистка чекбоксов статуса и пользователей
        dom.querySelectorAll('input[type="checkbox"][name="status[]"], input[type="checkbox"][name="users[]"]').forEach(input => {
            input.checked = false;
        });

        // Создание и отправка временной формы
        let tempForm = dom.createElement('form');
        tempForm.action = '';
        tempForm.method = 'POST';

        // Добавление скрытых полей
        ['filter-by-status', 'filter-by-user', 'filter-by-client'].forEach(name => {
            let hiddenInput = dom.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = name;
            hiddenInput.value = '';
            tempForm.appendChild(hiddenInput);
        });

        // Добавление формы в body и её отправка
        dom.body.appendChild(tempForm);
        tempForm.submit();
    });

    // функция работы с глобальным чатом
    // dom.globalChatWindow("#msg_count","#msg_area","#msg");
});

// noinspection JSUnusedGlobalSymbols
function getInfo(id) {
    // Меняет текущий URL, перенаправляя на новый открываем в новой вкладке
    window.open("/order/preview?orid=" + id, '_blank');
}