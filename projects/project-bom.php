<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
include_once 'projects/Project.php';

$page = 'project_part_list';
$backButton = ['url' => '/', 'text' => 'Back To Orders'];
$formButton = ['name' => 'save-item-to-bom', 'text' => 'Save Item'];

/* flow from create project */
if (isset($_GET['pid']) && !isset($_GET['orid']) && !isset($_GET['bomid'])) {
    $backButton['url'] = '/add_step?pid=' . $_GET['pid'];
    $backButton['text'] = 'Enter new Project Details';
}

/* flow from edit project steps */
if (isset($_GET['pid']) && isset($_GET['orid'])) {
    $backButton['url'] = '/edit_project?pid=' . $_GET['pid'];
    $backButton['text'] = 'Back to Project Details';
}

/* flow from order details project/order bom creation */
if (isset($_GET['pid']) && isset($_GET['back-id']) && isset($_GET['mode'])) {
    $tab = ($_GET['mode'] == 'orderbom') ? 'tab2' : 'tab5';
    $backButton['url'] = "/order/preview?orid={$_GET['back-id']}&tab=$tab";
    $backButton['text'] = 'Back to Order Details';
}

/* saving the item to DB */
if (isset($_POST['save-item-to-bom'])) {
    if (!isset($_FILES['import_csv']['name'][0])) {
        Project::createProjectBomItem($_POST, $user, $_GET['pid']);
    } else {
        Project::importProjectBomFromFile($_FILES, $_POST, $user, $_GET['pid']);
    }
}

/* delete item from project BOM */
if (isset($_POST['password']) && isset($_POST['itemId']) && isset($_POST['delete-item'])) {
    Project::deleteProjectBomItem($_POST, $user);
}

/* undo delete item */
if (isset($_GET['undo']) && isset($_GET['bomid'])) {
    Undo::RestoreDeletedRecord(_E($_GET['bomid']));
    $pid = _E($_GET['pid']);
    redirectTo("check_part_list?pid=$pid");
}

// получаем проект в котором работаем
$project = R::load(PROJECTS, $_GET['pid']);
$p_name = $project->projectname;
$c_name = $project->customername;

// получение БОМа проекта для вывода в таблице
$it = R::FindAll(PROJECT_BOM, 'projects_id = ?', [$_GET['pid']]);

// уточнение кол-ва элементов в БОМе для вывода на странице
$sku = ($it) ? (count($it) + 1) : 1;

