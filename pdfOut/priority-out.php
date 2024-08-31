<?php
const ID = 0;
const PROJECT_NAME = 1;
const ASMAX_1 = 2;
const ASMAX_2 = 3;
const AMOUNT = 4;
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
//
if (isset($_GET['order-ids'])) {
    if (isset($_POST['data'])) {
        $data = $_POST['data'];
        foreach ($data as $line) {
            $order = R::load(ORDERS, $line[ID]);
            if ($order) {
                // i меняем имя проекта в заказе если оно было не правильное что врядли конечно
                // i продумать смену имени в проекте если оно действительно было не правильное - проекты смена имени!
                $order->project_name = $line[PROJECT_NAME];
                // i если кол-во было больше до изменения отнимаем новое значение от старого и разницу сохраняем
                // i это нужно при частичной отдаче заказа
                if ($order->order_amount > (int)$line[AMOUNT]) {
                    $order->order_amount = $order->order_amount - $line[AMOUNT];
                }
                // i если кол-во было меньше или равно до изменения добавляем новое значение
                // i это нужно если сделали больше чем былоо указано и клиент забрал все или сделали по факту
                if ($order->order_amount <= (int)$line[AMOUNT]) {
                    $order->order_amount = $line[AMOUNT];
                }
                // i тут добавится другие данные для изменения если нужны
            }

            // R::store($order);
        }
    }
    // Если идентификаторы были отправлены в виде строки, разделенной запятыми
    $ids = explode(',', $_GET['order-ids']);
    $clientName = _E($_GET['customer-name']);
    $priorityId = _E($_GET['priority-id']);
    // Загружаем записи из БД
    $orderData = R::loadAll(ORDERS, $ids);
    ?>
    <!doctype html>
    <html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
    <head>
        <?php HeadContent('priority'); ?>
        <style>
            /* таблица вывода запючастей */
            table {
                border-collapse: collapse;
                white-space: nowrap;
                cursor: pointer;
            }

            table thead tr th {
                /* Important */
                position: sticky;
                z-index: 100;
                top: 0;
            }

            th, td {
                text-align: left;
                padding: 2px 5px 2px 5px;
                border: 1px solid #ddd;
            }

            th {
                background-color: #f2f2f2;
            }

            th i {
                color: #ff0015;
            }

            /* подсветка всей строки при наведении мышкой */
            .item-list:hover {
                background: #0d6efd;
                color: white;
            }
        </style>
    </head>
    <body class="p-3 mt-3">

    <table id="items-table">
        <thead>
        <tr class="p-3">
            <th colspan="3">Customer: <?= $clientName; ?></th>
            <th colspan="2">Priority ID: <?= $priorityId; ?></th>
        </tr>
        <tr>
            <th colspan="5">-</th>
        </tr>
        <tr class="p-3">
            <th>מק׳׳ט/SKU</th>
            <th>תיאור מוצר/שירות/Product/service description</th>
            <th>אסמכתא/reference 1</th>
            <th>אסמכתא/reference 2</th>
            <th>כמות/QTY</th>
        </tr>
        </thead>

        <tbody>
        <?php
        foreach ($orderData as $order) {
            if (!empty($order['project_name'])) {
                ?>
                <tr class="item-list">
                    <td><?= $order['id']; /* макат */ ?></td>
                    <td><?= $order['project_name']; /* теур муцар/ширут */ ?></td>
                    <td><?= 'for futures needs'; /* асмахта 1 */ ?></td>
                    <td><?= 'for futures needs'; /* асмахта 2 */ ?></td>
                    <td><?= $order['order_amount']; /* камут */ ?></td>
                </tr>
            <?php }
        } ?>
        </tbody>
    </table>

    <form id="hiddenForm" style="display: none;" action="" method="post">
        <!-- Поля формы будут добавлены динамически -->
    </form>

    <button type="button" class="url btn btn-proma mt-5" value="order">Back to Orders</button>
    <button id="saveChanges" type="button" class="btn btn-success mt-5" disabled>Save Changes</button>

    <pre class="mt-5">
    <i class="bi bi-info-circle text-danger fs-4"></i>
    Предупреждение!
        Изменение имени проекта повлечет за собой цепочку измеений как в заказе так и в проектах,
        более правильно сделать это из формы самого проекта так будет уверенность что все сделано правильно!
        Есть вероятность что был не правильно произведен поиск и выбран неверный заказ для этой страницы!
        Просьба убедится в правильности выбора!

    Описание:
        Изменение количества в заказе при частичном изготовлении будет отминусовано от данного заказа,
        остаток будет сохранен для продолжения работы с заказом.
        Если заказ был перевыполнен и клиент согласен забрать актуальное изготовленное количество,
        измененное количество будет сохранено в заказе.
    </pre>
    <?php PAGE_FOOTER('priority', false); ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const table = document.getElementById('items-table');

            table.addEventListener('dblclick', function (e) {
                const target = e.target;
                if (target.tagName === 'TD') {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = target.textContent;
                    input.style.width = target.clientWidth + 'px'; // Размер как у ячейки
                    input.addEventListener('blur', function () {
                        target.textContent = this.value;
                        document.getElementById('saveChanges').disabled = false; // Активировать кнопку при изменении
                    });
                    input.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                            this.blur();
                        }
                    });
                    target.textContent = '';
                    target.appendChild(input);
                    input.focus();
                }
            });

            document.getElementById('saveChanges').addEventListener('click', function () {
                const confirmation = confirm("Вы уверены, что хотите изменить данные? Изменения могут повлечь необратимые последствия и привести к проблемам.");

                if (confirmation) {
                    const rows = document.querySelectorAll('#items-table tr');
                    let form = document.getElementById('hiddenForm');

                    // Очистка предыдущих полей формы
                    form.innerHTML = '';

                    rows.forEach((row, idx) => {
                        if (idx === 0) return; // Пропускаем заголовок
                        const cells = row.querySelectorAll('td');
                        cells.forEach((cell, index) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'data[' + idx + '][' + index + ']';
                            input.value = cell.textContent;
                            form.appendChild(input);
                        });
                    });

                    // Отправка формы
                    form.submit();
                } else {
                    // Пользователь нажал отмена, форма не отправляется
                    alert("Изменения не будут сохранены!");
                }
            });
        });
    </script>
    </body>
    </html>
<?php } ?>
