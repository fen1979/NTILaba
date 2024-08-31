<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        /*.add-img-style {*/
        /*    width: auto;*/
        /*    max-width: 100%;*/
        /*}*/

        /*.input {*/
        /*    display: block;*/
        /*    width: 100%;*/
        /*    padding: .375rem .75rem;*/
        /*    font-size: .9rem;*/
        /*    font-weight: 400;*/
        /*    line-height: 1.5;*/
        /*    background-clip: padding-box;*/
        /*    border: .05em solid #ced4da;*/
        /*    -webkit-appearance: none;*/
        /*    -moz-appearance: none;*/
        /*    appearance: none;*/
        /*    border-radius: .25rem;*/
        /*    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;*/
        /*    margin: .3em;*/
        /*}*/

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

        #pasteArea {
            width: 100%;
            height: 30rem;
            background-image: url("/public/images/drop-here.png");
            background-repeat: no-repeat;
            background-position: center;
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
NavBarContent(['title' => "NTI Tools $title mode", 'user' => $user, 'page_name' => $page, 'btn_title' => 'tool']); ?>

<div class="main-container">
    <main class="container-fluid content">
        <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
            <table id="tool-table">
                <thead>
                <tr style="white-space: nowrap">
                    <?= CreateTableHeaderUsingUserSettings($settings, 'tool-table', TOOLS) ?>
                </tr>
                </thead>

                <tbody id="data-container">
                <?php $table = R::find(TOOLS);
                foreach ($table as $tool) { ?>
                    <tr class="item-list" data-id="<?= $tool['id'] ?>">
                        <?php
                        if ($settings) {
                            foreach ($settings as $item => $_) {
                                if ($item == 'responsible') {
                                    echo '<td>' . (!empty($tool[$item]) ? json_decode($tool[$item])->name : '') . '</td>';
                                } else {
                                    echo '<td>' . $tool[$item] . '</td>';
                                }
                            }
                        }
                        ?>
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
                        <input type="hidden" name="imageData" id="imageData">

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
                                <button type="button" class="btn btn-danger form-control" id="delete_btn" data-id="tools-<?= $tool['id'] ?? ''; ?>">
                                    Delete Tool [password required!!!]
                                </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>

                <!-- image container -->
                <div class="col-6 p-2">
                    <div class="mt-5 mb-5 d-flex justify-content-center">
                        <?php $hide = !empty($tool['image']) ? '' : 'hidden'; ?>
                        <img src="<?= !empty($tool['image']) ? $tool['image'] : 'public/images/pna_en.webp'; ?>" alt="Tool Preview"
                             style="width: 600px; height: 400px;" id="preview" class="<?= $hide ?>">
                    </div>

                    <div id="pasteArea" contenteditable="true" class="mb-4 border-bottom"></div>
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
deleteModalRouteForm();

// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');
// Футер // SCRIPTS
PAGE_FOOTER($page); ?>
</body>
</html>