/* edit item from project bom */
if (isset($_GET['edit-item'])) {
    if (isset($_POST['edit-item-btn'])) {
        $args = Project::updateProjectBomItem($_POST, $user, _E($_GET['pid']), _E($_GET['edit-item']));

        // обновляем адресную строку
        if ($args['args']) {
            $_SESSION['info'] = $args;
            redirectTo('check_part_list?orid=none&pid=' . $_GET['pid']);
        }
    }

    $itFedit = R::load(PROJECT_BOM, _E($_GET['edit-item']));
    $formButton = ['name' => 'edit-item-btn', 'text' => 'Update Item'];
    $sku = $itFedit->sku;
}
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        /* подсветка всей строки при наведении мышкой */
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        /* таблица вывода запючастей */
        table {
            width: 100%;
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
            padding: 0 5px 0 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        th i {
            color: #ff0015;
        }
    </style>
</head>
<body>

<div class="row p-2 secondary">
    <!-- adding form -->
    <div class="col-3 ps-3 pe-3 mt-3 border-end">
        <!-- i searching for parts in to warehouse -->
        <div class="mb-2">
            <label for="search" class="form-label">Search for Parts</label>
            <input type="text" role="searchbox" aria-label="Search" class="searchThis form-control"
                   id="searching" data-request="project_bom" placeholder="Search" required autofocus>
        </div>

        <h5 class="text-center">Adding Form</h5>
        <form action="" method="post" autocomplete="off" id="uploadForm" enctype="multipart/form-data">
            <input type="hidden" name="item_id" id="item_id">
            <input type="hidden" name="owner_id" id="owner_id">

            <div class="mb-2">
                <label for="sku" class="form-label">SKU <b class="text-danger">*</b></label>
                <input type="text" class="form-control" name="sku" id="sku" value="<?= $sku; ?>" required>
            </div>

            <div class="mb-2">
                <label for="pn" class="form-label">Part Name <b class="text-danger">*</b></label>
                <input type="text" class="form-control" name="part_name" id="pn"
                       value="<?= set_value('part_name', $itFedit->part_name ?? '0'); ?>" required>
            </div>

            <div class="mb-2">
                <label for="pv" class="form-label">Part value <!--<b class="text-danger">*</b>--></label>
                <input type="text" class="form-control" name="part_value" id="pv" value="<?= set_value('part_value', $itFedit->part_value ?? '0'); ?>">
            </div>

            <div class="mb-2">
                <label for="mounting_type" class="form-label">Part type <!--<b class="text-danger">*</b>--></label>
                <input type="text" class="form-control" name="mounting_type" id="mounting_type"
                       value="<?= set_value('mounting_type', $itFedit->mounting_type ?? '0'); ?>">
            </div>

            <div class="mb-2">
                <label for="footprint" class="form-label">Footprint <!--<b class="text-danger">*</b>--></label>
                <input type="text" class="form-control" name="footprint" id="footprint"
                       value="<?= set_value('footprint', $itFedit->footprint ?? '0'); ?>">
            </div>

            <div class="mb-2">
                <label for="mf" class="form-label">Manufacturer <b class="text-danger">*</b></label>
                <input type="text" class="form-control" name="manufacturer" id="mf"
                       value="<?= set_value('manufacturer', $itFedit->manufacturer ?? ''); ?>" required>
            </div>

            <div class="mb-2">
                <label for="mf_pn" class="form-label">Manufacturer P/N <b class="text-danger">*</b></label>
                <input type="text" class="form-control" name="manufacture_pn" id="mf_pn"
                       value="<?= set_value('manufacture_pn', $itFedit->manufacture_pn ?? ''); ?>" required>
            </div>

            <div class="mb-2">
                <label for="owner_pn" class="form-label">Owner P/N</label>
                <input type="text" class="form-control" name="owner_pn" id="owner_pn" value="<?= set_value('owner_pn', $itFedit->owner_pn ?? 'NTI') ?>">
            </div>

            <div class="mb-2">
                <label for="desc" class="form-label">Description <b class="text-danger">*</b></label>
                <input type="text" class="form-control" name="description" id="desc"
                       value="<?= set_value('description', $itFedit->description ?? ''); ?>" required>
            </div>

            <div class="mb-2">
                <label for="nt" class="form-label">Note</label>
                <input type="text" class="form-control" name="note" id="nt" value="<?= set_value('note', $itFedit->notes ?? '0') ?>">
            </div>

            <div class="mb-2">
                <?php $t = 'The number of pieces of one length or one type for the entire assembly.'; ?>
                <label for="qty" class="form-label">QTY<b class="text-danger">*</b>&nbsp;
                    <i class="bi bi-info-circle" data-title="<?= $t ?>"></i></label>
                <input type="text" class="form-control" name="qty" id="qty" value="<?= set_value('qty', $itFedit->amount ?? '0'); ?>"
                       required>
            </div>

            <div class="mb-2">
                <?php $t = 'The length specified for one piece during assembly.'; ?>
                <label for="length_mm" class="form-label">Length in MM<b class="text-danger">*</b>&nbsp;
                    <i class="bi bi-info-circle" data-title="<?= $t ?>"></i></label>
                <input type="text" class="form-control" name="length_mm" id="length_mm" value="<?= set_value('length_mm', $itFedit->length_mm ?? '0'); ?>"
                       required>
            </div>

            <div class="mb-2">
                <button type="submit" name="<?= $formButton['name']; ?>" class="btn btn-outline-success form-control"><?= $formButton['text']; ?></button>
            </div>

            <!--            <div class="mb-2">-->
            <!--                --><?php //$t = 'First you need to select a file! This will cancel the required fields,
            //                then enter the names of the columns in the file in the fields that you want to fill in!
            //                The remaining fields must remain empty!!! Click on the save button and you\'re done,
            //                you will see the args to the right of the form.'; ?>
            <!--                <button type="button" id="import_csv" class="btn btn-outline-info form-control" data-title="--><?php //= $t; ?><!--">-->
            <!--                    Import CSV file-->
            <!--                    <i class="bi bi-info-circle"></i>-->
            <!--                </button>-->
            <!--                <input type="file" name="import_csv" id="csv_input" accept="text/csv" hidden>-->
            <!--            </div>-->

            <div class="mb-2">
                <button type="button" class="url btn btn-outline-info form-control" value="import-file?table-name=projectbom">
                    Import Project BOM from file
                    <i class="bi bi-filetype-xlsx"></i>
                </button>
            </div>

            <div class="mb-2">
                <a type="button" class="btn btn-outline-warning form-control" href="<?= $backButton['url']; ?>">
                    <?= $backButton['text']; ?>
                </a>
            </div>

            <div class="mb-2">
                <a type="button" class="btn btn-outline-dark form-control" href="/order">
                    Back to Orders List
                </a>
            </div>
        </form>
    </div>

    <!-- items table -->
    <div class="col-9 ps-3">
        <h4 class="text-center"><?= "Project: $p_name, Customer: $c_name, BOM items table."; ?></h4>
        <div style="overflow-y: scroll; overflow-x: scroll; height: 100vh;">
            <table id="itemTable">
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>Length [mm]</th>
                    <th>QTY [pcs]</th>
                    <?php $t = 'Press and hold the CTRL button for Windows or the COMMAND button for Mac OS and hover over the SKU to remove the part from the list.'; ?>
                    <th><i class="bi bi-info-circle text-primary" data-title="<?= $t; ?>"></i> P/N</th>
                    <th class="sortable" onclick="sortTable(4)"><i class="bi bi-filter"></i> Value</th>
                    <th>Manufacturer</th>
                    <th>Manufacturer P/N</th>
                    <th>Owner P/N</th>
                    <th>Desc</th>
                    <th>Note</th>
                    <th class="sortable" onclick="sortTable(10)"><i class="bi bi-filter"></i> Type</th>
                    <th class="sortable" onclick="sortTable(11)"><i class="bi bi-filter"></i> Footprint</th>
                </tr>
                </thead>
                <tbody id="tbody-responce">
                <?php if ($it): foreach ($it as $item): ?>
                    <tr class="item-list">
                        <td class="item-btn" data-id="<?= $item['id']; ?>" data-num="<?= $item['sku']; ?>"><?= $item['sku']; ?></td>
                        <td><?= $item['length_mm']; ?></td>
                        <td><?= $item['amount']; ?></td>
                        <td><?= $item['part_name']; ?></td>
                        <td><?= $item['part_value']; ?></td>
                        <td><?= $item['manufacturer']; ?></td>
                        <td><?= $item['manufacture_pn']; ?></td>
                        <td><?= $item['owner_pn']; ?></td>
                        <td><?= $item['description']; ?></td>
                        <td><?= $item['notes']; ?></td>
                        <td><?= $item['mounting_type']; ?></td>
                        <td><?= $item['footprint']; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!--  модальное окно форма для архивирования заказа  -->
<div class="modal" id="deleteItem" style="backdrop-filter: blur(15px);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Заголовок модального окна -->
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Item number: <b id="item-number"></b></h5>
                <button type="button" class="btn-close" data-aj-dismiss="modal" style="border:solid red 1px;"></button>
            </div>

            <!-- Содержимое модального окна -->
            <div class="modal-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autofocus>
                        <input type="hidden" id="item_id_for_delete" name="itemId" value="">
                    </div>
                    <button type="submit" class="btn btn-danger" name="delete-item">Delete Item</button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php PAGE_FOOTER($page, false); ?>
<script type="text/javascript" src="/public/js/project-bom.js"></script>
</body>
</html>
