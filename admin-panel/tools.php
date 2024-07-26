<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .custom-table thead th,
        .custom-table tbody td {
            display: inline-flex;

        }

        .d-flex {
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
$navBarData['title'] = 'NTI Tools';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'tool';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<!-- add new item to list  -->
<form method="post" action="" class="hidden" id="create-form">
    <input type="hidden" name="create">
</form>

<div class="main-container">
    <main class="container-fluid content">
        <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
            <table class="table">
                <thead class="bg-light">
                <tr>
                    <th>N</th>
                    <th>Manufacturer name</th>
                    <th>Model</th>
                    <th>Device type</th>
                    <th>Device location</th>
                    <th>Calibration/No calibration required</th>
                    <th>Serial No</th>
                    <th>Calibration date</th>
                    <th>Next calibration</th>
                    <th>Work Life</th>
                    <th>The responsible person for the device</th>
                    <th>Remarks</th>
                    <th>Image Path</th>
                    <th>Date in</th>
                </tr>
                </thead>

                <tbody id="data-container">
                <?php $table = R::find(TOOLS);
                foreach ($table as $tool) { ?>
                    <tr>
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
                        <td><?= $tool['responsible'] ?></td>
                        <td><?= $tool['remarks'] ?></td>
                        <td><?= $tool['image'] ?></td>
                        <td><?= $tool['date_in'] ?></td>
                    </tr>
                    <!--                    <tr class="align-middle">-->
                    <!--                        <td class="border-end">--><?php //= $row['toolname']; ?><!--</td>-->
                    <!--                        <td class="border-end"><img src="--><?php //= $row['image']; ?><!--" alt="Tool Image Preview" width="100px" height="100px"></td>-->
                    <!--                        <td class="border-end">--><?php //= $row['specifications']; ?><!--</td>-->
                    <!--                        <td class="border-end">--><?php //= $row['esd']; ?><!--</td>-->
                    <!--                        <td class="border-end">--><?php //= $row['exp_date']; ?><!--</td>-->
                    <!--                        <td>-->
                    <!--                            <form method="post" style="margin:0;">-->
                    <!--                                <button type="submit" name="edit" class="btn btn-warning btn-sm mb-1 mt-1" value="--><?php //= $row['id']; ?><!--">-->
                    <!--                                    <i class="bi bi-pencil"></i>-->
                    <!--                                </button>-->
                    <!--                                <button type="button" class="btn btn-danger btn-sm mb-1 mt-1 del-but" data-id="tools---><?php //= $row['id']; ?><!-- ">-->
                    <!--                                    <i class="bi bi-trash"></i>-->
                    <!--                                </button>-->
                    <!--                            </form>-->
                    <!--                        </td>-->
                    <!--                    </tr>-->
                    <?php
                }
                ?>
                </tbody>
            </table>

            <?php
        }
        if (isset($_POST['edit']) || isset($_POST['create'])) {
            if (isset($_POST['edit'])) {
                echo '<h2>Edit Tool</h2>';
                $tool = R::load(TOOLS, $_POST['edit']);
                $action = 'tools-editing';
            }

            if (isset($_POST['create'])) {
                echo '<h2>Add New Tool</h2>';
                $action = 'tools-saving';
            }
            ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="toolname" class="form-label">Name <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="toolname" name="toolname" value="<?= $tool['toolname'] ?? ''; ?>"
                           placeholder="Tool name" required>
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">View <b class="text-danger">*</b></label>
                    <input type="file" class="form-control" id="files" name="imageFile">
                </div>

                <div class="mb-3">
                    <label for="specifications" class="form-label">Specifications <b class="text-danger">*</b></label>
                    <textarea class="form-control" id="specifications" name="specifications"
                              placeholder="Here you need to write all possible useful information!" required><?= $tool['specifications'] ?? ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="esd" class="form-label">ESD Sertificate</label>
                    <input type="text" class="form-control" id="esd" name="esd-sertificate" value="<?= $tool['esd'] ?? ''; ?>"
                           placeholder="Optional">
                </div>

                <div class="mb-3">
                    <label for="qc" class="form-label">Next QC date <b class="text-danger">*</b></label>
                    <input type="text" class="form-control" id="qc" name="date-qc" value="<?= $tool['exp_date'] ?? ''; ?>"
                           placeholder="Does not require verification = EOL" required>
                </div>

                <div class="mb-3">
                    <label for="ruf" class="form-label">Service Manager <b class="text-danger">*</b></label>
                    <select name="service_manager" id="ruf" class="form-control">
                        <?php
                        foreach (R::findAll(USERS) as $u) {
                            if ($u['id'] != 1) {
                                $v = json_encode(['name' => $u['user_name'], 'email' => $u['email']]);
                                $escapedValue = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                                ?>
                                <option value="<?= $escapedValue ?>"><?= $u['user_name'] ?></option>
                            <?php }
                        } ?>
                    </select>
                </div>


                <button type="submit" class="btn btn-success form-control" value="<?= $tool['id'] ?? ''; ?>" name="<?= $action; ?>">Save</button>
            </form>

            <div class="row mt-5 mb-5 d-flex justify-content-center">
                <img src="<?= $tool['image'] ?? 'public/images/tools.webp'; ?>" alt="Tool Preview" style="width: 600px; height: 400px;" id="preview">
            </div>
        <?php } ?>
    </main>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm($_GET['route-page'] ?? 1);
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.doSubmit('#create-btn', '#create-form');
    });
</script>
</body>
</html>

<?php

//$t = R::findAll(TOOLS);
//foreach ($t as $tool) {
//    $nt = R::dispense('devices');
//    $nt->manufacturer_name = $tool['toolname']; // имя инструмента от производителя
//    $nt->device_model = $tool['device_model']; // модель инструмента
//    $nt->device_type = $tool['device_type']; // тип инструмента
//    $nt->device_location = $tool['device_location']; // рабочее местонахождение инструмента
//    $nt->calibration = $tool['calibration']; // NONC = no need calibration, NEC = need calibration
//    $nt->serial_num = $tool['serial_num']; // сирийный номер инструмента после калибровки
//    $nt->date_of_inspection = $tool['date_of_inspection']; // дата последней калибровки - обслуживания инструмента
//    $nt->next_inspection_date = $tool['exp_date']; // следующая дата калибровки - обслуживания инструмента !!!
//    $nt->work_life = $tool['work_life']; // интервал обслуживания/калибровки (месяцев)
//    $nt->responsible = $tool['service_manager']; // ответственный за инструмент
//    $nt->remarks = $tool['specifications']; // заметки на полях
//    $nt->image = $tool['image']; // путь к фото инструмента или ПДФ
//    $nt->date_in = $tool['date_in']; // дата внесения в БД
//
//    R::store($nt);
//    //$nt->colored = $tool['colored']; // надо уточнить
//
//}

?>