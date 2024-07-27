<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .add-img-style {
            width: auto;
            max-width: 100%;
        }

        .input {
            display: block;
            width: 100%;
            padding: .375rem .75rem;
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.5;
            background-clip: padding-box;
            border: .05em solid #ced4da;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            margin: .3em;
        }

        /* СТИЛИ ДЛЯ ВЫВОДА ТАБЛИЦ */
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: pre;
            cursor: pointer;
        }


        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 4.7rem;
        }

        th:last-child, td:last-child {
            text-align: right;
            padding-right: 1rem;
        }

        th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }

        #search-responce thead {
            display: none;
        }
    </style>
</head>
<body>
<?php
list($action, $title, $saveBtnText) = ['tools-saving', 'creation', 'Save New Tool'];
if (isset($_POST['edit'])) {
    $tool = R::load(TOOLS, $_POST['edit']);
    list($action, $title, $saveBtnText) = ['tools-editing', 'editing', 'Update This Tool Information'];
}

// NAVIGATION BAR
$navBarData['title'] = "NTI Tools $title mode";
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'tool';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="main-container">
    <main class="container-fluid content">
        <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
            <table id="tool-table">
                <thead>
                <tr>
                    <th>N</th>
                    <th>Manufacturer name</th>
                    <th>Model</th>
                    <th>Device type</th>
                    <th>Device location</th>
                    <th>Calibration status</th>
                    <th>Serial No</th>
                    <th>Calibration date</th>
                    <th>Next calibration</th>
                    <th>Work Life</th>
                    <th>Service Manager</th>
                    <th>Remarks</th>
                    <th>Image Path</th>
                    <th>Date in</th>
                </tr>
                </thead>

                <tbody id="data-container">
                <?php $table = R::find(TOOLS);
                foreach ($table as $tool) { ?>
                    <tr class="item-list" data-id="<?= $tool['id'] ?>">
                        <td><?= $tool['id'] ?></td>
                        <td><?= $tool['manufacturer_name'] ?></td>
                        <td><?= $tool['device_model'] ?></td>
                        <td><?= $tool['device_type'] ?></td>
                        <td><?= $tool['device_location'] ?></td>
                        <td><?= $tool['calibration'] ?></td>
                        <td><?= $tool['serial_num'] ?></td>
                        <td><?= $tool['date_of_inspection'] ?></td>
                        <td><?= $tool['next_inspection_date'] ?></td>
                        <td><?= $tool['work_life'] ?></td>
                        <td><?= !empty($tool['responsible']) ? json_decode($tool['responsible'])->name : ''; ?></td>
                        <td><?= $tool['remarks'] ?></td>
                        <td><?= $tool['image'] ?></td>
                        <td><?= $tool['date_in'] ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php
        }

        // edit or create case for page
        if (isset($_POST['edit']) || isset($_POST['create'])) { ?>

            <div class="row">
                <!-- form container -->
                <div class="col-6 p-2">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="tool_id" value="<?= $tool['id'] ?? '' ?>">

                        <div class="mb-2">
                            <label for="manufacturer_name" class="form-label">Manufacturer name <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="manufacturer_name" id="manufacturer_name" value="<?= $tool['manufacturer_name'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="device_model" class="form-label">Model <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="device_model" id="device_model" value="<?= $tool['device_model'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="device_type" class="form-label">Device type</label>
                            <input type="text" class="form-control" name="device_type" id="device_type" value="<?= $tool['device_type'] ?? '' ?>">
                        </div>
                        <div class="mb-2">
                            <label for="device_location" class="form-label">Device location <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="device_location" id="device_location" value="<?= $tool['device_location'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="calibration" class="form-label">Calibration/No calibration required <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="calibration" id="calibration" value="<?= $tool['calibration'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="serial_num" class="form-label">Serial No: <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="serial_num" id="serial_num" value="<?= $tool['serial_num'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="date_of_inspection" class="form-label">Calibration date <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="date_of_inspection" id="date_of_inspection" value="<?= $tool['date_of_inspection'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="next_inspection_date" class="form-label">Next calibration <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="next_inspection_date" id="next_inspection_date" value="<?= $tool['next_inspection_date'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="work_life" class="form-label">Work Life <b class="text-danger">*</b></label>
                            <input type="text" class="form-control" name="work_life" id="work_life" value="<?= $tool['work_life'] ?? '' ?>" required>
                        </div>
                        <div class="mb-2 row">
                            <div class="col">
                                <label for="responsible">The responsible person for the device <b class="text-danger">*</b></label>
                                <select name="responsible" id="responsible" class="form-control" required>
                                    <option value="0">No Choosen Yet</option>
                                    <?php
                                    $name = !empty($tool['responsible']) ? json_decode($tool['responsible'])->name : '';
                                    foreach (R::findAll(USERS) as $u) {
                                        if ($u['id'] != 1) {
                                            $v = json_encode(['name' => $u['user_name'], 'email' => $u['email']]);
                                            $escapedValue = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                                            $sel = !empty($tool['responsible']) && $u['user_name'] == $name ? 'selected' : '';
                                            ?>
                                            <option value="<?= $escapedValue ?>" <?= $sel ?>><?= $u['user_name'] ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </div>

                            <div class="col">
                                <label for="in_use">A worker using a Tool</label>
                                <select name="in_use" id="in_use" class="form-control">
                                    <option value="0">Not in use yet</option>
                                    <?php
                                    $name = !empty($tool['in_use']) ? $tool['in_use'] : '';
                                    foreach (R::findAll(USERS) as $u) {
                                        if ($u['id'] != 1) {
                                            $sel = !empty($tool['responsible']) && $u['user_name'] == $name ? 'selected' : '';
                                            ?>
                                            <option value="<?= $u['user_name'] ?>" <?= $sel ?>><?= $u['user_name'] ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </div>

                        </div>
                        <div class="mb-2">
                            <label for="remarks" class="form-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" id="remarks" value="<?= $tool['remarks'] ?? '' ?>">
                        </div>
                        <div class="row mb-4">
                            <div class="col">
                                <label for="image" class="form-label">Image for this toll/device [optional]</label>
                                <button type="button" id="take_a_pic" class="btn btn-outline-dark form-control">Take A Picture for Tool</button>
                                <input type="file" name="imageFile" id="image" value="<?= $tool['image'] ?? '' ?>" hidden>
                            </div>

                            <div class="col">
                                <label for="db-image" class="form-label">Existing images [optional]</label>
                                <button type="button" id="db-image-btn" class="btn btn-outline-dark form-control" data-request="tools-images">Choose Picture</button>
                                <input type="hidden" name="image-from-db" id="db-image" value="">
                            </div>

                            <div class="col">
                                <label for="date_in" class="form-label">Date in</label>
                                <input type="datetime-local" class="form-control" name="date_in" id="date_in" value="<?= $tool['date_in'] ?? date('Y-m-d H:i') ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <button type="submit" class="btn btn-success form-control mb-2" value="<?= $tool['id'] ?? ''; ?>" name="<?= $action; ?>"
                                    id="save-btn" disabled>
                                <?= $saveBtnText ?>
                            </button>

                            <?php if (isset($_POST['edit'])) { ?>
                                <button type="button" class="btn btn-danger form-control" id="delete_tool" data-id="tools-<?= $tool['id'] ?? ''; ?>">
                                    Delete Tool [password required!!!]
                                </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>

                <!-- image container -->
                <div class="col-6 p-2">
                    <div class="mt-5 mb-5 d-flex justify-content-center">
                        <img src="<?= !empty($tool['image']) ? $tool['image'] : 'public/images/pna_en.webp'; ?>" alt="Tool Preview" style="width: 600px; height: 400px;" id="preview">
                    </div>
                </div>
            </div>
        <?php } ?>
    </main>
</div>

<form method="post" enctype="multipart/form-data" id="import-file-form" hidden>
    <input type="hidden" name="import-from-csv-file" value="1">
    <input type="file" name="csvFile" id="import-file-input" accept="text/csv">
</form>
<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm($_GET['route-page'] ?? 1);

// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // take picture for tool
        dom.doClick("#take_a_pic", "#image");

        // preview image taken by user
        dom.doPreviewFile("#image", "#preview", function () {
            dom.e("#take_a_pic").textContent = dom.e("#image").files[0].name;
        });
        // some sotr of routing when user press the create new tool btn
        dom.doSubmit('#create-btn', '#create-form');

        // Выбираем таблицу с id searchAnswer
        const tBody = dom.e("#data-container");

        if (tBody) {
            // Добавляем делегированный обработчик событий на таблицу
            tBody.addEventListener('click', function (event) {
                // // Проверяем, был ли клик по ссылке
                // fixme если будет ссылка на даташит в будущем
                // if (event.target.tagName.toLowerCase() === 'a') {
                //     return; // Прекращаем выполнение функции, если клик был по ссылке
                // }

                // Находим родительский <tr> элемент
                let row = event.target;
                while (row && row.tagName.toLowerCase() !== 'tr') {
                    row = row.parentElement;
                }

                // Если <tr> элемент найден и у него есть data-id
                if (row && row.dataset.id) {
                    // Получаем значение data-id
                    const id = row.dataset.id;

                    // Создаем скрытую форму
                    const form = document.createElement('form');
                    form.method = 'post';

                    // Создаем скрытый инпут
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'edit';
                    input.value = id;

                    // Добавляем инпут в форму
                    form.appendChild(input);

                    // Добавляем форму на страницу
                    document.body.appendChild(form);

                    // Отправляем форму
                    form.submit();
                }
            });
        }

        // при изменени списка ответственного за инструмент открываем кнопку сохранить/изменить инструмент
        dom.in("change", "#responsible", function () {
            dom.e("#save-btn").disabled = false;
        });

        // delete one item from database modal open
        dom.in("click", "#delete_tool", function () {
            if (this.dataset.id) {
                dom.e("#idForUse").value = this.dataset.id;
                dom.show("#deleteModal");
                dom.e("#password").focus();
            } else {
                console.log("do id in dataset");
            }
        });

        // событие клик на выбор файла для импорта инструментов из файла
        dom.doClick("#import-from-file", "#import-file-input", function (elem) {
            if (elem.files[0]) {
                const file = elem.files[0];
                const fileExtension = file.name.split('.').pop().toLowerCase();

                if (fileExtension !== 'csv') {
                    alert('Invalid file type. Please select a CSV file.');
                    elem.value = ''; // Clear the selected file
                } else {
                    dom.e("#import-file-form").submit();
                }
            }
        });

        // выборка фоток из БД которые существуют
        const args = {method: "POST", url: "get_data", headers: null};
        dom.makeRequest("#db-image-btn", "click", "data-request", args, function (error, result, _) {
            if (error) {
                console.error('Error during fetch:', error);
                return;
            }

            // вывод информации в модальное окно
            let modalTable = dom.e("#searchModal");
            if (modalTable) {
                dom.e("#search-responce").innerHTML = result;
                dom.show("#searchModal", "fast", true);
            }
        });

        // установка результата выбора фото из БД
        dom.in("click", "#search-responce td.image-path", function () {
            console.log(this.dataset)
            if (this.dataset.info) {
                // Извлекаем и парсим данные из атрибута data-info
                let info = this.dataset.info;
                dom.e("#preview").src = info;
                dom.e("#db-image").value = info;
            }
            // Очищаем результаты поиска
            dom.hide("#searchModal");
        });
    });
</script>
</body>
</html>