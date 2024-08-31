<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'warehouse/WareHouse.php';
$page = 'staging';

$order = null;
// Прямой SQL-запрос через RedBeanPHP
$wh_staging = PO_AIRRVAL;
$sql = "
    SELECT * 
    FROM $wh_staging t1
    WHERE t1.id = (
        SELECT MAX(t2.id) 
        FROM $wh_staging t2
        WHERE t2.staging_id = t1.staging_id
    )
    ORDER BY t1.staging_id
";

$items = R::getAll($sql);
?>

<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .container-r {
            width: 95%;
            padding: 20px;
            margin: 20px auto;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }


        label {
            display: inline-block;
            width: 200px;
        }

        input[type="text"], select {
            width: 45%;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 5px;
        }

        input[type="datetime-local"], input[type="number"] {
            width: 20%;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 5px;
        }


        .table-container {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f9f9f9;
        }


        button {
            padding: 10px 20px;
            font-size: 15px;
            margin-left: 10px;
            background-color: #ffcc00;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
        }

        a {
            padding: 10px 20px;
            font-size: 15px;
            margin-left: 10px;
            background-color: #0080ff;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
        }

        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['title' => 'Preliminary check of arrival', 'user' => $user, 'page_name' => $page]); ?>

<div class="container-r">
    <!-- ТАБЛИЦА ВНЕСЕННЫХ ПРЕДМЕТОВ ИЗ ПОСЫЛКИ -->
    <div class="table-container">
        <table id="staging-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Consignment</th>
                <th>For whom</th>
                <th>Date in</th>
                <!--                <th>Part number</th>-->
                <!--                <th>Manufacture P/N</th>-->
                <th>Notes</th>
                <!--                <th>Declared qty</th>-->
                <!--                <th>Aqtual qty</th>-->
                <th>Package</th>
                <th>Storage</th>
                <th>Warehouse</th>
                <!--                <th>Deffect</th>-->
                <th>Create By</th>
                <!--                <th>Difference</th>-->
            </tr>
            </thead>
            <tbody>
            <?php
            if ($items) {
                foreach ($items as $item) {
                    // list($difference, $qty) = WareHouse::getQtyDifference($item['declared_qty'], $item['actual_qty']);
                    ?>
                    <tr class="item-list <?php //$difference ?>">
                        <td><?= $item['staging_id']; ?></td>
                        <td><?= $item['owner_name'] ?></td>
                        <td><?= $item['consignment'] ?></td>
                        <td><?= $item['for_whom'] ?></td>
                        <td><?= $item['date_in'] ?></td>
                        <!--                        <td>--><?php //= $item['part_number'] ?><!--</td>-->
                        <!--                        <td>--><?php //= $item['manufacture_pn'] ?><!--</td>-->
                        <td><?= $item['notes'] ?></td>
                        <!--                        <td>--><?php //= $item['declared_qty'] ?><!--</td>-->
                        <!--                        <td>--><?php //= $item['actual_qty'] ?><!--</td>-->
                        <td><?= $item['package_type'] ?></td>
                        <td><?= $item['storage_place'] ?></td>
                        <td><?= R::load(WH_TYPES, $item['warehouse_type'])->type_name; ?></td>
                        <!--                        <td>--><?php //= $item['deffect'] ?? '' ?><!--</td>-->
                        <td><?= json_decode($item['user'], true)['name'] ?></td>
                        <!--                        <td>--><?php //= $qty ?><!--</td>-->
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// MODAL FOR SEARCH RESPONCE ANSWER
SearchResponceModalDialog($page, 'search-responce');

// FOOTER & SCRIPTS
PAGE_FOOTER($page);

// если мы пришли сюда после создания РО то открываем доп вкладку для печати данных о заказе
if (isset($_GET['orid']) && $order && !isset($_SESSION['pdf_printed'])) {
    $_SESSION['pdf_printed'] = 1;
    $url = "/order_pdf?pid=$order->projects_id&orid=$order->id";
    echo "<script>window.onload = function(){window.open('$url', '_blank');}; </script>";
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Обработка клика по результату поиска запчасти
        dom.in("click", "#search-responce tr.part", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                // Устанавливаем полученные значения в поля ввода
                dom.e("#item_id").value = info.item_id;
                dom.e("#part-name").value = info.part_name;
                dom.e("#part-value").value = info.part_value;
                dom.e("#mounting-type").value = info.mounting_type;
                dom.e("#manufacture-part-number").value = info.manufacture_part_number;
                dom.e("#manufacturer").value = info.manufacturer;
                dom.e("#footprint").value = info.footprint;
                dom.e("#minimun-quantity").value = info.minimal_quantity;
                dom.e("#description").value = info.description;
                dom.e("#notes").value = info.notes;
                dom.e("#datasheet").value = info.datasheet;
                dom.e("#shelf-life").value = info.shelf_life;
                dom.e("#storage-class").value = info.storage_class;
                dom.e("#storage-state").value = info.storage_state;
                dom.e("#owner").value = info.owner;
                if (info.owner_part_name && info.owner_part_name.trim() !== '') {
                    // Получаем список опций
                    const options = dom.e("#owner-pn-list");
                    // Оставляем только буквы для сравнения
                    const searchText = info.owner_part_name.replace(/[^a-zA-Z]/g, '');
                    // Проходим по всем опциям в списке
                    for (let i = 0; i < options.length; i++) {
                        // Оставляем только буквы для сравнения в значении опции
                        const optionValue = options[i].value;

                        // Проверяем, начинается ли значение опции с нужного текста
                        if (optionValue.startsWith(searchText)) {
                            // Устанавливаем опцию как выбранную и выходим из цикла
                            options[i].selected = true;
                            break;
                        }
                    }
                }
                dom.e("#quantity").value = info.quantity;
                dom.e("#storage-box").value = info.storage_box;
                dom.e("#storage-shelf").value = info.storage_shelf;

                // Очищаем результаты поиска
                dom.hide("#searchModal");
                createSearchLinks(info.MFpartName);
            }
        });

        // Обработка клика по результату поиска клиента
        dom.in("click", "#search-responce tr.customer", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                dom.e("#owner").value = info.name; // Устанавливаем имя клиента
                dom.e("#owner_id").value = info.clientID; // Устанавливаем ID клиента
                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        });

        // Обработка клика по результату поиска supplier/manufacturer
        dom.in("click", "#search-responce tr.supplier", function () {
            if (this.parentElement.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = JSON.parse(this.parentElement.dataset.info);
                if (info.is_request === 'supplier') {
                    dom.e("#supplier").value = info.supplier_name; // Устанавливаем имя поставщика
                    dom.e("#supplier-id").value = info.supplier_id; // Устанавливаем имя поставщика
                }
                if (info.is_request === 'manufacturer') {
                    dom.e("#manufacturer").value = info.supplier_name; // Устанавливаем имя производителя
                }
                // Очищаем результаты поиска
                dom.hide("#searchModal");
            }
        });

        // Send request to create XML files
        dom.in("click", "#print-invoice", function () {
            // Create a new form element
            const form = document.createElement('form');

            // Set the form's action and method
            form.action = ''; // Замените на ваш URL для обработки запроса
            form.method = 'POST';

            // Create a hidden input field
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'make-xmls';
            input.value = '1'; // Значение, которое вы хотите отправить

            // Append the input field to the form
            form.appendChild(input);

            // Append the form to the body (необходимо для отправки)
            document.body.appendChild(form);

            // Submit the form
            form.submit();
        });

        dom.hideEmptyColumnsInTable("#staging-table");
    });
</script>
</body>
</html>
